/**
 * ==================================================
 *  AGENTE SOCIALIZADOR — v3
 * ==================================================
 * SiGeRU no solo gestiona residuos: busca fomentar comportamientos
 * responsables en la comunidad. Cada mensaje se fundamenta en un
 * doble marco (ver Anexo "Marco Teórico y Transversalización con
 * Sociología y Derechos Humanos"): por un lado, cinco autores de la
 * sociología clásica y contemporánea (Durkheim, Bourdieu, Goffman,
 * Beck, Bauman); por otro, tres derechos humanos de tercera
 * generación (ambiente sano, patrimonio cultural y natural,
 * desarrollo sostenible / generaciones futuras).
 *
 * Este archivo ofrece dos formas de mostrar esos mensajes, ambas
 * alimentadas por la misma lista:
 *
 * 1) Widget flotante (landing / páginas públicas):
 *    <div id="agente-socializador"></div>
 *    Se arma solo si ese contenedor existe en la página. Es un
 *    único elemento con position:fixed que nunca se saca del DOM:
 *    entre el estado "tarjeta abierta" y "botón cerrado" solo se
 *    alterna una clase, así el widget nunca reserva espacio de más
 *    ni empuja el layout de la página.
 *
 * 2) Banner integrado (panel interno):
 *    window.SiGeRUAgente.insertar('id-del-contenedor', 'idMensaje')
 *    Inserta el mensaje como parte del contenido de esa sección
 *    (no flota por encima de nada). Se usa, por ejemplo, arriba del
 *    dashboard y del formulario de "Reportar Incidencia".
 *
 * Cada mensaje incluye "Ver más sobre esto", que lleva a la sección
 * correspondiente de impacto-social.html (misma pestaña/ancla, sin
 * desviar al usuario a una página externa).
 */

(function () {
  var MENSAJES = {
    comunidad: {
      icono: "🏘️",
      color: "#6A994E",
      etiqueta: "Comunidad y responsabilidad colectiva",
      texto:
        "Una incidencia que parece pequeña puede afectar a todo tu barrio. Reportarla ayuda a que el problema se identifique y se resuelva a tiempo.",
      enlace: "Ver más sobre responsabilidad comunitaria",
    },
    habitos: {
      icono: "♻️",
      color: "#A7C957",
      etiqueta: "Hábitos y reciclaje",
      texto:
        "Separar tus residuos es un hábito. Repetido por muchas personas, ese pequeño gesto puede transformar la forma en que toda una comunidad los gestiona.",
      enlace: "Ver más sobre hábitos y reciclaje",
    },
    convivencia: {
      icono: "👥",
      color: "#6A994E",
      etiqueta: "Comportamiento y convivencia",
      texto:
        "Lo que hacemos con nuestros residuos también comunica cómo convivimos con los demás. Cuidar los espacios comunes construye un entorno mejor para todos.",
      enlace: "Ver más sobre comportamiento y convivencia",
    },
    riesgos: {
      icono: "⚠️",
      color: "#B08968",
      etiqueta: "Riesgos ambientales",
      texto:
        "Los residuos mal gestionados no desaparecen: la contaminación y la acumulación pueden terminar afectando el entorno y la calidad de vida de todos.",
      enlace: "Ver más sobre riesgos ambientales",
    },
    consumo: {
      icono: "🛒",
      color: "#386641",
      etiqueta: "Consumo y generación de residuos",
      texto:
        "¿Alguna vez pensaste cuántos residuos genera lo que consumís? La cantidad que producimos está directamente ligada a nuestros hábitos de consumo.",
      enlace: "Ver más sobre consumo y residuos",
    },
    ambiente: {
      icono: "🌎",
      color: "#2A9D8F",
      etiqueta: "Derecho a un ambiente sano",
      texto:
        "Vivir en un ambiente sano no es un lujo, es un derecho de todos. Cada contenedor bien gestionado es un paso hacia ese derecho.",
      enlace: "Ver más sobre este derecho",
    },
    patrimonio: {
      icono: "🏛️",
      color: "#8B5E34",
      etiqueta: "Derecho al patrimonio cultural y natural",
      texto:
        "Las plazas, calles y costas que compartimos también son patrimonio de todos. Cuidarlas es una forma de cuidar nuestra identidad colectiva.",
      enlace: "Ver más sobre este derecho",
    },
    futuras: {
      icono: "🌱",
      color: "#457B9D",
      etiqueta: "Desarrollo sostenible y generaciones futuras",
      texto:
        "Lo que hacemos hoy con nuestros residuos también le pertenece a las próximas generaciones. Gestionar bien hoy es cuidar el mañana.",
      enlace: "Ver más sobre este derecho",
    },
  };
  var ORDEN = [
    "comunidad",
    "habitos",
    "convivencia",
    "riesgos",
    "consumo",
    "ambiente",
    "patrimonio",
    "futuras",
  ];
  var ROTACION_MS = 12000;
  var CLAVE_CERRADO = "sigeruAgenteCerrado";

  function href(id) {
    return "impacto-social.html#" + id;
  }

  // ---------- 1) Widget flotante ----------
  function iniciarFlotante(contenedor) {
    var indice = Math.floor(Math.random() * ORDEN.length);
    var timer = null;
    var cerrado = sessionStorage.getItem(CLAVE_CERRADO) === "1";

    contenedor.className =
      "agente-flotante" + (cerrado ? " agente-flotante--cerrado" : "");
    contenedor.innerHTML =
      '<button type="button" class="agente-flotante__lanzador" aria-label="Mostrar consejo de SiGeRU">' +
      '  <span class="agente-flotante__lanzador-icono">🌱</span>' +
      "</button>" +
      '<div class="agente-flotante__tarjeta" role="status">' +
      '  <div class="agente-flotante__progreso"><span id="agente-progreso-barra"></span></div>' +
      '  <button type="button" class="agente-flotante__cerrar" aria-label="Cerrar">×</button>' +
      '  <div class="agente-flotante__fila">' +
      '    <div class="agente-flotante__icono" id="agente-icono"></div>' +
      '    <div class="agente-flotante__cuerpo">' +
      '      <span class="agente-flotante__etiqueta" id="agente-etiqueta"></span>' +
      '      <p class="agente-flotante__mensaje" id="agente-mensaje"></p>' +
      '      <a class="agente-flotante__link" id="agente-link" href="#" target="_blank" rel="noopener">Ver más →</a>' +
      "    </div>" +
      "  </div>" +
      "</div>";

    var elBarra = contenedor.querySelector("#agente-progreso-barra");
    var elIcono = contenedor.querySelector("#agente-icono");
    var elEtiqueta = contenedor.querySelector("#agente-etiqueta");
    var elMensaje = contenedor.querySelector("#agente-mensaje");
    var elLink = contenedor.querySelector("#agente-link");
    var elTarjeta = contenedor.querySelector(".agente-flotante__tarjeta");

    function pintar() {
      var id = ORDEN[indice];
      var m = MENSAJES[id];
      elIcono.textContent = m.icono;
      elIcono.style.background = m.color;
      elEtiqueta.textContent = m.etiqueta;
      elMensaje.textContent = m.texto;
      elLink.textContent = m.enlace + " →";
      elLink.href = href(id);
    }

    function reiniciarBarra() {
      elBarra.style.animation = "none";
      void elBarra.offsetWidth;
      elBarra.style.animation =
        "agenteProgreso " + ROTACION_MS + "ms linear forwards";
    }

    function siguiente() {
      elTarjeta.classList.add("agente-flotante__tarjeta--saliendo");
      setTimeout(function () {
        indice = (indice + 1) % ORDEN.length;
        pintar();
        elTarjeta.classList.remove("agente-flotante__tarjeta--saliendo");
        reiniciarBarra();
      }, 260);
    }

    function play() {
      if (timer) clearInterval(timer);
      reiniciarBarra();
      timer = setInterval(siguiente, ROTACION_MS);
    }
    function pausar() {
      if (timer) clearInterval(timer);
      elBarra.style.animationPlayState = "paused";
    }
    function resumir() {
      elBarra.style.animationPlayState = "running";
      if (timer) clearInterval(timer);
      timer = setInterval(siguiente, ROTACION_MS);
    }

    elTarjeta.addEventListener("mouseenter", pausar);
    elTarjeta.addEventListener("mouseleave", resumir);

    contenedor
      .querySelector(".agente-flotante__cerrar")
      .addEventListener("click", function () {
        if (timer) clearInterval(timer);
        sessionStorage.setItem(CLAVE_CERRADO, "1");
        contenedor.classList.add("agente-flotante--cerrado");
      });

    contenedor
      .querySelector(".agente-flotante__lanzador")
      .addEventListener("click", function () {
        sessionStorage.removeItem(CLAVE_CERRADO);
        contenedor.classList.remove("agente-flotante--cerrado");
        play();
      });

    pintar();
    if (!cerrado) play();
  }

  // ---------- 2) Banner integrado dentro de una vista del panel ----------
  function insertar(idContenedor, idMensaje, opciones) {
    var contenedor = document.getElementById(idContenedor);
    if (!contenedor) return;
    var m = MENSAJES[idMensaje];
    if (!m) return;

    var claveDescarte = "sigeruAgenteInlineCerrado:" + idMensaje;
    if (
      !(opciones && opciones.repetible) &&
      localStorage.getItem(claveDescarte) === "1"
    ) {
      contenedor.innerHTML = "";
      return;
    }

    contenedor.innerHTML =
      '<div class="agente-inline" style="--agente-color:' +
      m.color +
      '">' +
      '  <div class="agente-inline__icono">' +
      m.icono +
      "</div>" +
      '  <div class="agente-inline__cuerpo">' +
      '    <span class="agente-inline__etiqueta">' +
      m.etiqueta +
      "</span>" +
      "    <p>" +
      m.texto +
      "</p>" +
      '    <a href="' +
      href(idMensaje) +
      '" target="_blank" rel="noopener">' +
      m.enlace +
      " →</a>" +
      "  </div>" +
      '  <button type="button" class="agente-inline__cerrar" aria-label="Cerrar consejo">×</button>' +
      "</div>";

    var tarjeta = contenedor.querySelector(".agente-inline");
    // Fuerza el reflow para que la animación de entrada se dispare siempre,
    // incluso si la vista se vuelve a renderizar con el mismo contenido.
    void tarjeta.offsetWidth;
    tarjeta.classList.add("agente-inline--visible");

    contenedor
      .querySelector(".agente-inline__cerrar")
      .addEventListener("click", function () {
        tarjeta.classList.add("agente-inline--saliendo");
        localStorage.setItem(claveDescarte, "1");
        setTimeout(function () {
          contenedor.innerHTML = "";
        }, 320);
      });
  }

  function iniciar() {
    var flotante = document.getElementById("agente-socializador");
    if (flotante) iniciarFlotante(flotante);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", iniciar);
  } else {
    iniciar();
  }

  window.SiGeRUAgente = {
    insertar: insertar,
    MENSAJES: MENSAJES,
    ORDEN: ORDEN,
  };
})();
