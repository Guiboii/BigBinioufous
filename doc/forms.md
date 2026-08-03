# Formulaires (`src/Form/`)

Tous héritent de `ApplicationType` (sauf `AddAdminType`/`AddAccountantType`/`ValidRoleType`), qui fournit juste un helper `getConfiguration($label, $placeholder)` pour éviter de répéter `['label' => ..., 'attr' => ['placeholder' => ...]]` sur chaque champ.

| Formulaire | `data_class` | Utilisé par | Champs notables |
|---|---|---|---|
| `RegistrationType` | `User` | `LoginController::register` (`/register`) | Identité complète + `wish` (choix Binioufous/Member/Simple) + `hash`/`passwordConfirm` + photo (jpeg, 5 Mo max, non mappée) |
| `AccountType` | `User` | `LoginController::profile` (`/desk/profile`) | Même champs que Registration **sauf** mot de passe et `wish` — pas de `passwordConfirm`/`hash` |
| `EditUserType` | `User` | `AdminController::showUser` (`/admin/user/{slug}`) | Identique à `AccountType` (dupliqué) — édition d'un user par un admin |
| `PasswordUpdateType` | `PasswordUpdate` (DTO) | `LoginController::updatePassword` | `oldPassword`, `newPassword`, `confirmPassword` |
| `TrackType` | `Track` | `TrackController::new`/`edit` | `title`, `minutes`/`seconds` (ChoiceType 0-15 / 0-59), `artist` (EntityType), `file` (mp3, 200 Mo max, non mappé) |
| `ValidRoleType` | `User` | `AdminController::validUser` | Une simple checkbox `validation` — sert de confirmation avant d'attribuer le rôle demandé |
| `AddAdminType` | `User` | `AdminController::addAdminRole` | **Vide** (`buildForm` ne fait rien) — juste un formulaire de confirmation à soumettre |
| `AddAccountantType` | `User` | `AdminController::addAccountantRole` | **Vide**, même principe |

## À savoir en modifiant un formulaire "profil"

`AccountType` et `EditUserType` sont quasiment identiques (copier-coller). Si tu ajoutes un champ profil, il faut le dupliquer dans les deux (et potentiellement dans `RegistrationType` si le champ doit être saisi dès l'inscription).

## Upload de fichiers

Pattern commun aux 3 formulaires avec upload (photo dans `RegistrationType`/`AccountType`, mp3 dans `TrackType`) :
1. Champ `FileType` avec `'mapped' => false` (pas lié directement à une propriété de l'entité).
2. Dans le contrôleur : récupération via `$form->get('picture'|'file')->getData()`, slug du nom de fichier via `SluggerInterface`, déplacement physique via `move()` vers un paramètre (`pictures_directory` pour les photos, `mp3` pour les morceaux — voir `config/services.yaml`), puis `set...Filename()` sur l'entité.
2 bis. Les fichiers sont servis depuis `public/uploads/`.
