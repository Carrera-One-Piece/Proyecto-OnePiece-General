/*
 * berries.js — pone los puntos de miles mientras se escribe un valor en berries.
 *
 * Uso: <input type="text" inputmode="numeric" data-berries name="Valor">
 * Escribes 10000 y queda 10.000. Solo es visual: el servidor le quita los
 * puntos antes de validar (RecompensaController::sinPuntos).
 */
document.querySelectorAll('input[data-berries]').forEach(function (campo) {
    function conPuntos(texto) {
        var digitos = texto.replace(/\D/g, '').replace(/^0+(?=\d)/, '');
        return digitos.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    campo.addEventListener('input', function () {
        // Cuántos dígitos había antes del cursor, para dejarlo en el mismo sitio.
        var antes = campo.value.slice(0, campo.selectionStart).replace(/\D/g, '').length;

        campo.value = conPuntos(campo.value);

        var pos = 0;
        var vistos = 0;
        while (pos < campo.value.length && vistos < antes) {
            if (/\d/.test(campo.value[pos])) vistos++;
            pos++;
        }
        campo.setSelectionRange(pos, pos);
    });

    // Si el campo ya trae un valor (el actual, o lo que se escribió antes de un error).
    campo.value = conPuntos(campo.value);
});
