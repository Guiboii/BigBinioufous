# Contrôleurs & routes

Toutes les routes sont déclarées en attributs PHP natifs `#[Route]` directement sur les méthodes (pas de fichier `routes.yaml` central). Migré depuis les annotations `@Route` lors de l'upgrade Symfony 6.4 (retrait de `sensio/framework-extra-bundle`, cf. `ROADMAP.md` Phase 2).

## `HomeController`
- `GET /` (`home`) : page d'accueil.

## `StoryController`
- `GET /story` (`story`) : page histoire de l'association.
- `GET /story/mini` (`minisite`) : minisite (scène 3D).

## `ScheduleController`
- `GET /schedule` (`schedule`) : page planning.

## `LoginController` (`src/Controller/LoginController.php`)
- `GET /join` (`join`) : page de connexion/inscription (SPA-like, affiche l'erreur d'auth éventuelle).
- `GET /login` (`login`) : redirige vers `/join` (route jamais utilisée par le vrai flux de connexion, `security.yaml` pointe `login_path`/`check_path` vers `join`). Rendait `join/login.html.twig` en document autonome jusqu'au 2026-08-10 ; devenu impossible une fois ce template allégé en simple fragment inclus dans `join/index.html.twig` (cf. `ROADMAP.md` Phase 5, entrée "Audit accessibilité global").
- `/logout` (`logout`) : géré entièrement par le firewall Symfony (méthode vide, jamais exécutée).
- `/register` (`register`) : inscription (`RegistrationType`) :
  - si `wish == 'Simple'` → `validation = true` automatiquement, sinon en attente d'un admin.
  - upload de la photo de profil (jpeg) si fournie.
- `/desk/profile` (`profile`) : édition du profil connecté (`AccountType`), upload photo.
- `/desk/update-password` (`update-password`) : changement de mot de passe (`PasswordUpdateType` / entité `PasswordUpdate`), vérifie l'ancien hash avec `password_verify`.

⚠️ Bug existant : dans `updatePassword`, si `password_verify` échoue (mauvais ancien mot de passe), le bloc `if` est **vide** : aucun message d'erreur n'est affiché, ça retombe juste sur le formulaire silencieusement.

## `DeskController`
- `GET /desk` (`desk`) : tableau de bord : liste des rôles, des inscriptions non validées, et des utilisateurs groupés par rôle (admins, comptables, binioufous, membres, simples) via des méthodes dédiées de `UserRepository` (`findAdmins`, `findAccountants`, etc.).
- `GET /desk/music` (`deskmusic`) : page "favoris/playlist" (statique pour l'instant, pas de logique).

## `AdminController` (accès `ROLE_ADMIN` via `security.yaml`)
- `GET /admin/valid` (`valid`) : liste des inscriptions en attente de validation.
- `/admin/{wish}/{slug}/valid` (`user_valid`) : valide un utilisateur : retrouve le `Role` correspondant au `wish` (`Role::description`) et l'attribue.
- `/admin/setadmin/{slug}` (`create_admin`) : attribue `ROLE_ADMIN` à un utilisateur (formulaire `AddAdminType`, vide : sert juste de confirmation).
- `/admin/setaccountant/{slug}` (`create_accountant`) : idem pour `ROLE_COMPTA`.
- `/admin/user/{slug}` (`user_show`) : fiche + édition d'un utilisateur (`EditUserType`).

## `AccountantController`
- `GET /accountant` (`accountant`) : espace comptabilité (statique, pas encore de logique métier).

## `TrackController` (préfixe `/music`)
- `GET /music/` (`music`) : liste des morceaux.
- `GET|POST /music/new` (`track_new`) : création + upload mp3.
- `GET /music/{id}` (`track_show`) : détail d'un morceau.
- `GET|POST /music/{id}/edit` (`track_edit`) : édition.
- `DELETE /music/{id}` (`track_delete`) : suppression (protégée par token CSRF `delete{id}`).

## Points d'attention transverses

- Aucun contrôleur (à part la config `security.yaml`) ne vérifie explicitement les rôles avec `$this->denyAccessUnlessGranted(...)` ou `@IsGranted` : le contrôle d'accès repose uniquement sur `access_control` dans `security.yaml` (`/admin/*` → `ROLE_ADMIN`, `/desk/*` → `ROLE_USER`). Donc `/music/*` et `/accountant` ne sont **pas** protégés par un rôle particulier au niveau routing.
- `AccountantController` et `AdminController::index` référencent des routes/vues qui ne vérifient pas non plus `ROLE_COMPTA` : la page `accountant` est juste `/accountant`, accessible à tout utilisateur connecté (`ROLE_USER` suffit puisqu'elle n'est pas sous `/admin`).
