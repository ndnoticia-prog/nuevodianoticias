import '../scss/app.scss';

const STORAGE_KEY = 'nd-theme-color-scheme';

function currentTheme() {
  return document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
}

function applyTheme(theme) {
  if (theme === 'dark') {
    document.documentElement.setAttribute('data-theme', 'dark');
  } else {
    document.documentElement.setAttribute('data-theme', 'light');
  }

  try {
    window.localStorage.setItem(STORAGE_KEY, theme);
  } catch (error) {
    // localStorage puede estar bloqueado (modo privado); el toggle sigue funcionando en memoria.
  }
}

function initThemeToggle() {
  const toggle = document.querySelector('[data-nd-theme-toggle]');

  if (!toggle) {
    return;
  }

  toggle.addEventListener('click', () => {
    applyTheme(currentTheme() === 'dark' ? 'light' : 'dark');
  });
}

document.addEventListener('DOMContentLoaded', initThemeToggle);
