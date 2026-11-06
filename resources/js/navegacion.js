const boton = document.querySelector('[data-menu-toggle]');
const navegacion = document.querySelector('[data-nav]');
const movil = window.matchMedia('(max-width: 900px)');

if (boton && navegacion) {
    boton.hidden = false;
    navegacion.dataset.mejorado = 'true';
    const establecerMenu = (abierto, devolverFoco = false) => {
        navegacion.dataset.abierto = String(abierto);
        boton.setAttribute('aria-expanded', String(abierto));
        boton.setAttribute('aria-label', abierto ? 'Cerrar menú de navegación' : 'Abrir menú de navegación');
        if (devolverFoco) boton.focus();
    };
    boton.addEventListener('click', () => establecerMenu(navegacion.dataset.abierto !== 'true'));
    navegacion.addEventListener('click', (evento) => {
        if (evento.target.closest('a') && movil.matches) establecerMenu(false, true);
    });
    document.addEventListener('keydown', (evento) => {
        if (evento.key === 'Escape' && navegacion.dataset.abierto === 'true') establecerMenu(false, true);
    });
    document.addEventListener('click', (evento) => {
        if (!navegacion.contains(evento.target) && !boton.contains(evento.target)) establecerMenu(false);
    });
    movil.addEventListener('change', () => establecerMenu(false));
}
