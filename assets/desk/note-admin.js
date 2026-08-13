import { createMarkdownEditor } from './markdown-editor.js';

// Id auto-généré par Symfony pour NoteType.content (note_content), même
// raisonnement que story-admin.js (pas d'id forcé en attr, laisser le
// widget_attributes de Bootstrap 4 générer l'id à partir du nom du champ).
createMarkdownEditor('note_content');
