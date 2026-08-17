# Rôles utilisateurs

Référence à jour au 2026-08-17. Voir aussi [security.md](security.md) pour le détail technique de `access_control` et [controllers.md](controllers.md) pour les routes par contrôleur.

## Les 3 rôles

| Rôle | Description (`Role::$description` en base) | Comment on l'obtient |
|---|---|---|
| `ROLE_ADMIN` | Administrator | Jamais via l'inscription. Accordé manuellement par un admin (`AdminController::addAdminRole`, bouton "Faire administrateur·rice" sur `/admin/user/{slug}`). |
| `ROLE_COMPTA` | Accountant | Jamais via l'inscription. Accordé manuellement par un admin (`AdminController::addAccountantRole`, bouton "Faire comptable"). |
| `ROLE_BINIOUFOUS` | Binioufous | Jamais automatique. Accordé/retiré manuellement par un admin, soit via le bouton "Faire binioufous" sur `/admin/user/{slug}` (`addBinioufousRole`), soit via le toggle "Passer Membre"/"Retirer Membre" en un clic depuis les listes `/desk` (`AdminController::toggleMembership`). Débloque l'accès à l'espace fichiers Musique (lecture) et à son écriture, ainsi qu'à l'espace "Autre" en lecture. |

C'est tout : ce sont les **3 seuls** rôles qui existent dans le projet, chacun stocké en base (table `role`, relation `user<->role`) et retirable via la pastille "poubelle" de `/admin/user/{slug}`.

## Historique : les rôles retirés (2026-08-12)

Trois autres "rôles" existaient avant, tous retirés le même jour (audit complet des rôles) :

- **`ROLE_SIMPLE`** marquait "compte validé, sans `ROLE_BINIOUFOUS`" (un état déductible sans rôle dédié). Fusionné avec `ROLE_USER` (migration `Version20260812170000`) : `UserRepository::findSimples()` filtre `validation = true` en excluant `ROLE_BINIOUFOUS` directement, sans jointure sur un rôle explicite.
- **`ROLE_MEMBER`** (legacy, plus attribuable dans l'UI mais laissé en base "pour ne pas perdre de données") a été supprimé purement et simplement (migration `Version20260812180000`, cascade sur `role_user`) : plus aucun compte ne peut l'avoir.
- **`ROLE_USER`** était doublement présent : une chaîne ajoutée en dur par `User::getRoles()` à tout compte connecté, et une ligne `Role` fantôme en base jamais assignée. Les deux retirés : `/desk` n'exige plus qu'un rôle particulier mais simplement d'être connecté, via `IS_AUTHENTICATED_FULLY` dans `access_control`.

**Les rôles sont cumulatifs et indépendants**, pas un statut unique : un compte peut avoir `ROLE_ADMIN` + `ROLE_COMPTA` + `ROLE_BINIOUFOUS` en même temps. Il n'existe aucune hiérarchie ni exclusion en base ; la notion de "priorité" n'existe que dans l'affichage de certaines pages (voir plus bas).

## Qui a accès à quoi

Basé sur `config/packages/security.yaml` (`access_control`), plus quelques vérifications en dur documentées dans [controllers.md](controllers.md) (`FolderWriteVoter`, `NoteController::denyUnlessAuthor`, `TwoFactorController`).

| Zone / route | Accès requis | Notes |
|---|---|---|
| `/` , `/story`, `/story/mini`, `/schedule`, `/schedule/event/{id}.ics`, `/music` (lecture), `/contact` | public | Pages vitrines + setlist en lecture, aucune restriction. |
| `/join`, `/login`, `/logout`, `/register`, `/locale/{locale}` | public | Flux pré-connexion. |
| `/desk`, `/desk/profile`, `/desk/update-password`, `/desk/files` (hub) | `IS_AUTHENTICATED_FULLY` | Tout compte connecté, validé ou non, quel que soit son rôle. Le hub `/desk/files` est atteignable par n'importe quel compte connecté, mais n'affiche une carte que pour les espaces auxquels le compte a vraiment accès. |
| `/desk/files/music/*` (dossiers/documents/setlist) | `ROLE_BINIOUFOUS` ou `ROLE_ADMIN` | Lecture et écriture soumises à la même règle. |
| `/desk/files/admin/*` | `ROLE_ADMIN` | Lecture et écriture. |
| `/desk/files/accounting/*` (dossiers/documents + devis/factures/clients/trésorerie, `AccountingController`) | `ROLE_COMPTA` ou `ROLE_ADMIN` | Lecture et écriture. |
| `/desk/files/other/*` (lecture) | `ROLE_BINIOUFOUS` ou `ROLE_ADMIN` | **Écriture réservée `ROLE_ADMIN` seul** (`FolderWriteVoter`, `Folder::WRITE_ROLES`), 1er espace où lecture et écriture divergent. |
| `/desk/notes/*` | `ROLE_ADMIN` ou `ROLE_COMPTA` (lecture/liste) | Modifier/supprimer une note réservé à son auteur·ice, même si elle est partagée en lecture (`NoteController::denyUnlessAuthor`, vérifié en dur, pas dans `access_control`). |
| `/desk/profile/2fa/*` | `ROLE_ADMIN` (vérifié en dur en plus de `^/desk`) | Activation/désactivation de la 2FA TOTP, optionnelle côté compte. |
| `/admin/*` (validation des inscriptions, promotion/retrait de rôle, fiche utilisateur, planning `/admin/event/*`, contenu Histoire `/admin/story/*`) | `ROLE_ADMIN` | Une seule règle générique `^/admin`, pas de règle dédiée par sous-section. |
| `/music/new`, `/music/*/edit`, `/music/*/voice`, `/music/quick-upload`, `/music` en `DELETE` | `ROLE_ADMIN` (ou `ROLE_BINIOUFOUS` pour `quick-upload`) | **Règles mortes** : visaient l'ancien `TrackController`/`VoiceController`, supprimés le 2026-08-13. Plus aucune route ne matche ces chemins. Voir [security.md](security.md). |

## Comportement des comptes multi-rôles

Deux logiques différentes cohabitent, pas interchangeables :

- **Cumul (OR)**, utilisé pour `/desk/files/*` et le hub `/desk/files` : chaque espace/carte a sa propre condition `is_granted('ROLE_X') or is_granted('ROLE_ADMIN')`, évaluée indépendamment. Un compte avec plusieurs rôles voit simplement plusieurs cartes/espaces.
- **Priorité (une seule branche)**, utilisé sur `/desk` (`templates/desk/index.html.twig`) : un seul bloc de contenu s'affiche, celui du rôle le plus "haut" (`{% if is_granted('ROLE_ADMIN') %}...{% elseif is_granted('ROLE_BINIOUFOUS') %}...{% else %}...{% endif %}`). Le bloc admin contient déjà la liste des binioufous (entre autres), donc un compte admin+binioufous ne voit pas deux fois le contenu binioufous.

  **Bug corrigé le 2026-08-11** : avant, `/desk` bouclait sur `app.user.roles` et évaluait le if/elseif à *chaque* rôle du compte plutôt qu'une seule fois. Un compte admin+binioufous affichait donc "Les Binioufous" deux fois. Remplacé par une évaluation unique.

## Comment un rôle est attribué (flux détaillé)

1. **Inscription** (`/register`, `RegistrationType`) : juste pseudo/email/mot de passe. Le compte est créé avec `validation = false` et **aucun rôle**.
2. **Le compte apparaît en attente** sur `/admin/valid` (`UserRepository::findUnvalids()`), et le membre voit un bandeau "compte en cours de vérification" avec lien vers `/desk/profile` sur son `/desk` tant que `validation` reste `false` (le reste de `/desk` reste accessible : la connexion elle-même n'est jamais bloquée par la validation, cf. [security.md](security.md)).
3. **Un admin ouvre la fiche** (`/admin/{slug}/valid`) et clique **Valider** (`ValidRoleType`, formulaire sans aucun champ) : `AdminController::validUser()` passe uniquement `validation` à `true` et envoie un mail de confirmation. N'attribue plus aucun rôle : ça se décide après coup, indépendamment de cette validation (étape 5).
4. **Refuser une inscription en attente** : `AdminController::refuseUser()` supprime le compte.
5. **Toggle Membre/Pas membre** (boutons sur les listes `/desk`, indépendant de la validation) : `AdminController::toggleMembership()` ajoute ou retire `ROLE_BINIOUFOUS`.
6. **Promotion manuelle admin/comptable/binioufous** (`/admin/user/{slug}`, boutons visibles seulement pour les rôles que le compte n'a pas déjà) : ajoute le rôle directement, sans passer par la validation.
7. **Retirer un rôle** (bouton poubelle sur `/admin/user/{slug}`) : `AdminController::removeUserRole()`.

## Points d'attention

- **Les 5 règles `access_control` `^/music/*` sont mortes** (`/music/new`, `/music/[^/]+/edit`, `/music/[^/]+/voice`, `/music/quick-upload`, `/music` en `DELETE`) : visaient `TrackController`/`VoiceController`, supprimés le 2026-08-13. Ne cause aucun problème (aucune route derrière), mais à nettoyer dans `security.yaml` si une prochaine tâche touche à ce fichier. Voir [security.md](security.md).
- Le hub `/desk/files` est **atteignable** par tout compte connecté, y compris un compte qui n'a accès à aucun espace : la page s'affiche simplement sans aucune carte plutôt que de renvoyer un 403. Comportement volontaire, pas un bug.
- L'espace "Autre" (`/desk/files/other`) est le seul où lecture et écriture divergent (`ROLE_BINIOUFOUS`/`ROLE_ADMIN` en lecture, `ROLE_ADMIN` seul en écriture) : à garder en tête si un futur espace suit le même besoin, `FolderWriteVoter` généralise déjà le mécanisme.
