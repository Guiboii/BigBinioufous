import './contact.css';
import './contact-mascotte.js';

// Submits the contact form via fetch; the server checks the honeypot/timing trap/CSRF, this script only displays the result.
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

// HelloAsso widgets grow to their real content height via postMessage. e.source identifies the sending iframe, needed since there are 2 widgets on the page.
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
