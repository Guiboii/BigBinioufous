// Préremplissage des champs client (texte libre, cf. AccountingDocument)
// à partir d'une fiche Client sélectionnée dans le formulaire devis/facture
// (App\Form\AccountingDocumentType::client, choice_attr data-address/
// data-contact). Les champs texte restent la source réellement soumise :
// ce script ne fait que gagner du temps de saisie, rien n'empêche de les
// modifier ensuite pour ce document précis.
const clientSelect = document.getElementById('accounting_document_client');

if (clientSelect) {
  const nameField = document.getElementById('accounting_document_clientName');
  const addressField = document.getElementById('accounting_document_clientAddress');
  const contactField = document.getElementById('accounting_document_clientContact');

  clientSelect.addEventListener('change', function () {
    const option = clientSelect.options[clientSelect.selectedIndex];
    const isClientSelected = option && option.value !== '';

    if (nameField) {
      nameField.value = isClientSelected ? option.text : '';
    }
    if (addressField) {
      addressField.value = isClientSelected ? option.dataset.address || '' : '';
    }
    if (contactField) {
      contactField.value = isClientSelected ? option.dataset.contact || '' : '';
    }
  });
}
