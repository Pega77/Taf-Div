document.addEventListener('DOMContentLoaded', function () {
  const selectButton = document.getElementById('select-all-students');
  const clearButton = document.getElementById('clear-all-students');
  const checkboxes = document.querySelectorAll('input[name="student_ids[]"]');

  if (selectButton) {
    selectButton.addEventListener('click', function () {
      checkboxes.forEach(function (checkbox) {
        checkbox.checked = true;
      });
    });
  }

  if (clearButton) {
    clearButton.addEventListener('click', function () {
      checkboxes.forEach(function (checkbox) {
        checkbox.checked = false;
      });
    });
  }
});
