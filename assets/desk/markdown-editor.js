import EasyMDE, {
  toggleBold,
  toggleItalic,
  toggleHeadingSmaller,
  toggleBlockquote,
  toggleUnorderedList,
  toggleOrderedList,
  drawLink,
} from 'easymde';
import 'easymde/dist/easymde.min.css';

/**
 * Éditeur Markdown partagé par toutes les pages admin qui éditent du
 * Markdown (sections Histoire, notes...) : barre d'outils réduite (pas de
 * tableaux/code/image) + aperçu affiché en permanence à côté plutôt qu'un
 * bouton à activer, pour des rédacteur·ices non-informaticien·nes.
 *
 * Icônes de la barre d'outils en Remix Icon (classes ri-*, déjà chargées
 * partout sur le site via app.js) plutôt que les classes Font Awesome par
 * défaut d'EasyMDE : avec autoDownloadFontAwesome désactivé (la lib peut
 * sinon injecter un <link> CDN, contraire à la politique self-contained de
 * ce projet), les boutons par défaut s'affichaient vides (repéré par
 * l'utilisatrice le 2026-08-12). Boutons redéfinis en objets custom (action
 * + className + title) plutôt que réactiver Font Awesome.
 *
 * @param {string} fieldId id du textarea à transformer
 * @returns {EasyMDE|null} null si le champ n'est pas présent sur la page
 */
export function createMarkdownEditor(fieldId) {
  const field = document.getElementById(fieldId);

  if (!field) {
    return null;
  }

  const easyMDE = new EasyMDE({
    element: field,
    autoDownloadFontAwesome: false,
    spellChecker: false,
    status: false,
    toolbar: [
      { name: 'bold', action: toggleBold, className: 'ri-bold', title: 'Gras' },
      { name: 'italic', action: toggleItalic, className: 'ri-italic', title: 'Italique' },
      { name: 'heading', action: toggleHeadingSmaller, className: 'ri-heading', title: 'Titre' },
      '|',
      {
        name: 'quote',
        action: toggleBlockquote,
        className: 'ri-double-quotes-l',
        title: 'Citation',
      },
      {
        name: 'unordered-list',
        action: toggleUnorderedList,
        className: 'ri-list-unordered',
        title: 'Liste à puces',
      },
      {
        name: 'ordered-list',
        action: toggleOrderedList,
        className: 'ri-list-ordered',
        title: 'Liste numérotée',
      },
      '|',
      { name: 'link', action: drawLink, className: 'ri-link', title: 'Lien' },
    ],
  });

  easyMDE.toggleSideBySideView();

  return easyMDE;
}
