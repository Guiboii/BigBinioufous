# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Application web Symfony 7.4 pour la gestion d'une fanfare ("Binioufous") : inscription des membres, validation des adhésions, gestion des rôles/instruments, bibliothèque musicale, pages vitrines.

## Commands

### PHP / Symfony

- `composer install` — installe les deps PHP
- `php bin/console doctrine:database:create` / `doctrine:migrations:migrate` / `doctrine:fixtures:load` — setup BDD (fixtures = jeu de données de démo via Faker)
- `php -S localhost:8000 -t public` — lance le serveur de dev
- `php bin/phpunit` — lance les tests (aucun test réel actuellement, seulement `tests/bootstrap.php`)

Version PHP : `composer.json` requiert `^8.1` (bump depuis `^7.2.5|^8.0` lors de l'upgrade Symfony 5→6.4). Le PHP CLI système en `8.4.24` convient.

Historique upgrade Symfony : `5.4` → `6.4` LTS (annotations → attributs PHP, sensio retiré) → `7.4` LTS (aucun changement de code nécessaire, tout avait déjà été traité au passage 6.4 ; seul `symfony/webpack-encore-bundle` a dû être bumpé `^1.7` → `^2.4` pour supporter `symfony/asset ^7.0`).

### Frontend (Node)

- Version Node pinnée dans `.nvmrc` à **24** (requise par `@symfony/webpack-encore@^7.2.0`, dont l'`engines` exige `^22.18.0 || ^24.11.0 || >=26.0`). Faire `nvm use` avant toute commande npm.
- `npm ci` est la commande canonique d'installation (utilise `package-lock.json`, seul lock file du projet — `yarn.lock` a été supprimé car legacy/inutilisé, ne pas le réintroduire)
- `npm run watch` — build Webpack Encore en dev avec watch
- `npm run build` — build Webpack Encore en production

### Conventions de code

- `make lint` — vérifie PHP (PHP-CS-Fixer, `@Symfony`) + Twig (Twig-CS-Fixer) + JS (ESLint/Prettier), sans rien modifier
- `make lint-fix` — corrige tout automatiquement
- Configs : `.php-cs-fixer.dist.php`, `.twig-cs-fixer.dist.php`, `eslint.config.js` (flat config) + `.prettierrc.json`, `.editorconfig` (4 espaces PHP/Twig, 2 espaces JS/JSON/YAML)
- Quelques règles JS (`no-unused-vars`, `no-redeclare`, `no-unassigned-vars`) sont volontairement en `warn` plutôt que `error` : le code legacy en contient déjà, corriger ça touche à la logique et relève de la passe page par page (Phase 5 de `ROADMAP.md`), pas de cette passe de style pure

## Architecture

- Backend : Symfony 7.4 LTS, Doctrine ORM/DBAL, MySQL 5.7. Routes et mapping ORM en **attributs PHP natifs** (`#[Route]`, `#[ORM\Entity]`...), plus d'annotations docblock (`sensio/framework-extra-bundle` retiré, remplacé par les attributs core Symfony 6.2+).
- Entités principales : `User` (membre, lié à un `Instrument` et plusieurs `Role`), `Role` (`ROLE_ADMIN`, `ROLE_COMPTA`, `ROLE_BINIOUFOUS`, `ROLE_MEMBER`, `ROLE_Simple`, `ROLE_USER`), `Instrument`, `Artist`/`Track` (bibliothèque musicale).
- Sécurité (`config/packages/security.yaml`) : `form_login`, provider Doctrine sur `User` (identifiant = email, `getUserIdentifier()`), `password_hashers` bcrypt (`encoders` renommé depuis 5.3). `/admin/*` réservé à `ROLE_ADMIN`, `/desk/*` réservé à `ROLE_USER`. Une inscription "Simple" est auto-validée ; les inscriptions Binioufous/Membre attendent une validation admin qui attribue le rôle.
- Frontend : Webpack Encore `^7.2.0` (npm) + `symfony/webpack-encore-bundle` `^2.4` (côté PHP, bumpé depuis `^1.7` pour Symfony 7), jQuery/Bootstrap 4, Sass via `sass` (dart-sass), Three.js (mascotte 3D, pages story/schedule), wavesurfer.js (lecteur musique). Points d'entrée sous `assets/<section>/`. `webpack.config.js` est en ESM (`import`/`export default`, `package.json` a `"type": "module"`).

## Branches & CI

- `main_2026` sert de branche principale de travail pour cette utilisatrice (pas de droits de push sur `master`) ; la CI cible donc `main_2026` **et** `master`.
- `.github/workflows/pipeline.yml` : un seul fichier, jobs enchaînés via `needs:` (lint → sécurité → déploiement à venir en Phase 3) :
  - `lint-php-twig` / `lint-js` : PHP-CS-Fixer + Twig-CS-Fixer / ESLint, sur chaque push (toutes branches) et PR vers `main_2026`/`master`
  - `npm-audit` (sur push) / `dependency-review` (sur PR) : dépendent des jobs de lint, mêmes seuils qu'avant (`--audit-level=high` / `fail-on-severity: high`)
  - job `deploy` pas encore implémenté (commenté dans le fichier), prévu pour push sur `master` uniquement une fois la Phase 3 de `ROADMAP.md` faite
- Dependabot est déjà actif sur ce repo (nombreuses branches distantes `dependabot/*`) — vérifier si une PR Dependabot existe déjà avant de monter une dépendance à la main.

## Convention de commit

- Messages courts, **en français**, à l'impératif, préfixés d'un **gitmoji** : `✨` feature, `🐛` fix, `🔒️` sécurité, `👷` CI/build, `📝` doc, `♻️` refacto, `🎨` style, `✅` tests, `🔧` config, `⬆️` mise à jour de dépendance, `🔥` suppression de code/fichier.
- Un template de commit est configuré localement (`git config commit.template` → `.gitmessage` à la racine), il s'affiche automatiquement à chaque `git commit`.
