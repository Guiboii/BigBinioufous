# Modèle de données (entités Doctrine)

Toutes les entités sont dans `src/Entity/`, mappées avec des attributs PHP natifs Doctrine ORM (`#[ORM\Entity]`, `#[ORM\Column]`...). Migré depuis les annotations docblock lors de l'upgrade Symfony 6.4 (cf. `ROADMAP.md` Phase 2).

## `User`

Membre du site. Implémente `UserInterface` (Symfony Security).

| Champ | Type | Notes |
|---|---|---|
| `firstName`, `lastName` | string | Requis (`NotBlank`) |
| `email` | string | Identifiant de connexion (`property: email` dans le provider), format validé |
| `hash` | string | Mot de passe hashé en bcrypt |
| `passwordConfirm` | (aucun) | Champ non persisté, doit être égal à `hash` (formulaire d'inscription) |
| `nickname`, `city`, `country`, `gender`, `birth`, `picture` | string/date | Profil |
| `slug` | string | Généré automatiquement depuis prénom+nom (`initializeSlug`, `@PrePersist`/`@PreUpdate`) |
| `validation` | bool | Compte validé par un admin ou non |
| `wish` | string | Rôle souhaité à l'inscription. Seulement 3 valeurs possibles en pratique (`RegistrationType`) : `Binioufous`, `Member`, `Simple`. `Administrator`/`Accountant` ne sont jamais un `wish` : ces 2 rôles s'obtiennent uniquement par promotion manuelle d'un admin, cf. [role.md](role.md) |
| `instrument` | `Instrument` | Relation ManyToOne |
| `roles` | `Collection<Role>` | Relation ManyToMany (côté inverse, `mappedBy="users"`) |
| `createdAt` | datetime | Rempli automatiquement (`@PrePersist`) |

Points clés :
- `getRoles()` retourne uniquement les titres des `Role` réellement liés (plus de `ROLE_USER` ajouté en dur depuis le 2026-08-12) : un compte sans rôle métier renvoie un tableau vide, `/desk` n'exige plus qu'un compte authentifié (`IS_AUTHENTICATED_FULLY` côté `access_control`, pas un rôle). Voir [role.md](role.md).
- `getPassword()` retourne `hash`, `getUsername()` retourne `email` (contrat `UserInterface` de Symfony 5).
- `getSalt()` et `eraseCredentials()` sont vides (bcrypt gère le salage, pas de credential en clair à effacer).

## `Role`

Rôle applicatif, indépendant des constantes `ROLE_*` de Symfony (stocké en base).

| Champ | Type |
|---|---|
| `title` | string : ex. `ROLE_ADMIN` |
| `description` | string : ex. `Administrator` (utilisé pour retrouver le rôle depuis le champ `wish` de `User`) |
| `users` | `Collection<User>` : côté propriétaire de la relation ManyToMany |

Voir [role.md](role.md) pour le détail des rôles existants et leurs droits.

## `Instrument`

Instrument de musique (Hautbois, Cor Anglais, Flûte, Clarinette, Tuba, Euphonium, Batterie, Cor : créés par les fixtures).

| Champ | Type |
|---|---|
| `title` | string |
| `users` | `Collection<User>` : OneToMany, `mappedBy="instrument"` |

## `Artist`

Artiste de la bibliothèque musicale.

| Champ | Type |
|---|---|
| `name` | string |
| `songs` | `Collection<Track>` : OneToMany, `mappedBy="artist"` |

## `Track`

Morceau de musique.

| Champ | Type |
|---|---|
| `title` | string |
| `artist` | `Artist` : ManyToOne |
| `trackFilename` | string, nullable : nom du fichier mp3 stocké sur disque |
| `trackFile` | non persisté, contrainte `@Assert\File` (200 Mo max, mp3 uniquement) : utilisé par le formulaire d'upload |
| `minutes`, `seconds` | int : durée du morceau |

## `PasswordUpdate`

DTO de formulaire (non mappé Doctrine, pas d'entité en base) utilisé pour le changement de mot de passe.

| Champ | Contrainte |
|---|---|
| `oldPassword` | requis pour vérifier l'ancien mot de passe (`password_verify`) |
| `newPassword` | `@Assert\Length(min=8)` |
| `confirmPassword` | `@Assert\EqualTo(newPassword)` |

## Schéma des relations

```
User ──ManyToOne──> Instrument
User ──ManyToMany──> Role
Artist ──OneToMany──> Track
```

## Fixtures (`src/DataFixtures/AppFixtures.php`)

Génère un jeu de données de démo : 6 rôles, 8 instruments, 1 super-admin (`guibrouille@gmail.com` / `password`), 20 utilisateurs Binioufous/Membres non validés, 10 utilisateurs "Simple" validés, 5 artistes et 10 morceaux (via Faker, locale `FR-fr`).
