import EasyMDE from 'easymde';
import 'easymde/dist/easymde.min.css';

/**
 * Éditeur Markdown du champ "Contenu" des sections Histoire (/admin/story),
 * pour des rédacteur·ices non-informaticien·nes : barre d'outils réduite
 * (pas de tableaux/code/image, pas utiles ici) + aperçu affiché en
 * permanence à côté plutôt qu'un bouton à activer (toggleSideBySideView()
 * après l'init), pour que le rendu soit visible sans avoir à le chercher.
 * autoDownloadFontAwesome désactivé explicitement : la lib peut sinon
 * injecter un <link> vers un CDN si elle ne détecte pas Font Awesome sur la
 * page, ce que ce projet évite partout ailleurs (assets self-contained).
 */
const contentField = document.getElementById('story-section-content');

if (contentField) {
  const easyMDE = new EasyMDE({
    element: contentField,
    autoDownloadFontAwesome: false,
    spellChecker: false,
    status: false,
    toolbar: [
      'bold',
      'italic',
      'heading',
      '|',
      'quote',
      'unordered-list',
      'ordered-list',
      '|',
      'link',
      '|',
      'guide',
    ],
  });

  easyMDE.toggleSideBySideView();
}
