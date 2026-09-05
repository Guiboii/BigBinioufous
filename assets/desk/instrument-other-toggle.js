// The "which one?" detail field only makes sense when "Autre" is the selected instrument:
// hidden and disabled otherwise, so it can't be filled in (and won't be submitted) alongside a real instrument.
document.querySelectorAll('select[data-instrument-select]').forEach(function (select) {
  var row = select.closest('.profile-field-grid');
  var otherRow = row && row.querySelector('[data-other-instrument-row]');
  var otherInput = otherRow && otherRow.querySelector('input, textarea');
  if (!otherRow || !otherInput) {
    return;
  }

  function sync() {
    var selectedOption = select.options[select.selectedIndex];
    var isOther = !!selectedOption && selectedOption.dataset.other === 'true';
    otherRow.hidden = !isOther;
    otherInput.disabled = !isOther;
  }

  select.addEventListener('change', sync);
  sync();
});
