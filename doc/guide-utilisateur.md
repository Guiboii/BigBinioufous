# Guide d'utilisation, de bout en bout

Parcours complet du site côté utilisateur·ice, du premier arrivage sur `/` jusqu'aux actions d'administration. Pour la doc technique (code, routes, entités), voir les autres fichiers de [doc/](README.md). Basé sur l'état du site au 2026-09-12.

## Qui utilise le site, et avec quels droits

Trois rôles existent, cumulables (un compte peut en avoir plusieurs à la fois) : `ROLE_ADMIN`, `ROLE_COMPTA`, `ROLE_BINIOUFOUS`. Aucun n'est jamais attribué automatiquement : un compte connecté sans rôle métier (dit "simple") a déjà accès à tout `/desk` (profil, notes... si autorisé, covoiturage), juste pas aux espaces réservés. Détail complet : [role.md](role.md).

| Rôle | Débloque |
|---|---|
| (aucun, "simple") | `/desk`, profil, mot de passe, covoiturage |
| `ROLE_BINIOUFOUS` | + espace fichiers Musique (lecture/écriture), écoute audio sur `/music`, espace "Autre" (lecture) |
| `ROLE_COMPTA` | + espace fichiers Comptabilité (devis/factures/clients/trésorerie), notes de bureau |
| `ROLE_ADMIN` | tout, + validation des inscriptions, gestion des rôles, planning, contenu de la page Histoire, espace "Autre" en écriture, notes de bureau, 2FA disponible |

## 1. Avant de créer un compte : les pages publiques

Accessibles sans connexion, aucune inscription requise :

- **`/`** : accueil, scène 3D avec la mascotte.
- **`/story`** : page Histoire du groupe (sur mobile, redirige vers une version simplifiée `/story/mini`).
- **`/schedule`** : planning, mais seuls les **concerts** sont visibles sans connexion (les répétitions restent réservées aux membres connectés, avec une note "connecte-toi pour voir plus").
- **`/music`** : setlist du groupe (titre, artiste, lien YouTube). L'écoute audio directe est réservée aux comptes `ROLE_BINIOUFOUS`/`ROLE_ADMIN`.
- **`/contact`** : formulaire de contact + widgets d'adhésion/don HelloAsso.

## 2. Devenir membre

### Inscription (`/register`)

Formulaire volontairement minimal : pseudo, email, mot de passe. Rien d'autre n'est demandé à ce stade (identité, instrument, adhésion... se complètent après, cf. section suivante).

À la validation du formulaire :
1. Le compte est créé (`validation = false`), sans aucun rôle.
2. Connexion automatique : tu arrives directement sur `/desk/profile`, pas besoin de te reconnecter.
3. Un mail part vers l'équipe (notification "nouvelle inscription en attente"), un autre vers toi une fois ton compte accepté (si `MAILER_DSN` est configuré côté serveur, cf. `CLAUDE.md`).

Tant que le compte n'est pas encore validé par un·e admin, un bandeau "compte en cours de vérification" apparaît sur `/desk`, avec un lien direct vers ton profil. **La connexion elle-même n'est jamais bloquée** : tu peux te connecter, compléter ton profil et naviguer sur `/desk` avant même la validation, seule la visibilité dans les listes de membres et l'attribution d'un rôle en dépendent.

### Compléter son profil (`/desk/profile`)

Tout est facultatif à ce stade : prénom, nom, genre, date de naissance, ville, pays, instrument (avec un champ libre si "Autre"), photo de profil. C'est aussi ici que se change le mot de passe (`/desk/update-password`) et, pour les comptes admin, que se configure la 2FA (cf. section Administration).

Une case "Es-tu déjà adhérent·e ?" (déclarative, purement informative) et un rappel du lien HelloAsso pour adhérer si ce n'est pas encore fait.

### Validation par un·e admin

Un·e admin voit les inscriptions en attente sur `/admin/valid`, et peut :
- **Valider** : passe le compte en `validation = true`. N'attribue **aucun rôle** à ce stade.
- **Refuser** : supprime le compte en attente.

Le rôle `ROLE_BINIOUFOUS` se décide ensuite, séparément, via le bouton "Passer Membre"/"Retirer Membre" visible sur les listes de `/desk` (indépendant de la validation elle-même). `ROLE_ADMIN`/`ROLE_COMPTA` s'attribuent depuis la fiche détaillée du compte (`/admin/user/{slug}`).

## 3. Le tableau de bord (`/desk`)

Le contenu affiché dépend du rôle le plus "haut" du compte (admin > binioufous > simple), pas un cumul de tout ce que le compte pourrait voir :
- **Admin** : listes complètes (admins, comptables, binioufous, simples) avec actions (éditer, promouvoir, rétrograder), compteur d'inscriptions en attente.
- **Binioufous** (sans admin) : liste des autres binioufous, en lecture seule.
- **Simple** : message d'accueil, invitation à rejoindre les listes de diffusion.

La navbar membre (`.navbar-admin`) donne accès selon le rôle à : Dossiers, Musique (setlist), Notes (admin/compta), Administration (admin), Profil, Déconnexion. Sur mobile, elle se replie derrière un bouton hamburger.

## 4. Le gestionnaire de fichiers (`/desk/files`)

Façon "Drive" : dossiers et fichiers organisés en 4 espaces cloisonnés par rôle.

| Espace | Qui peut lire | Qui peut écrire (créer/déposer/déplacer/supprimer) |
|---|---|---|
| Musique | Binioufous, Admin | Binioufous, Admin |
| Administratif | Admin | Admin |
| Comptabilité | Comptable, Admin | Comptable, Admin |
| Autre | Binioufous, Admin | **Admin seul** |

Le lien "Dossiers" de la navbar mène directement au seul espace accessible s'il n'y en a qu'un, ou à un hub de sélection (`/desk/files`) à partir de deux.

Dans un espace, en haut de page (si tu as le droit d'écrire) :
- **Nouveau dossier** : nom + bouton "Créer", toujours visible (pas besoin de déplier quoi que ce soit).
- **Dépôt de fichiers** : glisser-déposer ou clic pour choisir, plusieurs fichiers à la fois. Types acceptés : documents/PDF, images, audio, vidéo, formats Office/LibreOffice courants.

Une fois dans un dossier :
- **Recherche** (`?q=`) : par nom, sur tout l'espace (récursive, pas juste le dossier courant).
- **Tri** : nom, date ou taille, croissant/décroissant.
- **Multi-sélection** : case à cocher par ligne + "Tout sélectionner", puis actions groupées (déplacer, supprimer) via la barre qui apparaît en bas.
- **Déplacer** : soit glisser-déposer directement une ligne sur un dossier, soit cliquer sur l'icône déplacer puis naviguer jusqu'à la destination et cliquer "Déplacer ici".
- **Corbeille** (lien en haut de page) : les éléments supprimés y restent jusqu'à restauration ou suppression définitive. Supprimer un dossier ne supprime pas récursivement son contenu dans la corbeille (un seul "objet" y apparaît, la restauration ramène tout l'arbre d'un coup).
- **Favoris/"je joue cette voix"** (fichiers audio, espace Musique uniquement) : étoile pour marquer un morceau en favori, bascule "je joue cette partie" pour indiquer qui joue quoi.

## 5. Musique

Deux endroits distincts, à ne pas confondre :

- **`/music`** (page publique) : la setlist du groupe, un morceau = titre + artiste + éventuellement un lien YouTube et un dossier de fichiers associé. Cliquer sur un morceau charge sa piste audio dans le lecteur (réservé binioufous/admin) ; le rond "Envoyer" du lecteur et le bouton dans l'espace membre ouvrent tous deux la modale de gestion.
- **Gérer la setlist** (`/desk/files/music/setlist`, accessible aussi via la modale sur `/music`) : ajouter/éditer/supprimer un morceau, réordonner (boutons monter/descendre), lier un dossier de fichiers déjà créé dans l'espace Musique (le dossier et ses fichiers audio doivent exister **avant** de pouvoir le lier depuis un menu déroulant, pas de création à la volée par texte libre).

## 6. Comptabilité (`ROLE_COMPTA`/`ROLE_ADMIN`)

Trois onglets sous `/desk/files/accounting` :

- **Relevés** : le gestionnaire de fichiers générique (cf. section 4), pour les relevés bancaires et justificatifs.
- **Devis & Factures** : création avec lignes multiples (libellé, prix unitaire, quantité), génération d'une facture à partir d'un devis existant, page imprimable (impression navigateur, pas de PDF généré côté serveur). Une fiche client réutilisable préremplit nom/adresse/contact.
- **Trésorerie** : journal recettes/dépenses avec solde calculé automatiquement, rattachement facultatif à un devis/une facture pour traçabilité (pas de rapprochement automatique).

## 7. Planning et covoiturage

- **`/schedule`** : calendrier mois par mois, répétitions et concerts. Adresse cliquable (Google Maps), bouton "Ajouter à mon agenda" (Google, Outlook, ou fichier `.ics` pour Apple Calendar/Thunderbird). Édition réservée aux admins (`/admin/event`).
- **`/desk/carpool`** : covoiturage pour un événement à venir, ouvert à tout compte connecté sans rôle particulier. Un·e membre propose un trajet (lieu de départ, places disponibles, heure si différente de l'événement), les autres rejoignent/quittent en un clic tant qu'il reste de la place. Suppression réservée au conducteur·rice ou à un·e admin.

## 8. Page Histoire (`/story`)

Contenu éditorial en sections Markdown, gérées par un·e admin sur `/admin/story` (création, édition avec aperçu live, suppression, réordonnancement). Rendu sur `/story` dans une "minisite" façon terminal rétro (fenêtre flottante sur un bureau en desktop, plein écran sur mobile).

## 9. Administration (`ROLE_ADMIN`)

Tout sous `/admin/*` :

- **`/admin/valid`** : inscriptions en attente, valider/refuser (cf. section 2).
- **`/admin/user/{slug}`** : fiche d'un compte, édition de son profil, boutons de promotion (admin/comptable/binioufous, seulement pour les rôles qu'il n'a pas déjà), retrait de rôle.
- **`/admin/event`** : CRUD du planning affiché sur `/schedule`.
- **`/admin/story`** : CRUD du contenu de la page Histoire.
- **Notes de bureau** (`/desk/notes`, accessible aussi aux `ROLE_COMPTA`) : prise de notes Markdown, privées par défaut ou partagées en lecture seule avec les autres admins/comptables (jamais co-éditables). Export PDF (impression navigateur) pour l'ensemble des notes visibles.
- **2FA** (`/desk/profile/2fa`) : activation optionnelle d'une double authentification par application (Google Authenticator, Aegis...) sur le compte admin. Scanner le QR code, saisir le code à 6 chiffres généré pour confirmer. Désactivable à tout moment depuis la même page.

## Récapitulatif : qui peut faire quoi

Voir le tableau complet dans [role.md](role.md) ("Qui a accès à quoi"). En résumé, du plus large au plus restreint :

1. **Personne (pages publiques)** : accueil, Histoire, concerts du planning, setlist musique (sans écoute), contact.
2. **Compte simple (connecté, sans rôle)** : tout `/desk` sauf les espaces réservés, covoiturage, répétitions du planning.
3. **`ROLE_BINIOUFOUS`** : + fichiers Musique, écoute audio, espace Autre en lecture.
4. **`ROLE_COMPTA`** : + fichiers Comptabilité, notes de bureau.
5. **`ROLE_ADMIN`** : tout, sans exception.
