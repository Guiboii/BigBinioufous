# Rôles utilisateurs

Référence à jour (2026-08-12, après suppression complète de `ROLE_USER` en plus de `ROLE_SIMPLE`/`ROLE_MEMBER`) de tous les rôles, de ce que chacun donne accès à faire, et de comment un compte peut cumuler plusieurs rôles. Voir aussi [security.md](security.md) pour le détail technique de `access_control`.

## Les 3 rôles

| Rôle | Description (`Role::$description` en base) | Comment on l'obtient |
|---|---|---|
| `ROLE_ADMIN` | Administrator | Jamais via l'inscription. Accordé manuellement par un admin (`AdminController::addAdminRole`, bouton "Faire administrateur·rice" sur `/admin/user/{slug}`). |
| `ROLE_COMPTA` | Accountant | Jamais via l'inscription. Accordé manuellement par un admin (`AdminController::addAccountantRole`, bouton "Faire comptable"). |
| `ROLE_BINIOUFOUS` | Binioufous | Jamais automatique. Accordé/retiré manuellement par un admin à tout moment, soit via le bouton "Faire binioufous" sur la fiche `/admin/user/{slug}` (`AdminController::addBinioufousRole`), soit via le toggle "Passer Membre"/"Retirer Membre" en un clic depuis les listes `/desk` (`AdminController::toggleMembership`, boutons dans `desk/lists/simples.html.twig`/`binioufous.html.twig`). Seul rôle avec une vraie différence fonctionnelle (accès aux partitions/voix). |

C'est tout : ce sont désormais les **3 seuls** rôles qui existent dans le projet, chacun réellement stocké en base (table `role`, relation `user<->role`) et réellement retirable via la pastille "poubelle" de `/admin/user/{slug}`.

## Historique : les rôles retirés

Trois autres "rôles" existaient avant le 2026-08-12, tous retirés le même jour (audit complet des rôles) :

- **`ROLE_SIMPLE`** marquait "compte validé, sans `ROLE_BINIOUFOUS`" (un état déductible sans rôle dédié). Fusionné avec `ROLE_USER` (migration `Version20260812170000`) : `UserRepository::findSimples()` filtre `validation = true` en excluant `ROLE_BINIOUFOUS` directement, sans jointure sur un rôle explicite.
- **`ROLE_MEMBER`** (legacy, plus attribuable dans l'UI depuis "Rôles simplifiés" mais laissé en base "pour ne pas perdre de données") a été supprimé purement et simplement (migration `Version20260812180000`, cascade sur `role_user`) : plus aucun compte ne peut l'avoir. `UserRepository::findMembers()` et sa branche dédiée dans `desk/index.html.twig` ont été retirés avec.
- **`ROLE_USER`** était doublement présent : (a) une chaîne ajoutée en dur par `User::getRoles()` à tout compte connecté (jamais stockée en base côté relation), et (b) une ligne `Role` fantôme en base (créée par les fixtures, jamais assignée à personne via `->addRole()`) qui ne servait qu'à afficher une pastille cosmétique sur les fiches admin. Les deux ont été retirés le 2026-08-12 : `/desk` (et tout ce qui en dépend) n'exige plus un rôle particulier mais simplement d'être connecté, via l'attribut Symfony natif `IS_AUTHENTICATED_FULLY` dans `access_control` (`security.yaml`), sans avoir besoin ni d'une ligne en base ni d'une injection de rôle en code. `User::getRoles()` retourne désormais **uniquement** les rôles réellement stockés (peut être un tableau vide pour un compte sans rôle métier, ce qui ne pose aucun problème à Symfony : authentification et rôles sont deux notions distinctes pour l'`AuthorizationChecker`).

**Les rôles sont cumulatifs et indépendants**, pas un statut unique : un compte peut avoir `ROLE_ADMIN` + `ROLE_COMPTA` + `ROLE_BINIOUFOUS` en même temps (cas du compte `admin@admin.com` des fixtures). Il n'existe aucune hiérarchie ni exclusion en base ; la notion de "priorité" n'existe que dans l'affichage de certaines pages (voir plus bas).

## Qui a accès à quoi

Basé sur `config/packages/security.yaml` (`access_control`, seule couche de contrôle d'accès : aucune vérification de rôle en dur dans les contrôleurs, cf. [security.md](security.md)).

| Zone / route | Accès requis | Notes |
|---|---|---|
| `/` , `/story`, `/story/mini`, `/schedule`, `/schedule/event/{id}.ics` | public | Pages vitrines, aucune restriction. |
| `/music`, `/music/{id}` (liste + fiche d'un morceau) | public | Catalogue en lecture, y compris pour un visiteur non connecté. |
| `/music/new` (créer un morceau) | `ROLE_ADMIN` | |
| `/music/{id}/edit` | `ROLE_ADMIN` | |
| `/music/{id}` en `DELETE` | `ROLE_ADMIN` | |
| `/music/{id}/voice` (ajouter/supprimer une voix sur un morceau) | `ROLE_ADMIN` | |
| `/music/quick-upload` (`TrackController::quickUpload`, route `track_quick_upload`) | `ROLE_BINIOUFOUS` **uniquement** (pas de `ou ROLE_ADMIN` sur cette règle précise, contrairement à `/desk/files/music`) | **Route morte en pratique** : aucun template ni fichier JS du projet n'appelle cette URL (vérifié par recherche globale), remplacée par le dépôt rapide générique de `/desk/files/music` (`document_upload`, section Gestionnaire de fichiers, Phase 7). À supprimer avec sa règle `access_control` si confirmé inutile, ou à corriger si un usage caché existe. Pas encore traité (trouvé lors de l'audit du 2026-08-12, hors périmètre de la demande initiale). |
| `/join`, `/login`, `/logout`, `/register`, `/locale/{locale}` | public | Flux pré-connexion. |
| `/desk`, `/desk/profile`, `/desk/update-password`, `/desk/files` (hub) | `IS_AUTHENTICATED_FULLY` | Donc tout compte connecté, validé ou non, quel que soit son rôle (voire sans aucun rôle métier). Le hub `/desk/files` est **atteignable** par n'importe quel compte connecté, mais n'affiche une carte que pour les espaces auxquels le compte a vraiment accès (peut donc s'afficher vide pour un compte sans `ROLE_BINIOUFOUS`/`ROLE_COMPTA`/`ROLE_ADMIN`). |
| `/desk/files/music/*` (espace Musique : dossiers, documents, morceaux/voix favorites) | `ROLE_BINIOUFOUS` ou `ROLE_ADMIN` | Lecture et écriture (upload/suppression/déplacement/création de dossier) soumises à la **même règle**. |
| `/desk/files/admin/*` (espace Administratif) | `ROLE_ADMIN` | |
| `/desk/files/accounting/*` (espace Comptabilité) | `ROLE_COMPTA` ou `ROLE_ADMIN` | |
| `/accountant` | `ROLE_COMPTA` | |
| `/admin/*` (validation des inscriptions, promotion/retrait de rôle, fiche utilisateur, planning `/admin/event/*`, contenu Histoire `/admin/story/*`) | `ROLE_ADMIN` | Toutes ces sous-routes sont couvertes par la seule règle générique `^/admin`, pas de règle dédiée par sous-section. |

## Comportement des comptes multi-rôles

Deux logiques différentes cohabitent dans le projet, pas interchangeables :

- **Cumul (OR)**, utilisé pour `/desk/files/*` et le hub `/desk/files` : chaque espace/carte a sa propre condition `is_granted('ROLE_X') or is_granted('ROLE_ADMIN')`, évaluée indépendamment. Un compte avec plusieurs rôles voit simplement plusieurs cartes/espaces, sans conflit possible.
- **Priorité (une seule branche)**, utilisé sur `/desk` (`templates/desk/index.html.twig`) : un seul bloc de contenu s'affiche, celui du rôle le plus "haut" (`{% if is_granted('ROLE_ADMIN') %}...{% elseif is_granted('ROLE_BINIOUFOUS') %}...{% else %}...{% endif %}`). Le bloc admin contient déjà la liste des binioufous (entre autres), donc un compte admin+binioufous ne voit **pas** deux fois le contenu binioufous : c'est la branche admin, plus complète, qui l'emporte. Le `{% else %}` final (contenu "bienvenue simple utilisateur·rice") couvre tout compte connecté qui ne matche aucune branche au-dessus, y compris un compte sans aucun rôle du tout.

  **Bug corrigé le 2026-08-11** : avant, ce même `/desk` bouclait sur `app.user.roles` (`{% for role in app.user.roles %}`) et évaluait le if/elseif à *chaque* rôle du compte plutôt qu'une seule fois pour le compte entier. Résultat : un compte admin+binioufous déclenchait la branche admin (qui inclut déjà la liste des binioufous) **et** la branche binioufous séparément, donc "Les Binioufous" s'affichait deux fois sur la page. Remplacé par une évaluation unique.

## Comment un rôle est attribué (flux détaillé)

1. **Inscription** (`/register`, `RegistrationType`) : depuis "Facilitons l'inscription" (2026-08-12), juste pseudo/email/mot de passe. Le compte est créé avec `validation = false` et **aucun rôle**. Plus de champ "souhait" (`wish`, retiré) : le rôle métier ne se décide plus du tout à l'inscription.
2. **Le compte apparaît en attente** sur `/admin/valid` (`UserRepository::findUnvalids()`, filtre `validation = false`, trié par date d'inscription), et le membre voit un bandeau "compte en cours de vérification" avec lien vers `/desk/profile` sur son `/desk` tant que `validation` reste `false` (le reste de `/desk` reste accessible : la connexion elle-même n'est jamais bloquée par la validation, cf. [security.md](security.md)).
3. **Un admin ouvre la fiche** (`/admin/{slug}/valid`, affiche instrument/pays/ville/date de naissance/date de création pour aider à la décision) et clique **Valider** (`ValidRoleType`, formulaire sans aucun champ) : `AdminController::validUser()` passe uniquement `validation` à `true` et envoie un mail de confirmation. **N'attribue plus aucun rôle** : le rôle Membre/Pas membre se décide après coup, indépendamment de cette validation, via le toggle sur les listes `/desk` (étape 5 ci-dessous).
4. **Refuser une inscription en attente** (`admin/unvalids.html.twig`, bouton "Refuser") : `AdminController::refuseUser()` supprime purement et simplement le compte (jamais validé, pas de rôle à retirer).
5. **Toggle Membre/Pas membre** (boutons dans `desk/lists/simples.html.twig`/`binioufous.html.twig`, indépendant de la validation) : `AdminController::toggleMembership()` ajoute ou retire `ROLE_BINIOUFOUS`, seule action désormais.
6. **Promotion manuelle admin/comptable/binioufous** (`/admin/user/{slug}`, boutons "Faire administrateur·rice"/"Faire comptable"/"Faire binioufous", visibles seulement pour les rôles que le compte n'a pas déjà) : ajoute le rôle directement, sans passer par la validation.
7. **Retirer un rôle** (pastille + bouton poubelle sur `/admin/user/{slug}`, sans exception désormais) : `AdminController::removeUserRole()`.

## Points d'attention

- **`/music/{id}` affiche un lien "modifier" et un formulaire de suppression à tout le monde**, y compris un visiteur anonyme (`templates/music/show.html.twig`), alors que les routes `track_edit`/`track_delete` derrière sont réservées à `ROLE_ADMIN`. Cliquer dessus sans le rôle renvoie un 403, mais rien dans le template ne les masque pour les autres visiteurs. Pas corrigé (repéré pendant l'audit du 2026-08-11), mais à garder en tête si une passe UI/UX est faite sur `/music`.
- **`/music/quick-upload` (route `track_quick_upload`) semble mort** : trouvé pendant l'audit du 2026-08-12, aucune référence dans les templates ni le JS. Sa règle `access_control` (`ROLE_BINIOUFOUS` seul, sans `ROLE_ADMIN`) est en plus incohérente avec la règle sœur `/desk/files/music` (`ROLE_BINIOUFOUS` **ou** `ROLE_ADMIN`) : si ce endpoint était un jour rebranché tel quel côté UI, un admin sans `ROLE_BINIOUFOUS` obtiendrait un 403 alors qu'il a accès à tout le reste de l'espace Musique. À trancher : supprimer (route + règle) si confirmé obsolète, ou aligner la règle sur `[ROLE_BINIOUFOUS, ROLE_ADMIN]` si on le garde. Pas corrigé (trouvé pendant l'audit, hors périmètre de la demande initiale).
- Le hub `/desk/files` (voir tableau plus haut) est **atteignable** par tout compte connecté, y compris un compte qui n'a accès à aucun espace : la page s'affiche simplement sans aucune carte plutôt que de renvoyer un 403. Comportement volontaire, pas un bug.
- `doc/controllers.md` et `doc/entities.md` contenaient encore, avant le 2026-08-12, des mentions du champ `wish` (retiré début-08-12 lors de "Facilitons l'inscription") et de la route `/admin/{wish}/{slug}/valid` (devenue `/admin/{slug}/valid`) : les mentions `ROLE_USER` y ont été corrigées au passage de cet audit, mais **le reste de leur contenu lié à `wish` n'a pas été revérifié en détail** (hors périmètre de la demande initiale, contrairement à `role.md`/`security.md`). À auditer de la même façon si une prochaine tâche touche à ces zones.
