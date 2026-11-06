const patronCedula = /^(?:\d{8}|\d{10})$/;
const patronCorreo = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

const mensajes = {
    cedulaVacia: 'Ingresa tu número de cédula.',
    cedulaLongitud: 'La cédula debe tener exactamente 8 o 10 dígitos.',
    cedulaFormato: 'La cédula solo puede contener números, sin espacios ni letras.',
    correoVacio: 'Ingresa tu correo electrónico.',
    correoInvalido: 'Ingresa un correo electrónico válido.',
    passwordVacia: 'Ingresa tu contraseña.',
};

function mostrarError(campo, entrada, error, mensaje) {
    if (!campo || !entrada || !error) {
        return;
    }

    campo.classList.add('campo--error');
    entrada.setAttribute('aria-invalid', 'true');
    error.textContent = mensaje;
    error.hidden = false;
}

function limpiarError(campo, entrada, error) {
    if (!campo || !entrada || !error) {
        return;
    }

    campo.classList.remove('campo--error');
    entrada.removeAttribute('aria-invalid');
    error.textContent = '';
    error.hidden = true;
}

function configurarCedula(formulario) {
    const campo = formulario.querySelector('[data-campo="cedula"]');
    const entrada = formulario.querySelector('[data-cedula]');
    const error = formulario.querySelector('[data-error-cedula]');

    if (!campo || !entrada || !error) {
        return null;
    }

    const validar = () => {
        const valor = entrada.value.trim();

        if (!valor) {
            mostrarError(campo, entrada, error, mensajes.cedulaVacia);
            return false;
        }

        if (!/^\d+$/.test(valor)) {
            mostrarError(campo, entrada, error, mensajes.cedulaFormato);
            return false;
        }

        if (!patronCedula.test(valor)) {
            mostrarError(campo, entrada, error, mensajes.cedulaLongitud);
            return false;
        }

        limpiarError(campo, entrada, error);
        return true;
    };

    entrada.addEventListener('input', () => {
        const original = entrada.value;
        const soloNumeros = original.replace(/\D/g, '').slice(0, 10);

        if (original !== soloNumeros) {
            entrada.value = soloNumeros;
            mostrarError(campo, entrada, error, mensajes.cedulaFormato);
            return;
        }

        if (!soloNumeros || patronCedula.test(soloNumeros)) {
            limpiarError(campo, entrada, error);
        }
    });

    entrada.addEventListener('blur', () => {
        if (entrada.value.trim() !== '') {
            validar();
        }
    });

    return validar;
}

function configurarAdministrativo(formulario) {
    const campoCorreo = formulario.querySelector('[data-campo="correo"]');
    const correo = formulario.querySelector('[data-correo-admin]');
    const errorCorreo = formulario.querySelector('[data-error-correo]');
    const campoPassword = formulario.querySelector('[data-campo="password"]');
    const password = formulario.querySelector('[data-password]');
    const errorPassword = formulario.querySelector('[data-error-password]');

    if (!campoCorreo || !correo || !errorCorreo || !campoPassword || !password || !errorPassword) {
        return null;
    }

    const validar = () => {
        let valido = true;

        if (!correo.value.trim()) {
            mostrarError(campoCorreo, correo, errorCorreo, mensajes.correoVacio);
            valido = false;
        } else if (!patronCorreo.test(correo.value.trim())) {
            mostrarError(campoCorreo, correo, errorCorreo, mensajes.correoInvalido);
            valido = false;
        } else {
            limpiarError(campoCorreo, correo, errorCorreo);
        }

        if (!password.value) {
            mostrarError(campoPassword, password, errorPassword, mensajes.passwordVacia);
            valido = false;
        } else {
            limpiarError(campoPassword, password, errorPassword);
        }

        return valido;
    };

    correo.addEventListener('input', () => {
        if (correo.value.trim()) {
            limpiarError(campoCorreo, correo, errorCorreo);
        }
    });

    password.addEventListener('input', () => {
        if (password.value) {
            limpiarError(campoPassword, password, errorPassword);
        }
    });

    correo.addEventListener('blur', () => {
        if (correo.value.trim()) {
            validar();
        }
    });

    return validar;
}

document.querySelectorAll('[data-formulario-acceso]').forEach((formulario) => {
    const tipo = formulario.dataset.formularioAcceso;
    const validar = tipo === 'paciente' ? configurarCedula(formulario) : configurarAdministrativo(formulario);

    if (!validar) {
        return;
    }

    formulario.addEventListener('submit', (evento) => {
        if (!validar()) {
            evento.preventDefault();

            const primerCampoConError = formulario.querySelector('.campo--error .campo__entrada');
            primerCampoConError?.focus();
        }
    });
});

document.querySelectorAll('[data-alternar-password]').forEach((boton) => {
    const entrada = document.getElementById(boton.getAttribute('aria-controls'));

    if (!entrada) {
        return;
    }

    boton.addEventListener('click', () => {
        const mostrar = entrada.type === 'password';

        entrada.type = mostrar ? 'text' : 'password';
        boton.setAttribute('aria-pressed', String(mostrar));
        boton.setAttribute('aria-label', mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña');
    });
});
