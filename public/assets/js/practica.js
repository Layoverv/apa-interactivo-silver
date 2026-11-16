// Práctica guiada (HU-05, HU-06, CU-05): un ejercicio a la vez; la respuesta se envía
// al servidor sin recargar la página y se muestra la retroalimentación que devuelve.
(function () {
  'use strict';

  const fuente = document.getElementById('datos-practica');
  const contenedor = document.querySelector('[data-practica] .card-body');
  if (!fuente || !contenedor) return;

  const datos = JSON.parse(fuente.textContent);
  const ejercicios = datos.ejercicios;
  const botonAnterior = document.getElementById('anterior');
  const botonSiguiente = document.getElementById('siguiente');
  const contador = document.getElementById('contador');
  const barra = document.getElementById('barra');
  let actual = Math.max(0, ejercicios.findIndex((e) => !e.resultado));
  if (ejercicios.every((e) => e.resultado)) actual = 0;
  let terminado = ejercicios.length > 0 && ejercicios.every((e) => e.resultado);

  function crear(etiqueta, clase, texto) {
    const el = document.createElement(etiqueta);
    if (clase) el.className = clase;
    if (texto !== undefined) el.textContent = texto;
    return el;
  }

  function actualizarAvance() {
    const respondidas = ejercicios.filter((e) => e.resultado).length;
    const correctas = ejercicios.filter((e) => e.resultado && e.resultado.es_correcta).length;
    contador.textContent = 'Ejercicio ' + (actual + 1) + ' de ' + ejercicios.length + ' · ' + correctas + ' correctas de ' + respondidas + ' respondidas';
    barra.value = respondidas;
    botonAnterior.disabled = actual === 0;
    botonSiguiente.disabled = actual >= ejercicios.length - 1;
  }

  function retroalimentacion(ejercicio) {
    const r = ejercicio.resultado;
    const caja = crear('div', 'retroalimentacion ' + (r.es_correcta ? 'retro-correcta' : 'retro-incorrecta'));
    caja.setAttribute('role', 'status');
    caja.tabIndex = -1;
    caja.appendChild(crear('p', 'retro-titulo', (r.es_correcta ? '✓ Correcto' : '✗ Incorrecto')));
    if (!r.es_correcta) {
      const correcta = crear('p', 'mb-2');
      correcta.appendChild(crear('strong', '', 'Respuesta correcta: '));
      correcta.appendChild(document.createTextNode(r.opcion_correcta));
      caja.appendChild(correcta);
    }
    caja.appendChild(crear('p', 'mb-0', r.explicacion));
    return caja;
  }

  function mostrar() {
    const ejercicio = ejercicios[actual];
    contenedor.replaceChildren();
    if (!ejercicio) {
      contenedor.appendChild(crear('p', 'mb-0', 'No hay ejercicios en esta práctica.'));
      return;
    }
    const etiquetas = crear('div', 'd-flex flex-wrap gap-2 mb-3');
    [ejercicio.tipo_texto, ejercicio.nivel, ejercicio.tipo_error].forEach((t) => etiquetas.appendChild(crear('span', 'badge text-bg-light', t)));
    contenedor.appendChild(etiquetas);

    const formulario = crear('form', 'formulario-ejercicio');
    formulario.noValidate = true;
    const grupo = crear('fieldset');
    const leyenda = crear('legend', 'enunciado', (actual + 1) + '. ' + ejercicio.enunciado);
    grupo.appendChild(leyenda);
    const r = ejercicio.resultado;
    ejercicio.opciones.forEach((opcion, i) => {
      const id = 'op-' + opcion.id;
      const fila = crear('div', 'opcion');
      const input = crear('input', 'form-check-input');
      input.type = 'radio';
      input.name = 'opcion';
      input.id = id;
      input.value = opcion.id;
      input.disabled = Boolean(r);
      if (r && r.opcion_id === opcion.id) input.checked = true;
      if (ejercicio.seleccion === opcion.id) input.checked = true;
      input.addEventListener('change', () => { ejercicio.seleccion = opcion.id; });
      const etiqueta = crear('label', 'form-check-label');
      etiqueta.htmlFor = id;
      etiqueta.appendChild(crear('b', 'me-2', String.fromCharCode(65 + i) + '.'));
      etiqueta.appendChild(document.createTextNode(opcion.texto));
      if (r) {
        if (opcion.id === r.opcion_correcta_id) {
          fila.classList.add('opcion-correcta');
          etiqueta.appendChild(crear('span', 'opcion-marca', ' ✓ Respuesta correcta'));
        } else if (opcion.id === r.opcion_id) {
          fila.classList.add('opcion-incorrecta');
          etiqueta.appendChild(crear('span', 'opcion-marca', ' ✗ Tu respuesta'));
        }
      }
      fila.appendChild(input);
      fila.appendChild(etiqueta);
      grupo.appendChild(fila);
    });
    formulario.appendChild(grupo);
    const aviso = crear('p', 'aviso text-danger small mt-2 mb-0');
    aviso.setAttribute('role', 'alert');
    formulario.appendChild(aviso);

    if (r) {
      formulario.appendChild(retroalimentacion(ejercicio));
    } else {
      const boton = crear('button', 'btn btn-primary mt-3', 'Responder');
      boton.type = 'submit';
      formulario.appendChild(boton);
      formulario.addEventListener('submit', (evento) => {
        evento.preventDefault();
        responder(ejercicio, boton, aviso);
      });
    }
    contenedor.appendChild(formulario);

    if (terminado) {
      const final = crear('div', 'alert alert-success mt-4 mb-0 d-flex flex-wrap gap-3 align-items-center');
      final.appendChild(crear('span', '', 'Terminaste todos los ejercicios de esta práctica.'));
      const enlace = crear('a', 'btn btn-success btn-sm', 'Ver mis aciertos');
      enlace.href = datos.resumen;
      final.appendChild(enlace);
      contenedor.appendChild(final);
    }
    actualizarAvance();
  }

  async function responder(ejercicio, boton, aviso) {
    if (!ejercicio.seleccion) {
      // CU-05, flujo 3a: pide seleccionar una opción.
      aviso.textContent = 'Selecciona una opción antes de responder.';
      return;
    }
    aviso.textContent = '';
    boton.disabled = true;
    boton.textContent = 'Verificando…';
    try {
      const respuesta = await fetch(datos.endpoint, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-Token': datos.token },
        body: JSON.stringify({ ejercicio_id: ejercicio.id, opcion_id: ejercicio.seleccion }),
      });
      const cuerpo = await respuesta.json().catch(() => ({}));
      if (!respuesta.ok) {
        if (respuesta.status === 401) {
          aviso.textContent = 'Tu sesión terminó. Vuelve a iniciar sesión para continuar.';
        } else {
          aviso.textContent = cuerpo.error || 'No fue posible verificar la respuesta. Inténtalo de nuevo.';
        }
        boton.disabled = false;
        boton.textContent = 'Responder';
        return;
      }
      ejercicio.resultado = {
        es_correcta: cuerpo.es_correcta,
        opcion_id: ejercicio.seleccion,
        opcion_correcta_id: cuerpo.opcion_correcta_id,
        opcion_correcta: cuerpo.opcion_correcta,
        explicacion: cuerpo.explicacion,
      };
      terminado = cuerpo.terminado;
      mostrar();
      const caja = contenedor.querySelector('.retroalimentacion');
      if (caja) caja.focus();
    } catch (error) {
      // CU-05, flujo 4a: sin conexión se conserva la selección y se permite reintentar.
      aviso.textContent = 'No hay conexión con el servidor. Tu selección se conservó; vuelve a pulsar Responder.';
      boton.disabled = false;
      boton.textContent = 'Responder';
    }
  }

  botonAnterior.addEventListener('click', () => { if (actual > 0) { actual--; mostrar(); } });
  botonSiguiente.addEventListener('click', () => { if (actual < ejercicios.length - 1) { actual++; mostrar(); } });
  mostrar();
})();
