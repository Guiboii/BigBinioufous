# Contrôleurs & routes

Toutes les routes sont déclarées en attributs PHP natifs `#[Route]` directement sur les méthodes (pas de fichier `routes.yaml` central). Sauf mention contraire ("écriture vérifiée en dur"), aucun contrôleur ne vérifie de rôle en dur : le contrôle d'accès passe par `access_control` dans `config/packages/security.yaml`. Voir [security.md](security.md) et [role.md](role.md) pour la vue d'ensemble par rôle plutôt que par contrôleur.

Mise à jour 2026-08-17 : ce fichier décrivait encore `TrackController`/`VoiceController`/`AccountantController` (retirés lors de la fusion du gestionnaire de fichiers, 2026-08-13) et la route `/admin/{wish}/{slug}/valid` (le paramètre `wish` a disparu avec le champ du même nom). Réécrit contrôleur par contrôleur contre l'état réel du code et `php bin/console debug:router`.

## `HomeController`, `StoryController`, `ScheduleController`, `MusicController`
- `GET /` (`home`), `GET /story` (`story`), `GET /story/mini` (`minisite`), `GET /schedule` (`schedule`), `GET /schedule/event/{id}.ics` (`event_ics`), `GET /music` (`music`) : pages vitrines, publiques. `MusicController::index()` affiche la setlist (`SetlistItem`) et, pour qui a accès à l'espace musique, un lien vers `/desk/files/music` plutôt qu'un 2e rendu de l'arbre.

## `LoginController`
- `GET /join` (`join`) : page de connexion/inscription. `login_path`/`check_path` de `security.yaml` pointent tous les deux ici.
- `GET /login` (`login`) : redirige vers `/join`, route legacy jamais utilisée par le vrai flux de connexion.
- `/logout` (`logout`) : géré entièrement par le firewall Symfony.
- `/register` (`register`) : inscription simplifiée (`RegistrationType` : pseudo/email/mot de passe seulement). Compte créé avec `validation = false`, jamais auto-validé. Envoie un mail (squelette, `MAILER_DSN` non configuré) aux admins et un accusé de réception à l'inscrit·e.
- `/desk/profile` (`profile`) : édition du profil connecté (`AccountType`), upload photo (jpeg). `IS_AUTHENTICATED_FULLY` (`^/desk`).
- `/desk/update-password` (`update-password`) : changement de mot de passe (`PasswordUpdateType`), vérifie l'ancien hash avec `password_verify`. `IS_AUTHENTICATED_FULLY`.

⚠️ Bug existant : dans `updatePassword`, si `password_verify` échoue (mauvais ancien mot de passe), le bloc `if` est **vide** : aucun message d'erreur affiché, retombe silencieusement sur le formulaire.

## `TwoFactorController` (préfixe `/desk/profile/2fa`, `ROLE_ADMIN` vérifié en dur par `denyAccessUnlessGranted` en plus de `access_control`)
- `GET ''` (`two_factor_setup`) : affiche le QR code TOTP à scanner (secret généré à la volée, gardé en session tant qu'il n'est pas confirmé) ou un état "déjà activée".
- `POST /enable` (`two_factor_enable`) : vérifie le code saisi contre le secret en session ; seulement si valide, le secret est persisté sur `User::$totpSecret`.
- `POST /disable` (`two_factor_disable`) : efface `User::$totpSecret`.

Routes du bundle scheb/2fa (pas dans ce contrôleur) : `ANY /2fa` (`2fa_login`), `ANY /2fa_check` (`2fa_login_check`), déclenchées automatiquement par le firewall pour tout compte `ROLE_ADMIN` ayant une 2FA active, désactivées en environnement `dev`.

## `DeskController`
- `GET /desk` (`desk`) : tableau de bord, contenu différent selon le rôle le plus "haut" du compte (cf. [role.md](role.md)). `IS_AUTHENTICATED_FULLY`.
- `GET /desk/files` (`desk_files_hub`) : hub vers les espaces de fichiers accessibles (cartes affichées/masquées en Twig via `is_granted`, l'accès réel reste vérifié par `access_control` sur chaque espace).
- `GET /desk/files/{space}` (`desk_files`, `space` = `music|admin|accounting|other`) : navigateur de fichiers façon Drive (dossiers + documents), avec recherche récursive (`?q=`), tri (`?sort=`/`?dir=`), déplacement "clic à clic" (`?move_document=`/`?move_folder=`) et déplacement groupé (`?bulk_move=1`).
- `GET /desk/files/{space}/trash` (`desk_files_trash`) : corbeille de l'espace, liste à plat.
- `POST /desk/files/{space}/trash/empty` (`desk_files_trash_empty`) : vide la corbeille (suppression définitive, écriture vérifiée en dur : `FolderWriteVoter`).

## `FolderController` (préfixe `/desk/files/{space}/folders`, écriture vérifiée en dur : `denyAccessUnlessGranted(FolderWriteVoter::WRITE, $space)` sur chaque action)
- `POST ''` (`folder_create`) : crée un sous-dossier vide, refuse un doublon de nom dans le même parent.
- `DELETE /{id}` (`folder_delete`) : corbeille, **non récursive** (cf. `Folder::$deletedAt`, [entities.md](entities.md)).
- `POST /{id}/restore` (`folder_restore`) : sort de la corbeille ; si un ancêtre est toujours supprimé, repart à la racine de l'espace.
- `DELETE /{id}/purge` (`folder_purge`) : suppression définitive, récursive, seul endroit (avec `document_purge`) qui `unlink()` un fichier physique.
- `POST /{id}/move` (`folder_move`) : refuse un déplacement dans lui-même ou un de ses descendants (`FolderRepository::isSelfOrDescendantOf`).

## `DocumentController` (préfixe `/desk/files/{space}/documents`, écriture vérifiée en dur sauf mention contraire)
- `POST ''` (`document_upload`) : dépôt (glisser-déposer ou clic), JSON. Vérifie `$file->isValid()` avant tout (fichier au-delà de `upload_max_filesize` sinon), type MIME (`Folder::ALLOWED_MIME_TYPES[$space]`), puis déplace vers `documents_directory`. Un champ `path` optionnel reconstitue l'arborescence d'un dossier glissé-déposé entier.
- `DELETE /{id}` (`document_delete`), `POST /{id}/restore` (`document_restore`), `DELETE /{id}/purge` (`document_purge`), `POST /{id}/move` (`document_move`) : mêmes principes que `FolderController` ci-dessus.
- `POST /{id}/favorite` (`document_favorite_toggle`) : **pas** de `FolderWriteVoter`, juste `access_control` (lecture) : préférence personnelle, pas une écriture sur le fichier.
- `POST /{id}/played` (`document_played_toggle`) : idem, "je joue cette partie" (`Document::$playedBy`). Utilisé depuis `/music`, redirige vers le `referer`.

## `BulkActionController` (préfixe `/desk/files/{space}/bulk`, écriture vérifiée en dur)
- `POST /delete` (`bulk_delete`), `POST /move` (`bulk_move`) : appliquent en boucle la même logique que `FolderController`/`DocumentController` à une sélection multiple (`folder_ids[]`/`document_ids[]`), sans dupliquer leurs routes individuelles.

## `SetlistController` (préfixe `/desk/files/music/setlist`, écriture vérifiée en dur : toujours espace `music`)
- `POST ''` (`setlist_new`), `POST /{id}` (`setlist_edit`), `DELETE /{id}` (`setlist_delete`) : CRUD d'un morceau de la setlist publique. `youtubeUrl` restreint à `youtube.com`/`youtu.be` en `https` côté serveur (`resolveYoutubeUrl()`). Le dossier lié (`folder`) doit déjà exister dans `/desk/files/music` (créé à l'avance), pas de création à la volée par texte libre.
- `POST /{id}/move-up` (`setlist_move_up`), `POST /{id}/move-down` (`setlist_move_down`) : réordonnancement par échange de position avec le voisin.

## `AdminController` (tout sous `ROLE_ADMIN` via `^/admin`)
- `GET /admin/valid` (`valid`) : liste des inscriptions en attente (`validation = false`).
- `ANY /admin/{slug}/valid` (`user_valid`) : valide un utilisateur (`ValidRoleType`, formulaire **vide**, un seul bouton) : passe `validation` à `true` et envoie un mail. N'attribue plus aucun rôle (le rôle se décide séparément, cf. `toggleMembership` ci-dessous).
- `DELETE /admin/user/{slug}/refuse` (`user_refuse`) : refuse une inscription en attente = supprime le compte.
- `POST /admin/setadmin/{slug}` (`create_admin`), `POST /admin/setaccountant/{slug}` (`create_accountant`), `POST /admin/setbinioufous/{slug}` (`create_binioufous`) : promotion manuelle en un clic (CSRF direct, pas de `FormType`), indépendante de la validation.
- `POST /admin/user/{slug}/toggle-membership` (`user_toggle_membership`) : bascule `ROLE_BINIOUFOUS` en un clic depuis les listes `/desk`, indépendant de la validation du compte.
- `ANY /admin/user/{slug}` (`user_show`) : fiche + édition d'un utilisateur (`EditUserType`, mêmes champs qu'`AccountType` sauf pseudo/email), affiche les boutons de promotion pour les rôles manquants.
- `DELETE /admin/user/{slug}/role/{roleId}` (`user_remove_role`) : retire un rôle.

## `EventController` (préfixe `/admin/event`, `ROLE_ADMIN` via `^/admin`)
- `GET /` (`event_index`), `GET|POST /new` (`event_new`), `GET|POST /{id}/edit` (`event_edit`), `DELETE /{id}` (`event_delete`) : CRUD du planning affiché publiquement sur `/schedule`, upload d'affiche optionnel (`event_posters`).

## `StorySectionController` (préfixe `/admin/story`, `ROLE_ADMIN` via `^/admin`)
- `GET /` (`story_section_index`), `GET|POST /new` (`story_section_new`), `GET /{id}` (`story_section_show`, lecture seule rendue), `GET|POST /{id}/edit` (`story_section_edit`), `DELETE /{id}` (`story_section_delete`), `POST /{id}/move-up`/`/{id}/move-down` (`story_section_move_up`/`_down`) : CRUD du contenu éditorial de `/story`, slug généré une fois à la création (`Cocur\Slugify`, jamais recalculé).

## `NoteController` (préfixe `/desk/notes`, `ROLE_ADMIN`/`ROLE_COMPTA` via `^/desk/notes`)
- `GET /` (`note_index`) : notes visibles pour l'utilisateur (les siennes + celles partagées par d'autres).
- `GET|POST /new` (`note_new`) : création, auteur = utilisateur courant.
- `GET /{id}` (`note_show`) : 403 si ni partagée ni auteur.
- `GET|POST /{id}/edit` (`note_edit`), `DELETE /{id}` (`note_delete`) : **écriture vérifiée en dur**, réservée à l'auteur·ice même si la note est partagée (`denyUnlessAuthor()`) : `access_control` ne suffit pas ici, une note partagée reste en lecture seule pour les autres.

## `AccountingController` (préfixe `/desk/files/accounting`, `ROLE_COMPTA`/`ROLE_ADMIN` via `^/desk/files/accounting`)
- Devis/factures : `GET /documents` (`accounting_documents_index`), `GET|POST /documents/new/{type}` (`accounting_document_new`, `type` = `quote|invoice`), `GET /documents/new/invoice/choose-quote` (`accounting_invoice_choose_quote`), `POST /documents/{id}/toggle-invoicing` (`accounting_document_toggle_invoicing`), `GET|POST /documents/{quote}/invoice` (`accounting_document_new_from_quote`, facture depuis un devis existant), `GET|POST /documents/{id}/edit` (`accounting_document_edit`), `GET /documents/{id}` (`accounting_document_show`, page imprimable côté client), `DELETE /documents/{id}` (`accounting_document_delete`).
- Clients : `GET /clients` (`accounting_clients_index`), `GET|POST /clients/new` (`accounting_client_new`), `GET|POST /clients/{id}/edit` (`accounting_client_edit`), `DELETE /clients/{id}` (`accounting_client_delete`).
- Trésorerie : `GET /treasury` (`accounting_treasury_index`, avec solde), `GET|POST /treasury/new` (`accounting_treasury_new`), `DELETE /treasury/{id}` (`accounting_treasury_delete`).

## `ContactController`
- `POST /contact` (`contact_submit`) : formulaire de contact public, JSON. Anti-spam à 3 niveaux : CSRF, honeypot + piège temporel (délai minimum entre affichage et soumission, faux succès renvoyé sans email si détecté), rate limiting (1/min/IP, `RateLimiterFactoryInterface`). `MAILER_DSN` non configuré : le circuit est actif, mais rien ne part réellement pour l'instant.

## `LocaleController`
- `GET /locale/{locale}` (`app_locale`, `locale` restreint à `fr|en|br`) : stocke la langue en session, redirige vers le `referer` **seulement s'il pointe vers ce même site** (sinon vers `home`) : protection anti-redirection-ouverte.
