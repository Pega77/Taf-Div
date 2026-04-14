document.addEventListener('DOMContentLoaded', function () {
  const idInput = document.querySelector('input[name="national_id"]');
  if (!idInput) return;
  idInput.addEventListener('input', function () {
    this.value = this.value.replace(/[^0-9]/g, '').slice(0, 9);
  });
});
