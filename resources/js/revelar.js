const prefiereMenosMovimiento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const elementosRevelables = document.querySelectorAll('[data-revelar]');

if (prefiereMenosMovimiento || !('IntersectionObserver' in window)) {
    elementosRevelables.forEach((elemento) => elemento.classList.add('es-visible'));
} else if (elementosRevelables.length > 0) {
    document.documentElement.classList.add('js-revelar');

    const observador = new IntersectionObserver(
        (entradas) => {
            entradas.forEach((entrada) => {
                if (entrada.isIntersecting) {
                    entrada.target.classList.add('es-visible');
                    observador.unobserve(entrada.target);
                }
            });
        },
        {
            threshold: 0.12,
            rootMargin: '0px 0px -8% 0px',
        },
    );

    elementosRevelables.forEach((elemento) => observador.observe(elemento));
}
