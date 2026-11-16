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
  const avisoSesion = document.getElementById('aviso-sesion');
  const primeraFaltante = preguntas.findIndex((p) => p.classList.contains('pregunta-faltante'));
  let actual = primeraFaltante >= 0 ? primeraFaltante : 0;

  // Las respuestas se guardan en este navegador mientras se responde. Si la sesión se cierra
  // antes de enviar, al volver a ingresar y abrir la evaluación se recuperan.
  const clave = 'apa-evaluacion-' + formulario.dataset.intento;
  function leerGuardadas() {
    try { return JSON.parse(window.localStorage.getItem(clave) || '{}'); } catch (e) { return {}; }
  }
  function guardar() {
    const elegidas = {};
    formulario.querySelectorAll('input[type=radio]:checked').forEach((r) => { elegidas[r.name] = r.value; });
    try { window.localStorage.setItem(clave, JSON.stringify(elegidas)); } catch (e) { /* almacenamiento no disponible */ }
  }
  try {
    // Se descartan las respuestas guardadas de evaluaciones anteriores.
    Object.keys(window.localStorage).forEach((k) => {
      if (k.indexOf('apa-evaluacion-') === 0 && k !== clave) window.localStorage.removeItem(k);
    });
  } catch (e) { /* almacenamiento no disponible */ }
  const guardadas = leerGuardadas();
  Object.keys(guardadas).forEach((nombre) => {
    const grupo = formulario.querySelectorAll('input[type=radio]');
    if (Array.from(grupo).some((r) => r.name === nombre && r.checked)) return;
    const opcion = Array.from(grupo).find((r) => r.name === nombre && r.value === String(guardadas[nombre]));
    if (opcion) opcion.checked = true;
  });

  // Mientras el estudiante responde se avisa al servidor (como máximo cada dos minutos)
  // para que la sesión no se cierre por inactividad en una evaluación larga.
  let ultimoAviso = Date.now();
  function mantenerSesion() {
    if (!formulario.dataset.sesion || Date.now() - ultimoAviso < 120000) return;
    ultimoAviso = Date.now();
    fetch(formulario.dataset.sesion, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
      .then((respuesta) => {
        if (respuesta.status === 401 && avisoSesion) {
          avisoSesion.textContent = 'Tu sesión terminó. Tus respuestas quedaron guardadas en este navegador: vuelve a iniciar sesión y abre la evaluación para enviarla.';
          avisoSesion.classList.remove('d-none');
        }
      })
      .catch(() => { /* sin conexión: se intentará en la próxima respuesta */ });
  }

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
    guardar();
    mantenerSesion();
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
