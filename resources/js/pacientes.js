const formulariosConfirmados = document.querySelectorAll('form[data-confirm]');

formulariosConfirmados.forEach((formulario) => {
    formulario.addEventListener('submit', (evento) => {
        const mensaje = formulario.dataset.confirm;

        if (mensaje && !window.confirm(mensaje)) {
            evento.preventDefault();
        }
    });
});

const camposNumericos = document.querySelectorAll('[data-solo-digitos]');

camposNumericos.forEach((campo) => {
    const maximo = Number(campo.dataset.maxDigitos ?? campo.maxLength ?? 10);

    campo.addEventListener('input', () => {
        const soloDigitos = campo.value.replace(/\D+/g, '').slice(0, maximo);

        if (campo.value !== soloDigitos) {
            campo.value = soloDigitos;
        }
    });
});

const fechaNacimiento = document.querySelector('[data-fecha-nacimiento]');
const salidaEdad = document.querySelector('[data-edad-paciente]');

function calcularEdad() {
    if (!fechaNacimiento || !salidaEdad) {
        return;
    }

    if (!fechaNacimiento.value) {
        salidaEdad.textContent = 'La edad se calculará al elegir la fecha.';
        return;
    }

    const [anio, mes, dia] = fechaNacimiento.value.split('-').map(Number);
    const nacimiento = new Date(anio, mes - 1, dia);
    const hoy = new Date();

    if (Number.isNaN(nacimiento.getTime())) {
        salidaEdad.textContent = 'La edad se calculará al elegir la fecha.';
        return;
    }

    hoy.setHours(0, 0, 0, 0);

    let edad = hoy.getFullYear() - nacimiento.getFullYear();
    const diferenciaMes = hoy.getMonth() - nacimiento.getMonth();

    if (diferenciaMes < 0 || (diferenciaMes === 0 && hoy.getDate() < nacimiento.getDate())) {
        edad--;
    }

    salidaEdad.textContent = edad >= 0
        ? `Edad calculada: ${edad} ${edad === 1 ? 'año' : 'años'}.`
        : 'La fecha de nacimiento no puede estar en el futuro.';
}

if (fechaNacimiento && salidaEdad) {
    fechaNacimiento.addEventListener('change', calcularEdad);
    fechaNacimiento.addEventListener('input', calcularEdad);

    if (fechaNacimiento.value) {
        calcularEdad();
    }
}
