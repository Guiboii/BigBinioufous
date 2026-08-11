# Sécurité / authentification (`config/packages/security.yaml`)

- **Provider** : `in_database`, entité `App\Entity\User`, identifiant = `email`.
- **Mot de passe** : encodeur `bcrypt` sur `App\Entity\User`.
- **Firewall `main`** : `form_login` (`login_path`/`check_path` pointent tous les deux vers la route `join`, donc `/join`), redirection par défaut vers `desk` après connexion. Logout sur `/logout` → redirige vers `home`. Pas de clé `anonymous` (obsolète, retirée lors de l'upgrade Symfony 6.4, cf. `ROADMAP.md` Phase 2).
- **`access_control`** (seule couche de contrôle d'accès basée sur les rôles, aucune vérification en dur dans les contrôleurs : voir [controllers.md](controllers.md)), dans l'ordre (le premier qui matche gagne) :
  - `^/admin` → `ROLE_ADMIN`
  - `^/accountant` → `ROLE_COMPTA`
  - `^/desk/files/music` → `ROLE_BINIOUFOUS` ou `ROLE_ADMIN`
  - `^/desk/files/admin` → `ROLE_ADMIN`
  - `^/desk/files/accounting` → `ROLE_COMPTA` ou `ROLE_ADMIN`
  - `^/desk` → `ROLE_USER` (couvre tout le reste de `/desk`, y compris le hub `/desk/files`)
  - `^/music/new`, `^/music/[^/]+/edit`, `^/music/[^/]+/voice` → `ROLE_ADMIN`
  - `^/music/quick-upload` → `ROLE_BINIOUFOUS`
  - `^/music` en méthode `DELETE` → `ROLE_ADMIN`
  - Tout le reste (`/`, `/music` en `GET`, `/story`, `/schedule`, `/join`, `/login`, `/register`...) : public, aucune règle de rôle.

  Détail métier complet (qui a accès à quoi, comment un rôle est obtenu, comportement des comptes multi-rôles) : [role.md](role.md).

## Upload / fichiers

- Paramètres définis dans `config/services.yaml` : `mp3` (`public/uploads/music`), `voices_directory` (`public/uploads/music/voices`), `documents_directory` (`public/uploads/documents`, gestionnaire de fichiers `/desk/files/*`), `pictures_directory` (`public/uploads/pictures`), `event_posters` (`public/uploads/events`). Voir [forms.md](forms.md).

## Gotcha

- Pas de vote/voter Symfony custom (`Security\Voter`) dans le projet : tout le contrôle d'accès par rôle passe par `access_control`. Pour une nouvelle page réservée à un rôle donné, ajouter une règle `access_control` (ou, à défaut, un `denyAccessUnlessGranted`/`isGranted` dans le contrôleur) : rien de générique ne le fait automatiquement.
- `access_control` gère l'accès aux **routes**, pas l'affichage : un lien/bouton vers une route réservée peut très bien rester visible dans un template pour quelqu'un qui n'a pas le rôle (cliquer dessus renvoie un 403, mais rien ne le cache). Au moins un cas connu actuellement : `/music/{id}` affiche le lien "modifier" à tout le monde alors que `track_edit` est réservé à `ROLE_ADMIN` (détail dans [role.md](role.md), section "Points d'attention"). À vérifier au cas par cas plutôt que de supposer que la visibilité d'un lien reflète les droits réels.
