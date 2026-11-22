document
  .querySelectorAll('[data-toast]')
  .forEach((toast) => setTimeout(() => toast.remove(), 4200));
document.querySelectorAll('input[type=file]').forEach((input) =>
  input.addEventListener('change', () => {
    const file = input.files?.[0];
    if (file && file.size > 5 * 1024 * 1024) {
      alert('Ukuran file maksimal 5 MB.');
      input.value = '';
    }
  }),
);
