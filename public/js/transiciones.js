(function () {
    var vista = document.querySelector(".vista");
    if (!vista) {
        return;
    }

    var reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    var saliendo = false;
    var DURACION_SALIDA = 260;

    function mismaAplicacion(url) {
        return (url.protocol === "http:" || url.protocol === "https:") && url.origin === window.location.origin;
    }

    function esRetroceso(url) {
        var destino = url.pathname + url.search;
        var historial = [];
        try {
            historial = JSON.parse(sessionStorage.getItem("vista-historial") || "[]");
        } catch (error) {
            return false;
        }
        return Array.isArray(historial) && historial.length >= 2 && historial[historial.length - 2] === destino;
    }

    function salirYSeguir(continuar, retroceso) {
        if (reduce || saliendo) {
            continuar();
            return;
        }
        saliendo = true;
        if (retroceso) {
            document.documentElement.classList.add("vista-salida-atras");
        }
        vista.classList.add("vista-saliendo");

        var terminado = false;
        function fin() {
            if (terminado) {
                return;
            }
            terminado = true;
            continuar();
        }

        vista.addEventListener("animationend", function (evento) {
            if (evento.animationName === "vista-salir" || evento.animationName === "vista-salir-atras") {
                fin();
            }
        });
        window.setTimeout(fin, DURACION_SALIDA);
    }

    document.addEventListener("click", function (evento) {
        if (evento.defaultPrevented || evento.button !== 0) {
            return;
        }
        if (evento.metaKey || evento.ctrlKey || evento.shiftKey || evento.altKey) {
            return;
        }

        var enlace = evento.target.closest("a[href]");
        if (!enlace) {
            return;
        }
        if (enlace.target && enlace.target !== "_self") {
            return;
        }
        if (enlace.hasAttribute("download") || enlace.hasAttribute("data-bs-toggle")) {
            return;
        }

        var url;
        try {
            url = new URL(enlace.href, window.location.href);
        } catch (error) {
            return;
        }
        if (!mismaAplicacion(url)) {
            return;
        }
        if (url.pathname === window.location.pathname && url.search === window.location.search) {
            return;
        }

        evento.preventDefault();
        salirYSeguir(function () {
            window.location.href = url.href;
        }, esRetroceso(url));
    });

    document.addEventListener("submit", function (evento) {
        var formulario = evento.target;
        if (!(formulario instanceof HTMLFormElement)) {
            return;
        }
        if (formulario.dataset.vistaLista === "1" || evento.defaultPrevented) {
            return;
        }
        if (formulario.target && formulario.target !== "_self") {
            return;
        }

        var accion = (evento.submitter && evento.submitter.getAttribute("formaction")) || formulario.getAttribute("action") || window.location.href;
        var url;
        try {
            url = new URL(accion, window.location.href);
        } catch (error) {
            return;
        }
        if (!mismaAplicacion(url)) {
            return;
        }

        evento.preventDefault();
        salirYSeguir(function () {
            formulario.dataset.vistaLista = "1";
            if (typeof formulario.requestSubmit === "function") {
                if (evento.submitter) {
                    formulario.requestSubmit(evento.submitter);
                } else {
                    formulario.requestSubmit();
                }
            } else {
                formulario.submit();
            }
        }, esRetroceso(url));
    });

    window.addEventListener("pageshow", function (evento) {
        if (!evento.persisted) {
            return;
        }
        saliendo = false;
        document.documentElement.classList.remove("vista-salida-atras");
        vista.classList.remove("vista-saliendo");
    });
})();
