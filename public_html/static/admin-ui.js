document.addEventListener('click', (event) => {
  const button = event.target.closest('[data-bs-toggle="collapse"]');
  if (!button) return;
  const menu = document.querySelector(button.getAttribute('data-bs-target'));
  if (!menu) return;
  const expanded = menu.classList.toggle('show');
  button.setAttribute('aria-expanded', String(expanded));
});

document.addEventListener('submit', (event) => {
  const message = event.target.getAttribute('data-confirm');
  if (message && !window.confirm(message)) event.preventDefault();
});
