// Multi-select + bulk actions on the file manager. The checkboxes aren't tied to either hidden form (move/delete) via a "form" attribute; this script injects the checked folder_ids[]/document_ids[] into the right form right before submitting it.
var bulkBar = document.getElementById('desk-bulk-bar');

if (bulkBar) {
  var selectAll = document.getElementById('desk-select-all');
  var count = document.getElementById('desk-bulk-count');
  var moveBtn = document.getElementById('desk-bulk-move-btn');
  var deleteBtn = document.getElementById('desk-bulk-delete-btn');
  var moveForm = document.getElementById('desk-bulk-move-form');
  var deleteForm = document.getElementById('desk-bulk-delete-form');

  function checkboxes() {
    return Array.prototype.slice.call(document.querySelectorAll('.desk-select'));
  }

  function selected() {
    return checkboxes().filter(function (box) {
      return box.checked;
    });
  }

  function updateBar() {
    var boxes = selected();

    if (boxes.length === 0) {
      bulkBar.hidden = true;

      return;
    }

    bulkBar.hidden = false;
    count.textContent = bulkBar.dataset.countLabel.replace('__COUNT__', String(boxes.length));
  }

  document.addEventListener('change', function (e) {
    if (e.target.classList.contains('desk-select')) {
      updateBar();

      var all = checkboxes();
      if (selectAll) {
        selectAll.checked =
          all.length > 0 &&
          all.every(function (box) {
            return box.checked;
          });
      }
    }
  });

  if (selectAll) {
    selectAll.addEventListener('change', function () {
      checkboxes().forEach(function (box) {
        box.checked = selectAll.checked;
      });
      updateBar();
    });
  }

  // Clears the ids left by a previous submission before adding the current selection: the hidden form is reused across submissions, it must not accumulate stale ids.
  function fillForm(form) {
    Array.prototype.slice.call(form.querySelectorAll('.desk-bulk-id')).forEach(function (input) {
      input.remove();
    });

    selected().forEach(function (box) {
      var input = document.createElement('input');
      input.type = 'hidden';
      input.className = 'desk-bulk-id';
      input.name = (box.dataset.kind === 'folder' ? 'folder_ids' : 'document_ids') + '[]';
      input.value = box.dataset.id;
      form.appendChild(input);
    });
  }

  moveBtn.addEventListener('click', function () {
    if (selected().length === 0) {
      return;
    }

    fillForm(moveForm);
    moveForm.submit();
  });

  deleteBtn.addEventListener('click', function () {
    if (selected().length === 0) {
      return;
    }

    fillForm(deleteForm);
    deleteForm.submit();
  });
}
