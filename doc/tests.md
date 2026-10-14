# Tests

Deux niveaux de tests, du plus rapide au plus lent.

## PHPUnit (`tests/`) : par défaut

- `vendor/bin/phpunit` (ou `composer test`), quelques secondes.
- Couvre tout ce qui se joue côté serveur : routes, droits d'accès, formulaires, réponses JSON, base de données.
- Un nouveau correctif ou une nouvelle fonctionnalité commence **ici**.
- `composer test:coverage` : mêmes tests + couverture de `src/` (résumé texte + `var/coverage/html/index.html`). Nécessite `pcov` (`sudo dnf install php-pecl-pcov`). En CI : résumé dans l'onglet Summary du run, HTML dans l'artefact `coverage-php`. Pas de seuil minimum imposé pour l'instant.

## Playwright (`tests/e2e/`) : seulement si PHPUnit ne suffit pas

Un vrai navigateur (Chromium) pilote le site. Plus lent (une trentaine de secondes) et plus fragile, donc réservé aux cas où **le bug vit dans le JavaScript ou le rendu**, invisibles pour PHPUnit :

- glisser-déposer (fichiers, dossiers, déplacement) ;
- comportements qui n'existent qu'en JS (sélection multiple, modales, rechargement partiel) ;
- clics sur la scène 3D, éléments superposés, affichage mobile.

Règle simple : si le test peut s'écrire avec une requête HTTP et une vérification de la réponse, c'est du PHPUnit.

### Lancer

```bash
npx playwright install chromium   # une seule fois
npm run test:e2e
```

- `playwright.config.js` lance lui-même un serveur `php -S` sur le port 8001, avec une base **SQLite dédiée** (`var/e2e.db`), recréée à chaque lancement avec les fixtures (`tests/e2e/global-setup.js`). La base MariaDB de dev n'est jamais touchée.
- **Piège** : `php -S` ne transmet les variables d'environnement à Symfony qu'avec `-d variables_order=EGPCS`. Sans ça, le serveur retombe silencieusement sur la base du `.env`.
- En échec, une trace est gardée dans `test-results/` : `npx playwright show-trace <fichier>.zip` pour rejouer le test pas à pas.
- En CI : job `tests-e2e` de `.github/workflows/pipeline.yml`, qui bloque le déploiement comme `tests-php`.

### Tests existants

- `folder-drop.spec.js` : dépôt d'un dossier dans un dossier qui a un homonyme (régression #36). Un `DataTransfer` synthétique ne peut pas contenir de vrais dossiers, donc l'API `webkitGetAsEntry` du navigateur est simulée ; tout le reste (chemins, envoi, arborescence côté serveur) est réel.
- `click-through.spec.js` : sur les 5 pages 3D, un clic sur le burger ne doit pas atteindre la scène derrière (régression #44, reproduite sur l'accueil en 1280×800 avec l'ancien code).
- `files.spec.js` : dépôt (types acceptés, messages d'erreur précis), glisser-déposer, déplacement groupé, corbeille, aperçu des fichiers.
- `music.spec.js` : modale setlist sans rechargement (ajout, ordre, suppression), lecture au clic, pas de lecture automatique, overlay YouTube, téléphone.
- `join.spec.js` : popup de connexion (focus, `Échap`, message après identifiants faux).
- `story.spec.js` : minisite (iframe sans navbar, redirection sur téléphone, formulaire). Clics sur la mascotte et la soucoupe en `fixme` (bug ouvert, cf. `doc/plan-tests-e2e.md`).
- `navigation.spec.js` : sur téléphone, chaque gabarit de page fait la largeur de l'écran (balise viewport) ; `Échap` ferme le menu.
- `profile.spec.js` : champ « lequel ? » de l'instrument Autre.
- `schedule.spec.js` : mois d'ouverture du mini calendrier (horloge figée avec `page.clock`), navigation, téléphone.
- `contact.spec.js` : affichage des réponses du formulaire (réponses serveur simulées).
- `admin.spec.js` : sélection des inscriptions, éditeur Markdown, lignes de devis et fiche client.

Ce qui reste à écrire, fonctionnalité par fonctionnalité : `doc/plan-tests-e2e.md`.

### Écrire un test

- Session admin déjà ouverte : `test.use({ storageState: ADMIN_STATE })` (connexion faite une seule fois par `auth.setup.js`, au lieu de repasser par la scène 3D de `/join` à chaque test).
- Chaque test crée son propre dossier (`openFreshFolder()`, nom horodaté) : la base est partagée entre tous les tests d'un lancement.
- Téléphone : `test.use(MOBILE)` dans un `test.describe` (réglages Pixel 7 de `helpers.js`).
- Formulaires dont le serveur a une limite par IP (contact) : simuler la réponse avec `page.route()`, les règles serveur restent testées par PHPUnit.
- **Piège** : jamais de vrai `/logout` dans un test qui utilise `ADMIN_STATE` : la session côté serveur est partagée par toutes les specs, les suivantes se retrouveraient déconnectées. Pour devenir visiteur·ice : `page.context().clearCookies()`.
- **Piège** : attendre l'événement `load` avant d'interagir (`openFreshFolder()` le fait). Les scripts JS arrivent après le HTML : un fichier choisi trop tôt n'est jamais envoyé, sans aucune erreur.
