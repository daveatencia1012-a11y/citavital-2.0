// Mejora progresiva: sin JavaScript, los enlaces móviles siguen visibles.
const boton = document.querySelector('[data-inicio-menu]');
const navegacion = document.getElementById('navegacion-inicio');
const movil = window.matchMedia('(max-width: 600px)');

if (boton && navegacion) {
    boton.hidden = false;
    navegacion.dataset.menuListo = 'true';

    const abrirMenu = (abierto) => {
        navegacion.dataset.abierto = String(abierto);
        boton.setAttribute('aria-expanded', String(abierto));
        boton.setAttribute('aria-label', abierto ? 'Cerrar menú de navegación' : 'Abrir menú de navegación');
    };

    boton.addEventListener('click', () => {
        abrirMenu(boton.getAttribute('aria-expanded') !== 'true');
    });

    navegacion.addEventListener('click', (evento) => {
        if (evento.target.closest('a')) abrirMenu(false);
    });

    document.addEventListener('keydown', (evento) => {
        if (evento.key === 'Escape' && boton.getAttribute('aria-expanded') === 'true') {
            abrirMenu(false);
            boton.focus();
        }
    });

    document.addEventListener('click', (evento) => {
        if (!navegacion.contains(evento.target) && !boton.contains(evento.target)) {
            abrirMenu(false);
        }
    });

    movil.addEventListener('change', () => abrirMenu(false));
}
