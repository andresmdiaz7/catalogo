
// Inicializar Toast
Array.from(document.querySelectorAll('.toast')).forEach(toastNode => new Toast(toastNode));

// Inicializar todos los tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, {
            boundary: document.body
        });
    });
});

// Inicializa todos los dropdowns
document.addEventListener('DOMContentLoaded', function() {
    var dropdowns = document.querySelectorAll('.dropdown-toggle');
    dropdowns.forEach(function(dropdown) {
        new bootstrap.Dropdown(dropdown);
    });
});
    
    
// Copiar texto a la izquierda de los iconos de copiar
const iconosCopiables = document.querySelectorAll('.copiable');
iconosCopiables.forEach(icono => {
    icono.addEventListener('click', () => {
        const texto = icono.previousElementSibling.textContent;
        if (navigator.clipboard && navigator.clipboard.writeText) {
        // Método moderno
        navigator.clipboard.writeText(texto)
            .then(() => cambiarIcono(icono))
            .catch(err => console.error('Error al copiar: ', err));
        } else {
        // Fallback antiguo
        const seleccion = window.getSelection();
        const rango = document.createRange();
        rango.selectNodeContents(icono.previousElementSibling);
        seleccion.removeAllRanges();
        seleccion.addRange(rango);
        document.execCommand('copy');
        seleccion.removeAllRanges();

        cambiarIcono(icono);
        }
    });
});

function cambiarIcono(icono) {
    icono.classList.remove('bi-copy');
    icono.classList.add('bi-clipboard-check-fill');
    setTimeout(() => {
        icono.classList.remove('bi-clipboard-check-fill');
        icono.classList.add('bi-copy');
    }, 1000);
}
    