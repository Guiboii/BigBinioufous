# Roadmap

Objectif : remettre BigBinioufous dans un état correct pour 2026, et facile à faire évoluer ensuite.

Ordre choisi pour éviter de refaire le même travail deux fois : les fondations et l'architecture d'abord, le contenu (pages, fonctionnalités) ensuite.

## Phase 1 : Fondations

- [x] Conventions de code : `.editorconfig`, PHP-CS-Fixer (`@Symfony`), Twig-CS-Fixer, ESLint/Prettier (flat config), `Makefile` (`make lint` / `make lint-fix`)
- [x] Intégrer ces conventions à la CI (`.github/workflows/pipeline.yml`, consolidé avec la CI sécurité JS : lint → sécurité, jobs enchaînés via `needs:`)

## Phase 2 : Upgrade Symfony 5 → 7.4 (prérequis pour AssetMapper)

**Bloquant découvert le 2026-08-05** : `symfony/asset-mapper` n'existe qu'à partir de Symfony `6.3.0`, le projet tournait en `5.4.45`. Upgrade fait avant de reprendre le pivot AssetMapper.

- [x] Upgrade composer.json 5.* → 6.4.* (aucun conflit de dépendances remonté par composer)
- [x] Remplacer `sensio/framework-extra-bundle` par les attributs PHP natifs Symfony 6.2+ (`#[Route]` sur tous les contrôleurs)
- [x] Mapping Doctrine ORM : annotations → attributs PHP (`#[ORM\Entity]` etc.) sur les 6 entités
- [x] `security.yaml` : `anonymous` (obsolète) retiré, `encoders` → `password_hashers`
- [x] `User.php` : interface de sécurité modernisée (`getRoles(): array`, `getUserIdentifier()`, `PasswordAuthenticatedUserInterface`)
- [x] `Kernel.php` : `RouteCollectionBuilder` (supprimé) → `RoutingConfigurator`
- [x] Bump PHP minimum requis par composer.json à `^8.1` (Symfony 6.3+), couvert par le PHP CLI système en 8.4
- [x] Toutes les pages testées manuellement (accueil, story, schedule, join, login, music, desk, admin, accountant) + validé par l'utilisatrice en localhost
- [x] Upgrade vers Symfony 7.4 LTS : aucun changement de code nécessaire (tout traité au passage 6.4), seul `symfony/webpack-encore-bundle` bumpé `^1.7` → `^2.4` pour supporter `symfony/asset ^7.0`

## Phase 2bis : Pivot AssetMapper (après la Phase 2)

- [x] Remplacer Webpack Encore par AssetMapper (natif Symfony, zéro build Node en prod) : `webpack-encore-bundle`, `webpack.config.js` supprimés, dépendances JS déclarées dans `importmap.php`
- [x] `eternicode/bootstrap-datepicker` retiré (confirmé mort), ce qui a aussi supprimé `robloach/component-installer` (abandonné) et `components/`
- [x] Adapter les 9 templates Twig qui référencent les assets compilés (`encore_entry_*` → `importmap()`)
- [x] Piège Bootstrap 5/Three.js récent résolus par défaut par `importmap:require` (incompatibles avec le code existant) : versions épinglées explicitement (`bootstrap@^4.6`, `three@0.128.0`)
- [x] Piège des URLs hashées pour les fichiers binaires (`.gltf`, images) référencés en JS : résolu via `window.BB_ASSETS` injecté dans `base.html.twig`
- [x] Testé manuellement (curl + navigateur Playwright, screenshot de la mascotte 3D) sur toutes les pages, 0 erreur console (hors CDN wavesurfer bloqué par la sandbox de test, sans rapport avec la migration)
- [x] CI JS réévaluée : gardée telle quelle, `npm audit` passe à 0 vulnérabilité (devDependencies de lint uniquement désormais)

## Phase 3 : Déploiement automatique (VPS auto-géré)

- [x] Approche décidée : déploiement direct (pas de Docker). Priorité donnée à la simplicité de reprise en main par un·e futur·e mainteneur·se de l'assos qui ne connaît pas forcément Docker, plutôt qu'à la reproductibilité de l'environnement (peu utile ici : un seul VPS, pas de scaling prévu)
- [x] Pipeline GitHub Actions : job `deploy` ajouté (`needs: [composer-audit, npm-audit]`, déclenché sur push vers `master` uniquement) : connexion SSH au VPS, `git pull`, `composer install --no-dev`, migrations, `asset-map:compile`, `cache:clear`
- [x] Migrations Doctrine en prod : automatisées comme étape du pipeline (`doctrine:migrations:migrate --no-interaction`, jouée après le `git pull` et avant le `cache:clear`), pas d'étape manuelle à retenir pour un futur repreneur
- [ ] Secrets de déploiement à créer dans GitHub Secrets (Settings → Secrets and variables → Actions) : `VPS_HOST`, `VPS_USER`, `VPS_SSH_KEY` (clé privée), `VPS_PATH` (chemin du repo cloné sur le VPS). Étape manuelle côté GitHub, à faire par l'utilisatrice.
- [ ] Setup initial one-shot sur le VPS (avant le premier déploiement automatique) : `git clone` du repo, PHP 8.1+/MySQL/Apache ou Nginx installés, `.env.prod.local` créé à la main (voir point suivant), `composer install` une première fois
- [ ] Gestion `.env` dev vs prod : `.env` reste les valeurs par défaut (déjà le cas), `.env.local`/`.env.prod.local` pour les vraies valeurs de prod (jamais commitées, créé une fois à la main sur le VPS), voir `composer dump-env prod` pour compiler les variables en prod

## Phase 4 : Système de traduction (i18n)

Priorité moyenne, mais à faire *avant* le passage page par page (Phase 5) : évite de retraduire/re-toucher chaque page une deuxième fois après coup.

- [x] Mettre en place le composant de traduction Symfony (`symfony/translation`, déjà en dépendance) : `default_locale: fr`, `enabled_locales: [fr, en, br]`, fallback `fr`
- [x] Extraire les textes en dur des templates Twig (tous) et des labels/placeholders des formulaires (`src/Form/*.php`, via `ApplicationType::getConfiguration` qui passe désormais par le `TranslatorInterface`) vers `translations/messages.{fr,en,br}.yaml` et `translations/validators.{fr,en,br}.yaml`
- [x] Langues couvertes : **fr** (défaut), **en**, et **br** (breton, "pour le fun" ; traduction perso non relue par un·e locuteur·rice native, à vérifier si l'exactitude compte un jour). Voir [doc/i18n.md](doc/i18n.md) pour le détail (sélecteur de langue, route `/locale/{locale}`, limite du check CI sur `br`)

## Phase 5 : Passage page par page (code + UX)

- [x] Navbar principale (`templates/partials/header.html.twig`) : contraste rouge/vert historique corrigé (~1.1:1 → conforme WCAG AA), palette clarifiée (vert clair/foncé + or existants, rouge retiré du chrome interactif), icônes Remixicon, `<select>` natif remplacé par des liens (moins de mélange chrome Bootstrap/custom), icônes seules sur mobile (texte gardé en `sr-only` pour l'accessibilité)
- [ ] Une page à la fois : lisibilité du code (Twig/PHP/JS) et lisibilité visuelle/UX
- [x] Ordre des pages : vitrine d'abord, en commençant par **Histoire** (`templates/story/`, branche `phase5_story`)
- [ ] Idée à creuser : rendre le texte de la page Histoire modifiable via un simple fichier Markdown plutôt que codé en dur dans `minisite.html.twig`/`translations/*.yaml`, éditable par un ou plusieurs rôles à définir (lesquels ? admin seul, ou aussi binioufous ?)
- [ ] À un moment : audit accessibilité **global**, toutes pages confondues (pas juste page par page au fil de l'eau) : contrastes, daltonisme, navigation clavier, `alt`/labels manquants... Voir la section Accessibilité de `CLAUDE.md` pour la méthode déjà utilisée (navbar + page Histoire). Probablement à faire une fois plusieurs pages retravaillées, pour auditer d'un coup plutôt que plusieurs fois.

## Phase 6 : Pages par rôle

- [ ] Même passage (code + UX), page par page, pour les vues spécifiques à chaque rôle (admin, comptable, membre, binioufous...)

## Phase 7 : Nouvelles fonctionnalités

- [ ] Une par une, sur une base stabilisée

## Déjà fait

- [x] CI sécurité JS (`npm audit` + `dependency-review-action`)
- [x] Migration Webpack Encore 0.30 → 7.2, `node-sass` → `sass`
- [x] Node pinné à `24` via `.nvmrc`
- [x] Nettoyage fichiers obsolètes (backups d'éditeur, `old.cpp`, `.env` retiré du tracking git)
- [x] Convention de commit (gitmoji + français, template `.gitmessage`)
- [x] Crédits déplacés dans `.github/CONTRIBUTING.md` (emplacement standard GitHub, plus de `CREDITS.md` à la racine) : cousin de Guillaume à identifier, aucune trace trouvée dans git ni dans les fichiers `.gltf`
- [x] CI cassée depuis le 2026-08-06 (job `lint-php-twig`, check des traductions) : `DATABASE_URL` factice ajoutée au `.env` éphémère du job, le kernel Symfony refusait de booter sans (le retrait de `.env` du tracking git avait cassé cette étape sans que personne ne le remarque, `composer-audit`/`npm-audit`/`deploy` ne tournaient donc plus sur les push depuis 4 jours)
- [x] Ménage CSS (`assets/main/app.css`, `music.css`, `story.css`) : toutes les couleurs de marque en variables `--bb-*` (quasiment aucune ne l'était), CSS mort supprimé (`.device-orientation`, `.bg-red`, `.bg-green`, `.bg-red-light`, `.yellow-hover`, jamais référencés dans aucun template/JS), et plusieurs bugs de contraste rouge/vert du même type que celui corrigé sur la navbar (texte illisible ou invisible) trouvés et corrigés ailleurs : boutons `.btn-red`/`.btn-green` (desk/admin), pills actives du planning et de la minisite histoire, `.red-hover`/`.green-hover` (page adhésion)
