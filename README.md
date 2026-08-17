# BigBinioufous

Application web Symfony pour la gestion d'un orchestre/fanfare (les "Binioufous") : inscription et validation des adhésions, gestion des rôles, gestion des instruments, bibliothèque musicale (setlist publique + partitions/voix), gestionnaire de fichiers façon Drive (musique, administratif, comptabilité, autre), comptabilité (devis/factures/clients/trésorerie), notes de bureau, planning et pages vitrines (accueil, histoire, planning). Authentification 2FA (TOTP) pour les comptes admin. Interface en français/anglais/breton.

Pour le détail par sujet (entités, contrôleurs/routes, rôles, sécurité, style frontend, i18n), voir [doc/](doc/README.md). Pour l'historique des chantiers en cours et l'état d'avancement, voir [CLAUDE.md](CLAUDE.md) et [ROADMAP.md](ROADMAP.md).

## Stack technique

### Backend

- **PHP** `^8.1`
- **Symfony** `7.4.*` LTS (framework-bundle, security-bundle, form, validator, twig, orm-pack, serializer-pack, mailer...) : routes/entités en attributs PHP natifs
- **Doctrine ORM/DBAL** + **Doctrine Migrations** : persistance des données (migrations dans `src/Migrations/`, voir plus bas)
- **MySQL** `5.7` (voir `DATABASE_URL` dans `.env`)
- **scheb/2fa-bundle** + **scheb/2fa-totp** : 2FA par application d'authentification (TOTP), optionnel, réservé aux comptes `ROLE_ADMIN`
- **Symfony Flex** : gestion des recettes de bundles
- Bibliothèques notables : `cocur/slugify` (slugs), `fakerphp/faker` (fixtures)

### Frontend

- **Symfony AssetMapper** (natif, zéro build Node en prod) : remplace Webpack Encore depuis la migration du 2026-08-05. Dépendances JS déclarées dans `importmap.php`, installées via `php bin/console importmap:require`, servies avec URL versionnée (hash) directement par Symfony.
- **jQuery** `~3.6`, **Bootstrap** `^4.6` (+ `popper.js` `^1.16`), versions **épinglées explicitement** : `importmap:require bootstrap` résout par défaut la dernière version (Bootstrap 5, incompatible avec le code existant en syntaxe `data-toggle`/v4)
- **Three.js** `0.128.0`, épinglé aussi : le code utilise des API anciennes (`PlaneBufferGeometry`, `sRGBEncoding`...) supprimées dans les versions récentes. Rendu 3D pour la mascotte et les pages story/schedule/music (modèles `.gltf`)
- **wavesurfer.js** `6.6.4` (épinglé, `<script src="https://unpkg.com/wavesurfer.js@6.6.4">`), pas géré par AssetMapper. Même piège que Bootstrap/Three.js : sans version, unpkg résout la dernière release (7.x, réécriture complète en modules ES) au lieu de l'API globale utilisée par `assets/music/Player.js`
- **EasyMDE** + **CodeMirror** (+ `codemirror-spell-checker`, `marked`, `typo-js`) : éditeur markdown avec correction orthographique pour la rédaction des pages "Histoire" (`templates/story_section/_form.html.twig`)
- **Remixicon** (icônes) : via `importmap:require`, requis directement sur `remixicon/fonts/remixicon.css` (pas de module JS, juste CSS+fonts)
- **Google Fonts** : `Bungee` pour la navbar principale ; `VT323` + `IBM Plex Mono` pour le thème terminal rétro de la minisite Histoire. Ni l'une ni l'autre gérée par AssetMapper (même logique que wavesurfer.js)
- Fichiers binaires (`.gltf`, `.mp4`, `.png`) référencés depuis du JS vanilla via `window.BB_ASSETS` (objet injecté dans `base.html.twig` via `asset()`, voir `CLAUDE.md`) : AssetMapper ne sert que les URLs hashées exactes, pas les chemins logiques bruts

### Outils / environnement

- **Node.js** `24` (version pinnée dans `.nvmrc`) : utilisé uniquement pour l'outillage de lint (ESLint/Prettier), aucun build JS en prod
- **PHP** CLI système en `8.4.24`, compatible avec `^8.1` requis par `composer.json`
- Gestion de version des deps : `composer.lock`, `package-lock.json` (source de vérité pour npm, devDependencies de lint uniquement), `symfony.lock`
- **CI** : GitHub Actions (`.github/workflows/pipeline.yml`), un seul fichier, jobs enchaînés via `needs:` : lint PHP/Twig (+ vérif des traductions) et lint JS en parallèle sur chaque push/PR, puis audit des dépendances (`composer audit`, `npm audit --audit-level=high` sur push, `dependency-review-action` seuil `high` sur PR vers `master`), puis déploiement SSH sur le VPS (push sur `master` uniquement : `git pull` + `composer install` + migrations + `asset-map:compile` + `cache:clear`)

## Structure du projet

```
├── assets/                    # Sources JS/CSS/3D par page, servies par AssetMapper
│   ├── accounting/               # Comptabilité (devis/factures/clients/trésorerie)
│   ├── desk/                      # Gestionnaire de fichiers, notes, admin/story, avatar...
│   ├── login/                      # Point d'entrée "join" (login/register)
│   ├── main/                        # Point d'entrée "app" (styles/scripts globaux, navbars)
│   ├── mascotte/                     # Point d'entrée "index", mascotte 3D (gltf)
│   ├── music/                         # Point d'entrée "music", setlist + lecteur wavesurfer
│   ├── schedule/                       # Point d'entrée "schedule", planning + assets 3D
│   ├── story/                           # Point d'entrée "story", page histoire + minisite
│   ├── vendor/                            # Dépendances JS téléchargées par importmap:require (gitignoré)
│   └── uploads/                            # (vide, cf. public/uploads/ pour les fichiers réellement servis)
│
├── config/
│   ├── packages/               # Config des bundles Symfony (security, doctrine, twig, asset_mapper, scheb_two_factor, mailer...)
│   ├── routes/                  # Routes (attributs PHP)
│   ├── bundles.php
│   └── services.yaml
│
├── src/Migrations/             # Migrations Doctrine (schéma BDD) ; `migrations/` à la racine est un
│                                # dossier legacy vide (gitignoré à part), non utilisé
│
├── public/
│   ├── uploads/                # Fichiers uploadés servis publiquement (musique, docs, photos ;
│   │                            # .htaccess bloque l'exécution PHP dedans)
│   └── index.php               # Front controller Symfony
│
├── src/
│   ├── Controller/             # HomeController, LoginController, DeskController, AdminController,
│   │                            # MusicController, SetlistController, FolderController,
│   │                            # DocumentController, BulkActionController, AccountingController,
│   │                            # EventController, ScheduleController, StoryController,
│   │                            # StorySectionController, NoteController, TwoFactorController,
│   │                            # ContactController, LocaleController
│   ├── Entity/                  # User, Role, Instrument, Folder, Document, SetlistItem, Artist,
│   │                             # Event, Note, StorySection, Client, AccountingDocument(Line),
│   │                             # LedgerEntry, PasswordUpdate
│   ├── Security/                 # FolderWriteVoter (droits d'écriture par espace du gestionnaire de fichiers)
│   ├── Form/                      # Types de formulaires (inscription, profil, édition user...)
│   ├── Repository/                 # Repositories Doctrine associés à chaque entité
│   ├── DataFixtures/                # Jeux de données de démo (AppFixtures)
│   └── Kernel.php
│
├── templates/                  # Vues Twig, une sous-arborescence par section
│   ├── home/ join/ desk/ admin/ music/ schedule/ story/ note/ event/ accounting/ ...
│   ├── base.html.twig           # Layout principal (contient window.BB_ASSETS, voir CLAUDE.md)
│   ├── desk/base.html.twig       # Layout du tableau de bord membre (navbar admin fixe en desktop)
│   └── partials/                  # Header, etc.
│
├── tests/                      # Tests PHPUnit (squelette uniquement, aucun test réel actuellement)
├── translations/                # fr/en/br (br partielle, repli implicite sur fr/en)
├── composer.json / composer.lock    # Dépendances PHP
├── package.json / package-lock.json  # Outillage de lint JS uniquement (npm)
├── importmap.php                     # Dépendances JS AssetMapper (points d'entrée + libs)
├── symfony.lock                      # Recettes Flex installées
└── phpunit.xml.dist
```

## Modèle de données et rôles

Aperçu rapide, détail complet dans [doc/entities.md](doc/entities.md) et [doc/role.md](doc/role.md) :

- **User** (membre) : identité facultative (nom/prénom/genre/naissance/ville/pays/instrument, tout se complète après coup sur `/desk/profile`), lié à plusieurs **Role**, éventuellement à un **Instrument**. Seuls 3 rôles existent : `ROLE_ADMIN`, `ROLE_COMPTA`, `ROLE_BINIOUFOUS`.
- **Folder** / **Document** : gestionnaire de fichiers façon Drive, un espace parmi `music`/`admin`/`accounting`/`other` par dossier racine, droits lecture/écriture par espace (`access_control` + `FolderWriteVoter`), corbeille avec restauration (`deletedAt`).
- **SetlistItem** / **Artist** : setlist affichée sur `/music` (page publique), chaque morceau pointe éventuellement vers un `Folder` de l'espace `music` portant les fichiers audio réels.
- **AccountingDocument** / **AccountingDocumentLine** / **Client** / **LedgerEntry** : devis/factures et trésorerie de l'espace comptabilité.
- **Event** : planning (`/schedule`), export `.ics`.
- **StorySection** : contenu éditorial de la page Histoire (`/story`), édité en markdown (EasyMDE).
- **Note** : notes du bureau/conseil (`/desk/notes`).

## Fonctionnalités / espaces principaux

Table complète des routes par contrôleur dans [doc/controllers.md](doc/controllers.md). Aperçu :

| Espace                        | Routes principales                       | Accès                                          |
| ------------------------------ | ----------------------------------------- | ----------------------------------------------- |
| Pages vitrines                | `/`, `/story`, `/schedule`, `/music`      | Public                                          |
| Connexion / inscription        | `/join`, `/login`, `/register`, `/logout` | Public                                          |
| Tableau de bord membre         | `/desk`, `/desk/profile`                  | Connecté (`IS_AUTHENTICATED_FULLY`)             |
| Gestionnaire de fichiers        | `/desk/files/{music,admin,accounting,other}` | Par espace, cf. `security.yaml`/`FolderWriteVoter` |
| Setlist (gestion)               | `/desk/files/music/setlist`               | `ROLE_BINIOUFOUS`/`ROLE_ADMIN` (écriture espace musique) |
| Comptabilité                     | `/desk/files/accounting/{documents,clients,treasury}` | `ROLE_COMPTA`/`ROLE_ADMIN`         |
| Notes de bureau                   | `/desk/notes`                             | `ROLE_ADMIN`/`ROLE_COMPTA`                      |
| Validation / gestion des membres   | `/admin/*` (valid, user, event, story...) | `ROLE_ADMIN`                                    |
| 2FA (activation compte admin)       | `/desk/profile/2fa`, `/2fa`               | `ROLE_ADMIN` (optionnel côté compte)            |

## Sécurité (`config/packages/security.yaml`)

Détail complet dans [doc/security.md](doc/security.md). Résumé :

- Authentification par formulaire (`form_login`), provider Doctrine sur `App\Entity\User` (identifiant = email), mot de passe hashé en `bcrypt`. Anti brute-force natif Symfony (`login_throttling`).
- 2FA TOTP (`scheb/2fa-bundle`) pour les comptes `ROLE_ADMIN`, optionnel côté compte (activé une fois un secret enregistré depuis `/desk/profile`), désactivé en environnement `dev`.
- `/admin/*` réservé à `ROLE_ADMIN`. `/desk/*` réservé à un compte connecté (`IS_AUTHENTICATED_FULLY`, pas un rôle métier) : l'inscription n'attend pas de validation admin pour se connecter, un bandeau invite juste à compléter son profil tant que le compte n'est pas validé.
- Chaque espace du gestionnaire de fichiers (`/desk/files/{space}`) a ses propres rôles de lecture (`access_control`) et d'écriture (`FolderWriteVoter`, peut diverger de la lecture, cf. espace `other`).
- Uploads : types MIME restreints par espace, anti path-traversal, exécution PHP bloquée dans `public/uploads` (`.htaccess`).

## Installation

```bash
# Dépendances PHP (installe aussi les deps JS via importmap.php, pas besoin de npm pour faire tourner le site)
composer install

# Outillage de lint JS (optionnel, pour contribuer au code JS)
nvm use
npm ci

# Base de données
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load   # jeu de données de démo (Faker)
```

## Lancer le projet en local

```bash
php -d upload_max_filesize=200M -d post_max_size=210M -S localhost:8000 -t public
```

Les options `-d` sont nécessaires : le `php.ini` CLI système par défaut limite `upload_max_filesize` à `2M`/`post_max_size` à `8M`, insuffisant pour les fichiers audio/vidéo de l'espace musique (sinon échec silencieux de l'upload, sans page d'erreur claire).

Aucune étape de build front nécessaire : AssetMapper sert les assets directement depuis `assets/` en dev. En prod, `php bin/console asset-map:compile` (joué automatiquement au déploiement, cf. CI ci-dessus).

## Configuration (`.env`)

```
APP_ENV=dev
APP_SECRET=...
DATABASE_URL=mysql://root:root@127.0.0.1:3306/binioufous_4?serverVersion=5.7
```

`MAILER_DSN` n'est volontairement pas déclaré dans `.env` : les emails admin (notification d'inscription, validation...) ne sont pas encore configurés (`config/services.yaml` pose `null://null` en repli pour que l'injection de `MailerInterface` ne fasse pas planter le conteneur, cf. ROADMAP.md "Emails fonctionnels").

## Tests

Tests PHPUnit via le pack Symfony `test-pack` :

```bash
php bin/phpunit
```

Aucun test réel actuellement (seulement `tests/bootstrap.php`).

## Conventions de code

PHP (PHP-CS-Fixer, ruleset `@Symfony`), Twig (Twig-CS-Fixer) et JS (ESLint + Prettier), avec un `.editorconfig` commun pour l'indentation. Un `Makefile` unifie les commandes des deux écosystèmes (composer/npm) :

```bash
make lint       # vérifie tout (PHP + Twig + JS), sans rien modifier
make lint-fix   # corrige tout automatiquement
```

Commandes détaillées si besoin de cibler un seul outil : `composer cs-php` / `cs-php-fix`, `composer cs-twig` / `cs-twig-fix`, `npm run lint:js` / `lint:js:fix`.
