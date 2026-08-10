# BigBinioufous

Application web Symfony pour la gestion d'un orchestre/fanfare (les "Binioufous") : inscriptions des membres, validation des adhésions, gestion des rôles (admin, comptable, membre...), gestion des instruments, bibliothèque musicale (morceaux/artistes) et pages vitrines (accueil, histoire, planning).

## Stack technique

### Backend

- **PHP** `^8.1` (requis par Symfony 6.3+)
- **Symfony** `7.4.*` LTS (framework-bundle, security-bundle, form, validator, twig, orm-pack, serializer-pack...) : upgradé depuis Symfony 5.4 (via 6.4), routes/entités en attributs PHP natifs
- **Doctrine ORM/DBAL** + **Doctrine Migrations** : persistance des données
- **MySQL** `5.7` (voir `DATABASE_URL` dans `.env`)
- **Symfony Flex** : gestion des recettes de bundles
- Bibliothèques notables : `cocur/slugify` (slugs), `fakerphp/faker` (fixtures)

### Frontend

- **Symfony AssetMapper** (natif, zéro build Node en prod) : remplace Webpack Encore depuis la migration du 2026-08-05. Dépendances JS déclarées dans `importmap.php`, installées via `php bin/console importmap:require`, servies avec URL versionnée (hash) directement par Symfony.
- **jQuery** `~3.6`, **Bootstrap** `^4.6` (+ `popper.js` `^1.16`), versions **épinglées explicitement** : `importmap:require bootstrap` résout par défaut la dernière version (Bootstrap 5, incompatible avec le code existant en syntaxe `data-toggle`/v4)
- **Three.js** `0.128.0`, épinglé aussi : le code utilise des API anciennes (`PlaneBufferGeometry`, `sRGBEncoding`...) supprimées dans les versions récentes. Rendu 3D pour la mascotte et les pages story/schedule (modèles `.gltf`)
- **wavesurfer.js** `6.6.4` (épinglé, `<script src="https://unpkg.com/wavesurfer.js@6.6.4">`), pas géré par AssetMapper. Même piège que Bootstrap/Three.js : sans version, unpkg résout la dernière release (7.x, réécriture complète en modules ES) au lieu de l'API globale `WaveSurfer.create()`/plugin `.regions.create()` qu'utilise `assets/music/Player.js`. Repéré et corrigé le 2026-08-10 (le lecteur ne fonctionnait pas du tout avant, cf. `ROADMAP.md`)
- **Font Awesome** `^7` (icônes desk/admin) et **Remixicon** (icônes navbar principale) : les deux via `importmap:require`. Remixicon n'a pas de module JS (juste du CSS+fonts), donc requis directement sur le fichier `remixicon/fonts/remixicon.css` plutôt que sur le package entier
- **Google Fonts** : `Bungee` (`<link>` CDN dans `base.html.twig`) pour la navbar principale ; `VT323` + `IBM Plex Mono` (`<link>` CDN dans `templates/story/minisite.html.twig`, propre à cette page) pour le thème terminal rétro de la minisite Histoire. Aucune des deux n'est gérée par AssetMapper (même logique que wavesurfer.js)
- Fichiers binaires (`.gltf`, `.mp4`, `.png`) référencés depuis du JS vanilla via `window.BB_ASSETS` (objet injecté dans `base.html.twig` via `asset()`, voir `CLAUDE.md`) : AssetMapper ne sert que les URLs hashées exactes, pas les chemins logiques bruts

### Outils / environnement

- **Node.js** `24` (version pinnée dans `.nvmrc`) : utilisé uniquement pour l'outillage de lint (ESLint/Prettier), plus aucun build JS en prod
- **PHP** CLI système en `8.4.24`, compatible avec `^8.1` requis par `composer.json`
- Gestion de version des deps : `composer.lock`, `package-lock.json` (source de vérité pour npm, devDependencies de lint uniquement), `symfony.lock`
- **CI** : GitHub Actions (`.github/workflows/pipeline.yml`), un seul fichier, jobs enchaînés (lint → sécurité → déploiement à venir) : lint PHP/Twig/JS, puis `npm audit` sur push (scan complet) ou `dependency-review-action` sur PR vers `main_2026`/`master` (diff des deps ajoutées), seuil `high`

## Structure du projet

```
├── assets/                 # Sources JS/CSS/3D par page, servies par AssetMapper
│   ├── login/                # Point d'entrée "join" (login/register)
│   ├── main/                  # Point d'entrée "app" (styles/scripts globaux)
│   ├── mascotte/               # Point d'entrée "index", mascotte 3D (gltf)
│   ├── music/                  # Point d'entrée "music", lecteur wavesurfer
│   ├── schedule/                # Point d'entrée "schedule", planning + assets 3D
│   ├── story/                    # Point d'entrée "story", page histoire + minisite
│   ├── vendor/                    # Dépendances JS téléchargées par importmap:require (gitignoré)
│   └── uploads/                    # Fichiers uploadés (musique...)
│
├── config/
│   ├── packages/            # Config des bundles Symfony (security, doctrine, twig, asset_mapper, mailer...)
│   ├── routes/               # Routes (attributs PHP)
│   ├── bundles.php
│   └── services.yaml
│
├── migrations/ + src/Migrations/   # Migrations Doctrine (schéma BDD)
│
├── public/
│   ├── uploads/               # Fichiers uploadés servis publiquement (musique, photos)
│   └── index.php              # Front controller Symfony
│
├── src/
│   ├── Controller/           # HomeController, LoginController, DeskController, AdminController,
│   │                          # AccountantController, ScheduleController, StoryController, TrackController
│   ├── Entity/                # User, Role, Instrument, Artist, Track, PasswordUpdate
│   ├── Form/                  # Types de formulaires (inscription, profil, édition user, morceaux...)
│   ├── Repository/            # Repositories Doctrine associés à chaque entité
│   ├── DataFixtures/           # Jeux de données de démo (AppFixtures)
│   └── Kernel.php
│
├── templates/                # Vues Twig, une sous-arborescence par section
│   ├── home/ join/ desk/ admin/ accountant/ music/ schedule/ story/
│   ├── base.html.twig         # Layout principal (contient window.BB_ASSETS, voir CLAUDE.md)
│   └── partials/                # Header, etc.
│
├── tests/                    # Tests PHPUnit
├── translations/
├── composer.json / composer.lock   # Dépendances PHP
├── package.json / package-lock.json # Outillage de lint JS uniquement (npm)
├── importmap.php                   # Dépendances JS AssetMapper (points d'entrée + libs)
├── symfony.lock                    # Recettes Flex installées
└── phpunit.xml.dist
```

## Modèle de données

- **User** : membre du site, identité (nom, prénom, email, hash de mot de passe bcrypt), profil (surnom, ville, genre, pays, date de naissance, photo, slug), statut d'inscription (`validation`, `wish` = rôle souhaité), lié à un **Instrument** et à plusieurs **Role** (relation Many-to-Many).
- **Role** : rôle applicatif (`ROLE_ADMIN`, `ROLE_COMPTA`, `ROLE_BINIOUFOUS`, `ROLE_MEMBER`, `ROLE_Simple`, `ROLE_USER`), avec titre + description, lié à plusieurs `User`.
- **Instrument** : instrument de musique (Hautbois, Cor Anglais, Flûte, Clarinette, Tuba, Euphonium, Batterie, Cor...), lié à plusieurs `User`.
- **Artist** : artiste, lié à plusieurs `Track`.
- **Track** : morceau de musique (titre, durée, fichier mp3 uploadé), lié à un `Artist`.
- **PasswordUpdate** : DTO de formulaire pour le changement de mot de passe (non persisté).

Les fixtures (`src/DataFixtures/AppFixtures.php`) créent : les rôles, les instruments, un super-admin, 20 utilisateurs Binioufous/Membres, 10 utilisateurs simples, 5 artistes et 10 morceaux (via Faker).

## Fonctionnalités / routes principales

| Route                                                     | Contrôleur             | Description                                                                                                       |
| --------------------------------------------------------- | ---------------------- | ------------------------------------------------------------------------------------------------------------------|
| `/`                                                       | `HomeController`       | Page d'accueil                                                                                                    |
| `/story`, `/story/mini`                                   | `StoryController`      | Page histoire de l'association + minisite                                                                         |
| `/schedule`                                               | `ScheduleController`   | Page planning                                                                                                     |
| `/join`, `/login`, `/register`, `/logout`                 | `LoginController`      | Connexion, inscription, déconnexion                                                                               |
| `/desk`                                                   | `DeskController`       | Tableau de bord membre : listes des admins, comptables, binioufous, membres, simples et inscriptions non validées |
| `/desk/music`                                             | `DeskController`       | Playlist / favoris du membre                                                                                      |
| `/desk/profile`                                           | `LoginController`      | Édition du profil (photo, infos)                                                                                  |
| `/desk/update-password`                                   | `LoginController`      | Changement de mot de passe                                                                                        |
| `/admin/valid`                                            | `AdminController`      | Liste des inscriptions à valider                                                                                  |
| `/admin/{wish}/{slug}/valid`                              | `AdminController`      | Validation d'une inscription (attribution du rôle demandé)                                                        |
| `/admin/setadmin/{slug}`                                  | `AdminController`      | Attribution du rôle admin                                                                                         |
| `/admin/setaccountant/{slug}`                             | `AdminController`      | Attribution du rôle comptable                                                                                     |
| `/admin/user/{slug}`                                      | `AdminController`      | Fiche / édition d'un utilisateur                                                                                  |
| `/accountant`                                             | `AccountantController` | Espace comptabilité                                                                                               |
| `/music`, `/music/new`, `/music/{id}`, `/music/{id}/edit` | `TrackController`      | CRUD des morceaux (upload mp3)                                                                                    |

### Sécurité (`config/packages/security.yaml`)

- Authentification par formulaire (`form_login`), provider Doctrine sur `App\Entity\User` (identifiant = email via `getUserIdentifier()`), mot de passe hashé en `bcrypt` (`password_hashers`).
- `/admin/*` réservé à `ROLE_ADMIN`, `/desk/*` réservé à `ROLE_USER`.
- Un nouvel inscrit avec le souhait "Simple" est validé automatiquement ; les autres (Binioufous/Membre) attendent une validation admin qui leur attribue le rôle correspondant.

## Installation

Voir aussi les indications historiques ci-dessous (environnement legacy PHP 7.4 / npm 10, obsolètes depuis la migration AssetMapper).

```bash
# Dépendances PHP (installe aussi les deps JS via importmap.php, pas besoin de npm pour faire tourner le site)
composer install

# Outillage de lint JS (optionnel, pour contribuer au code JS)
nvm use
npm ci

# Base de données
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load   # jeu de données de démo
```

### Notes d'installation historiques (environnement d'origine)

```
alias composer="php7.4 ~/composer.phar"

composer require symfony/webpack-encore-bundle

if problem with memory : php7.4 -d memory_limit=-1 ../composer.phar --profile install --no-dev --optimize-autoloader

npm-node10 install
npm-node10 install jquery popper.js bootstrap --save
npm-node10 install wavesurfer.js bootstrap-icons --save

rm -rf node_modules
npm install

composer require orm-fixtures --dev
composer require fzaninotto/faker
composer require cocur/slugify
```

## Lancer le projet en local

```bash
php -S localhost:8000 -t public
```

Aucune étape de build front nécessaire : AssetMapper sert les assets directement depuis `assets/` en dev. En prod, `php bin/console asset-map:compile` (généralement joué automatiquement par la recette Flex au déploiement).

## Configuration (`.env`)

```
APP_ENV=dev
APP_SECRET=...
DATABASE_URL=mysql://root:root@127.0.0.1:3306/binioufous_4?serverVersion=5.7
```

## Tests

Tests PHPUnit via le pack Symfony `test-pack` :

```bash
php bin/phpunit
```

## Conventions de code

PHP (PHP-CS-Fixer, ruleset `@Symfony`), Twig (Twig-CS-Fixer) et JS (ESLint + Prettier), avec un `.editorconfig` commun pour l'indentation. Un `Makefile` unifie les commandes des deux écosystèmes (composer/npm) :

```bash
make lint       # vérifie tout (PHP + Twig + JS), sans rien modifier
make lint-fix   # corrige tout automatiquement
```

Commandes détaillées si besoin de cibler un seul outil : `composer cs-php` / `cs-php-fix`, `composer cs-twig` / `cs-twig-fix`, `npm run lint:js` / `lint:js:fix`.
