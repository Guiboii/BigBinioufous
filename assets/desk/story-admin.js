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
 * Éditeur Markdown du champ "Contenu" des sections Histoire (/admin/story),
 * pour des rédacteur·ices non-informaticien·nes : barre d'outils réduite
 * (pas de tableaux/code/image, pas utiles ici) + aperçu affiché en
 * permanence à côté plutôt qu'un bouton à activer (toggleSideBySideView()
 * après l'init), pour que le rendu soit visible sans avoir à le chercher.
 *
 * Icônes de la barre d'outils en Remix Icon (classes ri-*, déjà chargées
 * partout sur le site via app.js) plutôt que les classes Font Awesome par
 * défaut d'EasyMDE : les boutons par défaut ("bold", "italic"...) comptent
 * sur Font Awesome pour afficher une icône, absent de ce projet. Avec
 * autoDownloadFontAwesome désactivé (la lib peut sinon injecter un <link>
 * CDN, contraire à la politique self-contained de ce projet), ces boutons
 * s'affichaient vides (repéré par l'utilisatrice le 2026-08-12 : cliquables,
 * mais sans icône visible). Boutons redéfinis en objets custom (action +
 * className + title) plutôt que réactiver Font Awesome, pour rester
 * cohérent avec l'iconographie du reste de l'admin.
 */
const contentField = document.getElementById('story_section_content');

if (contentField) {
  const easyMDE = new EasyMDE({
    element: contentField,
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
}
