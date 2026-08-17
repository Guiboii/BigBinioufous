# Formulaires (`src/Form/`)

Tous héritent de `ApplicationType`, qui fournit `getConfiguration($label, $placeholder, $options = [])` (label + placeholder traduits, mergeable avec d'autres options de champ) et `trans($key)` pour les cas qui ne passent pas par `getConfiguration()` (labels de `FileType`, messages de contrainte).

Mise à jour 2026-08-17 : ce fichier décrivait encore `TrackType`/`VoiceType` (retirés le 2026-08-13, upload mp3 remplacé par le dépôt générique du gestionnaire de fichiers, `DocumentController::upload()`, qui ne passe pas par un `FormType`) et `RegistrationType` avec un champ `wish` (retiré le 2026-08-12). Réécrit contre l'état réel de `src/Form/`.

| Formulaire | `data_class` | Utilisé par | Champs notables |
|---|---|---|---|
| `RegistrationType` | `User` | `LoginController::register` (`/register`) | Juste `nickname`/`email`/`hash`/`passwordConfirm` (inscription simplifiée, 2026-08-12) : identité/instrument/adhésion se complètent après coup sur `/desk/profile` |
| `AccountType` | `User` | `LoginController::profile` (`/desk/profile`) | `nickname`/`email` + identité complète, tous facultatifs sauf les deux premiers : `firstName`, `lastName`, `gender`, `birth`, `instrument` (EntityType) + `otherInstrumentDetail`, `city`, `country`, `picture` (jpeg, non mappé) |
| `EditUserType` | `User` | `AdminController::showUser` (`/admin/user/{slug}`) | Mêmes champs qu'`AccountType` **sauf** `nickname`/`email` (identifiants de connexion, affichés en lecture seule côté template plutôt qu'éditables par un admin) : dupliqué plutôt que factorisé, cf. "À savoir" ci-dessous |
| `PasswordUpdateType` | `PasswordUpdate` (DTO) | `LoginController::updatePassword` | `oldPassword`, `newPassword`, `confirmPassword` |
| `ValidRoleType` | `User` | `AdminController::validUser` | **Vide** (`buildForm` ne fait rien) : un seul bouton "Valider" dans le template passe `validation` à `true`, plus aucun rôle à choisir ici (cf. [role.md](role.md)) |
| `EventType` | `Event` | `EventController::new`/`edit` | `title`, `location`, `type` (ChoiceType : rehearsal/concert/other), `date`/`endDate` (DateTimeType, `endDate` facultative), `description`, `poster` (jpeg/png, 5 Mo max, non mappé) |
| `NoteType` | `Note` | `NoteController::new`/`edit` | `title`, `content` (textarea transformée en éditeur Markdown EasyMDE par `assets/desk/note-admin.js`), `shared` (checkbox) |
| `StorySectionType` | `StorySection` | `StorySectionController::new`/`edit` | `title`, `content` (idem `NoteType`, éditeur Markdown via `assets/desk/story-admin.js`, cible l'id auto-généré `story_section_content`) |
| `ClientType` | `Client` | `AccountingController::clientNew`/`clientEdit` | `name`, `address`, `contact` (facultatifs sauf `name`) |
| `AccountingDocumentType` | `AccountingDocument` | `AccountingController::handleDocumentForm` (new/edit/from quote) | `date`, `client` (EntityType, préremplit côté JS via `choice_attr` data-address/data-contact, cf. `assets/accounting/client-autofill.js`), `clientName`/`clientAddress`/`clientContact` (les champs réellement soumis), `correspondentName`/`Email`/`Phone`, `lines` (CollectionType imbriquant `AccountingDocumentLineType`, `allow_add`/`allow_delete`) |
| `AccountingDocumentLineType` | `AccountingDocumentLine` | Entrée de `AccountingDocumentType.lines` | `label`, `unitPrice` (NumberType, `min=0`, `step=0.01`), `quantity` (IntegerType, `min=1`) |
| `LedgerEntryType` | `LedgerEntry` | `AccountingController::treasuryNew` | `date`, `type` (ChoiceType : income/expense), `label`, `amount` (`min=0.01`, `step=0.01`), `category` (facultatif) |

`AddAdminType`/`AddAccountantType`/`AddBinioufousType` n'existent pas : `AdminController::addAdminRole`/`addAccountantRole`/`addBinioufousRole` font un CSRF check direct sans passer par `$this->createForm()`, même pattern que `user_refuse`/`user_remove_role`/toutes les suppressions du gestionnaire de fichiers.

## À savoir en modifiant un formulaire "profil"

`AccountType` et `EditUserType` sont quasiment identiques (copier-coller, volontaire : un admin ne doit pas pouvoir changer l'identifiant de connexion de quelqu'un d'autre en éditant son profil). Si tu ajoutes un champ profil, il faut le dupliquer dans les deux (et potentiellement dans `RegistrationType` si le champ doit être saisi dès l'inscription, ce qui n'est plus le cas pour rien depuis "Inscription simplifiée").

## Upload de fichiers

Deux mécanismes distincts, ne pas confondre :

1. **Via un `FormType` classique** (photo de profil dans `RegistrationType`/`AccountType`/`EditUserType`, affiche d'événement dans `EventType`) : champ `FileType` avec `'mapped' => false`, récupéré dans le contrôleur via `$form->get('picture'|'poster')->getData()`, nom slugifié + `uniqid()` via `SluggerInterface`, `move()` vers un paramètre de `config/services.yaml` (`pictures_directory`, `event_posters`), puis `set...Filename()` sur l'entité. Fichiers servis depuis `public/uploads/`.
2. **Dépôt générique du gestionnaire de fichiers** (`/desk/files/{space}/documents`, `DocumentController::upload()`) : **pas** de `FormType`, requête `multipart/form-data` en JS (`assets/desk/quick-upload.js`), réponse JSON. Type MIME vérifié contre `Folder::ALLOWED_MIME_TYPES[$space]`, fichier déplacé vers `documents_directory`. C'est ce mécanisme qui gère désormais tout ce qui allait avant dans `TrackType`/`VoiceType` (mp3, PDF, images...).
