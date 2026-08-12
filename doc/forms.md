# Formulaires (`src/Form/`)

Tous héritent de `ApplicationType`, qui fournit juste un helper `getConfiguration($label, $placeholder)` pour éviter de répéter `['label' => ..., 'attr' => ['placeholder' => ...]]` sur chaque champ.

| Formulaire | `data_class` | Utilisé par | Champs notables |
|---|---|---|---|
| `RegistrationType` | `User` | `LoginController::register` (`/register`) | Identité complète + `wish` (choix Binioufous/Member/Simple, jamais Administrator/Accountant : ces 2 rôles ne s'obtiennent que par promotion manuelle, cf. [role.md](role.md)) + `hash`/`passwordConfirm` + photo (jpeg, 5 Mo max, non mappée) |
| `AccountType` | `User` | `LoginController::profile` (`/desk/profile`) | Même champs que Registration **sauf** mot de passe et `wish` : pas de `passwordConfirm`/`hash` |
| `EditUserType` | `User` | `AdminController::showUser` (`/admin/user/{slug}`) | Identique à `AccountType` (dupliqué) : édition d'un user par un admin |
| `PasswordUpdateType` | `PasswordUpdate` (DTO) | `LoginController::updatePassword` | `oldPassword`, `newPassword`, `confirmPassword` |
| `TrackType` | `Track` | `TrackController::new`/`edit` | `title`, `minutes`/`seconds` (ChoiceType 0-15 / 0-59), `artist` (EntityType), `file` (mp3, 200 Mo max, non mappé) |
| `VoiceType` | `Voice` | `VoiceController::new` (ajout d'une voix sur un morceau) | `name`, `file` (mp3 facultatif, non mappé) |
| `ValidRoleType` | `User` | `AdminController::validUser` | **Vide** depuis le 2026-08-11 (`buildForm` ne fait rien) : un seul bouton "Valider" dans le template attribue le rôle **et** passe `validation` à `true` en une seule action. Avant : une checkbox `validation` qui ne gouvernait que ce 2e effet, jamais l'attribution du rôle (accordée inconditionnellement au submit) : un admin qui validait sans la cocher obtenait un membre avec son rôle mais `validation` resté à `false`. Voir [role.md](role.md). |

`AddAdminType`/`AddAccountantType` n'existent plus : `AdminController::addAdminRole`/`addAccountantRole`/`addBinioufousRole` (ce dernier n'a jamais eu de `FormType` dédié) font un CSRF check direct sans passer par `$this->createForm()`, même pattern que `user_refuse`/`user_remove_role`. Simplification faite le 2026-08-11 (avant : formulaire vide = 2 clics pour confirmer une action qui n'avait qu'un seul état possible).

## À savoir en modifiant un formulaire "profil"

`AccountType` et `EditUserType` sont quasiment identiques (copier-coller). Si tu ajoutes un champ profil, il faut le dupliquer dans les deux (et potentiellement dans `RegistrationType` si le champ doit être saisi dès l'inscription).

## Upload de fichiers

Pattern commun aux 3 formulaires avec upload (photo dans `RegistrationType`/`AccountType`, mp3 dans `TrackType`) :
1. Champ `FileType` avec `'mapped' => false` (pas lié directement à une propriété de l'entité).
2. Dans le contrôleur : récupération via `$form->get('picture'|'file')->getData()`, slug du nom de fichier via `SluggerInterface`, déplacement physique via `move()` vers un paramètre (`pictures_directory` pour les photos, `mp3` pour les morceaux : voir `config/services.yaml`), puis `set...Filename()` sur l'entité.
2 bis. Les fichiers sont servis depuis `public/uploads/`.
