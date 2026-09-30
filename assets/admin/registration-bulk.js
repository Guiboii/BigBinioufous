// "Select all" checkbox and live selection count for the pending registrations page. The bulk form itself works without this script.
const selectAll = document.getElementById('registration-select-all');
const count = document.getElementById('desk-bulk-count');
const form = document.getElementById('registration-bulk-form');

function boxes() {
  return Array.from(document.querySelectorAll('.registration-select'));
}

function update() {
  const all = boxes();
  const checked = all.filter((box) => box.checked).length;

  count.textContent = form.dataset.countLabel.replace('__COUNT__', String(checked));
  selectAll.checked = checked > 0 && checked === all.length;
  selectAll.indeterminate = checked > 0 && checked < all.length;
}

if (selectAll && count && form) {
  selectAll.addEventListener('change', () => {
    boxes().forEach((box) => {
      box.checked = selectAll.checked;
    });
    update();
  });

  document.addEventListener('change', (e) => {
    if (e.target.classList.contains('registration-select')) {
      update();
    }
  });

  update();
}
