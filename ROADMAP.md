# Roadmap

Objectif : remettre BigBinioufous dans un état correct pour 2026, et facile à faire évoluer ensuite.

Ordre choisi pour éviter de refaire le même travail deux fois : les fondations et l'architecture d'abord, le contenu (pages, fonctionnalités) ensuite.

## Phase 1 — Fondations

- [x] Conventions de code : `.editorconfig`, PHP-CS-Fixer (`@Symfony`), Twig-CS-Fixer, ESLint/Prettier (flat config), `Makefile` (`make lint` / `make lint-fix`)
- [ ] Intégrer ces conventions à la CI (job de lint qui bloque la PR si non respecté)

## Phase 2 — Pivot AssetMapper

- [ ] Remplacer Webpack Encore par AssetMapper (natif Symfony, zéro build Node en prod)
- [ ] Adapter les templates Twig qui référencent les assets compilés
- [ ] Réévaluer la CI JS (le job `npm audit`/`dependency-review` n'aura peut-être plus lieu d'être si Node disparaît du projet)

## Phase 3 — Déploiement automatique (VPS auto-géré)

- [ ] Décider de l'approche (Docker + docker-compose vs déploiement direct) — à creuser ensemble
- [ ] Pipeline GitHub Actions : build, migrations, déploiement SSH vers le VPS
- [ ] Secrets de déploiement (clé SSH, credentials DB prod) via GitHub Secrets

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
- [x] `CREDITS.md` créé (cousin de Guillaume à identifier, aucune trace trouvée dans git ni dans les fichiers `.gltf`)
