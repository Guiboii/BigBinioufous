// Submits the minisite's contact form via fetch to keep the terminal window context displayed. The server checks honeypot/timing trap/CSRF, this script only shows the result. Kept separate from story.js: this page renders in its own document (iframe on /story, standalone on mobile).
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
