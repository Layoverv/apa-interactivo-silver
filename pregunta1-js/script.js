// El conteo corresponde a campeonatos de liga de primera división.
const equipos = [
  { nombre: "FC Barcelona", titulosNacionales: 29, colores: "Azul y grana", estadio: "Spotify Camp Nou", capacidad: 105000, sitioWeb: "https://www.fcbarcelona.es" },
  { nombre: "Real Madrid", titulosNacionales: 36, colores: "Blanco", estadio: "Santiago Bernabéu", capacidad: 83186, sitioWeb: "https://www.realmadrid.com" },
  { nombre: "Manchester United", titulosNacionales: 20, colores: "Rojo, blanco y negro", estadio: "Old Trafford", capacidad: 74310, sitioWeb: "https://www.manutd.com" },
  { nombre: "Liverpool", titulosNacionales: 20, colores: "Rojo", estadio: "Anfield", capacidad: 61276, sitioWeb: "https://www.liverpoolfc.com" },
  { nombre: "Bayern Múnich", titulosNacionales: 34, colores: "Rojo y blanco", estadio: "Allianz Arena", capacidad: 75024, sitioWeb: "https://fcbayern.com" },
  { nombre: "Juventus", titulosNacionales: 36, colores: "Blanco y negro", estadio: "Allianz Stadium", capacidad: 41507, sitioWeb: "https://www.juventus.com" },
  { nombre: "Benfica", titulosNacionales: 38, colores: "Rojo y blanco", estadio: "Estádio da Luz", capacidad: 65647, sitioWeb: "https://www.slbenfica.pt" },
  { nombre: "FC Porto", titulosNacionales: 30, colores: "Azul y blanco", estadio: "Estádio do Dragão", capacidad: 50033, sitioWeb: "https://www.fcporto.pt" },
  { nombre: "Ajax", titulosNacionales: 36, colores: "Blanco y rojo", estadio: "Johan Cruyff Arena", capacidad: 55865, sitioWeb: "https://english.ajax.nl" },
  { nombre: "Celtic", titulosNacionales: 55, colores: "Verde y blanco", estadio: "Celtic Park", capacidad: 60411, sitioWeb: "https://www.celticfc.com" },
  { nombre: "Rangers", titulosNacionales: 55, colores: "Azul, blanco y rojo", estadio: "Ibrox Stadium", capacidad: 50817, sitioWeb: "https://www.rangers.co.uk" },
  { nombre: "Boca Juniors", titulosNacionales: 35, colores: "Azul y oro", estadio: "La Bombonera", capacidad: 54000, sitioWeb: "https://www.bocajuniors.com.ar" },
  { nombre: "River Plate", titulosNacionales: 38, colores: "Blanco y rojo", estadio: "Estadio Monumental", capacidad: 84567, sitioWeb: "https://www.cariverplate.com.ar" },
  { nombre: "Olympiacos", titulosNacionales: 48, colores: "Rojo y blanco", estadio: "Estadio Georgios Karaiskakis", capacidad: 33296, sitioWeb: "https://www.olympiacos.org" },
  { nombre: "Galatasaray", titulosNacionales: 25, colores: "Rojo y amarillo", estadio: "RAMS Park", capacidad: 53800, sitioWeb: "https://www.galatasaray.org" },
];

/** Devuelve los equipos que alcanzan el mínimo de títulos indicado. */
function validarTitulosNacionales(listaEquipos) {
  return listaEquipos.filter((equipo) => equipo.titulosNacionales >= 20);
}

/** Crea objetos nuevos con solo los campos que necesita la interfaz. */
function procesarDatos(equiposSeleccionados) {
  return equiposSeleccionados.map(({ nombre, colores, estadio, capacidad, sitioWeb }) => ({
    nombre,
    colores,
    estadio,
    capacidad,
    sitioWeb,
  }));
}

/** Presenta cada objeto como un elemento numerado y seguro del DOM. */
function mostrarDatos(equiposProcesados) {
  const lista = document.querySelector("#lista-equipos");
  const mensajeInicial = document.querySelector("#mensaje-inicial");
  const elementos = equiposProcesados.map((equipo) => {
    const elemento = document.createElement("li");
    elemento.append(`${equipo.nombre} tiene los colores ${equipo.colores} y su estadio es ${equipo.estadio} con una capacidad de ${equipo.capacidad.toLocaleString("es-CO")} personas. Para más información visita `);

    const enlace = document.createElement("a");
    enlace.href = equipo.sitioWeb;
    enlace.textContent = equipo.sitioWeb;
    enlace.target = "_blank";
    enlace.rel = "noopener noreferrer";
    elemento.append(enlace, ".");
    return elemento;
  });

  lista.replaceChildren(...elementos);
  mensajeInicial.hidden = equiposProcesados.length > 0;
  if (equiposProcesados.length === 0) {
    mensajeInicial.textContent = "No se encontraron equipos que cumplan la condición.";
  }
}

document.querySelector("#boton-procesar").addEventListener("click", () => {
  const equiposValidados = validarTitulosNacionales(equipos);
  const equiposProcesados = procesarDatos(equiposValidados);
  mostrarDatos(equiposProcesados);
});
