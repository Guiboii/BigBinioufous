# Rôles utilisateurs

Référence à jour (2026-08-11) de tous les rôles, de ce que chacun donne accès à faire, et de comment un compte peut cumuler plusieurs rôles. Voir aussi [security.md](security.md) pour le détail technique de `access_control`.

## Les 6 rôles

| Rôle | Description (`Role::$description` en base) | Comment on l'obtient |
|---|---|---|
| `ROLE_ADMIN` | Administrator | Jamais via l'inscription. Accordé manuellement par un admin (`AdminController::addAdminRole`, bouton "Faire administrateur·rice" sur `/admin/user/{slug}`). |
| `ROLE_COMPTA` | Accountant | Jamais via l'inscription. Accordé manuellement par un admin (`AdminController::addAccountantRole`, bouton "Faire comptable"). |
| `ROLE_BINIOUFOUS` | Binioufous | Soit choisi comme souhait (`wish`) à l'inscription puis validé par un admin, soit accordé manuellement à tout moment par un admin (`AdminController::addBinioufousRole`, bouton "Faire binioufous" sur `/admin/user/{slug}`, indépendamment du souhait d'origine). |
| `ROLE_MEMBER` | Member | Choisi comme souhait à l'inscription et accordé à la validation par un admin. Peut aussi être accordé manuellement à tout moment par un admin (`AdminController::addMemberRole`, bouton "Faire membre" sur `/admin/user/{slug}`). |
| `ROLE_SIMPLE` | Simple | Choisi comme souhait à l'inscription et accordé à la validation. Peut aussi être accordé manuellement à tout moment par un admin (`AdminController::addSimpleRole`, bouton "Faire simple utilisateur·rice"). |
| `ROLE_USER` | User | Implicite : `User::getRoles()` l'ajoute toujours en dur, en plus des rôles réellement stockés en base. Jamais retirable (pas de bouton "poubelle" sur sa pastille, `admin/user/show.html.twig`). |

**Les rôles sont cumulatifs et indépendants**, pas un statut unique : un compte peut avoir `ROLE_ADMIN` + `ROLE_COMPTA` + `ROLE_BINIOUFOUS` en même temps (cas du compte `admin@admin.com` des fixtures). Il n'existe aucune hiérarchie ni exclusion en base ; la notion de "priorité" n'existe que dans l'affichage de certaines pages (voir plus bas).

## Qui a accès à quoi

Basé sur `config/packages/security.yaml` (`access_control`, seule couche de contrôle d'accès basée sur les rôles : aucune vérification de rôle en dur dans les contrôleurs, cf. [security.md](security.md)).

| Zone / route | Rôle(s) requis | Notes |
|---|---|---|
| `/` , `/story`, `/story/mini`, `/schedule`, `/schedule/event/{id}.ics` | public | Pages vitrines, aucune restriction. |
| `/music`, `/music/{id}` (liste + fiche d'un morceau) | public | Catalogue en lecture, y compris pour un visiteur non connecté. |
| `/music/new` (créer un morceau) | `ROLE_ADMIN` | |
| `/music/{id}/edit` | `ROLE_ADMIN` | |
| `/music/{id}` en `DELETE` | `ROLE_ADMIN` | |
| `/music/{id}/voice` (ajouter/supprimer une voix sur un morceau) | `ROLE_ADMIN` | |
| `/music/quick-upload` (dépôt rapide d'un mp3) | `ROLE_BINIOUFOUS` (ou `ROLE_ADMIN`) | |
| `/join`, `/login`, `/logout`, `/register`, `/locale/{locale}` | public | Flux pré-connexion. |
| `/desk`, `/desk/profile`, `/desk/update-password`, `/desk/files` (hub) | `ROLE_USER` | Donc tout compte connecté et validé ou non. Le hub `/desk/files` est **atteignable** par n'importe quel `ROLE_USER`, mais n'affiche une carte que pour les espaces auxquels le compte a vraiment accès (peut donc s'afficher vide pour un `ROLE_SIMPLE`/`ROLE_MEMBER` sans autre rôle). |
| `/desk/files/music/*` (espace Musique : dossiers, documents, morceaux/voix favorites) | `ROLE_BINIOUFOUS` ou `ROLE_ADMIN` | Lecture et écriture (upload/suppression/déplacement/création de dossier) soumises à la **même règle**. |
| `/desk/files/admin/*` (espace Administratif) | `ROLE_ADMIN` | |
| `/desk/files/accounting/*` (espace Comptabilité) | `ROLE_COMPTA` ou `ROLE_ADMIN` | |
| `/accountant` | `ROLE_COMPTA` | |
| `/admin/*` (validation des inscriptions, promotion de rôle, fiche utilisateur, planning `/admin/event/*`) | `ROLE_ADMIN` | |

## Comportement des comptes multi-rôles

Deux logiques différentes cohabitent dans le projet, pas interchangeables :

- **Cumul (OR)**, utilisé pour `/desk/files/*` et le hub `/desk/files` : chaque espace/carte a sa propre condition `is_granted('ROLE_X') or is_granted('ROLE_ADMIN')`, évaluée indépendamment. Un compte avec plusieurs rôles voit simplement plusieurs cartes/espaces, sans conflit possible.
- **Priorité (une seule branche)**, utilisé sur `/desk` (`templates/desk/index.html.twig`) : un seul bloc de contenu s'affiche, celui du rôle le plus "haut" dans l'ordre admin > binioufous > membre > simple (`{% if is_granted('ROLE_ADMIN') %}...{% elseif is_granted('ROLE_BINIOUFOUS') %}...{% endif %}`). Le bloc admin contient déjà la liste des binioufous (entre autres), donc un compte admin+binioufous ne voit **pas** deux fois le contenu binioufous : c'est la branche admin, plus complète, qui l'emporte.

  **Bug corrigé le 2026-08-11** : avant, ce même `/desk` bouclait sur `app.user.roles` (`{% for role in app.user.roles %}`) et évaluait le if/elseif à *chaque* rôle du compte plutôt qu'une seule fois pour le compte entier. Résultat : un compte admin+binioufous déclenchait la branche admin (qui inclut déjà la liste des binioufous) **et** la branche binioufous séparément, donc "Les Binioufous" s'affichait deux fois sur la page. Remplacé par une évaluation unique.

## Comment un rôle est attribué (flux détaillé)

1. **Inscription** (`/register`) : l'utilisateur·ice choisit un souhait (`User::$wish`), une des 3 valeurs `Binioufous`/`Member`/`Simple` (`RegistrationType`). Le compte est créé avec `validation = false` et **aucun rôle** (à part `ROLE_USER` implicite).
2. **Le compte apparaît en attente** sur `/admin/valid` (`UserRepository::findUnvalids()`, filtre `validation = false`), et le membre voit un message "en attente de validation" sur son `/desk` tant que `validation` reste `false`.
3. **Un admin ouvre la fiche** (`/admin/{wish}/{slug}/valid`, affiche souhait/instrument/pays/ville/date de naissance/date de création pour aider à la décision) et clique **Valider** : `AdminController::validUser()` attribue le rôle correspondant au souhait (`Role::findOneByDescription($wish)`) **et** passe `validation` à `true`, dans la même action.

   **Bug corrigé le 2026-08-11** : avant, une case à cocher "Valider l'utilisateur ?" (non cochée par défaut) ne contrôlait en réalité que `User::$validation`, jamais l'attribution du rôle elle-même (accordée inconditionnellement au submit du formulaire, quelle que soit la case). Un admin qui cliquait "Valider" sans avoir pensé à cocher la case obtenait donc un membre avec son rôle déjà actif, mais `validation` resté à `false` : "en attente de validation" affiché indéfiniment sur son `/desk` malgré un accès déjà complet, et toujours listé comme en attente sur `/admin/valid`. La case a été retirée (`ValidRoleType`) : un seul bouton "Valider" qui fait les deux choses ensemble, sans ambiguïté.
4. **Refuser une inscription en attente** (`admin/unvalids.html.twig`, bouton "Refuser") : `AdminController::refuseUser()` supprime purement et simplement le compte (jamais validé, pas de rôle à retirer).
5. **Promotion manuelle** (`/admin/user/{slug}`, boutons "Faire administrateur·rice"/"Faire comptable"/"Faire binioufous", visibles seulement pour les rôles que le compte n'a pas déjà) : ajoute le rôle directement, sans passer par le souhait d'origine ni changer `validation`.
6. **Retirer un rôle** (pastille + bouton poubelle sur `/admin/user/{slug}`, sauf `ROLE_USER` qui n'a pas de bouton) : `AdminController::removeUserRole()`.

## Points d'attention

- **`/music/{id}` affiche un lien "modifier" et un formulaire de suppression à tout le monde**, y compris un visiteur anonyme (`templates/music/show.html.twig`), alors que les routes `track_edit`/`track_delete` derrière sont réservées à `ROLE_ADMIN`. Cliquer dessus sans le rôle renvoie un 403, mais rien dans le template ne les masque pour les autres visiteurs. Pas corrigé (repéré pendant l'audit du 2026-08-11, hors périmètre de la demande initiale), mais à garder en tête si une passe UI/UX est faite sur `/music`.
- Le hub `/desk/files` (voir tableau plus haut) est **atteignable** par tout `ROLE_USER`, y compris un compte qui n'a accès à aucun espace : la page s'affiche simplement sans aucune carte plutôt que de renvoyer un 403. Comportement volontaire, pas un bug.
