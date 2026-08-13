# AGENTS.md — Big Binioufous : Gestionnaire de fichiers

## Contexte

Projet Symfony existant, globalement propre. La partie **navigation fichiers/dossiers**
est bordélique (arborescence, UX, organisation du code) et doit être reprise.

Ce fichier complète `CLAUDE.md` (conventions générales du projet) — il ne les remplace pas.
En cas de conflit, `CLAUDE.md` fait foi sur le style/les conventions globales du repo.

## Mission de l'agent

Reprendre le module de navigation de fichiers/dossiers pour le rendre propre, cohérent
et agréable à utiliser, en s'inspirant des bonnes pratiques des gestionnaires type
Google Drive / Nextcloud.

### 1. Avant de coder
- Explorer l'existant : entités liées aux fichiers/dossiers, contrôleurs, templates,
  routes concernées. Identifier ce qui doit être refactoré vs réécrit.
- Vérifier le modèle de données actuel : est-ce que la hiérarchie (dossiers/fichiers)
  est bien découplée du stockage physique (table avec `parent_id`, pas juste un miroir
  de l'arborescence disque) ? Si non, le signaler avant de casser des choses.
- Résumer ce qui est trouvé avant de se lancer dans des changements structurels.

### 2. UX à viser
- Breadcrumb clair + navigation retour fluide
- Recherche par nom (au minimum)
- Multi-sélection avec actions groupées (déplacer, supprimer)
- Glisser-déposer pour l'upload et le déplacement
- Prévisualisation des fichiers courants (images, PDF) sans téléchargement
- Corbeille avec restauration plutôt que suppression définitive immédiate
- Tri (nom/date/taille) et au moins une vue liste correcte

### 3. Points techniques à respecter
- Upload : valider le type MIME réel (pas juste l'extension), limiter la taille
- Noms de fichiers : échapper/normaliser pour éviter tout path traversal
- Génération de miniatures/preview : ne pas bloquer la requête d'upload (queue/async
  si Symfony Messenger est déjà en place dans le projet, sinon proposer une solution
  simple avant d'ajouter une dépendance lourde)
- Permissions : si le projet a déjà un système d'utilisateurs/rôles, s'appuyer dessus —
  ne pas inventer un modèle de permissions parallèle
- Ne pas ajouter de dépendance externe majeure (stockage S3-like, etc.) sans le signaler
  d'abord — le projet reste en stockage local sauf indication contraire

### 4. Ce qu'il ne faut PAS faire
- Ne pas réécrire tout le module d'un coup si un refactoring ciblé suffit
- Ne pas ajouter versioning / sync offline / partage par lien tant que ce n'est pas
  demandé explicitement — juste un CRUD fichiers/dossiers solide, propre, avec upload
  fiable et preview
- Ne pas changer les conventions de code déjà en place dans le projet (voir CLAUDE.md)

### 5. À la fin
- Mettre à jour `CLAUDE.md` si l'agent a introduit une convention ou une dépendance
  nouvelle (queue async, lib de preview, etc.)
- Fournir un résumé clair : ce qui a été changé, pourquoi, et ce qui reste à faire
  si tout n'a pas pu être traité
