// Prefills the free-text client fields from the selected Client's data-address/data-contact attributes. Those text fields remain the real submitted source, this is just a shortcut, still editable afterwards.
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
