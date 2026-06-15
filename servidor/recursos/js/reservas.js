function initReservasPage() {

    if (window._reservasInit) return;
    window._reservasInit = true;

    document.addEventListener("click", function (e) {

        var btn = e.target.closest(".edit-btn");

        if (!btn) return;

        var page = document.body.dataset.page;

        if (page !== "Señas y Reservas") return;

        setTimeout(function () {

            var map = {
                reserva_cliente_display: "cliente",
                reserva_cancha_display: "cancha",
                reserva_fecha_display: "fecha",
                reserva_horario_display: "horario"
            };

            var key, el;

            for (key in map) {
                el = document.getElementById(key);
                if (el) el.textContent = btn.dataset[map[key]] || "—";
            }

            var form = document.querySelector(".drawer-form");

            if (form && typeof bindDrawerValidation === "function") {
                bindDrawerValidation(form);
            }

        }, 50);

    });

}
