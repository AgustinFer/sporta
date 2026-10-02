/* ==========================================
CANCHAS - Burbujas y CRUD + Mantenimiento/Inhabilitar con reubicación
========================================== */

var canchasLista = [];
var currentCanchaId = null;

function initCanchasPage() {
    var btnNueva = document.querySelector('.fab');
    var chkMostrar = document.getElementById('chkMostrarInhabilitadas');
    var formCancha = document.getElementById('formCancha');

    if (!btnNueva && !chkMostrar && !formCancha) return;

    if (btnNueva && !btnNueva.dataset.bound) {
        btnNueva.dataset.bound = 'true';
        btnNueva.addEventListener('click', abrirFormularioCancha);
    }

    if (chkMostrar && !chkMostrar.dataset.bound) {
        chkMostrar.dataset.bound = 'true';
        chkMostrar.addEventListener('change', cargarCanchas);
    }

    if (formCancha && !formCancha.dataset.bound) {
        formCancha.dataset.bound = 'true';
        formCancha.addEventListener('submit', guardarCancha);
    }

    cargarCanchas();
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

async function cargarCanchas() {
    try {
        var incluir = document.getElementById('chkMostrarInhabilitadas').checked;
        var respuesta = await fetch(BASE_URL + '/api/turnos_canchas.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ accion: 'obtener_canchas', incluir_inhabilitadas: incluir ? 1 : 0 })
        });

        var datos = await respuesta.json();
        if (!datos.ok) { console.error(datos.mensaje); return; }

        canchasLista = datos.canchas;
        generarBurbujas();
    } catch (error) {
        console.error(error);
        mostrarToast('Error de conexión', 'error');
    }
}

function generarBurbujas() {
    var contenedor = document.getElementById('contenedorBurbujas');
    if (!contenedor) return;
    contenedor.innerHTML = '';

    if (canchasLista.length === 0) {
        contenedor.innerHTML = '<div class="sin-canchas">No hay canchas agregadas. Agrega una!</div>';
        return;
    }

    canchasLista.forEach(function (cancha, index) {
        var burbuja = document.createElement('div');
        burbuja.className = 'burbuja-cancha';
        burbuja.dataset.canchaId = cancha.cancha_id;

        var enMantRango = Number(cancha.en_mantenimiento) === 1;
        var esMantenimiento = Number(cancha.cancha_estado) === 2 || enMantRango;
        var esInhabilitado = Number(cancha.cancha_estado) === 3;
        var claseEstado = 'estado-disponible-badge';
        if (esMantenimiento) claseEstado = 'estado-mantenimiento-badge';
        if (esInhabilitado) claseEstado = 'estado-inhabilitado-badge';
        var textoEstado = cancha.estado_descripcion || 'Disponible';
        if (enMantRango && Number(cancha.cancha_estado) === 1) textoEstado = 'En mantenimiento';

        if (esMantenimiento) burbuja.classList.add('burbuja-mantenimiento');
        if (esInhabilitado) burbuja.classList.add('burbuja-inhabilitado');

        var esDisponible = Number(cancha.cancha_estado) === 1 && !enMantRango;

        var botonesHtml = '';
        if (esDisponible) {
            botonesHtml =
                '<button class="btn-mantenimiento" onclick="preverYDeshabilitar(' + cancha.cancha_id + ', 2)">Mantenimiento</button>';
        } else if (esMantenimiento) {
            botonesHtml =
                '<button class="btn-habilitar-cancha" onclick="finalizarMantenimiento(' + cancha.cancha_id + ')">Finalizar mantenimiento</button>';
        } else {
            botonesHtml =
                '<button class="btn-habilitar-cancha" onclick="habilitarCancha(' + cancha.cancha_id + ')">Habilitar</button>';
        }

        burbuja.innerHTML =
            '<div class="burbuja-header">' +
            '<div class="numero-cancha">Cancha ' + cancha.cancha_numero + '</div>' +
            '<div class="botones-cancha">' +
            '<button class="btn-editar-cancha" onclick="editarCancha(' + cancha.cancha_id + ')">Editar</button>' +
            botonesHtml +
            '</div></div>' +
            '<div class="burbuja-info">' +
            '<div class="info-label">Descripción</div>' +
            '<div class="info-valor">' + (cancha.descripcion || 'Sin descripción') + '</div></div>' +
            '<div class="burbuja-estado"><span class="' + claseEstado + '">' + textoEstado + '</span></div>' +
            '<div class="burbuja-precio">' +
            '<div class="precio-label">Precio por hora</div>' +
            '<div class="precio-valor">$' + parseFloat(cancha.cancha_precio).toFixed(2) + '</div></div>';

        if (esInhabilitado) {
            burbuja.classList.add('burbuja-entrance-inhabilitada');
            burbuja.style.animationDelay = (index * 0.06 + 0.15) + 's';
        } else {
            burbuja.classList.add('burbuja-entrance');
            burbuja.style.animationDelay = (index * 0.04) + 's';
        }

        contenedor.appendChild(burbuja);
    });
}

function abrirFormularioCancha() {
    document.getElementById('drawer-title').textContent = 'Nueva Cancha';
    document.getElementById('edit_cancha_id').value = '';
    document.getElementById('formCancha').reset();
    var estadoTexto = document.getElementById('cancha_estado_texto');
    if (estadoTexto) estadoTexto.textContent = 'Disponible';
    var btnToggle = document.getElementById('btnToggleEstadoCancha');
    if (btnToggle) btnToggle.style.display = 'none';
    openDrawer();
}

function editarCancha(canchaId) {
    var cancha = null;
    for (var i = 0; i < canchasLista.length; i++) {
        if (canchasLista[i].cancha_id === canchaId) {
            cancha = canchasLista[i];
            break;
        }
    }
    if (!cancha) return;

    document.getElementById('drawer-title').textContent = 'Editar Cancha';
    document.getElementById('edit_cancha_id').value = cancha.cancha_id;
    document.getElementById('cancha_numero').value = cancha.cancha_numero;
    document.getElementById('cancha_precio').value = cancha.cancha_precio;
    document.getElementById('cancha_descripcion').value = cancha.descripcion || '';
    var estadoTexto = document.getElementById('cancha_estado_texto');
    if (estadoTexto) {
        var mapaEstado = {1: 'Disponible', 2: 'En mantenimiento', 3: 'Inhabilitado'};
        var txt = cancha.estado_descripcion || mapaEstado[Number(cancha.cancha_estado)] || 'Disponible';
        if (Number(cancha.cancha_estado) === 1 && Number(cancha.en_mantenimiento) === 1) txt = 'En mantenimiento';
        estadoTexto.textContent = txt;
    }

    var btnToggle = document.getElementById('btnToggleEstadoCancha');
    if (btnToggle) {
        if (Number(cancha.cancha_estado) === 3) {
            btnToggle.textContent = 'Habilitar cancha';
            btnToggle.classList.remove('drawer-danger');
            btnToggle.classList.add('drawer-success-btn');
            btnToggle.onclick = function() { habilitarCancha(cancha.cancha_id); };
        } else {
            btnToggle.textContent = 'Inhabilitar cancha';
            btnToggle.classList.remove('drawer-success-btn');
            btnToggle.classList.add('drawer-danger');
            btnToggle.onclick = function() { preverYDeshabilitar(cancha.cancha_id, 3); };
        }
        btnToggle.style.display = 'block';
    }

    openDrawer();
}

async function guardarCancha(e) {
    e.preventDefault();

    var canchaId = document.getElementById('edit_cancha_id').value;
    var numeroIngresado = document.getElementById('cancha_numero').value;

    var precioIngresado = parseFloat(document.getElementById('cancha_precio').value);
    if (isNaN(precioIngresado) || precioIngresado > 99999999.99) {
        mostrarToast('El precio no puede superar $99.999.999,99', 'error');
        return;
    }

    var duplicado = canchasLista.some(function(c) {
        return String(c.cancha_numero) === numeroIngresado && String(c.cancha_id) !== canchaId;
    });
    if (duplicado) {
        mostrarToast('Ya existe una cancha con ese número', 'error');
        return;
    }

    var payload = {
        accion: canchaId ? 'actualizar_cancha' : 'crear_cancha',
        cancha_numero: numeroIngresado,
        cancha_precio: document.getElementById('cancha_precio').value,
        descripcion: document.getElementById('cancha_descripcion').value
    };
    if (canchaId) payload.cancha_id = canchaId;

    try {
        var respuesta = await fetch(BASE_URL + '/api/turnos_canchas.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        var resultado = await respuesta.json();
        if (!resultado.ok) { mostrarToast(resultado.mensaje, 'error'); return; }

        closeDrawer();
        await cargarCanchas();
        mostrarToast(canchaId ? 'Cancha actualizada' : 'Cancha creada', 'success');
    } catch (error) {
        console.error(error);
        mostrarToast('Error guardando cancha', 'error');
    }
}

/* ==========================================
MANTENIMIENTO / INHABILITAR CON REUBICACIÓN
========================================== */

async function preverYDeshabilitar(canchaId, estadoDestino) {
    currentCanchaId = canchaId;
    if (estadoDestino === 2) {
        await abrirModalConfigMantenimiento(canchaId);
    } else {
        await preverDeshabilitarDirecto(canchaId, estadoDestino);
    }
}

async function abrirModalConfigMantenimiento(canchaId) {
    var hoy = new Date().toLocaleDateString('en-CA');
    var manana = new Date(Date.now() + 86400000).toLocaleDateString('en-CA');

    var modal = document.createElement('div');
    modal.className = 'modal-plan-overlay';
    modal.innerHTML =
        '<div class="modal-plan-box">' +
        '<h3>Configurar Mantenimiento - Cancha #' + canchaId + '</h3>' +
        '<form id="formConfigMantenimiento">' +
        '<div class="form-row">' +
        '<div><label>Fecha desde <span class="required">*</span></label><input type="date" id="m_fecha_desde" value="' + hoy + '" required></div>' +
        '<div><label>Fecha hasta <span class="required">*</span></label><input type="date" id="m_fecha_hasta" value="' + manana + '" required></div>' +
        '</div>' +
        '<div class="form-row">' +
        '<div><label>Hora desde <span class="required">*</span></label><input type="time" id="m_hora_desde" value="08:00" required></div>' +
        '<div><label>Hora hasta <span class="required">*</span></label><input type="time" id="m_hora_hasta" value="23:00" required></div>' +
        '</div>' +
        '<div><label>Motivo</label><textarea id="m_motivo" rows="2" placeholder="Ej: Reparación césped, cambio de luces..."></textarea></div>' +
        '<div class="modal-plan-actions">' +
        '<button type="button" class="confirm-btn confirm-cancel" onclick="this.closest(\'.modal-plan-overlay\').remove()">Cancelar</button>' +
        '<button type="submit" class="confirm-btn confirm-ok">Ver reservas afectadas</button>' +
        '</div>' +
        '</form>' +
        '</div>';
    document.body.appendChild(modal);
    requestAnimationFrame(function() { modal.classList.add('active'); });

    document.getElementById('formConfigMantenimiento').addEventListener('submit', async function(e) {
        e.preventDefault();
        var fechaDesde = document.getElementById('m_fecha_desde').value;
        var fechaHasta = document.getElementById('m_fecha_hasta').value;
        var horaDesde = document.getElementById('m_hora_desde').value;
        var horaHasta = document.getElementById('m_hora_hasta').value;
        var motivo = document.getElementById('m_motivo').value;

        modal.classList.remove('active');
        setTimeout(function() { modal.remove(); }, 300);

        await preverDeshabilitarDirecto(canchaId, 2, fechaDesde, fechaHasta, horaDesde, horaHasta, motivo);
    });
}

async function preverDeshabilitarDirecto(canchaId, estadoDestino, fechaDesde, fechaHasta, horaDesde, horaHasta, motivo) {
    try {
        var payload = {
            accion: 'prever_deshabilitar',
            cancha_id: canchaId,
            estado_destino: estadoDestino
        };
        if (fechaDesde) payload.fecha_desde = fechaDesde;
        if (fechaHasta) payload.fecha_hasta = fechaHasta;
        if (horaDesde) payload.hora_desde = horaDesde;
        if (horaHasta) payload.hora_hasta = horaHasta;
        if (motivo) payload.motivo = motivo;

        var respuesta = await fetch(BASE_URL + '/api/turnos_canchas.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        var resultado = await respuesta.json();
        if (!resultado.ok) {
            if (resultado.reservas_afectadas) {
                renderModalPlan(canchaId, resultado.reservas_afectadas, estadoDestino, {fechaDesde: fechaDesde, fechaHasta: fechaHasta, horaDesde: horaDesde, horaHasta: horaHasta, motivo: motivo}, 0);
            } else {
                mostrarToast(resultado.mensaje || 'Error', 'error');
            }
            return;
        }

        if (resultado.puede_deshabilitar) {
            if (await showConfirm('No hay reservas afectadas. Confirmar ' + (estadoDestino === 2 ? 'mantenimiento' : 'inhabilitación') + '?', estadoDestino === 2 ? '🔧' : '🚫')) {
                await ejecutarPlan(canchaId, [], estadoDestino, {fechaDesde: fechaDesde, fechaHasta: fechaHasta, horaDesde: horaDesde, horaHasta: horaHasta, motivo: motivo});
            }
        } else {
            renderModalPlan(canchaId, resultado.reservas_afectadas, estadoDestino, {fechaDesde: fechaDesde, fechaHasta: fechaHasta, horaDesde: horaDesde, horaHasta: horaHasta, motivo: motivo}, resultado.reservas_pasadas_omitidas || 0);
        }
    } catch (error) {
        console.error(error);
        mostrarToast('Error de conexión', 'error');
    }
}

function formatearDiferencia(dif) {
    var n = parseFloat(dif) || 0;
    if (n === 0) return 'mismo precio';
    var txt = '$' + Math.abs(n).toLocaleString('es-AR');
    return 'dif. ' + (n > 0 ? '+' : '-') + txt;
}

function renderModalPlan(canchaId, reservas, estadoDestino, rango, omitidas) {
    var existing = document.querySelector('.modal-plan-overlay');
    if (existing) existing.remove();

    var titulo = estadoDestino === 2 ? 'Mantenimiento programado' : 'Inhabilitación de cancha';
    var html = '<div class="modal-plan-overlay active"><div class="modal-plan-box"><h3>' + titulo + '</h3>';
    html += '<p>Se encontraron <strong>' + reservas.length + '</strong> reserva(s) afectada(s). Elegí una acción para cada una:</p>';
    if (omitidas > 0) {
        html += '<p class="detalle-aviso">' + omitidas + ' reserva(s) ya jugadas no se incluyen en el plan.</p>';
    }
    html += '<table id="tablaPlan"><thead><tr><th>Cliente</th><th>Fecha / Hora</th><th>Acción</th><th>Detalle</th></tr></thead><tbody>';

    reservas.forEach(function(r) {
        var cliente = (r.cliente_nombre || '') + ' ' + (r.cliente_apellido || '');
        var fechaHora = r.tur_fecha + ' ' + (r.tur_hora_inicio || '').substring(0,5) + ' - ' + (r.tur_hora_fin || '').substring(0,5);
        var horaCorta = (r.tur_hora_inicio || '').substring(0,5);
        var alts = r.alternativas_mismo_horario || [];
        var selectHtml = '<select class="plan-accion" data-reserva-id="' + r.reserva_id + '">';

        alts.forEach(function(alt, idx) {
            selectHtml += '<option value="reubicar_mismo_horario" data-cancha-id="' + alt.cancha_id + '"' + (idx === 0 ? ' selected' : '') + '>Mover a Cancha ' + alt.cancha_numero + ' (' + horaCorta + ' mismo horario, $' + parseFloat(alt.cancha_precio).toLocaleString('es-AR') + ', ' + formatearDiferencia(alt.diferencia) + ')</option>';
        });
        selectHtml += '<option value="cancelar">Cancelar reserva</option>';
        selectHtml += '<option value="avisar"' + (alts.length === 0 ? ' selected' : '') + '>Avisar al cliente</option>';
        selectHtml += '</select>';

        var detalleHtml = '<span class="detalle-aviso" style="display:none">TEL ' + (r.cliente_celular || 'Sin teléfono') + ' — ' + cliente + '</span>';

        html += '<tr data-reserva-id="' + r.reserva_id + '">' +
            '<td>' + cliente + '</td>' +
            '<td>' + fechaHora + '</td>' +
            '<td>' + selectHtml + '</td>' +
            '<td>' + detalleHtml + '</td>' +
            '</tr>';
    });

    html += '</tbody></table>';
    html += '<div class="modal-plan-actions">';
    html += '<button type="button" class="confirm-btn confirm-cancel" id="btnCancelarPlan">Cancelar</button>';
    html += '<button type="button" class="confirm-btn confirm-ok" id="btnEjecutarPlan">Confirmar y ejecutar</button>';
    html += '</div></div></div>';

    document.body.insertAdjacentHTML('beforeend', html);

    document.querySelectorAll('#tablaPlan .plan-accion').forEach(function(sel) {
        sel.addEventListener('change', function() {
            var row = this.closest('tr');
            var aviso = row.querySelector('.detalle-aviso');
            if (aviso) aviso.style.display = this.value === 'avisar' ? 'inline' : 'none';
        });
        if (sel.value === 'avisar') {
            var avisoInit = sel.closest('tr').querySelector('.detalle-aviso');
            if (avisoInit) avisoInit.style.display = 'inline';
        }
    });

    document.getElementById('btnCancelarPlan').addEventListener('click', cerrarModalPlan);
    document.getElementById('btnEjecutarPlan').addEventListener('click', async function() {
        var btn = this;
        btn.disabled = true;
        var plan = [];
        document.querySelectorAll('#tablaPlan .plan-accion').forEach(function(sel) {
            var reservaId = parseInt(sel.dataset.reservaId);
            var accion = sel.value;
            var item = { reserva_id: reservaId, accion: accion };
            if (accion === 'reubicar_mismo_horario') {
                item.nueva_cancha_id = parseInt(sel.options[sel.selectedIndex].dataset.canchaId);
            }
            plan.push(item);
        });
        cerrarModalPlan();
        await ejecutarPlan(canchaId, plan, estadoDestino, rango);
        btn.disabled = false;
    });
}

function cerrarModalPlan() {
    var modal = document.querySelector('.modal-plan-overlay');
    if (modal) {
        modal.classList.remove('active');
        setTimeout(function() { modal.remove(); }, 300);
    }
}

async function ejecutarPlan(canchaId, plan, estadoDestino, rango) {
    try {
        if (!canchaId) {
            mostrarToast('No se pudo determinar la cancha', 'error');
            return;
        }

        var payload = {
            accion: 'ejecutar_deshabilitar',
            cancha_id: canchaId,
            estado_destino: estadoDestino,
            plan: plan
        };

        if (rango) {
            if (rango.fechaDesde) payload.fecha_desde = rango.fechaDesde;
            if (rango.fechaHasta) payload.fecha_hasta = rango.fechaHasta;
            if (rango.horaDesde) payload.hora_desde = rango.horaDesde;
            if (rango.horaHasta) payload.hora_hasta = rango.horaHasta;
            if (rango.motivo) payload.motivo = rango.motivo;
        }

        var respuesta = await fetch(BASE_URL + '/api/turnos_canchas.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        var resultado = await respuesta.json();
        if (!resultado.ok) {
            mostrarToast(resultado.mensaje || 'Error al ejecutar', 'error');
            return;
        }

        var stats = resultado.stats || {};
        var parts = [];
        if (stats.mismo_horario) parts.push(stats.mismo_horario + ' mismo horario');
        if (stats.canceladas) parts.push(stats.canceladas + ' cancelada(s)');
        if (stats.avisar) parts.push(stats.avisar + ' avisar cliente');
        if (stats.slots_senados_omitidos) parts.push(stats.slots_senados_omitidos + ' slot(s) señados no tocados');
        mostrarToast('Ejecutado: ' + (parts.join(', ') || 'sin cambios'), 'success');
        currentCanchaId = null;
        if (typeof closeDrawer === 'function') closeDrawer();
        await cargarCanchas();
    } catch (error) {
        console.error(error);
        mostrarToast('Error de conexión', 'error');
    }
}

async function finalizarMantenimiento(canchaId) {
    if (!await showConfirm('¿Finalizar mantenimiento y liberar la cancha?', '✅')) return;

    try {
        var respuesta = await fetch(BASE_URL + '/api/turnos_canchas.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ accion: 'finalizar_mantenimiento', cancha_id: canchaId })
        });

        var resultado = await respuesta.json();
        if (!resultado.ok) { mostrarToast(resultado.mensaje, 'error'); return; }

        mostrarToast('Mantenimiento finalizado', 'success');
        await cargarCanchas();
    } catch (error) {
        console.error(error);
        mostrarToast('Error de conexión', 'error');
    }
}

async function habilitarCancha(canchaId) {
    if (!await showConfirm('¿Estás seguro de que deseas habilitar esta cancha?', '✅')) return;

    var burbuja = document.querySelector('.burbuja-cancha[data-cancha-id="' + canchaId + '"]');
    if (burbuja) {
        burbuja.classList.add('burbuja-habilitar');
        await new Promise(function (resolve) { setTimeout(resolve, 800); });
    }

    try {
        var respuesta = await fetch(BASE_URL + '/api/turnos_canchas.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ accion: 'habilitar_cancha', cancha_id: canchaId })
        });

        var resultado = await respuesta.json();
        if (!resultado.ok) { mostrarToast(resultado.mensaje, 'error'); return; }

        if (typeof closeDrawer === 'function') closeDrawer();
        await cargarCanchas();
        mostrarToast('Cancha habilitada', 'success');
    } catch (error) {
        console.error(error);
        mostrarToast('Error habilitando cancha', 'error');
    }
}
