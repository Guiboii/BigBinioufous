import { createMarkdownEditor } from './markdown-editor.js';

// No forced id on StorySectionType.content: Bootstrap 4's widget_attributes already prints "id" from vars.id, so an extra attr.id would just duplicate it. Targets Symfony's auto-generated id instead, stable between new/edit since it's derived from the form type name, not the entity id.
createMarkdownEditor('story_section_content');
