// Evaluación (HU-07, CU-06): muestra una pregunta a la vez con indicador de avance.
// Sin JavaScript se ven todas las preguntas y el formulario funciona igual.
(function () {
  'use strict';

  const formulario = document.getElementById('formulario-evaluacion');
  if (!formulario) return;
  const preguntas = Array.from(formulario.querySelectorAll('.pregunta'));
  const anterior = document.getElementById('eval-anterior');
  const siguiente = document.getElementById('eval-siguiente');
  const enviar = document.getElementById('eval-enviar');
  const contador = document.getElementById('contador-evaluacion');
  const barra = document.getElementById('barra-evaluacion');
  const mapa = Array.from(document.querySelectorAll('#mapa-preguntas [data-ir]'));
  const primeraFaltante = preguntas.findIndex((p) => p.classList.contains('pregunta-faltante'));
  let actual = primeraFaltante >= 0 ? primeraFaltante : 0;

  function respondida(p) {
    return Boolean(p.querySelector('input[type=radio]:checked'));
  }

  function actualizar() {
    const n = preguntas.filter(respondida).length;
    contador.textContent = 'Pregunta ' + (actual + 1) + ' de ' + preguntas.length + ' · ' + n + ' respondidas';
    barra.value = n;
    preguntas.forEach((p, i) => { p.hidden = i !== actual; });
    mapa.forEach((enlace, i) => {
      const ok = respondida(preguntas[i]);
      enlace.classList.toggle('btn-secondary', ok);
      enlace.classList.toggle('btn-outline-secondary', !ok && !preguntas[i].classList.contains('pregunta-faltante'));
      enlace.classList.toggle('active', i === actual);
      if (i === actual) enlace.setAttribute('aria-current', 'step'); else enlace.removeAttribute('aria-current');
    });
    anterior.disabled = actual === 0;
    siguiente.classList.toggle('d-none', actual >= preguntas.length - 1);
    enviar.classList.toggle('d-none', actual < preguntas.length - 1 && n < preguntas.length);
  }

  function ir(indice) {
    actual = Math.min(Math.max(indice, 0), preguntas.length - 1);
    actualizar();
    const leyenda = preguntas[actual].querySelector('legend');
    if (leyenda) { leyenda.tabIndex = -1; leyenda.focus(); }
  }

  anterior.classList.remove('d-none');
  siguiente.classList.remove('d-none');
  anterior.addEventListener('click', () => ir(actual - 1));
  siguiente.addEventListener('click', () => ir(actual + 1));
  mapa.forEach((enlace) => enlace.addEventListener('click', (evento) => {
    evento.preventDefault();
    ir(Number(enlace.dataset.ir));
  }));
  formulario.addEventListener('change', (evento) => {
    const p = evento.target.closest('.pregunta');
    if (p && respondida(p)) {
      p.classList.remove('pregunta-faltante');
      const alerta = p.querySelector('[role=alert]');
      if (alerta) alerta.remove();
      mapa[Number(p.dataset.indice)].classList.remove('btn-outline-danger');
    }
    actualizar();
  });
  formulario.addEventListener('submit', (evento) => {
    // CU-06, flujo 5a: si falta alguna, se señala y no se envía.
    const faltante = preguntas.findIndex((p) => !respondida(p));
    if (faltante >= 0) {
      evento.preventDefault();
      preguntas.forEach((p, i) => {
        if (!respondida(p)) { p.classList.add('pregunta-faltante'); mapa[i].classList.add('btn-outline-danger'); }
      });
      ir(faltante);
      contador.textContent = 'Faltan ' + preguntas.filter((p) => !respondida(p)).length + ' preguntas por responder.';
      return;
    }
    enviar.disabled = true;
    enviar.textContent = 'Enviando…';
  });
  actualizar();
})();
