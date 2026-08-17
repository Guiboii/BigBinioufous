var contactForm = document.getElementById('minisite-contact-form');
var contactFeedback = contactForm.querySelector('.term-field-feedback');
var contactSubmitBtn = contactForm.querySelector('button[type="submit"]');

contactForm.addEventListener('submit', function (e) {
  e.preventDefault();
  contactSubmitBtn.disabled = true;
  contactFeedback.textContent = contactFeedback.dataset.sending;

  fetch(contactForm.action, {
    method: 'POST',
    body: new FormData(contactForm),
    headers: { Accept: 'application/json' },
  })
    .then(function (response) {
      return response.json().then(function (data) {
        return { ok: response.ok, data: data };
      });
    })
    .then(function (result) {
      contactSubmitBtn.disabled = false;
      if (result.ok && result.data.success) {
        contactFeedback.textContent = contactFeedback.dataset.success;
        contactForm.reset();
      } else {
        contactFeedback.textContent = contactFeedback.dataset.error;
      }
    })
    .catch(function () {
      contactSubmitBtn.disabled = false;
      contactFeedback.textContent = contactFeedback.dataset.error;
    });
});
