// Visionneuse partagée pour les documents de /desk/music : évite de forcer
// un téléchargement juste pour regarder une image ou un PDF. Seuls
// image/pdf/audio/vidéo ont un rendu natif fiable dans le navigateur, les
// autres types (word/sheet/slides) gardent le comportement par défaut du
// lien (téléchargement/ouverture selon le navigateur).
var modal = document.getElementById('file-preview-modal');

if (modal) {
  var body = document.getElementById('file-preview-body');
  var title = document.getElementById('file-preview-title');
  var downloadLink = document.getElementById('file-preview-download');
  var PREVIEWABLE_KINDS = ['image', 'pdf', 'audio', 'video'];
  var lastFocused = null;

  document.addEventListener('click', function (e) {
    var link = e.target.closest('[data-preview-kind]');
    if (!link || PREVIEWABLE_KINDS.indexOf(link.dataset.previewKind) === -1) {
      return;
    }

    e.preventDefault();
    openPreview(link.dataset.previewKind, link.dataset.previewUrl, link.dataset.previewName);
  });

  modal.addEventListener('click', function (e) {
    if (e.target.closest('[data-close]')) {
      closePreview();
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !modal.hidden) {
      closePreview();
    }
  });

  function openPreview(kind, url, name) {
    lastFocused = document.activeElement;
    title.textContent = name;
    downloadLink.href = url;
    body.innerHTML = '';
    body.appendChild(buildViewer(kind, url, name));

    modal.hidden = false;
    modal.querySelector('.file-preview-close').focus();
  }

  function closePreview() {
    modal.hidden = true;
    body.innerHTML = '';
    if (lastFocused) {
      lastFocused.focus();
    }
  }

  function buildViewer(kind, url, name) {
    var el;

    if (kind === 'image') {
      el = document.createElement('img');
      el.src = url;
      el.alt = name;
    } else if (kind === 'pdf') {
      el = document.createElement('iframe');
      el.src = url;
      el.title = name;
    } else if (kind === 'audio') {
      el = document.createElement('audio');
      el.controls = true;
      el.src = url;
    } else {
      el = document.createElement('video');
      el.controls = true;
      el.src = url;
    }

    return el;
  }
}
