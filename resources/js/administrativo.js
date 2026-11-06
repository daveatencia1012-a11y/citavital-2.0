import './pacientes.js';
import './horarios.js';

const body = document.body;
const sidebar = document.querySelector('[data-admin-sidebar]');
const toggle = document.querySelector('[data-admin-menu-toggle]');
const scrim = document.querySelector('[data-admin-scrim]');
const closeButton = document.querySelector('[data-admin-menu-close]');
const mobileQuery = window.matchMedia('(max-width: 900px)');
const shell = document.querySelector('.admin-shell');
let lastFocused = document.activeElement;

function focusableControls() {
    return [...sidebar.querySelectorAll('a[href], button:not(:disabled), [tabindex="0"]')]
        .filter((element) => element.getClientRects().length > 0);
}

function menuIsOpen() {
    return sidebar?.classList.contains('admin-sidebar--abierto') ?? false;
}

function setMenuOpen(open, { restoreFocus = false } = {}) {
    if (!sidebar || !toggle || !scrim) {
        return;
    }

    open = open && mobileQuery.matches;
    sidebar.inert = mobileQuery.matches && !open;
    if (shell) shell.inert = open;
    sidebar.setAttribute('aria-hidden', String(sidebar.inert));
    if (open) {
        sidebar.setAttribute('role', 'dialog');
        sidebar.setAttribute('aria-modal', 'true');
    } else {
        sidebar.removeAttribute('role');
        sidebar.removeAttribute('aria-modal');
    }
    sidebar.classList.toggle('admin-sidebar--abierto', open);
    scrim.hidden = !open;
    toggle.setAttribute('aria-expanded', String(open));
    body.classList.toggle('admin-menu-abierto', open);

    if (open) {
        closeButton?.focus();
    } else if (restoreFocus && mobileQuery.matches) {
        toggle.focus();
    }
}

toggle?.addEventListener('click', () => {
    setMenuOpen(!menuIsOpen(), { restoreFocus: true });
});

scrim?.addEventListener('click', () => {
    setMenuOpen(false, { restoreFocus: true });
});

closeButton?.addEventListener('click', () => {
    setMenuOpen(false, { restoreFocus: true });
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && menuIsOpen()) {
        event.preventDefault();
        setMenuOpen(false, { restoreFocus: true });
    }
    if (event.key === 'Tab' && menuIsOpen()) {
        const controls = focusableControls();
        const first = controls[0];
        const last = controls.at(-1);
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last?.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first?.focus();
        }
    }
});

document.addEventListener('focusin', (event) => {
    lastFocused = event.target;
    if (menuIsOpen() && !sidebar.contains(event.target)) {
        closeButton?.focus();
    }
});

mobileQuery.addEventListener('change', () => {
    // CSS puede retirar el foco antes del evento change al ocultar el menú.
    const hadFocus = sidebar?.contains(document.activeElement)
        || (document.activeElement === body && sidebar?.contains(lastFocused));
    setMenuOpen(false, { restoreFocus: hadFocus });
    if (!mobileQuery.matches && hadFocus) focusableControls()[0]?.focus();
});

sidebar?.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => {
        if (mobileQuery.matches) {
            setMenuOpen(false, { restoreFocus: true });
        }
    });
});

setMenuOpen(false);
