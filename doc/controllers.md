# Contrôleurs & routes

Toutes les routes sont déclarées en attributs PHP natifs `#[Route]` directement sur les méthodes (pas de fichier `routes.yaml` central). Aucun contrôleur ne vérifie de rôle en dur (`denyAccessUnlessGranted`/`#[IsGranted]`) : tout le contrôle d'accès passe par `access_control` dans `config/packages/security.yaml`, détaillé ici route par route. Voir [security.md](security.md) et [role.md](role.md) pour la vue d'ensemble par rôle plutôt que par contrôleur.

## `HomeController`, `StoryController`, `ScheduleController`
- `GET /` (`home`), `GET /story` (`story`), `GET /story/mini` (`minisite`), `GET /schedule` (`schedule`), `GET /schedule/event/{id}.ics` (`event_ics`) : pages vitrines + export agenda, tous publics.

## `LoginController`
- `GET /join` (`join`) : page de connexion/inscription. `login_path`/`check_path` de `security.yaml` pointent tous les deux ici.
- `GET /login` (`login`) : redirige vers `/join`, route legacy jamais utilisée par le vrai flux de connexion.
- `/logout` (`logout`) : géré entièrement par le firewall Symfony.
- `/register` (`register`) : inscription (`RegistrationType`). Si `wish == 'Simple'`, `validation = true` automatiquement ; sinon en attente d'un admin (cf. [role.md](role.md)). Upload de la photo de profil (jpeg) si fournie.
- `/desk/profile` (`profile`) : édition du profil connecté (`AccountType`), upload photo. `IS_AUTHENTICATED_FULLY` (`^/desk`).
- `/desk/update-password` (`update-password`) : changement de mot de passe (`PasswordUpdateType`/`PasswordUpdate`), vérifie l'ancien hash avec `password_verify`. `IS_AUTHENTICATED_FULLY`.

⚠️ Bug existant : dans `updatePassword`, si `password_verify` échoue (mauvais ancien mot de passe), le bloc `if` est **vide** : aucun message d'erreur affiché, retombe silencieusement sur le formulaire.

## `DeskController`
- `GET /desk` (`desk`) : tableau de bord, contenu différent par rôle (cf. [role.md](role.md), section "comportement multi-rôles"). `IS_AUTHENTICATED_FULLY`.
- `GET /desk/files` (`desk_files_hub`) : hub vers les espaces de fichiers accessibles. `IS_AUTHENTICATED_FULLY` (mais n'affiche une carte que pour les rôles qui donnent vraiment accès à un espace).
- `GET /desk/files/{space}` (`desk_files`, `space` = `music|admin|accounting`) : navigateur de fichiers façon Drive pour l'espace donné (dossiers + documents +, pour `music` uniquement à sa racine, morceaux/voix favorites). `ROLE_BINIOUFOUS`/`ROLE_ADMIN` (music), `ROLE_ADMIN` (admin), `ROLE_COMPTA`/`ROLE_ADMIN` (accounting), via des règles `^/desk/files/{space}` dédiées.
- `POST /desk/files/music/voices/{voiceId}/toggle` (`desk_voice_toggle`) : coche/décoche la voix jouée par le membre connecté. Même règle que l'espace Musique (`^/desk/files/music`).

## `FolderController` (préfixe `/desk/files/{space}/folders`)
- `POST` (`folder_create`), `DELETE /{id}` (`folder_delete`), `POST /{id}/move` (`folder_move`) : mêmes règles d'accès que `desk_files` pour l'espace concerné (lecture et écriture soumises à la même règle). `folder_move` refuse un déplacement dans le dossier lui-même ou un de ses descendants (`FolderRepository::isSelfOrDescendantOf`).

## `DocumentController` (préfixe `/desk/files/{space}/documents`)
- `POST` (`document_upload`), `DELETE /{id}` (`document_delete`), `POST /{id}/favorite` (`document_favorite_toggle`), `POST /{id}/move` (`document_move`) : mêmes règles d'accès que l'espace concerné.

## `AdminController` (tout sous `ROLE_ADMIN` via `^/admin`)
- `GET /admin/valid` (`valid`) : liste des inscriptions en attente (`validation = false`).
- `/admin/{wish}/{slug}/valid` (`user_valid`) : valide un utilisateur (retrouve le `Role` correspondant au `wish`, l'attribue, passe `validation` à `true`). Formulaire `ValidRoleType`, aujourd'hui sans champ (juste un bouton de confirmation, cf. [forms.md](forms.md)).
- `POST /admin/user/{slug}/refuse` (`user_refuse`) : refuse une inscription en attente = supprime le compte.
- `POST /admin/setadmin/{slug}` (`create_admin`), `POST /admin/setaccountant/{slug}` (`create_accountant`), `POST /admin/setbinioufous/{slug}` (`create_binioufous`) : promotion manuelle en un clic (CSRF direct, pas de `FormType`), indépendante du `wish` d'origine.
- `GET|POST /admin/user/{slug}` (`user_show`) : fiche + édition d'un utilisateur (`EditUserType`), affiche les boutons de promotion ci-dessus pour les rôles manquants.
- `DELETE /admin/user/{slug}/role/{roleId}` (`user_remove_role`) : retire un rôle. Plus aucune exception à documenter depuis le 2026-08-12 : `ROLE_USER` n'existe plus du tout (ni ligne en base, ni injection en dur dans `User::getRoles()`), la liste des rôles d'un compte ne contient donc plus que des rôles réellement retirables.

## `EventController` (préfixe `/admin/event`, `ROLE_ADMIN` via `^/admin`)
- `GET /` (`event_index`), `GET|POST /new` (`event_new`), `GET|POST /{id}/edit` (`event_edit`), `DELETE /{id}` (`event_delete`) : CRUD du planning affiché publiquement sur `/schedule`.

## `AccountantController`
- `GET /accountant` (`accountant`) : espace comptabilité. `ROLE_COMPTA` (`^/accountant`).

## `TrackController` (préfixe `/music`, catalogue public)
- `GET /` (`music`), `GET /{id}` (`track_show`) : liste + détail d'un morceau, publics (y compris anonyme).
- `GET|POST /new` (`track_new`) : `ROLE_ADMIN` (`^/music/new`).
- `POST /quick-upload` (`track_quick_upload`) : dépôt rapide d'un mp3. `ROLE_BINIOUFOUS`/`ROLE_ADMIN` (`^/music/quick-upload`).
- `GET|POST /{id}/edit` (`track_edit`) : `ROLE_ADMIN` (`^/music/[^/]+/edit`).
- `DELETE /{id}` (`track_delete`) : `ROLE_ADMIN` (`^/music` en méthode `DELETE`).

⚠️ `templates/music/show.html.twig` affiche le lien "modifier" et le formulaire de suppression à **tout le monde**, y compris un visiteur anonyme, alors que les routes derrière sont `ROLE_ADMIN`. Pas de 403 caché, juste un lien qui échoue si on n'a pas le rôle. Voir [role.md](role.md), section "Points d'attention".

## `VoiceController` (préfixe `/music/{trackId}/voice`)
- `POST` (`voice_new`), `DELETE /{voiceId}` (`voice_delete`) : ajoute/retire une voix (partie instrumentale) sur un morceau. `ROLE_ADMIN` (`^/music/[^/]+/voice`). À ne pas confondre avec `desk_voice_toggle` (`DeskController`) : ici on gère les voix elles-mêmes (nom, fichier audio), là-bas un membre choisit juste la ou les voix qu'il joue.

## `LocaleController`
- `GET /locale/{locale}` (`app_locale`) : change la langue, public.
