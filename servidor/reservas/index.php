<?php

require_once __DIR__ . '/../config/init.php';

if (!isset($_SESSION['usuario'])) {

    header("Location: ../index.php");
    exit;

}

require_once __DIR__ . '/../config/conexion.php';

$pdo = conexion();

/* ========================= */
/* 🔄 TOGGLE PAGO */
/* ========================= */

if (isset($_POST["toggle_pago_id"])) {

    $reservaId = (int) $_POST["toggle_pago_id"];

    $stmt = $pdo->prepare("
        SELECT f.factura_id, f.factura_estado, ca.cancha_precio
        FROM reservas r
        JOIN turnos t ON r.tur_id = t.tur_id
        JOIN canchas ca ON t.id_cancha = ca.cancha_id
        LEFT JOIN facturacion f ON r.reserva_id = f.reserva_id
        WHERE r.reserva_id = ?
    ");
    $stmt->execute([$reservaId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && $row['factura_id']) {

        $nuevoEstado = $row['factura_estado'] === 'Pagada' ? 'Pendiente' : 'Pagada';
        $stmt = $pdo->prepare("UPDATE facturacion SET factura_estado = ? WHERE factura_id = ?");
        $stmt->execute([$nuevoEstado, $row['factura_id']]);

        $_SESSION['flash_success'] = $nuevoEstado === 'Pagada'
            ? "Reserva marcada como Pagada"
            : "Reserva marcada como Pendiente";

    } else {

        $stmt = $pdo->prepare("
            INSERT INTO facturacion (reserva_id, factura_fecha_emision, factura_total, factura_estado)
            VALUES (?, CURDATE(), ?, 'Pagada')
        ");
        $stmt->execute([$reservaId, $row['cancha_precio'] ?? 0]);

        $_SESSION['flash_success'] = "Reserva marcada como Pagada";

    }

    header("Location: " . BASE_URL . "/reservas/");
    exit;

}

/* ========================= */
/* ✏️ MODIFICAR RESERVA */
/* ========================= */

if (!empty($_POST["edit_reserva_id"])) {

    $id = (int) $_POST["edit_reserva_id"];
    $estado = (int) ($_POST["reser_estado"] ?? 0);
    $observaciones = trim($_POST["reser_observaciones"] ?? "");

    if ($estado >= 1 && $estado <= 3) {

        $stmt = $pdo->prepare("
            UPDATE reservas
            SET reser_estado = ?, reser_observaciones = ?
            WHERE reserva_id = ?
        ");
        $stmt->execute([$estado, $observaciones ?: null, $id]);

        $_SESSION['flash_success'] = "Reserva actualizada con éxito";

    } else {

        $_SESSION['flash_error'] = "Estado de reserva inválido";

    }

    header("Location: " . BASE_URL . "/reservas/");
    exit;

}

/* ========================= */
/* 📋 LISTADO */
/* ========================= */

$stmt = $pdo->query("
    SELECT
        r.reserva_id,
        r.reser_estado,
        r.reser_observaciones,
        c.cliente_id,
        c.cliente_nombre,
        c.cliente_apellido,
        ca.cancha_numero,
        t.tur_fecha,
        t.tur_hora_inicio,
        er.estado_reserva_descripcion,
        f.factura_id,
        f.factura_estado,
        f.factura_total
    FROM reservas r
    JOIN turnos t ON r.tur_id = t.tur_id
    JOIN canchas ca ON t.id_cancha = ca.cancha_id
    LEFT JOIN clientes c ON r.cliente_id = c.cliente_id
    LEFT JOIN estado_reserva er ON r.reser_estado = er.estado_reserva_id
    LEFT JOIN facturacion f ON r.reserva_id = f.reserva_id
    ORDER BY t.tur_fecha DESC, t.tur_hora_inicio
");

$reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ========================= */
/* ESTADOS PARA EL SELECT */
/* ========================= */

$stmtEstados = $pdo->query("SELECT * FROM estado_reserva ORDER BY estado_reserva_id");
$estados = $stmtEstados->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sporta - Señas y Reservas</title>

  <link rel="stylesheet" href="<?= BASE_URL ?>/recursos/css/global.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/recursos/css/layout.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/recursos/css/componentes.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/recursos/css/drawer.css">

  <link id="page-style" rel="stylesheet" href="<?= BASE_URL ?>/reservas/reservas.css">
  <link rel="icon" href="<?= BASE_URL ?>/recursos/img/favicon.ico">
</head>

<body class="screen" data-page="Señas y Reservas" data-drawer="reservas">

  <div class="background"></div>

  <div id="sidebar-container"></div>

  <button class="menu-toggle" id="menuToggle">☰</button>

  <main class="main-content">

    <div id="header-container"></div>

    <?php if (isset($_SESSION['flash_success'])): ?>
      <div id="flash-success" style="display:none;"><?= htmlspecialchars($_SESSION['flash_success']) ?></div>
      <script>
        (function() {
          var msg = document.getElementById('flash-success');
          if (msg) mostrarToast(msg.textContent, 'success');
        })();
      </script>
      <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_error'])): ?>
      <div id="flash-error" style="display:none;"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
      <script>
        (function() {
          var msg = document.getElementById('flash-error');
          if (msg) mostrarToast(msg.textContent, 'error');
        })();
      </script>
      <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div class="list-container">

      <h2>Señas y Reservas</h2>

      <p>Gestión de señas y reservas</p>

    </div>

    <div class="table-toolbar">
      <input type="text" id="tableSearch" class="table-search" placeholder="Buscar seña/reserva...">
    </div>

    <div class="table-container">
      <table class="table">
        <thead>
          <tr>
            <th data-sort="0" data-column="cliente">Cliente</th>
            <th data-sort="1" data-column="cancha">Cancha</th>
            <th data-sort="2" data-column="fecha">Fecha</th>
            <th data-sort="3" data-column="horario">Horario</th>
            <th data-sort="4" data-column="estado">Estado</th>
            <th data-sort="5" data-column="pago">Pago</th>
            <th data-column="acciones">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($reservas as $r): ?>
            <tr>
              <td data-column="cliente"><?= htmlspecialchars(($r['cliente_nombre'] ?? '') . ' ' . ($r['cliente_apellido'] ?? '')) ?></td>
              <td data-column="cancha">Cancha <?= (int) $r['cancha_numero'] ?></td>
              <td data-column="fecha"><?= htmlspecialchars($r['tur_fecha']) ?></td>
              <td data-column="horario"><?= htmlspecialchars(substr($r['tur_hora_inicio'], 0, 5)) ?></td>
              <td data-column="estado">
                <span class="estado-badge estado-<?= (int) $r['reser_estado'] ?>">
                  <?= htmlspecialchars($r['estado_reserva_descripcion'] ?? '') ?>
                </span>
              </td>
              <td data-column="pago">
                <?php if ($r['factura_estado'] === 'Pagada'): ?>
                  <span class="pago-badge pago-pagada">Pagada</span>
                <?php elseif ($r['factura_estado'] === 'Pendiente'): ?>
                  <span class="pago-badge pago-pendiente">Pendiente</span>
                <?php else: ?>
                  <span class="pago-badge pago-sin">Sin pago</span>
                <?php endif; ?>
              </td>
              <td data-column="acciones" class="acciones-cell">
                <button
                  type="button"
                  class="edit-btn"
                  data-id="<?= (int) $r['reserva_id'] ?>"
                  data-estado="<?= (int) $r['reser_estado'] ?>"
                  data-observaciones="<?= htmlspecialchars($r['reser_observaciones'] ?? '') ?>"
                  data-cliente="<?= htmlspecialchars(($r['cliente_nombre'] ?? '') . ' ' . ($r['cliente_apellido'] ?? '')) ?>"
                  data-cancha="Cancha <?= (int) $r['cancha_numero'] ?>"
                  data-fecha="<?= htmlspecialchars($r['tur_fecha']) ?>"
                  data-horario="<?= htmlspecialchars(substr($r['tur_hora_inicio'], 0, 5)) ?>"
                  data-factura-estado="<?= htmlspecialchars($r['factura_estado'] ?? '') ?>"
                  data-factura-total="<?= htmlspecialchars($r['factura_total'] ?? '') ?>"
                >✏️</button>

                <form method="POST" style="display:inline">
                  <input type="hidden" name="toggle_pago_id" value="<?= (int) $r['reserva_id'] ?>">
                  <button type="submit" class="btn-pago" title="Cambiar estado de pago">💳</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($reservas)): ?>
            <tr>
              <td colspan="7" style="text-align:center;padding:30px;color:#9ca3af;">No hay reservas registradas</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </main>

  <div id="drawer-container"></div>

  <div id="toast-container" class="toast-container"></div>

  <script>
    const BASE_URL = "<?= BASE_URL ?>";
  </script>

  <script src="<?= BASE_URL ?>/recursos/js/layout.js"></script>
  <script src="<?= BASE_URL ?>/recursos/js/table.js"></script>
  <script src="<?= BASE_URL ?>/recursos/js/drawer.js"></script>
  <script src="<?= BASE_URL ?>/recursos/js/turnos.js"></script>
  <script src="<?= BASE_URL ?>/recursos/js/canchas.js"></script>
  <script src="<?= BASE_URL ?>/recursos/js/reservas.js"></script>

</body>
</html>
