# BigBinioufous

Application web Symfony pour la gestion d'un orchestre/fanfare (les "Binioufous") : inscriptions des membres, validation des adhésions, gestion des rôles (admin, comptable, membre...), gestion des instruments, bibliothèque musicale (morceaux/artistes) et pages vitrines (accueil, histoire, planning).

## Stack technique

### Backend

- **PHP** `^8.1` (requis par Symfony 6.3+)
- **Symfony** `7.4.*` LTS (framework-bundle, security-bundle, form, validator, twig, orm-pack, serializer-pack...) — upgradé depuis Symfony 5.4 (via 6.4), routes/entités en attributs PHP natifs
- **Doctrine ORM/DBAL** + **Doctrine Migrations** — persistance des données
- **MySQL** `5.7` (voir `DATABASE_URL` dans `.env`)
- **Symfony Flex** — gestion des recettes de bundles
- Bibliothèques notables : `cocur/slugify` (slugs), `fakerphp/faker` (fixtures), `eternicode/bootstrap-datepicker`

### Frontend

- **Webpack Encore** `^7.2.0` — build des assets (JS/CSS)
- **jQuery** `^3.5.1`, **Bootstrap** `^4.5.0` (+ `bootstrap-icons`, `popper.js`)
- **Sass** via `sass` (dart-sass)
- **wavesurfer.js** — lecteur audio (waveform) pour la partie musique
- **Three.js** (`assets/libs/three.module.js`, `GLTFLoader`, `OrbitControls`, `dat.gui`) — rendu 3D pour la mascotte et les pages story/schedule (modèles `.gltf`)

### Outils / environnement

- **Node.js** `24` (version pinnée dans `.nvmrc`, requise par `@symfony/webpack-encore@^7.2.0`, dont l'`engines` exige `^22.18.0 || ^24.11.0 || >=26.0`)
- **PHP** CLI système en `8.4.24`, compatible avec `^8.1` requis par `composer.json`
- Gestion de version des deps : `composer.lock`, `package-lock.json` (source de vérité pour npm), `symfony.lock`
- **CI** : GitHub Actions (`.github/workflows/pipeline.yml`), un seul fichier, jobs enchaînés (lint → sécurité → déploiement à venir) : lint PHP/Twig/JS, puis `npm audit` sur push (scan complet) ou `dependency-review-action` sur PR vers `main_2026`/`master` (diff des deps ajoutées), seuil `high`

## Structure du projet

```
├── assets/                 # Sources JS/CSS/3D par page (compilées par Webpack Encore)
│   ├── libs/                # Librairies vendorisées (three.js, dat.gui, GLTFLoader, OrbitControls)
│   ├── login/                # Page "join" (login/register)
│   ├── main/                  # Entrée "app" (styles/scripts globaux)
│   ├── mascotte/               # Entrée "index" — mascotte 3D (gltf)
│   ├── music/                  # Entrée "music" — lecteur wavesurfer
│   ├── schedule/                # Entrée "schedule" — planning + assets 3D
│   ├── story/                    # Entrée "story" — page histoire + minisite
│   └── uploads/                   # Fichiers uploadés (musique...)
│
├── components/              # Dépendances front installées via composer/robloach (bootstrap, jquery, require.js...)
│
├── config/
│   ├── packages/            # Config des bundles Symfony (security, doctrine, twig, mailer...)
│   ├── routes/               # Routes (attributs PHP)
│   ├── bundles.php
│   └── services.yaml
│
├── migrations/ + src/Migrations/   # Migrations Doctrine (schéma BDD)
│
├── public/
│   ├── build/                # Assets compilés (générés par Encore, ne pas éditer à la main)
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
│   ├── base.html.twig         # Layout principal
│   └── partials/                # Header, etc.
│
├── tests/                    # Tests PHPUnit
├── translations/
├── composer.json / composer.lock   # Dépendances PHP
├── package.json / package-lock.json # Dépendances front (npm)
├── webpack.config.js               # Config Webpack Encore (points d'entrée)
├── symfony.lock                    # Recettes Flex installées
└── phpunit.xml.dist
```

## Modèle de données

- **User** — membre du site : identité (nom, prénom, email, hash de mot de passe bcrypt), profil (surnom, ville, genre, pays, date de naissance, photo, slug), statut d'inscription (`validation`, `wish` = rôle souhaité), lié à un **Instrument** et à plusieurs **Role** (relation Many-to-Many).
- **Role** — rôle applicatif (`ROLE_ADMIN`, `ROLE_COMPTA`, `ROLE_BINIOUFOUS`, `ROLE_MEMBER`, `ROLE_Simple`, `ROLE_USER`), avec titre + description, lié à plusieurs `User`.
- **Instrument** — instrument de musique (Hautbois, Cor Anglais, Flûte, Clarinette, Tuba, Euphonium, Batterie, Cor...), lié à plusieurs `User`.
- **Artist** — artiste, lié à plusieurs `Track`.
- **Track** — morceau de musique (titre, durée, fichier mp3 uploadé), lié à un `Artist`.
- **PasswordUpdate** — DTO de formulaire pour le changement de mot de passe (non persisté).

Les fixtures (`src/DataFixtures/AppFixtures.php`) créent : les rôles, les instruments, un super-admin, 20 utilisateurs Binioufous/Membres, 10 utilisateurs simples, 5 artistes et 10 morceaux (via Faker).

## Fonctionnalités / routes principales

| Route                                                     | Contrôleur             | Description                                                                                                       |
| --------------------------------------------------------- | ---------------------- | ----------------------------------------------------------------------------------------------------------------- |
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

Voir aussi les indications historiques ci-dessous (environnement legacy PHP 7.4 / npm 10).

```bash
# Dépendances PHP
composer install

# Dépendances front (utiliser la version Node du .nvmrc)
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

Serveur PHP Symfony :

```bash
php -S localhost:8000 -t public
```

Compilation des assets (Webpack Encore) :

```bash
npm run watch
# ou pour la prod :
npm run build
```

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
