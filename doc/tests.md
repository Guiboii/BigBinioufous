# Tests

Deux niveaux de tests, du plus rapide au plus lent.

## PHPUnit (`tests/`) : par défaut

- `vendor/bin/phpunit` (ou `composer test`), quelques secondes.
- Couvre tout ce qui se joue côté serveur : routes, droits d'accès, formulaires, réponses JSON, base de données.
- Un nouveau correctif ou une nouvelle fonctionnalité commence **ici**.

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
