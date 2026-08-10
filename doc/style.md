# Frontend / style : pour s'y retrouver vite

Pas de design system formalisé au sens strict, mais depuis la refonte de la navbar (2026-08-10) `assets/main/app.css` a un vrai jeu de variables de couleurs (`:root { --bb-* }`) à réutiliser. Reste un mélange **Bootstrap 4 + classes maison**, avec **trois systèmes d'icônes différents** (le vrai piège du projet, cf. ci-dessous).

## Icônes : attention, il y en a TROIS systèmes

| Système | Installé comment | Utilisé où | Syntaxe |
|---|---|---|---|
| **Font Awesome** (`@fortawesome/fontawesome-free`) | `importmap:require`, importé en JS/CSS dans `assets/main/app.js` (`fontawesome.min.js` + `.css`), donc dispo partout où l'entry `app` est chargée (= toutes les pages) | Navbar desk/admin (`templates/desk/partials/header.html.twig`) | `<i class="fas fa-home"></i>`, `<i class="far fa-user-circle"></i>`, etc. |
| **Remixicon** | `importmap:require remixicon/fonts/remixicon.css` (le package n'a pas de module JS, seul le fichier CSS est requis directement), importé dans `assets/main/app.js`, dispo partout comme Font Awesome | Navbar publique (`templates/partials/header.html.twig`), variante **`-fill`** (pleine, plus contrastée que `-line`) | `<i class="ri-home-4-fill"></i>`, `<i class="ri-music-2-fill"></i>`, etc. |
| **Bootstrap Icons** | package **pas installé du tout** : le SVG de chaque icône a été copié-collé à la main directement dans les templates | Le burger menu du header public (`bi bi-list`), les boutons du lecteur audio (`templates/music/index.html.twig` : play/pause/stop/loop) | `<svg class="bi bi-play-fill" ...>...path...</svg>` (markup SVG brut, pas une classe qui "marche" toute seule) |

**Piège** : mettre `<i class="bi bi-xxx">` ne fonctionnera pas : Bootstrap Icons n'est chargé nulle part comme police d'icônes, il faut copier le SVG complet depuis [bootstrap-icons](https://icons.getbootstrap.com/) (comme c'est déjà fait pour les icônes existantes).

→ Pour une nouvelle icône : dans la navbar publique ou toute nouvelle page "vitrine", utiliser **Remixicon** (`<i class="ri-...-fill">`, catalogue sur [remixicon.com](https://remixicon.com/)), c'est le système le plus récent et le plus riche. Dans le desk/admin, rester sur **Font Awesome** (`<i class="fas fa-...">`) pour la cohérence avec l'existant. Ne touche au SVG Bootstrap Icons que si tu modifies le lecteur audio ou le burger menu.

## CSS : `assets/main/app.css`

Pas de SCSS, mais un jeu de variables CSS natives (`:root`) en haut du fichier, à réutiliser plutôt que ressortir un hex :

| Variable | Hex | Usage |
|---|---|---|
| `--bb-gold` | `#F2B233` | Couleur signature du site (fond `body`, accents, texte clair) |
| `--bb-gold-pale` | `#FBDB7C` | Texte/accents sur fond vert foncé (navbar) |
| `--bb-gold-rgb` | `242, 178, 51` | Pour composer un `rgba(var(--bb-gold-rgb), <alpha>)` (ex. filets de séparation semi-transparents) |
| `--bb-green-deep` | `#0F352C` | Vert le plus foncé (hover boutons, fond badge toggler navbar ouverte) |
| `--bb-green-mid` | `#1F6652` | Vert "normal" du site (liens, panneau navbar, formulaires) |
| `--bb-green-vivid` | `#257A62` | Nuance distincte utilisée par `.text-green` |
| `--bb-red` | `#BC2727` | Rouge du site (danger/refus, navbar admin) |
| `--bb-red-dark` | `#9E0000` | Rouge plus foncé (hover, overlays de chargement) |
| `--bb-ink` | `#16140F` | Quasi-noir de la palette (icônes sur fond clair) |
| `--bb-white` | `#FFFFFF` | Blanc, utilisé notamment comme texte sur fond rouge/vert (voir Gotcha contraste ci-dessous) |
| `--bb-border-dark` | `#23272B` | Bordure sombre des formulaires (`#joinForm`, `#loginForm`, `form`) |

Classes maison notables (à réutiliser plutôt que recréer) :
- `.btn-red` / `.btn-green` : boutons custom (pas les boutons Bootstrap habituels `.btn-primary`)
- `.bg-yellow`, `.text-redwall` / `.text-green` / `.text-yellow` : couleur de fond/texte isolée
- `.avatar` / `.avatar-medium` : photos de profil rondes
- `.centered` : flex center rapide (`display:flex; align-items:center; justify-content:center`)
- `.red-hover` / `.green-hover` : effet hover générique (texte blanc au survol, voir Gotcha)

`.bg-red`, `.bg-green`, `.bg-red-light`, `.yellow-hover`, `.device-orientation` ont existé mais n'étaient référencés dans **aucun** template ni JS : supprimés le 2026-08-10 (ne pas les recréer sans vérifier qu'il y a un vrai usage).

Police d'affiche **Bungee** (Google Fonts, `<link>` CDN dans `base.html.twig`, pas géré par AssetMapper) pour les libellés de la navbar publique. Le reste du site n'a pas de police custom (défauts Bootstrap/navigateur), **sauf la minisite Histoire, cf. ci-dessous**.

Grille : classes Bootstrap standard (`container`, `row`, `col-*`).

### Cas à part : la minisite Histoire (`assets/story/minisite.css`)

**Piège** : cette page a sa **propre** palette (`--term-*`), différente de `--bb-*` ci-dessus. Ne pas confondre les deux en copiant une couleur d'un fichier vers l'autre.

`templates/story/minisite.html.twig` n'étend pas `base.html.twig` : page HTML autonome, sans AssetMapper (CSS et police Google Fonts `VT323`/`IBM Plex Mono` chargées en `<link>` manuel dans son propre `<head>`, pas d'entry `importmap`). Design terminal rétro, variables `--term-*` déclarées dans `assets/story/minisite.css` (redéclarent volontairement certaines valeurs `--bb-*` sous les mêmes noms, cf. commentaire en tête de fichier, plutôt que de charger `app.css` en entier et risquer des collisions de style).

**Cette page a 2 rôles distincts**, cf. `CLAUDE.md` :
1. Contenu affiché dans l'iframe `#miniSite` sur l'écran 3D de `/story` (desktop, ≥700px).
2. Page à part entière quand `/story` redirige ici directement sur mobile (<700px), cf. `templates/story/index.html.twig`.

Structure en "bureau + fenêtre" (rôle 1) plutôt qu'en plein écran : `.desktop` (dégradé `--desktop-a`/`--desktop-b`, icônes `.desktop-icon--folder`/`--trash` en CSS pur via `clip-path`, pas d'image) sert de fond, avec `.term-window` centrée dedans à 80% de la largeur/hauteur du conteneur (`width/height: 80%`, en `%` et pas `vw`/`vh` pour rester correct quand `.site-nav` ci-dessous prend de la place, cf. `.term-titlebar`/`.term-nav`/`main` en `flex-direction: column`, `main` seul scrollable via `flex:1; overflow-y:auto`). `.taskbar` (barre du bas, bouton "start" + horloge + batterie décorative) est en dehors du flux flex de `.desktop` (`position:absolute`). L'horloge (`#taskbar-clock`) est mise à jour par un `<script>` inline minimal dans le template (pas de fichier JS séparé, pas de dépendance) ; la batterie est purement décorative (l'API Battery Status du navigateur n'est plus fiable/disponible partout, pas utilisée).

Sous 700px de large, tout ce chrome "bureau" disparaît (`display:none` sur `.desktop-icons`/`.taskbar`) et la fenêtre repasse en plein écran (rôle 2, cf. ci-dessous) : pas la place de voir un bureau sur un petit écran.

**Détection rôle 1 vs rôle 2** : un `<script>` synchrone en tête de `<head>` (avant tout rendu, pour éviter un flash) pose `window.self !== window.top ? 'is-embedded' : 'is-standalone'` comme classe sur `<html>`. `.site-nav` (petite barre en haut : phrase "vue simplifiée" + liens vers Accueil/Musique/Planning/Rejoindre, mêmes routes que la navbar principale) n'est visible qu'en `.is-standalone`, jamais en `.is-embedded` (sinon on retombe dans le bug "page dans la page" déjà corrigé une fois). `body` est en `flex-direction:column` pour que `.desktop` se réduise proprement quand `.site-nav` prend de la place au-dessus, au lieu de déborder.

**Redirection mobile** : `templates/story/index.html.twig` a un `<script>` synchrone similaire en tête de `<head>` (avant le chargement des assets 3D/Three.js) qui fait un `window.location.replace(path('minisite'))` si `matchMedia('(max-width: 700px)')` correspond. Évite de charger toute la scène 3D pour un écran où le "moniteur" serait de toute façon minuscule et illisible.

## JS/CSS : AssetMapper, un entry point par section

Pas de Webpack : `symfony/asset-mapper` (natif Symfony, zéro build Node en prod, cf. `CLAUDE.md` et le `README.md` racine pour le détail). Les entries sont déclarées dans `importmap.php` (racine) et appelées par chaque template via `{{ importmap([...]) }}` :

| Entry | Fichier source | Contenu |
|---|---|---|
| `app` | `assets/main/app.js` | jQuery, Bootstrap JS/CSS, Font Awesome, Remixicon, `app.css` : chargé sur **toutes** les pages |
| `index` | `assets/mascotte/mascotte.js` | Mascotte 3D (Three.js + modèles `.gltf`) |
| `music` | `assets/music/music.js` (+ `Player.js`) | Lecteur audio wavesurfer.js (chargé en CDN, pas par AssetMapper) |
| `schedule` | `assets/schedule/schedule.js` | Page planning + éléments 3D |
| `story` | `assets/story/story.js` | Page histoire (scène 3D uniquement ; la minisite affichée dedans est une page à part, hors AssetMapper, cf. plus haut) |
| `join` | `assets/login/join.js` | Page login/register |

Three.js et ses modules (`GLTFLoader`, `OrbitControls`) viennent du package npm `three` via `importmap:require`, **épinglé à `0.128.0`** (pas de libs vendorisées à la main dans `assets/libs/`, ce dossier n'existe plus).

## Gotchas à retenir

- **Trois systèmes d'icônes cohabitent** (voir plus haut) : ne pas mélanger les syntaxes, et vérifier que la classe existe bien avant de l'utiliser (`grep` dans `assets/vendor/<lib>/` après un `importmap:install`, ce dossier est gitignoré donc pas toujours présent).
- **Ne jamais superposer rouge et vert pour du texte sur un fond** (ou l'inverse) : plusieurs bugs de contraste de ce type ont été trouvés et corrigés le 2026-08-10 (navbar, boutons `.btn-red`/`.btn-green`, pills actives du planning/histoire, `.red-hover`/`.green-hover`) — un cas allait même jusqu'au texte totalement invisible (vert sur vert, couleur héritée de la règle globale `a { color: var(--bb-green-mid) }`). Si tu ajoutes un état hover/actif avec un fond rouge ou vert, mets du texte blanc (`var(--bb-white)`) ou clair, pas une autre couleur saturée de la palette.
- Si tu ajoutes une couleur, réutilise une variable `--bb-*` existante plutôt que d'en inventer une nouvelle ; si vraiment aucune ne convient, ajoute-la au bloc `:root` de `assets/main/app.css` avec un nom `--bb-*` cohérent.
- `templates/desk/index.html.twig` avait un bug de typo sur les noms de rôles (`ROLE_BINOUFOUS`/`ROLE_SIMPLE` au lieu de `ROLE_BINIOUFOUS`/`ROLE_Simple`) : corrigé, voir [role.md](role.md).
