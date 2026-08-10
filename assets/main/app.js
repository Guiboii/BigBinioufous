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
import '@fortawesome/fontawesome-free/js/fontawesome.min.js';
import '@fortawesome/fontawesome-free/css/fontawesome.min.css';
import 'remixicon/fonts/remixicon.css';
import './app.css';

// Bootstrap 4's collapse plugin (used for the navbar toggler) doesn't close
// on Escape by default. The collapsed menu is styled as a centered overlay
// card (cf. .navbar-collapse in app.css), so it reads visually like a modal :
// Escape should close it too, with focus sent back to the toggler button.
// Toggling the 'show' class + aria-expanded directly (rather than going
// through jQuery/Bootstrap's collapse('hide') API) keeps this independent
// from whether jQuery is exposed as a global by the AssetMapper build.
document.addEventListener('DOMContentLoaded', function () {
  var toggler = document.querySelector('.navbar-toggler');
  var menu = document.getElementById('navbarText');
  if (!toggler || !menu) {
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
