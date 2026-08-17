# Sécurité / authentification (`config/packages/security.yaml`)

- **Provider** : `in_database`, entité `App\Entity\User`, identifiant = `email`.
- **Mot de passe** : hasher `bcrypt` sur `App\Entity\User`.
- **Firewall `main`** : `form_login` (`login_path`/`check_path` pointent tous les deux vers la route `join`, donc `/join`), redirection par défaut vers `desk` après connexion. Logout sur `/logout` → redirige vers `home`.
- **Anti brute-force** (`login_throttling`) : 5 tentatives / 15 minutes, par IP+identifiant **et** par IP seule (empêche de contourner la 1re limite en variant l'email). Natif Symfony (security-http + rate-limiter), rien à configurer à la main dans `rate_limiter.yaml` pour celui-ci.
- **2FA** (`two_factor`, scheb/2fa-bundle + scheb/2fa-totp) : réservée aux comptes `ROLE_ADMIN` (accès le plus sensible : membres, finances), optionnelle côté compte (`User::isTotpAuthenticationEnabled()`, `null !== totpSecret`) : un admin nouvellement créé n'est pas bloqué tant qu'il n'a pas configuré son application d'authentification depuis `/desk/profile/2fa`. Chemins par défaut du bundle (`/2fa`, `/2fa_check`), CSRF activé, désactivée en environnement `dev`. Voir `TwoFactorController` dans [controllers.md](controllers.md).
- **`access_control`** (seule couche de contrôle d'accès basée sur les rôles pour la plupart des routes ; quelques contrôleurs vérifient en plus en dur, voir [controllers.md](controllers.md) pour lesquels), dans l'ordre (le premier qui matche gagne) :
  - `^/admin` → `ROLE_ADMIN`
  - `^/desk/files/music` → `ROLE_BINIOUFOUS` ou `ROLE_ADMIN`
  - `^/desk/files/admin` → `ROLE_ADMIN`
  - `^/desk/files/accounting` → `ROLE_COMPTA` ou `ROLE_ADMIN`
  - `^/desk/files/other` → `ROLE_BINIOUFOUS` ou `ROLE_ADMIN` (lecture ; l'écriture est réservée `ROLE_ADMIN` seul via `FolderWriteVoter`, 1er espace où lecture et écriture divergent)
  - `^/desk/notes` → `ROLE_ADMIN` ou `ROLE_COMPTA`
  - `^/desk` → `IS_AUTHENTICATED_FULLY` (couvre tout le reste de `/desk`, y compris le hub `/desk/files`). Attribut Symfony natif évalué sur le token d'authentification, pas un rôle : un compte sans aucun rôle métier (`getRoles()` vide) passe cette règle normalement, authentification et rôles sont deux notions distinctes pour l'`AuthorizationChecker`. Voir [role.md](role.md) "Historique : les rôles retirés".
  - `^/music/new`, `^/music/[^/]+/edit`, `^/music/[^/]+/voice`, `^/music/quick-upload`, `^/music` en méthode `DELETE` → `ROLE_ADMIN` (ou `ROLE_BINIOUFOUS` pour `quick-upload`)
  - Tout le reste (`/`, `/music` en `GET`, `/story`, `/schedule`, `/join`, `/login`, `/register`, `/contact`...) : public, aucune règle de rôle.

  ⚠️ **Les 5 règles `^/music/*` ci-dessus sont mortes** : elles visaient l'ancien `TrackController`/`VoiceController`, supprimés le 2026-08-13 lors de la fusion du gestionnaire de fichiers (cf. `CLAUDE.md` "Gestion de fichiers unifiée"). Aucune route ne matche plus ces chemins (`php bin/console debug:router` ne les liste pas) : ces lignes n'ont plus d'effet, ni positif ni négatif, mais n'ont pas été retirées de `security.yaml`. À nettoyer si une prochaine tâche touche à ce fichier.

  Détail métier complet (qui a accès à quoi, comment un rôle est obtenu, comportement des comptes multi-rôles) : [role.md](role.md).

## Upload / fichiers

- Paramètres définis dans `config/services.yaml` : `documents_directory` (`public/uploads/documents`, gestionnaire de fichiers `/desk/files/*`), `pictures_directory` (`public/uploads/pictures`, photos de profil), `event_posters` (`public/uploads/events`, affiches de planning). Voir [forms.md](forms.md).
- Types MIME restreints par espace (`Folder::ALLOWED_MIME_TYPES`), anti path-traversal (nom de fichier physique toujours slug + `uniqid()`, jamais le chemin fourni par l'utilisateur), exécution PHP bloquée dans `public/uploads` (`.htaccess`), vérifiée le 2026-08-13.

## Gotcha

- Pas de `Security\Voter` générique dans le projet, un seul cas dédié : `FolderWriteVoter` (attribut `FOLDER_WRITE`, sujet = le `space`), pour distinguer lecture (`access_control`) et écriture (`Folder::WRITE_ROLES`) sur `/desk/files/*`. Pour une nouvelle page réservée à un rôle donné ailleurs, ajouter une règle `access_control` (ou, à défaut, un `denyAccessUnlessGranted`/`isGranted` dans le contrôleur) : rien de générique ne le fait automatiquement.
- `access_control` gère l'accès aux **routes**, pas l'affichage : un lien/bouton vers une route réservée peut rester visible dans un template pour quelqu'un qui n'a pas le rôle (cliquer dessus renvoie un 403, mais rien ne le cache). À vérifier au cas par cas plutôt que de supposer que la visibilité d'un lien reflète les droits réels.
- Pas de `role_hierarchy` configuré : `ROLE_ADMIN` n'hérite **pas** automatiquement des permissions des autres rôles au niveau `access_control`, chaque règle doit lister explicitement `[ROLE_X, ROLE_ADMIN]` si un admin doit y avoir accès (c'est le cas pour toutes les règles `/desk/files/*` actuelles). Toujours vérifier au cas par cas plutôt que de supposer qu'un admin passe partout.
