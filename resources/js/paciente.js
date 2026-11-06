const menuToggle = document.querySelector('[data-paciente-menu-toggle]');
const menu = document.querySelector('[data-paciente-nav]');

function setMenuOpen(open) {
    if (!menu || !menuToggle) {
        return;
    }

    menu.classList.toggle('paciente-nav--abierta', open);
    menuToggle.setAttribute('aria-expanded', String(open));
}

menuToggle?.addEventListener('click', () => {
    const open = menuToggle.getAttribute('aria-expanded') !== 'true';
    setMenuOpen(open);
});

menu?.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => setMenuOpen(false));
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        setMenuOpen(false);
    }
});

window.matchMedia('(min-width: 901px)').addEventListener('change', (event) => {
    if (event.matches) {
        setMenuOpen(false);
    }
});
