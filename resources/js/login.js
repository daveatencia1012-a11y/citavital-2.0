// Conserva la validación y el comportamiento de los accesos.
import './acceso.js';

// Habilita el envío cuando JavaScript está disponible. El acceso administrativo
// se procesa y valida nuevamente en el servidor.
document.querySelectorAll('[data-login-enviar]').forEach((boton) => {
    boton.disabled = false;
});
