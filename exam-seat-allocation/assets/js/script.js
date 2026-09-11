// Simple live table filter used on list pages (students, halls, exams...)
document.addEventListener('DOMContentLoaded', function () {
  const filterInput = document.getElementById('tableFilter');
  const table = document.getElementById('dataTable');
  if (filterInput && table) {
    filterInput.addEventListener('keyup', function () {
      const q = this.value.toLowerCase();
      table.querySelectorAll('tbody tr').forEach(function (row) {
        row.style.display = row.innerText.toLowerCase().includes(q) ? '' : 'none';
      });
    });
  }

  // Confirm before any delete link/button
  document.querySelectorAll('.confirm-delete').forEach(function (el) {
    el.addEventListener('click', function (e) {
      if (!confirm('Are you sure you want to delete this record?')) {
        e.preventDefault();
      }
    });
  });
});

function printSection() {
  window.print();
}
