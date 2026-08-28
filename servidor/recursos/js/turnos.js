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

function turnosFormatearFecha(iso) {
    try {
        var partes = iso.split('-');
        var fecha = new Date(Number(partes[0]), Number(partes[1]) - 1, Number(partes[2]));
        var texto = new Intl.DateTimeFormat('es-AR', {
            weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
        }).format(fecha);
        return texto.charAt(0).toUpperCase() + texto.slice(1);
    } catch (e) {
        return iso;
    }
}

function turnosActualizarTitulo() {
    var titulo = document.getElementById('tituloFecha');
    var fechaInput = document.getElementById('fechaSeleccionada');
    if (!titulo || !fechaInput || !fechaInput.value) return;
    titulo.textContent = turnosFormatearFecha(fechaInput.value);
}

function initTurnosPage() {
    var fechaInput = document.getElementById('fechaSeleccionada');
    var btnRecargar = document.getElementById('btnRecargar');

    if (!fechaInput) return;

    fechaInput.value = new Date().toLocaleDateString('en-CA');

    if (btnRecargar && !btnRecargar.dataset.bound) {
        btnRecargar.dataset.bound = 'true';
        btnRecargar.addEventListener('click', turnosCargarDatos);
    }

    if (fechaInput && !fechaInput.dataset.tituloBound) {
        fechaInput.dataset.tituloBound = 'true';
        fechaInput.addEventListener('change', function () {
            turnosActualizarTitulo();
            turnosCargarDatos();
        });
    }

    turnosActualizarTitulo();
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
    var searchInput = document.getElementById('cliente_search');
    var hiddenInput = document.getElementById('cliente_id');
    var dropdown = document.getElementById('cliente_dropdown');
    if (!dropdown) {
        dropdown = document.createElement('div');
        dropdown.id = 'cliente_dropdown';
        dropdown.className = 'search-dropdown';
        document.body.appendChild(dropdown);
    }
    if (!searchInput) return;

    searchInput._clientes = turnosClientes;

    if (window._turnosNuevoClienteId) {
        for (var i = 0; i < turnosClientes.length; i++) {
            if (Number(turnosClientes[i].cliente_id) === Number(window._turnosNuevoClienteId)) {
                searchInput.value = turnosClientes[i].cliente_apellido + ', ' + turnosClientes[i].cliente_nombre;
                hiddenInput.value = turnosClientes[i].cliente_id;
                break;
            }
        }
        window._turnosNuevoClienteId = null;
    }

    if (!searchInput.dataset.turnosBound) {
        searchInput.dataset.turnosBound = 'true';

        searchInput.addEventListener('input', function () {
            hiddenInput.value = '';
            renderTurnosDropdown();
            posicionarDropdownCliente(this);
        });

        searchInput.addEventListener('focus', function () {
            if (dropdown.style.display === 'block') return;
            renderTurnosDropdown();
            posicionarDropdownCliente(this);
        });

        searchInput.addEventListener('blur', function () {
            setTimeout(function () { dropdown.style.display = 'none'; }, 200);
        });

        searchInput.addEventListener('keydown', function (e) {
            var items = dropdown.querySelectorAll('.search-dropdown-item');
            var active = dropdown.querySelector('.search-dropdown-item.active');
            var idx = -1;
            if (active) idx = Array.prototype.indexOf.call(items, active);

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                var next = Math.min(idx + 1, items.length - 1);
                if (active) active.classList.remove('active');
                if (next >= 0 && items[next]) items[next].classList.add('active');
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                var prev = Math.max(idx - 1, 0);
                if (active) active.classList.remove('active');
                if (items[prev]) items[prev].classList.add('active');
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (active) active.click();
            } else if (e.key === 'Escape') {
                dropdown.style.display = 'none';
            }
        });
    }

    renderTurnosDropdown();
    dropdown.style.display = 'none';
}

function posicionarDropdownCliente(input) {
    var dropdown = document.getElementById('cliente_dropdown');
    if (!dropdown) return;
    var rect = input.getBoundingClientRect();
    dropdown.style.position = 'fixed';
    dropdown.style.top = (rect.bottom + 4) + 'px';
    dropdown.style.left = rect.left + 'px';
    dropdown.style.width = rect.width + 'px';
    dropdown.style.zIndex = '9999';
    dropdown.style.display = 'block';
}

function renderTurnosDropdown() {
    var searchInput = document.getElementById('cliente_search');
    var hiddenInput = document.getElementById('cliente_id');
    var dropdown = document.getElementById('cliente_dropdown');
    if (!searchInput || !dropdown) return;

    var clientes = searchInput._clientes || [];
    var query = searchInput.value.toLowerCase().trim();

    var html = '<div class="search-dropdown-item nuevo-cliente" data-id="nuevo">+ Nuevo Cliente</div>';
    for (var i = 0; i < clientes.length; i++) {
        var c = clientes[i];
        var haystack = (c.cliente_apellido + ', ' + c.cliente_nombre).toLowerCase();
        if (!query || haystack.indexOf(query) !== -1) {
            html += '<div class="search-dropdown-item" data-id="' + c.cliente_id + '">' +
                c.cliente_apellido + ', ' + c.cliente_nombre + '</div>';
        }
    }

    dropdown.innerHTML = html;

    var items = dropdown.querySelectorAll('.search-dropdown-item');
    for (var j = 0; j < items.length; j++) {
        items[j].addEventListener('click', function (e) {
            e.stopPropagation();
            var id = this.dataset.id;
            if (id === 'nuevo') {
                turnosAbrirNuevoCliente();
                dropdown.style.display = 'none';
                return;
            }
            for (var k = 0; k < clientes.length; k++) {
                if (Number(clientes[k].cliente_id) === Number(id)) {
                    searchInput.value = clientes[k].cliente_apellido + ', ' + clientes[k].cliente_nombre;
                    hiddenInput.value = id;
                    break;
                }
            }
            dropdown.style.display = 'none';
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
    document.getElementById('cliente_search').value = '';
    document.getElementById('cliente_id').value = '';

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

    var clienteId = document.getElementById('cliente_id').value;
    if (!clienteId) {
        mostrarToast('Debe seleccionar un cliente', 'error');
        return;
    }

    var fecha = document.getElementById('fecha_reserva').value;
    var horaInicio = document.getElementById('hora_inicio').value;
    var hoy = new Date().toLocaleDateString('en-CA');
    if (fecha === hoy) {
        var inicioTurno = new Date(fecha + 'T' + horaInicio).getTime();
        if (inicioTurno <= Date.now()) {
            mostrarToast('No se puede reservar un horario ya pasado', 'error');
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
        if (!resultado.ok) {
            if (resultado.requires_confirmation) {
                showConfirm(resultado.mensaje, '⚠️').then(function (confirma) {
                    if (!confirma) return;
                    payload.confirm_same_client = true;
                    fetch(BASE_URL + '/api/turnos_canchas.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    })
                    .then(function (r) { return r.json(); })
                    .then(function (r2) {
                        if (!r2.ok) { mostrarToast(r2.mensaje, 'error'); return; }
                        closeDrawer();
                        turnosCargarDatos();
                        mostrarToast('Reserva creada', 'success');
                    })
                    .catch(function (err) {
                        console.error(err);
                        mostrarToast('Error al guardar', 'error');
                    });
                });
                return;
            }
            mostrarToast(resultado.mensaje, 'error');
            return;
        }
        closeDrawer();
        turnosCargarDatos();
        mostrarToast('Reserva creada', 'success');
    })
    .catch(function (error) {
        console.error(error);
        mostrarToast('Error al guardar', 'error');
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
        if (!resultado.ok) { mostrarToast(resultado.mensaje, 'error'); return; }
        closeDrawer();
        turnosCargarDatos();
        mostrarToast('Estado actualizado', 'success');
    })
    .catch(function (error) {
        console.error(error);
        mostrarToast('Error actualizando estado', 'error');
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
