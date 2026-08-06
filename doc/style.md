# Frontend / style : pour s'y retrouver vite

Pas de design system formalisé : c'est un mélange **Bootstrap 4 + classes maison** dans `assets/main/app.css`, avec **deux systèmes d'icônes différents** (le vrai piège du projet, cf. ci-dessous).

## Icônes : attention, il y en a DEUX systèmes

| Système | Installé comment | Utilisé où | Syntaxe |
|---|---|---|---|
| **Font Awesome 5** (`@fortawesome/fontawesome-free`) | importé en JS/CSS dans `assets/main/app.js` (`fontawesome.min.js` + `.css`), donc dispo partout où l'entry `app` est chargée | Quasi tous les liens de menu (`templates/partials/header.html.twig`, `templates/desk/partials/header.html.twig`...) | `<i class="fas fa-home"></i>`, `<i class="fas fa-music"></i>`, etc. |
| **Bootstrap Icons** (`bootstrap-icons` dans `package.json`) | package présent mais **jamais importé comme police/CSS** : le SVG de chaque icône a été copié-collé à la main directement dans les templates | Le burger menu du header (`bi bi-list`), les boutons du lecteur audio (`templates/music/index.html.twig` : play/pause/stop/loop) | `<svg class="bi bi-play-fill" ...>...path...</svg>` (markup SVG brut, pas une classe qui "marche" toute seule) |

**Piège** : mettre `<i class="bi bi-xxx">` ne fonctionnera pas comme avec Font Awesome : bootstrap-icons n'est pas chargé comme police d'icônes, il faut copier le SVG complet depuis [bootstrap-icons](https://icons.getbootstrap.com/) (comme c'est déjà fait pour les 5 icônes existantes).

→ Pour une nouvelle icône : par défaut utiliser **Font Awesome** (`<i class="fas fa-...">`), c'est le système "normal" du projet. Ne touche au SVG bootstrap-icons que si tu modifies le lecteur audio ou le burger menu.

## CSS : `assets/main/app.css`

Pas de SCSS/variables : les couleurs sont des codes hex répétés partout. Palette du projet (couleurs de la fanfare) :

| Couleur | Hex | Usage |
|---|---|---|
| Jaune (fond, texte clair) | `#F2B233` | `body`, `.text-yellow`, `.bg-yellow` |
| Vert | `#1F6652` / `#298b70` (clair) | `.text-green`, `.bg-green`, `.btn-green`, liens |
| Rouge | `#BC2727` / `#9e0000` (foncé) | `.text-redwall`, `.bg-red`, `.btn-red`, navbar admin |

Classes maison notables (à réutiliser plutôt que recréer) :
- `.btn-red` / `.btn-green` : boutons custom (pas les boutons Bootstrap habituels `.btn-primary`)
- `.bg-red` / `.bg-green` / `.bg-yellow`, `.text-red...` / `.text-green` / `.text-yellow`
- `.avatar` / `.avatar-medium` : photos de profil rondes
- `.centered` : flex center rapide (`display:flex; align-items:center; justify-content:center`)
- `.red-hover` / `.green-hover` / `.yellow-hover` : effet hover générique

Grille : classes Bootstrap standard (`container`, `row`, `col-*`).

## JS : un entry point Webpack Encore par section

Voir `webpack.config.js` (`.addEntry(...)`). Chaque page charge sa propre entry, **`app` est toujours chargé** (dans `base.html.twig`) donc jQuery/Bootstrap/FontAwesome/`app.css` sont globaux ; le reste est spécifique à la page :

| Entry | Fichier source | Contenu |
|---|---|---|
| `app` | `assets/main/app.js` | jQuery, Bootstrap JS/CSS, Font Awesome, `app.css` : chargé sur **toutes** les pages |
| `index` | `assets/mascotte/mascotte.js` | Mascotte 3D (Three.js + modèles `.gltf`) |
| `music` | `assets/music/music.js` (+ `Player.js`) | Lecteur audio wavesurfer.js |
| `schedule` | `assets/schedule/schedule.js` | Page planning + éléments 3D |
| `story` | `assets/story/story.js` | Page histoire + minisite |
| `join` | `assets/login/join.js` | Page login/register |

`assets/libs/` contient des libs Three.js vendorisées à la main (pas via npm) : `three.module.js`, `GLTFLoader.js`, `OrbitControls.js`, `dat.gui.module.js`.

## Gotchas à retenir

- **Deux systèmes d'icônes cohabitent** (voir plus haut) : ne pas mélanger les syntaxes.
- `bootstrap-icons` est dans `package.json` mais son CSS/police n'est importé nulle part : ne pas essayer `<i class="bi bi-xxx">`, ça ne rendra rien.
- Pas de variables Sass malgré `node-sass` en dépendance : tout est en CSS brut avec des hex répétés. Si tu ajoutes une couleur, réutilise la palette ci-dessus plutôt que d'en inventer une nouvelle.
- `templates/desk/index.html.twig` avait un bug de typo sur les noms de rôles (`ROLE_BINOUFOUS`/`ROLE_SIMPLE` au lieu de `ROLE_BINIOUFOUS`/`ROLE_Simple`) : corrigé, voir [role.md](role.md).
