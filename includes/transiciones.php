<link rel="stylesheet" href="css/transiciones.css">
<script>
(function () {
    var actual = location.pathname + location.search;
    var historial = [];
    try {
        historial = JSON.parse(sessionStorage.getItem("vista-historial") || "[]");
        if (!Array.isArray(historial)) {
            historial = [];
        }
    } catch (error) {
        historial = [];
    }

    var navegacion = performance.getEntriesByType("navigation")[0];
    var recarga = navegacion && navegacion.type === "reload";
    var atras = false;

    if (!recarga && historial.length >= 2 && historial[historial.length - 2] === actual) {
        historial.pop();
        atras = true;
    } else if (!recarga && !(historial.length && historial[historial.length - 1] === actual)) {
        historial.push(actual);
    }

    try {
        sessionStorage.setItem("vista-historial", JSON.stringify(historial.slice(-30)));
    } catch (error) {}

    if (atras) {
        document.documentElement.classList.add("vista-atras");
    }
})();
</script>
<script src="js/transiciones.js" defer></script>
