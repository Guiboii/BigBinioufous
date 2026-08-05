# Contribuer à BigBinioufous

## Installation

Voir le [README](../README.md) pour l'installation complète (PHP, Node, base de données).

## Conventions

- Style de code : PHP-CS-Fixer (`@Symfony`), Twig-CS-Fixer, ESLint/Prettier. `make lint` pour vérifier, `make lint-fix` pour corriger automatiquement (détails dans [CLAUDE.md](../CLAUDE.md)).
- Commits : messages courts en français, à l'impératif, préfixés d'un [gitmoji](https://gitmoji.dev/) (`✨` feature, `🐛` fix, `🔒️` sécurité, `👷` CI/build, `📝` doc, `♻️` refacto, `🎨` style, `✅` tests, `🔧` config, `⬆️` dépendance, `🔥` suppression). Un template de commit est configuré localement (`.gitmessage`).
- Roadmap et priorités : voir [ROADMAP.md](../ROADMAP.md).

## Contributeurs

- **Guillaume** ([@Guiboii](https://github.com/Guiboii)) — développement initial (backend Symfony, frontend, mise en place du projet)
- **Marine Gonnord** ([@MarineG404](https://github.com/MarineG404)) — remise à niveau 2026 (CI/CD, migration dépendances, nettoyage, nouvelles fonctionnalités)
- **Cousin de Guillaume** (nom à compléter) — modèles 3D (mascotte, décors) sous `assets/mascotte/`, `assets/story/`, `assets/schedule/`, `assets/music/`. Aucune trace d'auteur trouvée dans les fichiers `.gltf` exportés (le champ n'est conservé que dans le `.blend` source, absent du repo) ni dans l'historique git.
