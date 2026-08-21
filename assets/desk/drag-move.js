// Drag-and-drop to move a folder/document onto another folder, Drive-style. Coexists with the existing click-to-move mode rather than replacing it, so keyboard/accessibility flows keep working.
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
    // Required by Firefox to allow the drag; Chrome doesn't need it but ignores it harmlessly.
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
