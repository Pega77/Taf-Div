document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.confirm-action').forEach(function (button) {
    button.addEventListener('click', function (event) {
      if (!window.confirm('האם לבצע את הפעולה?')) {
        event.preventDefault();
      }
    });
  });
});
