// Glisser-déposer pour déplacer un dossier/document sur un autre dossier,
// façon Drive. Coexiste avec le mode "clic pour déplacer" existant
// (desk-move-start, cf. templates/desk/files.html.twig) : simple ajout, pas
// un remplacement, pour ne rien casser côté clavier/accessibilité.
var bulkBar = document.getElementById('desk-bulk-bar');

if (bulkBar) {
  var draggedEl = null;

  document.addEventListener('dragstart', function (e) {
    var el = e.target.closest('[data-drag-kind]');
    if (!el) {
      return;
    }

    draggedEl = el;
    e.dataTransfer.effectAllowed = 'move';
    // Requis par Firefox pour autoriser le glisser (Chrome s'en passe, mais
    // ignore setData sans dégât si jamais le navigateur ne s'en sert pas).
    e.dataTransfer.setData('text/plain', el.dataset.dragKind + ':' + el.dataset.dragId);
  });

  document.addEventListener('dragover', function (e) {
    var zone = e.target.closest('[data-dropzone="folder"]');
    if (!zone || !draggedEl) {
      return;
    }

    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    zone.classList.add('desk-dropzone-active');
  });

  document.addEventListener('dragleave', function (e) {
    var zone = e.target.closest('[data-dropzone="folder"]');
    if (zone) {
      zone.classList.remove('desk-dropzone-active');
    }
  });

  document.addEventListener('drop', function (e) {
    var zone = e.target.closest('[data-dropzone="folder"]');
    if (!zone || !draggedEl) {
      return;
    }

    e.preventDefault();
    zone.classList.remove('desk-dropzone-active');

    var targetId = zone.dataset.dropId;
    var selectedBoxes = Array.prototype.slice.call(
      document.querySelectorAll('.desk-select:checked'),
    );
    var draggedIsSelected = selectedBoxes.some(function (box) {
      return (
        box.dataset.kind === draggedEl.dataset.dragKind &&
        box.dataset.id === draggedEl.dataset.dragId
      );
    });

    if (draggedIsSelected && selectedBoxes.length > 1) {
      moveSelection(selectedBoxes, targetId);
    } else {
      moveSingle(draggedEl, targetId);
    }

    draggedEl = null;
  });

  document.addEventListener('dragend', function () {
    draggedEl = null;
  });

  function moveSingle(el, targetId) {
    var body = new URLSearchParams();
    body.set('target', targetId);
    body.set('_token', el.dataset.moveToken);

    post(el.dataset.moveAction, body);
  }

  function moveSelection(boxes, targetId) {
    var body = new URLSearchParams();
    body.set('target', targetId);
    body.set('_token', bulkBar.dataset.bulkMoveToken);

    boxes.forEach(function (box) {
      body.append(
        (box.dataset.kind === 'folder' ? 'folder_ids' : 'document_ids') + '[]',
        box.dataset.id,
      );
    });

    post(bulkBar.dataset.bulkMoveAction, body);
  }

  function post(url, body) {
    fetch(url, {
      method: 'POST',
      body: body,
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    }).then(function () {
      window.location.reload();
    });
  }
}
