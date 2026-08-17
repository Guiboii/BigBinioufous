// Soumission du formulaire de contact de la minisite (#minisite-contact-form),
// en fetch plutôt qu'en navigation complète (même raison que le reste du
// projet : garder le contexte affiché, ici la fenêtre terminal). Le
// honeypot/piège temporel/CSRF sont vérifiés côté serveur
// (App\Controller\ContactController), ce script ne fait qu'afficher le
// résultat. Fichier séparé de story.js (contexte JS différent : cette page
// est rendue dans son propre document, en iframe sur /story ou en page à
// part entière sur mobile, cf. commentaire en tête de minisite.html.twig).
var minisiteContactForm = document.getElementById('minisite-contact-form');

if (minisiteContactForm) {
  var minisiteContactStatus = document.getElementById('minisite-contact-status');
  var minisiteContactSubmitBtn = minisiteContactForm.querySelector('button[type="submit"]');
  var minisiteContactMessages = {};
  try {
    minisiteContactMessages = JSON.parse(minisiteContactForm.dataset.messages || '{}');
  } catch (e) {
    minisiteContactMessages = {};
  }

  minisiteContactForm.addEventListener('submit', function (e) {
    e.preventDefault();

    minisiteContactSubmitBtn.disabled = true;
    minisiteContactStatus.classList.remove('is-error');
    minisiteContactStatus.textContent = minisiteContactMessages.sending || '';

    fetch(minisiteContactForm.action, {
      method: 'POST',
      body: new FormData(minisiteContactForm),
    })
      .then(function (response) {
        return response.json().then(function (data) {
          return { ok: response.ok, data: data };
        });
      })
      .then(function (result) {
        minisiteContactSubmitBtn.disabled = false;
        if (result.ok && result.data.success) {
          minisiteContactStatus.classList.remove('is-error');
          minisiteContactStatus.textContent = minisiteContactMessages.success || '';
          minisiteContactForm.reset();
        } else {
          minisiteContactStatus.classList.add('is-error');
          minisiteContactStatus.textContent =
            minisiteContactMessages[result.data.error] || minisiteContactMessages.generic || '';
        }
      })
      .catch(function () {
        minisiteContactSubmitBtn.disabled = false;
        minisiteContactStatus.classList.add('is-error');
        minisiteContactStatus.textContent = minisiteContactMessages.generic || '';
      });
  });
}
