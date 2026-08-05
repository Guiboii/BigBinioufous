# Roadmap

Objectif : remettre BigBinioufous dans un état correct pour 2026, et facile à faire évoluer ensuite.

Ordre choisi pour éviter de refaire le même travail deux fois : les fondations et l'architecture d'abord, le contenu (pages, fonctionnalités) ensuite.

## Phase 1 — Fondations

- [x] Conventions de code : `.editorconfig`, PHP-CS-Fixer (`@Symfony`), Twig-CS-Fixer, ESLint/Prettier (flat config), `Makefile` (`make lint` / `make lint-fix`)
- [x] Intégrer ces conventions à la CI (`.github/workflows/pipeline.yml`, consolidé avec la CI sécurité JS : lint → sécurité, jobs enchaînés via `needs:`)

## Phase 2 — Upgrade Symfony 5 → 7.4 (prérequis pour AssetMapper)

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

## Phase 2bis — Pivot AssetMapper (après la Phase 2)

- [x] Remplacer Webpack Encore par AssetMapper (natif Symfony, zéro build Node en prod) : `webpack-encore-bundle`, `webpack.config.js` supprimés, dépendances JS déclarées dans `importmap.php`
- [x] `eternicode/bootstrap-datepicker` retiré (confirmé mort), ce qui a aussi supprimé `robloach/component-installer` (abandonné) et `components/`
- [x] Adapter les 9 templates Twig qui référencent les assets compilés (`encore_entry_*` → `importmap()`)
- [x] Piège Bootstrap 5/Three.js récent résolus par défaut par `importmap:require` (incompatibles avec le code existant) : versions épinglées explicitement (`bootstrap@^4.6`, `three@0.128.0`)
- [x] Piège des URLs hashées pour les fichiers binaires (`.gltf`, images) référencés en JS : résolu via `window.BB_ASSETS` injecté dans `base.html.twig`
- [x] Testé manuellement (curl + navigateur Playwright, screenshot de la mascotte 3D) sur toutes les pages, 0 erreur console (hors CDN wavesurfer bloqué par la sandbox de test, sans rapport avec la migration)
- [x] CI JS réévaluée : gardée telle quelle, `npm audit` passe à 0 vulnérabilité (devDependencies de lint uniquement désormais)

## Phase 3 — Déploiement automatique (VPS auto-géré)

- [ ] Décider de l'approche (Docker + docker-compose vs déploiement direct) — à creuser ensemble
- [ ] Pipeline GitHub Actions : build, migrations, déploiement SSH vers le VPS
- [ ] Secrets de déploiement (clé SSH, credentials DB prod) via GitHub Secrets
- [ ] Gestion `.env` dev vs prod : `.env` reste les valeurs par défaut (déjà le cas), `.env.local`/`.env.prod.local` pour les vraies valeurs de prod (jamais commitées), voir `composer dump-env prod` pour compiler les variables en prod
- [ ] Gestion de la base de données dev vs prod : BDD locale (fixtures Faker) séparée de la vraie BDD prod, définir comment les migrations Doctrine sont jouées en prod (manuellement vs étape du pipeline de déploiement)

## Phase 4 — Système de traduction (i18n)

Priorité moyenne, mais à faire *avant* le passage page par page (Phase 5) : évite de retraduire/re-toucher chaque page une deuxième fois après coup.

- [ ] Mettre en place le composant de traduction Symfony (`symfony/translation`, déjà en dépendance)
- [ ] Extraire les textes actuellement en dur dans les templates Twig vers des fichiers de traduction (`translations/`)
- [ ] Décider des langues à couvrir (au moins FR, à confirmer si autre langue prévue)

## Phase 5 — Passage page par page (code + UX)

- [ ] Une page à la fois : lisibilité du code (Twig/PHP/JS) et lisibilité visuelle/UX
- [ ] Ordre des pages à définir (vitrine d'abord, ou pages métier d'abord ?)

## Phase 6 — Pages par rôle

- [ ] Même passage (code + UX), page par page, pour les vues spécifiques à chaque rôle (admin, comptable, membre, binioufous...)

## Phase 7 — Nouvelles fonctionnalités

- [ ] Une par une, sur une base stabilisée

## Déjà fait

- [x] CI sécurité JS (`npm audit` + `dependency-review-action`)
- [x] Migration Webpack Encore 0.30 → 7.2, `node-sass` → `sass`
- [x] Node pinné à `24` via `.nvmrc`
- [x] Nettoyage fichiers obsolètes (backups d'éditeur, `old.cpp`, `.env` retiré du tracking git)
- [x] Convention de commit (gitmoji + français, template `.gitmessage`)
- [x] Crédits déplacés dans `.github/CONTRIBUTING.md` (emplacement standard GitHub, plus de `CREDITS.md` à la racine) : cousin de Guillaume à identifier, aucune trace trouvée dans git ni dans les fichiers `.gltf`
