# Composants réutilisables

## `form-dialog` 

Composant pour un dialogue modal contenant un formulaire.

### Utilisation

```twig
{{ include('components/form-dialog.html.twig', {
    id: 'myFormId',
    action: path('route_name'),
    title: 'form.title_key',
    content: include('path/to/fields.html.twig')
}) }}
```

### Paramètres

- **id** (string, requis): identifiant unique du formulaire, utilisé pour `aria-labelledby` et par le JS de gestion des dialogues
- **action** (string, optionnel): URL de soumission du formulaire ; si absent, le formulaire ne précise pas d'action
- **title** (string, requis): clé de traduction du titre du dialogue (sera passée à `|trans`)
- **content** (string, requis): HTML du contenu du formulaire (champs, messages d'erreur, etc.)

### Accessibilité

Le composant inclut :
- `role="dialog"` + `aria-modal="true"` pour exposer la nature du composant
- `aria-labelledby` pointant vers le titre `<h2>` du dialogue
- Bouton fermer avec `aria-label` accessible
- Structure sémantique avec titre `<h2>` au lieu de variantes génériques

### Exemple : formulaire de connexion

```twig
{# templates/join/index.html.twig #}
{{ include('components/form-dialog.html.twig', {
    id: 'loginForm',
    action: path('join'),
    title: 'join.login_title',
    content: include('join/login.html.twig')
}) }}
```

Le contenu du formulaire (ici `join/login.html.twig`) doit contenir uniquement les champs du formulaire, pas les balises `<form>`/`<h2>`/bouton fermer qui sont générées par le composant.

### Gestion JavaScript

Le composant s'attend à ce que le JavaScript gère :
- Ouverture via `document.getElementById(id).classList.remove('d-none')`
- Fermeture via bouton `.close` ou `document.getElementById(id).classList.add('d-none')`
- Gestion du clavier (Échap, Tab trap) selon l'implémentation
