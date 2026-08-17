# Modèle de données (entités Doctrine)

Toutes les entités sont dans `src/Entity/`, mappées avec des attributs PHP natifs Doctrine ORM (`#[ORM\Entity]`, `#[ORM\Column]`...).

Mise à jour 2026-08-17 : ce fichier décrivait encore `Track`/`Artist`/`Voice` (bibliothèque musicale à plat, sans dossiers) et le champ `wish` sur `User`, tous deux retirés le 2026-08-12/13. Réécrit pour refléter le modèle actuel : gestionnaire de fichiers unifié (`Folder`/`Document`), setlist (`SetlistItem`), comptabilité (`AccountingDocument`/`Client`/`LedgerEntry`), planning (`Event`), notes (`Note`), contenu Histoire (`StorySection`).

## `User`

Membre du site. Implémente `UserInterface` + `TwoFactorInterface` (scheb/2fa-bundle).

| Champ | Type | Notes |
|---|---|---|
| `nickname` | string | Seul champ d'identité requis à l'inscription, avec `email`/`hash` |
| `email` | string | Identifiant de connexion (`property: email` dans le provider) |
| `hash` | string | Mot de passe hashé en bcrypt |
| `passwordConfirm` | (non mappé) | Doit être égal à `hash`, formulaire d'inscription seulement |
| `firstName`, `lastName`, `city`, `country`, `gender`, `birth`, `picture`, `instrument`, `otherInstrumentDetail` | string/date/relation | Profil, tous facultatifs, complétés après coup sur `/desk/profile` (cf. "Inscription simplifiée", `CLAUDE.md`) |
| `slug` | string | Généré depuis prénom+nom (ou pseudo si pas de nom saisi) |
| `validation` | bool | Compte validé par un admin ou non. Ne bloque **pas** la connexion elle-même (juste un bandeau sur `/desk`), cf. [security.md](security.md) |
| `memberCardNumber` | string, nullable | Laissé en base pour ne pas perdre de données, plus utilisé dans aucun formulaire |
| `roles` | `Collection<Role>` | ManyToMany, côté inverse (`mappedBy: 'users'`) |
| `instrument` | `Instrument`, nullable | ManyToOne |
| `playedDocuments` | `Collection<Document>` | ManyToMany inverse (`mappedBy: 'playedBy'`) : parties/voix jouées par ce membre |
| `favoriteDocuments` | `Collection<Document>` | ManyToMany inverse (`mappedBy: 'favoritedBy'`) |
| `totpSecret` | string, nullable | Secret TOTP (2FA), `null` = 2FA désactivée pour ce compte |
| `createdAt` | datetime | Rempli automatiquement |

Points clés :
- `getRoles()` retourne uniquement les titres des `Role` réellement liés (pas de rôle injecté en dur) : un compte sans rôle métier renvoie un tableau vide, `/desk` n'exige qu'un compte authentifié (`IS_AUTHENTICATED_FULLY`). Voir [role.md](role.md).
- `isTotpAuthenticationEnabled()` (interface scheb/2fa) renvoie `null !== totpSecret` : la 2FA est optionnelle côté compte même pour un `ROLE_ADMIN`, tant qu'un secret n'a pas été enregistré depuis `/desk/profile/2fa`.
- `getPassword()` retourne `hash`, `getUserIdentifier()` retourne `email`.

## `Role`

Rôle applicatif (stocké en base). **3 seuls rôles existent** : `ROLE_ADMIN`, `ROLE_COMPTA`, `ROLE_BINIOUFOUS`. Voir [role.md](role.md) pour le détail des droits et l'historique des rôles retirés (`ROLE_MEMBER`, `ROLE_SIMPLE`, `ROLE_USER`).

| Champ | Type |
|---|---|
| `title` | string, ex. `ROLE_ADMIN` |
| `description` | string, ex. `Administrator` |
| `users` | `Collection<User>` : côté propriétaire de la relation ManyToMany |

## `Instrument`

Instrument de musique (Hautbois, Cor Anglais, Flûte, Clarinette, Tuba, Euphonium, Batterie, Cor, Autre : créés par les fixtures).

| Champ | Type |
|---|---|
| `title` | string |
| `users` | `Collection<User>` : OneToMany, `mappedBy: 'instrument'` |

## `Folder` / `Document` : gestionnaire de fichiers façon Drive

Fusion 2026-08-13 des anciens `Track`/`Voice` (mp3 à plat) et `Folder`/`Document` (déjà façon Drive) en un seul système, cf. `CLAUDE.md` "Gestion de fichiers unifiée". Un dossier racine par espace (`Folder::SPACES` = `music`, `admin`, `accounting`, `other`), créé à la volée (`FolderRepository::findOrCreateRoot`).

### `Folder`

| Champ | Type | Notes |
|---|---|---|
| `name` | string | |
| `space` | string | Une des 4 constantes `Folder::SPACE_*` |
| `parent` | `Folder`, nullable | ManyToOne, `null` pour une racine d'espace |
| `children` | `Collection<Folder>` | OneToMany, cascade + `orphanRemoval` |
| `documents` | `Collection<Document>` | OneToMany, cascade + `orphanRemoval` |
| `deletedAt` | datetime, nullable | Corbeille, **non récursive** : seul ce dossier est marqué, pas ses enfants (la restauration ramène tout l'arbre d'un coup). Voir `FolderRepository::hasDeletedAncestor()` |

Constantes notables : `Folder::WRITE_ROLES` (rôles d'écriture par espace, consommé par `FolderWriteVoter`), `Folder::ALLOWED_MIME_TYPES` (même liste large pour les 4 espaces : PDF, vidéo, image, audio, Office/LibreOffice, texte brut).

### `Document`

| Champ | Type | Notes |
|---|---|---|
| `name` | string | Nom d'origine du fichier (affiché) |
| `filename` | string | Nom physique sur disque (slug + `uniqid`), dans `documents_directory` |
| `mimeType` | string | Capturé à l'upload |
| `size` | int | Octets, capturé à l'upload (`$file->getSize()` avant `move()`) plutôt que recalculé à l'affichage : évite un accès disque par document, permet un tri SQL |
| `uploadedBy` | `User`, nullable | ManyToOne |
| `folder` | `Folder` | ManyToOne |
| `favoritedBy` | `Collection<User>` | ManyToMany, `JoinTable: document_user` |
| `playedBy` | `Collection<User>` | ManyToMany, `JoinTable: document_played_by` (nommée explicitement pour éviter la collision avec `document_user` ci-dessus) : "je joue cette partie", remplace l'ancien `Voice::$users` |
| `deletedAt` | datetime, nullable | Corbeille |
| `createdAt` | datetime | |

## `SetlistItem` / `Artist`

Setlist affichée publiquement sur `/music` (distincte de l'arborescence de fichiers `/desk/files/music`, gérée par `SetlistController`).

### `SetlistItem`

| Champ | Type | Notes |
|---|---|---|
| `title` | string | |
| `artist` | `Artist`, nullable | ManyToOne |
| `youtubeUrl` | string, nullable | Restreint côté contrôleur à `youtube.com`/`youtu.be` en `https` (`SetlistController::resolveYoutubeUrl()`, corrige une XSS stockée trouvée le 2026-08-17, cf. `CLAUDE.md`) |
| `position` | int | Ordre d'affichage, boutons monter/descendre (pas de drag-and-drop) |
| `folder` | `Folder`, nullable | Pointe vers un dossier de 1er niveau de l'espace `music` portant les fichiers audio réels. Nullable : un morceau "juste un titre" est un état normal, pas incomplet |

### `Artist`

| Champ | Type |
|---|---|
| `name` | string |
| `songs` | `Collection<SetlistItem>` : OneToMany, `mappedBy: 'artist'` |

## Comptabilité : `AccountingDocument` / `AccountingDocumentLine` / `Client` / `LedgerEntry`

Espace `/desk/files/accounting` (`ROLE_COMPTA`/`ROLE_ADMIN`), géré par `AccountingController` en plus du gestionnaire de fichiers générique pour cet espace.

### `AccountingDocument`

Devis ou facture. Pas de génération PDF serveur : impression navigateur côté client (`assets/accounting/document-print.css`).

| Champ | Type | Notes |
|---|---|---|
| `type` | string | `AccountingDocument::TYPE_QUOTE` (`quote`) ou `TYPE_INVOICE` (`invoice`) |
| `number` | string | Attribué à la création (`AccountingDocumentRepository::findNextNumber()`), jamais modifiable après |
| `date` | date | |
| `client` | `Client`, nullable | ManyToOne, préremplit `clientName`/`clientAddress`/`clientContact` côté JS (`assets/accounting/client-autofill.js`) |
| `clientName`, `clientAddress`, `clientContact` | string | Champs réellement soumis (pas recalculés depuis `client` côté serveur) |
| `correspondentName`, `correspondentEmail`, `correspondentPhone` | string | Contact côté Binioufous, préremplis avec l'utilisateur courant à la création |
| `createdBy` | `User`, nullable | ManyToOne |
| `sourceQuote` | `AccountingDocument`, nullable | Le devis d'origine, pour une facture créée "à partir d'un devis" |
| `invoices` | `Collection<AccountingDocument>` | Factures créées à partir de ce devis (côté inverse de `sourceQuote`) |
| `excludedFromInvoicing` | bool | Retire un devis du sélecteur "facturer ce devis" sans le supprimer |
| `lines` | `Collection<AccountingDocumentLine>` | OneToMany, cascade |

### `AccountingDocumentLine`

| Champ | Type |
|---|---|
| `document` | `AccountingDocument` : ManyToOne |
| `label` | string |
| `unitPrice` | decimal |
| `quantity` | int, défaut 1 |
| `position` | int, défaut 0 : recalculée à chaque sauvegarde (`AccountingController::reorderLines()`), pas de tri JS séparé |

### `Client`

| Champ | Type |
|---|---|
| `name` | string |
| `address`, `contact` | string, facultatifs |

### `LedgerEntry`

Journal de trésorerie (indépendant des devis/factures).

| Champ | Type | Notes |
|---|---|---|
| `date` | date | |
| `type` | string | `LedgerEntry::TYPE_INCOME` (`income`) ou `TYPE_EXPENSE` (`expense`) |
| `label` | string | |
| `amount` | decimal | |
| `category` | string, nullable | |
| `relatedDocument` | `AccountingDocument`, nullable | Rattachement optionnel à un devis/facture |
| `createdBy` | `User`, nullable | |

## `Event`

Date du planning (`/schedule`, public ; CRUD sous `/admin/event`, `ROLE_ADMIN`).

| Champ | Type | Notes |
|---|---|---|
| `title`, `location` | string | |
| `type` | string | `rehearsal`/`concert`/`other` (choix libre, pas de constantes dédiées) |
| `date` | datetime | |
| `endDate` | datetime, nullable | Heure de fin facultative |
| `description` | text, nullable | |
| `posterFilename` | string, nullable | Affiche (jpeg/png, 5 Mo max), dans `event_posters` |

Export `.ics` par événement : `GET /schedule/event/{id}.ics`.

## `Note`

Prise de notes du bureau/conseil (`/desk/notes`, `ROLE_ADMIN`/`ROLE_COMPTA`). Non collaboratif : seul l'auteur·ice peut modifier/supprimer sa note, même partagée.

| Champ | Type | Notes |
|---|---|---|
| `title` | string | |
| `content` | text | Markdown, édité via EasyMDE (`assets/desk/note-admin.js`) |
| `author` | `User` | ManyToOne |
| `shared` | bool, défaut `false` | Visible par les autres `ROLE_ADMIN`/`ROLE_COMPTA` en lecture seule si `true`, sinon privée à l'auteur·ice |
| `createdAt`, `updatedAt` | datetime | |

## `StorySection`

Section de contenu éditorial de la page Histoire (`/story`), CRUD sous `/admin/story` (`ROLE_ADMIN`).

| Champ | Type | Notes |
|---|---|---|
| `title` | string | |
| `slug` | string | Généré à la création (`Cocur\Slugify`), unique, jamais recalculé après (permet de lier une URL stable) |
| `content` | text | Markdown, édité via EasyMDE (`assets/desk/story-admin.js`) |
| `position` | int, défaut 0 | Ordre d'affichage, boutons monter/descendre |

## `PasswordUpdate`

DTO de formulaire (non mappé Doctrine) pour le changement de mot de passe.

| Champ | Contrainte |
|---|---|
| `oldPassword` | Vérifié via `password_verify` contre `User::$hash` |
| `newPassword` | |
| `confirmPassword` | |

⚠️ Bug existant (`LoginController::updatePassword`) : si `password_verify` échoue, le bloc `if` correspondant est vide, aucun message d'erreur affiché, retombe silencieusement sur le formulaire. Voir [controllers.md](controllers.md).

## Schéma des relations (simplifié)

```
User ──ManyToOne──> Instrument
User ──ManyToMany──> Role
User ──ManyToMany──> Document (playedDocuments / favoriteDocuments)

Folder ──OneToMany──> Folder (children)
Folder ──OneToMany──> Document
SetlistItem ──ManyToOne──> Artist
SetlistItem ──ManyToOne──> Folder (dossier de fichiers du morceau, optionnel)

AccountingDocument ──OneToMany──> AccountingDocumentLine
AccountingDocument ──ManyToOne──> Client (optionnel)
AccountingDocument ──ManyToOne──> AccountingDocument (sourceQuote)
LedgerEntry ──ManyToOne──> AccountingDocument (optionnel)

Note ──ManyToOne──> User (author)
```

## Fixtures (`src/DataFixtures/AppFixtures.php`)

Génère un jeu de données de démo (Faker, locale `FR-fr`) : 3 rôles, 9 instruments, 1 super-admin (`guibrouille@gmail.com` / `password`) + 1 admin rapide de test, 20 comptes en attente de validation, 10 comptes "simples" déjà validés (tous mot de passe `password`), 5 artistes et 10 morceaux de setlist (sans fichier audio ni dossier lié : un titre seul est un état normal), le planning complet de la saison 2026-2027 communiqué par l'utilisatrice.
