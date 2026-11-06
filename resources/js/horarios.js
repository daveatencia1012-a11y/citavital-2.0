const filtroHorario = document.querySelector('[data-horario-filtro]');
const selectorDoctor = document.querySelector('[data-horario-doctor]');

selectorDoctor?.addEventListener('change', () => {
    filtroHorario?.requestSubmit();
});

const generarDesde = document.querySelector('#generar_desde');
const generarHasta = document.querySelector('#generar_hasta');

function sincronizarRangoGeneracion() {
    if (!generarDesde || !generarHasta) {
        return;
    }

    generarHasta.min = generarDesde.value || generarHasta.min;

    if (generarDesde.value && generarHasta.value && generarHasta.value < generarDesde.value) {
        generarHasta.value = generarDesde.value;
    }
}

if (generarDesde && generarHasta) {
    generarDesde.addEventListener('change', sincronizarRangoGeneracion);
    sincronizarRangoGeneracion();
}
