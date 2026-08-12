// Ajout/suppression des lignes de prestation du formulaire devis/facture
// (CollectionType App\Form\AccountingDocumentType::lines), pattern standard
// Symfony "prototype" : le conteneur porte le HTML d'une ligne vierge en
// attribut data-prototype, avec __line__ à remplacer par un index qui
// s'incrémente à chaque ajout (cf. AccountingDocumentType::buildForm,
// prototype_name '__line__').
const container = document.getElementById('accounting-document-lines');
const addButton = document.getElementById('accounting-add-line');

if (container && addButton) {
  addButton.addEventListener('click', function () {
    const index = parseInt(container.dataset.index, 10);
    const html = container.dataset.prototype.replace(/__line__/g, String(index));

    const row = document.createElement('div');
    row.classList.add('accounting-line-row');
    row.setAttribute('data-line-row', '');
    row.innerHTML = html;

    const removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.classList.add('btn', 'btn-red');
    removeButton.setAttribute('data-remove-line', '');
    removeButton.setAttribute('aria-label', addButton.dataset.removeLabel);
    removeButton.innerHTML = '<i class="ri-delete-bin-line" aria-hidden="true"></i>';
    row.appendChild(removeButton);

    container.appendChild(row);
    container.dataset.index = String(index + 1);
  });

  container.addEventListener('click', function (e) {
    const removeButton = e.target.closest('[data-remove-line]');
    if (removeButton) {
      removeButton.closest('[data-line-row]').remove();
    }
  });
}
