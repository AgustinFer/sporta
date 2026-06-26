/* ==========================================
TURNOS - Grilla Horaria
========================================== */

const HORA_INICIO = 8;
const HORA_FIN = 23;

var turnosCanchas = [];
var turnosClientes = [];
var turnosReservas = [];
var turnosNuevoClienteActivo = false;
var turnosNuevoClienteReserva = null;
var turnosOnCancel = null;

function turnosReiniciarBotones() {
    var formReserva = document.getElementById('formReserva');
    var btnPendiente = document.getElementById('btnPendiente');
    var btnConfirmada = document.getElementById('btnConfirmada');
    var btnCancelada = document.getElementById('btnCancelada');

    if (formReserva && !formReserva.dataset.bound) {
        formReserva.dataset.bound = 'true';
        formReserva.addEventListener('submit', turnosGuardarReserva);
    }
    if (btnPendiente && !btnPendiente.dataset.bound) {
        btnPendiente.dataset.bound = 'true';
        btnPendiente.addEventListener('click', function () { turnosCambiarEstado(1); });
    }
    if (btnConfirmada && !btnConfirmada.dataset.bound) {
        btnConfirmada.dataset.bound = 'true';
        btnConfirmada.addEventListener('click', function () { turnosCambiarEstado(2); });
    }
    if (btnCancelada && !btnCancelada.dataset.bound) {
        btnCancelada.dataset.bound = 'true';
        btnCancelada.addEventListener('click', function () { turnosCambiarEstado(3); });
    }
}

function initTurnosPage() {
    var fechaInput = document.getElementById('fechaSeleccionada');
    var btnRecargar = document.getElementById('btnRecargar');

    if (!fechaInput) return;

    fechaInput.value = new Date().toISOString().split('T')[0];

    if (btnRecargar && !btnRecargar.dataset.bound) {
        btnRecargar.dataset.bound = 'true';
        btnRecargar.addEventListener('click', turnosCargarDatos);
    }

    turnosReiniciarBotones();
    turnosCargarDatos();
}

function turnosCargarDatos() {
    var fecha = document.getElementById('fechaSeleccionada').value;
    fetch(BASE_URL + '/api/turnos_canchas.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ accion: 'obtener_datos', fecha: fecha })
    })
    .then(function (r) { return r.json(); })
    .then(function (datos) {
        if (!datos.ok) { mostrarToast(datos.mensaje || 'Error cargando datos', 'error'); return; }
        turnosCanchas = datos.canchas;
        turnosClientes = datos.clientes;
        turnosReservas = datos.reservas;
        turnosCargarClientes();
        turnosGenerarTabla();
    })
    .catch(function (error) {
        console.error(error);
        mostrarToast('Error de conexión al cargar datos', 'error');
    });
}

function turnosCargarClientes() {
    var select = document.getElementById('cliente_id');
    if (!select) return;
    var html = '<option value="">Seleccionar cliente</option>';
    html += '<option value="nuevo">+ Nuevo Cliente</option>';
    turnosClientes.forEach(function (cliente) {
        html += '<option value="' + cliente.cliente_id + '">' +
            cliente.cliente_apellido + ', ' + cliente.cliente_nombre + '</option>';
    });
    select.innerHTML = html;

    if (window._turnosNuevoClienteId) {
        select.value = String(window._turnosNuevoClienteId);
        window._turnosNuevoClienteId = null;
    }

    if (!select.dataset.turnosBound) {
        select.dataset.turnosBound = 'true';
        select.addEventListener('change', function () {
            if (this.value === 'nuevo') {
                turnosAbrirNuevoCliente();
                this.value = '';
            }
        });
    }
}

function turnosGenerarTabla() {
    turnosGenerarCabecera();
    turnosGenerarCuerpo();
}

function turnosGenerarCabecera() {
    var fila = document.getElementById('filaCanchas');
    if (!fila) return;
    fila.innerHTML = '<th class="columna-hora">Horario</th>';

    turnosCanchas.forEach(function (cancha) {
        var indicador = '';
        if (Number(cancha.cancha_estado) === 2) indicador = ' \uD83D\uDD27';
        if (Number(cancha.cancha_estado) === 3) indicador = ' \u26D4';
        fila.innerHTML += '<th>Cancha ' + cancha.cancha_numero + indicador + '</th>';
    });
}

function turnosGenerarCuerpo() {
    var tbody = document.getElementById('cuerpoTabla');
    if (!tbody) return;
    var html = '';

    for (var hora = HORA_INICIO; hora <= HORA_FIN; hora++) {
        var horaTexto = hora.toString().padStart(2, '0') + ':00:00';
        html += '<tr><td class="hora">' + hora + ':00</td>';

        turnosCanchas.forEach(function (cancha) {
            var reserva = turnosBuscarReserva(cancha.cancha_id, horaTexto);

            if (reserva) {
                html += turnosGenerarCelda(reserva);
            } else if (Number(cancha.cancha_estado) === 2) {
                html += '<td class="estado-mantenimiento">En mantenimiento</td>';
            } else if (Number(cancha.cancha_estado) === 3) {
                html += '<td class="estado-mantenimiento">Inhabilitada</td>';
            } else {
                html += '<td class="libre" onclick="turnosAbrirNuevaReserva(' +
                    cancha.cancha_id + ',\'' + cancha.cancha_numero + '\',\'' + horaTexto + '\')">+</td>';
            }
        });

        html += '</tr>';
    }

    tbody.innerHTML = html;
}

function turnosBuscarReserva(canchaId, hora) {
    for (var i = 0; i < turnosReservas.length; i++) {
        var r = turnosReservas[i];
        if (Number(r.cancha_id) === Number(canchaId) &&
            r.tur_hora_inicio === hora &&
            Number(r.reser_estado) !== 3) {
            return r;
        }
    }
    return null;
}

function turnosGenerarCelda(reserva) {
    var clase = '';
    switch (Number(reserva.reser_estado)) {
        case 1: clase = 'estado-pendiente'; break;
        case 2: clase = 'estado-confirmada'; break;
        case 3: clase = 'estado-cancelada'; break;
    }
    var nombre = (reserva.cliente_nombre || '') + ' ' + (reserva.cliente_apellido || '');
    var texto = turnosTextoEstado(reserva.reser_estado);

    return '<td class="' + clase + '" onclick="turnosAbrirDetalle(' + reserva.reserva_id + ')">' +
        '<div class="nombre-cliente">' + nombre + '</div>' +
        '<div class="estado-texto">' + texto + '</div></td>';
}

function turnosTextoEstado(estado) {
    switch (Number(estado)) {
        case 1: return 'Pendiente';
        case 2: return 'Confirmada';
        case 3: return 'Cancelada';
        default: return '';
    }
}

function turnosAbrirNuevaReserva(canchaId, canchaNumero, hora) {
    document.getElementById('cancha_id').value = canchaId;
    document.getElementById('hora_inicio').value = hora;
    document.getElementById('fecha_reserva').value = document.getElementById('fechaSeleccionada').value;
    document.getElementById('canchaTexto').value = 'Cancha ' + canchaNumero;
    document.getElementById('horaTexto').value = hora.substring(0, 5);
    document.getElementById('fechaTexto').value = document.getElementById('fechaSeleccionada').value;
    document.getElementById('observaciones').value = '';

    document.getElementById('panelDetalle').style.display = 'none';
    document.getElementById('panelReserva').style.display = 'block';
    document.getElementById('drawer-title').textContent = 'Nueva Reserva';

    openDrawer();
}

function turnosAbrirNuevoCliente() {
    if (turnosOnCancel) {
        document.removeEventListener('click', turnosOnCancel);
        turnosOnCancel = null;
    }
    turnosNuevoClienteReserva = {
        cancha_id: document.getElementById('cancha_id').value,
        hora: document.getElementById('hora_inicio').value,
        fecha: document.getElementById('fecha_reserva').value,
        canchaTexto: document.getElementById('canchaTexto').value,
        horaTexto: document.getElementById('horaTexto').value,
        fechaTexto: document.getElementById('fechaTexto').value,
        observaciones: document.getElementById('observaciones').value
    };

    turnosNuevoClienteActivo = true;
    closeDrawer();

    document.body.dataset.drawer = 'clientes';
    loadDrawer().then(function () {
        var form = document.getElementById('formCliente');
        if (!form) return;

        form.reset();
        document.getElementById('edit_cliente_id').value = '';
        document.getElementById('drawer-title').textContent = 'Nuevo Cliente';

        if (form._turnosSubmit) {
            form.removeEventListener('submit', form._turnosSubmit);
        }

        form._turnosSubmit = function (e) {
            e.preventDefault();

            var nombre = document.getElementById('cliente_nombre').value.trim();
            var apellido = document.getElementById('cliente_apellido').value.trim();
            var celular = document.getElementById('cliente_celular').value.trim();

            if (!celular) {
                mostrarToast('El teléfono es obligatorio', 'error');
                return;
            }

            var payload = {
                accion: 'crear',
                nombre: nombre,
                apellido: apellido,
                email: document.getElementById('cliente_email').value.trim(),
                celular: celular,
                dni: document.getElementById('cliente_dni').value.trim()
            };

            fetch(BASE_URL + '/api/clientes.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(function (r) { return r.json(); })
            .then(function (result) {
                if (result.ok) {
                    closeDrawer();
                    document.removeEventListener('click', onCancel);
                    turnosNuevoClienteActivo = false;
                    turnosOnCancel = null;
                    window._turnosNuevoClienteId = result.cliente_id;
                    document.body.dataset.drawer = 'turnos';
                    loadDrawer().then(function () {
                        document.getElementById('cancha_id').value = turnosNuevoClienteReserva.cancha_id;
                        document.getElementById('hora_inicio').value = turnosNuevoClienteReserva.hora;
                        document.getElementById('fecha_reserva').value = turnosNuevoClienteReserva.fecha;
                        document.getElementById('canchaTexto').value = turnosNuevoClienteReserva.canchaTexto;
                        document.getElementById('fechaTexto').value = turnosNuevoClienteReserva.fecha;
                        document.getElementById('horaTexto').value = turnosNuevoClienteReserva.horaTexto;
                        document.getElementById('observaciones').value = turnosNuevoClienteReserva.observaciones || '';

                        document.getElementById('panelDetalle').style.display = 'none';
                        document.getElementById('panelReserva').style.display = 'block';
                        document.getElementById('drawer-title').textContent = 'Nueva Reserva';

                        turnosReiniciarBotones();
                        turnosCargarDatos();
                        openDrawer();
                    });
                    mostrarToast(result.mensaje, 'success');
                } else {
                    mostrarToast(result.mensaje, 'error');
                }
            })
            .catch(function (err) { console.error(err); mostrarToast('Error al crear cliente', 'error'); });
        };

        form.addEventListener('submit', form._turnosSubmit);

        /* Cancel: restore turnos drawer if closed without saving */
        function onCancel(e) {
            if (!turnosNuevoClienteActivo) return;
            if (e.target.closest('.drawer-close') || e.target.classList.contains('drawer-overlay')) {
                document.removeEventListener('click', onCancel);
                turnosOnCancel = null;
                setTimeout(function () {
                    if (document.body.dataset.drawer === 'clientes') {
                        turnosNuevoClienteActivo = false;
                        document.body.dataset.drawer = 'turnos';
                        loadDrawer().then(function () {
                            if (!turnosNuevoClienteReserva) return;
                            document.getElementById('cancha_id').value = turnosNuevoClienteReserva.cancha_id;
                            document.getElementById('hora_inicio').value = turnosNuevoClienteReserva.hora;
                            document.getElementById('fecha_reserva').value = turnosNuevoClienteReserva.fecha;
                            document.getElementById('canchaTexto').value = turnosNuevoClienteReserva.canchaTexto;
                            document.getElementById('fechaTexto').value = turnosNuevoClienteReserva.fecha;
                            document.getElementById('horaTexto').value = turnosNuevoClienteReserva.horaTexto;
                            document.getElementById('observaciones').value = turnosNuevoClienteReserva.observaciones || '';

                            document.getElementById('panelDetalle').style.display = 'none';
                            document.getElementById('panelReserva').style.display = 'block';
                            document.getElementById('drawer-title').textContent = 'Nueva Reserva';

                            turnosReiniciarBotones();
                            turnosCargarClientes();
                            openDrawer();
                        });
                    }
                }, 100);
            }
        }
        turnosOnCancel = onCancel;
        document.addEventListener('click', onCancel);

        openDrawer();
    });
}

function turnosGuardarReserva(e) {
    e.preventDefault();

    var fecha = document.getElementById('fecha_reserva').value;
    var horaInicio = document.getElementById('hora_inicio').value;
    var hoy = new Date().toLocaleDateString('en-CA');
    if (fecha === hoy) {
        var inicioTurno = new Date(fecha + 'T' + horaInicio).getTime();
        if (inicioTurno <= Date.now()) {
            alert('No se puede reservar un horario ya pasado');
            return;
        }
    }

    var payload = {
        accion: 'crear_reserva',
        cancha_id: document.getElementById('cancha_id').value,
        cliente_id: document.getElementById('cliente_id').value,
        fecha: fecha,
        hora_inicio: horaInicio,
        observaciones: document.getElementById('observaciones').value
    };

    fetch(BASE_URL + '/api/turnos_canchas.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(function (r) { return r.json(); })
    .then(function (resultado) {
        if (!resultado.ok) { alert(resultado.mensaje); return; }
        closeDrawer();
        turnosCargarDatos();
        mostrarToast('Reserva creada', 'success');
    })
    .catch(function (error) {
        console.error(error);
        alert('Error al guardar');
    });
}

function turnosAbrirDetalle(reservaId) {
    var reserva = null;
    for (var i = 0; i < turnosReservas.length; i++) {
        if (Number(turnosReservas[i].reserva_id) === Number(reservaId)) {
            reserva = turnosReservas[i];
            break;
        }
    }
    if (!reserva) return;

    document.getElementById('detalleReservaId').value = reserva.reserva_id;
    document.getElementById('detalleCliente').textContent =
        (reserva.cliente_nombre || '') + ' ' + (reserva.cliente_apellido || '');
    document.getElementById('detalleCancha').textContent = 'Cancha ' + reserva.cancha_numero;
    document.getElementById('detalleHorario').textContent = reserva.tur_hora_inicio.substring(0, 5);
    document.getElementById('detalleEstado').textContent = turnosTextoEstado(reserva.reser_estado);
    document.getElementById('detalleObservaciones').textContent = reserva.reser_observaciones || '';

    document.getElementById('panelReserva').style.display = 'none';
    document.getElementById('panelDetalle').style.display = 'block';
    document.getElementById('drawer-title').textContent = 'Detalle de Reserva';

    openDrawer();
}

function turnosCambiarEstado(estado) {
    var reservaId = document.getElementById('detalleReservaId').value;

    fetch(BASE_URL + '/api/turnos_canchas.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ accion: 'cambiar_estado', reserva_id: reservaId, estado: estado })
    })
    .then(function (r) { return r.json(); })
    .then(function (resultado) {
        if (!resultado.ok) { alert(resultado.mensaje); return; }
        closeDrawer();
        turnosCargarDatos();
    })
    .catch(function (error) {
        console.error(error);
        alert('Error actualizando estado');
    });
}


function mostrarToast(mensaje, tipo) {
    var contenedor = document.getElementById('toast-container');
    if (!contenedor) {
        contenedor = document.createElement('div');
        contenedor.id = 'toast-container';
        document.body.appendChild(contenedor);
    }

    var toast = document.createElement('div');
    toast.className = 'toast toast-' + (tipo || 'success');
    toast.innerHTML = '<span>' + mensaje + '</span><button class="toast-close" onclick="this.parentElement.remove()">&times;</button>';
    contenedor.appendChild(toast);

    setTimeout(function () {
        toast.classList.add('toast-hiding');
        setTimeout(function () { toast.remove(); }, 300);
    }, 3000);
}
