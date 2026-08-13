// Multi-sélection + actions groupées sur /desk/files/{space} : les cases
// à cocher (.desk-select) n'ont pas d'attribut "form", elles ne sont
// associées à aucun des 2 formulaires cachés (desk-bulk-move-form/
// desk-bulk-delete-form) déclarés dans templates/desk/files.html.twig.
// Au clic sur "Déplacer"/"Supprimer la sélection", ce script y injecte les
// folder_ids[]/document_ids[] cochés juste avant de les soumettre.
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

  // Vide les folder_ids[]/document_ids[] posés par un envoi précédent puis
  // en recrée un par case cochée : un <form> caché est réutilisé pour
  // chaque soumission, il ne doit pas accumuler les cases d'une fois sur
  // l'autre.
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
