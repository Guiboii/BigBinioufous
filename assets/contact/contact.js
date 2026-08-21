import './contact.css';
import './contact-mascotte.js';

// Soumission du formulaire de contact en fetch (même pattern que
// assets/story/minisite.js) : App\Controller\ContactController vérifie
// honeypot/piège temporel/CSRF côté serveur, ce script n'affiche que le
// résultat.
var contactForm = document.getElementById('contact-form');

if (contactForm) {
  var contactStatus = document.getElementById('contact-status');
  var contactSubmitBtn = contactForm.querySelector('button[type="submit"]');
  var contactMessages = {};
  try {
    contactMessages = JSON.parse(contactForm.dataset.messages || '{}');
  } catch (e) {
    contactMessages = {};
  }

  contactForm.addEventListener('submit', function (e) {
    e.preventDefault();

    contactSubmitBtn.disabled = true;
    contactStatus.classList.remove('is-error');
    contactStatus.textContent = contactMessages.sending || '';

    fetch(contactForm.action, {
      method: 'POST',
      body: new FormData(contactForm),
    })
      .then(function (response) {
        return response.json().then(function (data) {
          return { ok: response.ok, data: data };
        });
      })
      .then(function (result) {
        contactSubmitBtn.disabled = false;
        if (result.ok && result.data.success) {
          contactStatus.classList.remove('is-error');
          contactStatus.textContent = contactMessages.success || '';
          contactForm.reset();
        } else {
          contactStatus.classList.add('is-error');
          contactStatus.textContent =
            contactMessages[result.data.error] || contactMessages.generic || '';
        }
      })
      .catch(function () {
        contactSubmitBtn.disabled = false;
        contactStatus.classList.add('is-error');
        contactStatus.textContent = contactMessages.generic || '';
      });
  });
}

// Widgets HelloAsso (adhésion + don) : grandissent à la hauteur réelle de
// leur contenu via postMessage (mécanisme fourni par HelloAsso). e.source
// identifie l'iframe émettrice : avec 2 widgets sur la page, un message
// non attribué appliquerait sa hauteur aux deux à la fois.
window.addEventListener('message', function (e) {
  var dataHeight = e.data && e.data.height;
  if (!dataHeight) {
    return;
  }
  document.querySelectorAll('.ha-widget').forEach(function (iframe) {
    if (
      e.source === iframe.contentWindow &&
      dataHeight > parseFloat(iframe.style.height || iframe.height || 0)
    ) {
      iframe.style.height = dataHeight + 'px';
    }
  });
});
