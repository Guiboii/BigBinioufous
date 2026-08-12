# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Application web Symfony 7.4 pour la gestion d'une fanfare ("Binioufous") : inscription des membres, validation des adhésions, gestion des rôles/instruments, bibliothèque musicale, pages vitrines.

## Commands

### PHP / Symfony

- `composer install` : installe les deps PHP
- `php bin/console doctrine:database:create` / `doctrine:migrations:migrate` / `doctrine:fixtures:load` : setup BDD (fixtures = jeu de données de démo via Faker)
- `php -S localhost:8000 -t public` : lance le serveur de dev
- `php bin/phpunit` : lance les tests (aucun test réel actuellement, seulement `tests/bootstrap.php`)

Version PHP : `composer.json` requiert `^8.1` (bump depuis `^7.2.5|^8.0` lors de l'upgrade Symfony 5→6.4). Le PHP CLI système en `8.4.24` convient.

Historique upgrade Symfony : `5.4` → `6.4` LTS (annotations → attributs PHP, sensio retiré) → `7.4` LTS (aucun changement de code nécessaire, tout avait déjà été traité au passage 6.4 ; seul `symfony/webpack-encore-bundle` a dû être bumpé `^1.7` → `^2.4` pour supporter `symfony/asset ^7.0`).

### Frontend (AssetMapper, plus de build Node)

- `composer require symfony/asset-mapper` a remplacé Webpack Encore le 2026-08-05. Zéro build JS : les assets sont servis directement par Symfony depuis `assets/`, en dev comme en prod (`asset-map:compile` en prod).
- Dépendances JS déclarées dans `importmap.php` (racine), installées/mises à jour via `php bin/console importmap:require <package>` (téléchargées dans `assets/vendor/`, gitignoré).
- **Piège** : `importmap:require <lib>` résout par défaut la dernière version du package. Pour ce projet, `bootstrap` et `three` doivent rester épinglés à d'anciennes versions (`bootstrap@^4.6`, `three@0.128.0`) car le code utilise leurs anciennes API (Bootstrap 4 `data-toggle`, Three.js `PlaneBufferGeometry`/`sRGBEncoding`). Toujours vérifier après un `importmap:require` que la version résolue est compatible avant de committer.
- **Fichiers binaires référencés en JS** (`.gltf`, images) : AssetMapper ne sert que l'URL hashée exacte, pas le chemin logique brut (`/assets/mascotte/x.gltf` → 404, il faut `/assets/mascotte/x-HASH.gltf`). Solution : `window.BB_ASSETS` est injecté dans `templates/base.html.twig` via `{{ asset('chemin/fichier') }}` pour chaque binaire utilisé en JS vanilla ; les fichiers JS consomment `window.BB_ASSETS['chemin/fichier']`. Pour un nouveau binaire, l'ajouter des deux côtés.
- **Un seul `{{ importmap(...) }}` par page rendue** : un document HTML n'accepte qu'un seul `<script type="importmap">` valide, un 2e/3e sur la même page peut être ignoré ou rendre incomplet celui réellement utilisé (bug trouvé le 2026-08-10 sur `/join` : `join/login.html.twig`/`join/launch.html.twig` étendaient `popup.html.twig`, un document complet avec son propre `importmap()`, tout en étant `{{ include() }}`-ées **inline** dans une page qui appelait déjà `importmap()` via `base.html.twig` ; CSS de la page cassée de façon intermittente, difficile à reproduire). Un template destiné à être inclus dans une page qui étend déjà `base.html.twig` ne doit jamais lui-même `extends` un document Twig complet (`base.html.twig`/`popup.html.twig`) : seulement des fragments.
- `.nvmrc` (Node `24`) reste utile uniquement pour l'outillage de lint JS (ESLint/Prettier), plus pour un build.
- `npm ci` : installe seulement les devDependencies de lint (`package.json` ne contient plus aucune dépendance runtime).

### Conventions de code

- `make lint` : vérifie PHP (PHP-CS-Fixer, `@Symfony`) + Twig (Twig-CS-Fixer) + JS (ESLint/Prettier), sans rien modifier
- `make lint-fix` : corrige tout automatiquement
- Configs : `.php-cs-fixer.dist.php`, `.twig-cs-fixer.dist.php`, `eslint.config.js` (flat config) + `.prettierrc.json`, `.editorconfig` (4 espaces PHP/Twig, 2 espaces JS/JSON/YAML)
- Quelques règles JS (`no-unused-vars`, `no-redeclare`, `no-unassigned-vars`) sont volontairement en `warn` plutôt que `error` : le code legacy en contient déjà, corriger ça touche à la logique et relève de la passe page par page (Phase 5 de `ROADMAP.md`), pas de cette passe de style pure

## Architecture

- Backend : Symfony 7.4 LTS, Doctrine ORM/DBAL, MySQL 5.7. Routes et mapping ORM en **attributs PHP natifs** (`#[Route]`, `#[ORM\Entity]`...), plus d'annotations docblock (`sensio/framework-extra-bundle` retiré, remplacé par les attributs core Symfony 6.2+).
- Entités principales : `User` (membre, lié à un `Instrument` facultatif et plusieurs `Role`), `Role` (`ROLE_ADMIN`, `ROLE_COMPTA`, `ROLE_BINIOUFOUS` : les 3 seuls rôles qui existent désormais dans le projet, cf. "Rôles retirés" ci-dessous), `Instrument`, `Artist`/`Track` (bibliothèque musicale).
- Sécurité (`config/packages/security.yaml`) : `form_login`, provider Doctrine sur `User` (identifiant = email, `getUserIdentifier()`), `password_hashers` bcrypt (`encoders` renommé depuis 5.3). `/admin/*` réservé à `ROLE_ADMIN`, `/desk/*` réservé à `IS_AUTHENTICATED_FULLY` (attribut Symfony natif, pas un rôle). La connexion n'est **pas** bloquée avant validation admin (`User::$validation`) : un 1er jet (`UserChecker`) bloquait la connexion elle-même, retiré le jour même sur retour utilisatrice (« même pas validé, on doit pouvoir accéder à son profil ») ; `/desk` affiche un bandeau « en cours de vérification » avec un lien vers `/desk/profile` tant que `$validation` est `false`, tout le reste de `/desk` reste accessible (juste être connecté suffit).
- **Inscription simplifiée** (2026-08-12, "Facilitons l'inscription") : `/register` ne demande plus que pseudo/email/mot de passe (`RegistrationType`) ; identité, instrument, adhésion... se complètent après coup sur `/desk/profile`, tout facultatif (`User::$firstName/$lastName/$gender/$birth/$city/$country/$instrument` nullable). Chaque inscription attend une validation manuelle (email squelette envoyé aux admins à l'inscription, à l'utilisateur·ice une fois accepté·e via `LoginController::notifyAdminsOfNewRegistration()`/`AdminController::notifyUserOfAcceptance()` : `MAILER_DSN` toujours pas configuré, cf. Phase "Emails fonctionnels" plus bas, `null://null` posé comme valeur de repli dans `config/services.yaml` pour que l'injection de `MailerInterface` ne fasse pas planter le conteneur en attendant).
- **Rôles simplifiés** : le champ `wish` (souhait choisi à l'inscription, `Binioufous`/`Member`/`Simple`) est retiré, il déterminait avant à la fois le rôle attribué à la validation ET si celle-ci était automatique. `ROLE_MEMBER` ne débloquait aucune permission propre dans le code (absent de `security.yaml`, seule étiquette historique/associative) : les admins basculent désormais `ROLE_BINIOUFOUS` (seul rôle avec une vraie différence fonctionnelle : accès aux partitions/voix) en un clic depuis les listes `/desk` (`AdminController::toggleMembership()`), décorrélé de la validation du compte. `User::$memberCardNumber` (numéro de carte) laissé en base pour ne pas perdre de données mais plus utilisé dans aucun formulaire, remplacé par `User::$claimsMembership` (déclaration facultative sur le profil, "Oui, il me semble" adhérent·e, purement informatif).
- **Rôles fusionnés** (même jour, 2026-08-12, suite directe du point précédent) : `ROLE_SIMPLE` retiré à son tour, fusionné avec `ROLE_USER` (déjà attribué à tout compte connecté directement dans `User::getRoles()`, jamais stocké en base). "Simple" = compte validé sans `ROLE_BINIOUFOUS`, un état déduit plutôt qu'un rôle assigné : `UserRepository::findSimples()` filtre `validation = true` en excluant les comptes ayant `ROLE_BINIOUFOUS`. `AdminController::validUser()` n'attribue plus rien à la validation, `toggleMembership()` n'ajoute/retire plus que `ROLE_BINIOUFOUS`. Migration `Version20260812170000` supprime la ligne `ROLE_SIMPLE` de la table `role` (cascade sur `role_user`) pour nettoyer les comptes de prod qui l'avaient déjà.
- **Rôles retirés** (même jour, 2026-08-12, suite d'un audit complet des rôles) : `ROLE_MEMBER`, laissé stale jusqu'ici (plus attribuable mais pas supprimé), retiré à son tour, plus aucun compte ne peut l'avoir (`UserRepository::findMembers()` supprimé, branche `ROLE_MEMBER` de `desk/index.html.twig` retirée). **`ROLE_USER` retiré aussi, entièrement** (pas juste sa trace en base) : `/desk` (`^/desk` dans `security.yaml`) n'exige plus un rôle particulier mais `IS_AUTHENTICATED_FULLY` (attribut Symfony natif, évalué sur le token d'authentification), donc `User::getRoles()` n'a plus besoin d'injecter `ROLE_USER` en dur (`$roles[] = 'ROLE_USER';` retiré) : un compte sans rôle métier renvoie simplement un tableau vide, ce qui ne pose aucun problème (authentification et rôles sont deux notions distinctes pour Symfony). Migration `Version20260812180000` : `DELETE FROM role WHERE title IN ('ROLE_MEMBER', 'ROLE_USER')` (la ligne `ROLE_USER` en base ne servait qu'à une pastille cosmétique sur les fiches admin, jamais assignée à personne).
- Frontend : Symfony AssetMapper (zéro build Node, cf. section Commands ci-dessus), jQuery/Bootstrap 4, Three.js (mascotte 3D, pages story/schedule), wavesurfer.js (lecteur musique, chargé en CDN, pas géré par AssetMapper). Points d'entrée déclarés dans `importmap.php`, sous `assets/<section>/`. Chaque template Twig appelle `{{ importmap([...]) }}` (remplace `encore_entry_*`), voir `templates/base.html.twig` pour le pattern par défaut. Cas particulier : `templates/story/minisite.html.twig` n'étend **pas** `base.html.twig` (évite la navbar/barre Symfony dupliquées dans le cadre) : document HTML autonome, CSS chargée en `<link>` manuel via `asset()` (`assets/story/minisite.css`), sans passer par `importmap()`. Cette page a 2 rôles selon le contexte (détecté en JS via `window.self !== window.top`, cf. `doc/style.md`) : contenu affiché dans l'iframe `#miniSite` sur l'écran 3D de `/story` (desktop), ou page à part entière quand `/story` y redirige directement sur mobile (<700px, la scène 3D y serait minuscule/illisible) : dans ce 2e cas seulement, une petite navbar de site apparaît pour continuer la visite.

## Accessibilité

- Objectif du projet : accessibilité **maximale**, pas juste le minimum légal. Sur toute nouvelle UI/couleur :
  - Contraste texte : viser AAA (7:1) quand c'est atteignable sans dénaturer une couleur de marque (une couleur saturée très éclaircie pour tenir 7:1 sur fond sombre finit souvent délavée/pastel : compromis à assumer et documenter, pas à cacher), AA (4.5:1) en minimum absolu pour du texte normal, 3:1 pour du texte large (≥18.66px gras ou ≥24px normal). Calculer la luminance relative (formule WCAG), pas à l'œil.
  - Ne jamais faire reposer la lisibilité sur la seule teinte (le bug rouge/vert de la navbar, ~1.1:1 voire texte invisible par endroits, corrigé le 2026-08-10 sur tout le site). La distinction doit tenir même en simulant protanopie/deutéranopie/tritanopie/achromatopsie : filtre SVG `feColorMatrix` appliqué en `filter: url(#id)` sur `<html>` + screenshot Playwright pour vérifier (voir l'historique de la branche `phase5_story`).
  - Indicateur de focus clavier (`:focus-visible`) visible sur tout élément interactif, dans une couleur dédiée indépendante du reste de la palette (ex. blanc pur dans `assets/story/minisite.css`) plutôt qu'une simple variation de la couleur de texte.
  - `assets/story/minisite.css` documente un exemple de palette pensée pour ça dès le départ (variables commentées avec leurs ratios de contraste) : à prendre comme référence pour les prochaines pages.

## Cadrage d'un overlay 2D sur une scène 3D (story/schedule/music)

Plusieurs pages (`/story`, `/schedule`, `/music`) posent du contenu 2D (DOM classique, pas du texte peint dans la texture 3D) par-dessus une scène Three.js à caméra fixe (`camera.position.set(...)`, pas d'orbit controls) : un `<div>`/`<iframe>` censé coïncider avec un écran/cadre du modèle 3D (`#miniSite` sur `/story`, `#waveform`/`.playlist-wrap` sur `/music`). Le rendu 3D suit la taille de la fenêtre (`renderer.setSize(window.innerWidth, window.innerHeight)`, aspect ratio de la caméra recalculé au resize), donc la position/taille à l'écran du modèle change avec la fenêtre. Trois symptômes rencontrés jusqu'ici : le cadre ne suit pas le redimensionnement (page Histoire, corrigé en vw/vh), le cadre ne correspondait déjà pas à l'écran du modèle avant même de parler de redimensionnement (page Musique, 1er passage en vw/vh le 2026-08-10 matin), et le vw/vh lui-même décale tout dès que le **ratio** largeur/hauteur de la fenêtre s'écarte de celui utilisé pour calibrer (page Musique de nouveau, sur une fenêtre large/basse type 1835×901 : repéré le 2026-08-10 après-midi, le cadre du player flottait au-dessus du meuble). Cause du 3e symptôme : une caméra en perspective a un champ de vision **vertical** fixe (`camera.fov`) mais un champ de vision **horizontal** qui dépend du ratio (`tan(hFov/2) = tan(vFov/2) * aspect`), donc la mise à l'échelle horizontale à l'écran d'un point 3D fixe n'est pas une fonction linéaire de la largeur de fenêtre seule, ce qu'un simple `vw` suppose à tort.

**Méthode "mesure empirique + vw/vh"** (page Histoire, `#miniSite`, cf. `assets/main/app.css`) : suffisante quand la fenêtre change de taille mais reste dans une gamme de ratios proche de celle testée.
1. Screenshot Playwright à une taille de référence connue (`browser_resize` puis `browser_take_screenshot`, ex. 1280×800).
2. Scanner les pixels du PNG avec un script Python + PIL (`Image.open(...).getpixel((x,y))`) pour trouver la frontière réelle du cadre/écran du modèle 3D : chercher la transition de couleur entre la texture du meuble et la zone cible. Scanner plusieurs lignes/colonnes pour confirmer l'absence de déformation en perspective.
3. Comparer à la position/taille réelle du `<div>` overlay via `browser_evaluate` + `getBoundingClientRect()`.
4. Recalibrer le CSS en `vw`/`vh` (proportions du viewport).
5. Revérifier avec un nouveau screenshot + scan pixel après coup, à plusieurs tailles.

**Méthode "vraie projection 3D→2D"** (page Musique, `assets/music/music.js`, depuis le 2026-08-10 après-midi) : robuste à tout ratio de fenêtre, à privilégier désormais dès qu'une page a déjà cassé une fois en vw/vh.
1. Mesurer au pixel près (même scan PNG que ci-dessus) le rectangle cible sur un rendu de référence, overlay CSS masqué (`el.style.display='none'`) pour voir le modèle nu.
2. Exposer temporairement `scene`/`camera`/`raycaster` (variables de module, invisibles depuis `window` sinon) et faire un raycast caméra→modèle à ces coordonnées pixel pour récupérer le point 3D réel (`raycaster.intersectObjects(scene.children, true)[0].point`) sur la surface du meuble. Retirer l'exposition une fois les coordonnées relevées.
3. Coder ces points comme constantes monde (pas de sous-objet "écran" nommé dans le GLTF ici, donc coordonnées en dur, commentées avec leur provenance) et écrire une fonction `projectPoint(x,y,z)` = `new THREE.Vector3(x,y,z).project(camera)` puis conversion NDC→px (`((v.x+1)/2)*innerWidth`, `((1-v.y)/2)*innerHeight`).
4. Appliquer position/taille (`position:fixed` + `left/top/width/height` en px) en JS, recalculée à l'init **et** à chaque `resize` (même fonction `updateOverlayPosition()` dans les deux cas). **Piège** : `Vector3.project(camera)` utilise `camera.matrixWorldInverse`, qui n'est recalculée que par un rendu : un appel avant la 1re frame de `animate()` projette avec une matrice caméra périmée et donne des positions aberrantes (valeurs à 5 chiffres). Appeler `camera.updateMatrixWorld()` explicitement avant de projeter si l'appel initial a lieu avant le premier `renderer.render()`.
5. Les éléments repositionnés en JS doivent porter leur propre `z-index` : si leur ancien parent (`.overlay`) ne s'occupe plus de les positionner, il perd aussi son intérêt à porter `position`/`z-index`, et sans lui pour créer un contexte d'empilement, les enfants en `position:fixed` peuvent se retrouver sous le `<canvas>` WebGL (append plus tardif dans le DOM).
6. Revérifier à plusieurs ratios très différents (ex. 1280×800, 1835×901, 2200×700, 1920×1080), **avec redimensionnement dynamique en plus du reload**, pas juste "à l'œil" sur un screenshot mais en comparant `el.style.left/top/width/height` aux coordonnées cibles mesurées à l'étape 1.

Sur `/schedule`, toujours pas revérifié à ce jour (ni vw/vh, ni projection) : probablement le prochain endroit où appliquer directement la méthode "vraie projection" plutôt que de repasser par du vw/vh qui recasse au premier ratio de fenêtre inhabituel.

## Branches & CI

- `master` est la branche principale de travail (2026-08-12 : `main_2026` fusionnée dans `master` via PR GitHub #33, puis supprimée locale + distante, devenue inutile). Chaque fonctionnalité part d'une branche dédiée depuis `master`, mergée dedans une fois prête.
- `.github/workflows/pipeline.yml` : un seul fichier, jobs enchaînés via `needs:` (lint → sécurité → déploiement à venir en Phase 3) :
  - `lint-php-twig` / `lint-js` : PHP-CS-Fixer + Twig-CS-Fixer / ESLint, sur chaque push (toutes branches) et PR vers `master`
  - `npm-audit` (sur push) / `dependency-review` (sur PR) : dépendent des jobs de lint, mêmes seuils qu'avant (`--audit-level=high` / `fail-on-severity: high`)
  - job `deploy` pas encore implémenté (commenté dans le fichier), prévu pour push sur `master` uniquement une fois la Phase 3 de `ROADMAP.md` faite
- Dependabot est déjà actif sur ce repo (nombreuses branches distantes `dependabot/*`) : vérifier si une PR Dependabot existe déjà avant de monter une dépendance à la main.

## Convention de commit

- Messages courts, **en français**, à l'impératif, préfixés d'un **gitmoji** : `✨` feature, `🐛` fix, `🔒️` sécurité, `👷` CI/build, `📝` doc, `♻️` refacto, `🎨` style, `✅` tests, `🔧` config, `⬆️` mise à jour de dépendance, `🔥` suppression de code/fichier.
- `🚧` (work in progress) peut se **combiner** avec l'emoji principal (`🚧✨`, `🚧🐛`...) plutôt que le remplacer, quand un commit apporte un vrai fix/feature mais que l'ensemble (page, fonctionnalité) n'est pas encore considéré fini/validé. Utilisé sur la branche `phase5_story`.
- Un template de commit est configuré localement (`git config commit.template` → `.gitmessage` à la racine), il s'affiche automatiquement à chaque `git commit`.
- **Toujours attendre l'accord explicite de l'utilisatrice avant de lancer `git commit`** (sauf instruction contraire donnée dans le fil), même après une série de modifications déjà validées visuellement.
