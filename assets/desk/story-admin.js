import { createMarkdownEditor } from './markdown-editor.js';

// Pas d'id forcé dans StorySectionType.content (attr) : le widget_attributes
// de Bootstrap 4 imprime déjà "id" à partir de vars.id, un attr.id en plus
// aurait juste dupliqué l'attribut HTML sans l'écraser. On cible donc l'id
// auto-généré par Symfony (story_section_content, stable entre new/edit :
// dérivé du nom du FormType, pas de l'id de l'entité).
createMarkdownEditor('story_section_content');
