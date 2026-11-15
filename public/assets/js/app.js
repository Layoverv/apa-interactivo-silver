// Comportamientos comunes. No hay JavaScript en línea: la política de seguridad de
// contenido (CSP) solo permite scripts de este mismo sitio.
(function () {
  'use strict';

  // Confirmación antes de acciones que cambian datos (prevenir errores, Nielsen).
  document.querySelectorAll('form[data-confirmar]').forEach((formulario) => {
    formulario.addEventListener('submit', (evento) => {
      if (!window.confirm(formulario.dataset.confirmar)) {
        evento.preventDefault();
      }
    });
  });
})();
