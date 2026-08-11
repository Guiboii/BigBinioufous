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
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && menu.classList.contains('show')) {
      menu.classList.remove('show');
      toggler.setAttribute('aria-expanded', 'false');
      toggler.focus();
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
