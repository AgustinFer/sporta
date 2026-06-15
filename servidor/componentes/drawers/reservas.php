<div class="drawer-overlay"></div>

<aside class="drawer">

  <div class="drawer-header">
    <h3 id="drawer-title">Modificar reserva</h3>
    <button type="button" class="drawer-close">✕</button>
  </div>

  <form class="drawer-form" method="POST" action="">

    <input type="hidden" name="edit_reserva_id" id="edit_reserva_id">

    <!-- INFO (solo lectura) -->
    <div class="drawer-info-grid">

      <div class="drawer-info-item">
        <label>Cliente</label>
        <span id="reserva_cliente_display">—</span>
      </div>

      <div class="drawer-info-item">
        <label>Cancha</label>
        <span id="reserva_cancha_display">—</span>
      </div>

      <div class="drawer-info-item">
        <label>Fecha</label>
        <span id="reserva_fecha_display">—</span>
      </div>

      <div class="drawer-info-item">
        <label>Horario</label>
        <span id="reserva_horario_display">—</span>
      </div>

    </div>

    <!-- ESTADO -->
    <div>
      <label>
        Estado
        <span class="required">*</span>
      </label>
      <select name="reser_estado" id="reserva_estado" required>
        <option value="">Seleccionar estado</option>
        <option value="1">Pendiente</option>
        <option value="2">Confirmada</option>
        <option value="3">Cancelada</option>
      </select>
      <span class="field-error" id="error_reserva_estado"></span>
    </div>

    <!-- OBSERVACIONES -->
    <div>
      <label>Observaciones</label>
      <textarea
        name="reser_observaciones"
        id="reserva_observaciones"
        class="drawer-textarea"
        maxlength="255"
        rows="3"
      ></textarea>
    </div>

    <button type="submit" class="drawer-submit">Guardar cambios</button>

  </form>

</aside>
