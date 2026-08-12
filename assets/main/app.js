/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)

import 'jquery';
import 'bootstrap';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'remixicon/fonts/remixicon.css';
import './app.css';

// Bootstrap 4's collapse plugin (used for the navbar toggler) doesn't close
// on Escape by default. The collapsed menu reads visually like a modal/panel
// (cf. .navbar-collapse in app.css for the public nav, .navbar-admin's own
// panel for the desk/admin nav) : Escape should close it too, with focus
// sent back to the toggler button. Toggling the 'show' class + aria-expanded
// directly (rather than going through jQuery/Bootstrap's collapse('hide')
// API) keeps this independent from whether jQuery is exposed as a global by
// the AssetMapper build.
// Reads the toggler's own data-target rather than hardcoding an id : the
// public nav (#navbarText) and the desk/admin nav (#navbarAdminNav) share
// this same script, only one of the two is ever present on a given page.
document.addEventListener('DOMContentLoaded', function () {
  var toggler = document.querySelector('.navbar-toggler');
  if (!toggler) {
    return;
  }
  var menu = document.querySelector(toggler.getAttribute('data-target'));
  if (!menu) {
    return;
  }

  // Remet le panneau replié dans l'état "fermé" tel que le ferait Bootstrap
  // au clic sur le toggler : factorisé car réutilisé à la fois par Échap et
  // par le clic sur le lien de la page courante ci-dessous.
  function closeMenu() {
    menu.classList.remove('show');
    toggler.setAttribute('aria-expanded', 'false');
  }

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && menu.classList.contains('show')) {
      closeMenu();
      toggler.focus();
    }
  });

  // Lien de la page courante (aria-current="page", posé côté Twig d'après la
  // route active) : cliquer dessus ne doit pas recharger la page pour rien,
  // mais doit quand même fermer le panneau replié comme n'importe quel autre
  // lien de la navbar (qui, eux, s'en chargent "gratuitement" via la
  // navigation qui recharge la page). Écouteur posé sur le panneau plutôt
  // que sur chaque lien : un seul <a aria-current="page"> existe par page,
  // et ça marche aussi bien pour la navbar admin dépliée en desktop (aucune
  // classe 'show' à retirer dans ce cas, closeMenu() n'a alors aucun effet
  // visible, ce qui est le comportement voulu).
  menu.addEventListener('click', function (e) {
    var currentLink = e.target.closest('a[aria-current="page"]');
    if (!currentLink) {
      return;
    }
    e.preventDefault();
    if (menu.classList.contains('show')) {
      closeMenu();
    }
  });
});

// Bootstrap 4's "custom file" widget (form_themes: bootstrap_4_layout.html.twig
// wraps every FileType field this way, cf. /register, /desk/profile,
// admin/user/show.html.twig, admin/event/new+edit) really does open the
// native file picker on click : the real <input type="file"> sits directly
// on top of the visible label, just invisible (opacity:0), that part works.
// What's missing is the other half of the pattern Bootstrap's own docs call
// for : nothing updates the label's text after a file is picked, so the
// button still reads "Browse"/"Choisir un fichier" as if nothing had
// happened, indistinguishable at a glance from the picker never opening at
// all. Never wired up anywhere on this project until now.
document.addEventListener('change', function (e) {
  if (!e.target.matches('.custom-file-input')) {
    return;
  }
  var label = e.target.nextElementSibling;
  if (label && label.classList.contains('custom-file-label') && e.target.files.length) {
    label.textContent = e.target.files[0].name;
  }
});
