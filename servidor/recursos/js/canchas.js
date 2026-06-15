/* ==========================================
CANCHAS - Burbujas y CRUD
========================================== */

var canchasLista = [];

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

    canchasLista.forEach(function (cancha) {
        var burbuja = document.createElement('div');
        burbuja.className = 'burbuja-cancha';

        var esMantenimiento = Number(cancha.cancha_estado) === 2;
        var esInhabilitado = Number(cancha.cancha_estado) === 3;
        var claseEstado = 'estado-disponible-badge';
        if (esMantenimiento) claseEstado = 'estado-mantenimiento-badge';
        if (esInhabilitado) claseEstado = 'estado-inhabilitado-badge';
        var textoEstado = cancha.estado_descripcion || 'Disponible';

        if (esMantenimiento) burbuja.classList.add('burbuja-mantenimiento');
        if (esInhabilitado) burbuja.classList.add('burbuja-inhabilitado');

        burbuja.innerHTML =
            '<div class="burbuja-header">' +
            '<div class="numero-cancha">Cancha ' + cancha.cancha_numero + '</div>' +
            '<div class="botones-cancha">' +
            '<button class="btn-editar-cancha" onclick="editarCancha(' + cancha.cancha_id + ')">Editar</button>' +
            '<button class="btn-eliminar-cancha" onclick="eliminarCancha(' + cancha.cancha_id + ')">Inhabilitar</button>' +
            '</div></div>' +
            '<div class="burbuja-info">' +
            '<div class="info-label">Descripción</div>' +
            '<div class="info-valor">' + (cancha.descripcion || 'Sin descripción') + '</div></div>' +
            '<div class="burbuja-estado"><span class="' + claseEstado + '">' + textoEstado + '</span></div>' +
            '<div class="burbuja-precio">' +
            '<div class="precio-label">Precio por hora</div>' +
            '<div class="precio-valor">$' + parseFloat(cancha.cancha_precio).toFixed(2) + '</div></div>';

        contenedor.appendChild(burbuja);
    });
}

function abrirFormularioCancha() {
    document.getElementById('drawer-title').textContent = 'Nueva Cancha';
    document.getElementById('edit_cancha_id').value = '';
    document.getElementById('formCancha').reset();
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
    document.getElementById('cancha_estado').value = cancha.cancha_estado;

    openDrawer();
}

async function guardarCancha(e) {
    e.preventDefault();

    var canchaId = document.getElementById('edit_cancha_id').value;
    var payload = {
        accion: canchaId ? 'actualizar_cancha' : 'crear_cancha',
        cancha_numero: document.getElementById('cancha_numero').value,
        cancha_precio: document.getElementById('cancha_precio').value,
        descripcion: document.getElementById('cancha_descripcion').value,
        cancha_estado: document.getElementById('cancha_estado').value
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

async function eliminarCancha(canchaId) {
    if (!confirm('¿Estás seguro de que deseas inhabilitar esta cancha?')) return;

    try {
        var respuesta = await fetch(BASE_URL + '/api/turnos_canchas.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ accion: 'eliminar_cancha', cancha_id: canchaId })
        });

        var resultado = await respuesta.json();
        if (!resultado.ok) { mostrarToast(resultado.mensaje, 'error'); return; }

        await cargarCanchas();
        mostrarToast('Cancha inhabilitada', 'success');
    } catch (error) {
        console.error(error);
        mostrarToast('Error inhabilitando cancha', 'error');
    }
}


