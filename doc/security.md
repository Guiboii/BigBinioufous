# Sécurité / authentification (`config/packages/security.yaml`)

- **Provider** : `in_database`, entité `App\Entity\User`, identifiant = `email`.
- **Mot de passe** : encodeur `bcrypt` sur `App\Entity\User`.
- **Firewall `main`** : `form_login` (`login_path`/`check_path` pointent tous les deux vers la route `join`, donc `/join`), redirection par défaut vers `desk` après connexion. Logout sur `/logout` → redirige vers `home`. Pas de clé `anonymous` (obsolète, retirée lors de l'upgrade Symfony 6.4, cf. `ROADMAP.md` Phase 2).
- **`access_control`** (seule couche de contrôle d'accès basée sur les rôles, aucune vérification en dur dans les contrôleurs : voir [controllers.md](controllers.md)) :
  - `^/admin` → `ROLE_ADMIN`
  - `^/desk` → `ROLE_USER`
  - Tout le reste (`/`, `/music/*`, `/accountant`, `/story`, `/schedule`...) n'est protégé par **aucune règle de rôle** ; `/music` et `/accountant` sont accessibles à n'importe quel utilisateur connecté (et même anonyme s'il n'y a pas de vérif dans le contrôleur : à vérifier au cas par cas).

## Comment un rôle est obtenu

Voir [role.md](role.md) pour le détail métier. En résumé technique :
- `User::getRoles()` (contrat Symfony `UserInterface`) retourne les titres des `Role` liés en base **+ `ROLE_USER` toujours ajouté en dur**.
- `ROLE_ADMIN`/`ROLE_COMPTA` : attribués manuellement par un admin (`/admin/setadmin/{slug}`, `/admin/setaccountant/{slug}`).
- `ROLE_BINIOUFOUS`/`ROLE_MEMBER`/`ROLE_SIMPLE` : attribués automatiquement lors de la validation de l'inscription (`/admin/{wish}/{slug}/valid`), en cherchant le `Role` dont la `description` correspond au `wish` choisi à l'inscription.

## Upload / fichiers

- `pictures_directory` (`public/uploads/pictures`) et `mp3` (`public/uploads/music`) sont définis comme paramètres dans `config/services.yaml` : utilisés par `LoginController` (photo profil) et `TrackController` (morceaux). Voir [forms.md](forms.md).

## Gotcha

- Pas de vote/voter Symfony custom (`Security\Voter`) dans le projet : tout le contrôle d'accès par rôle passe par `access_control`. Si tu ajoutes une page qui doit être réservée à `ROLE_COMPTA` ou `ROLE_BINIOUFOUS` par exemple, il n'existe **aucun mécanisme existant** pour ça : il faudra soit ajouter une règle `access_control`, soit un `denyAccessUnlessGranted` dans le contrôleur.
