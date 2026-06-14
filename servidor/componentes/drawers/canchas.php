<!-- OVERLAY -->
<div class="drawer-overlay"></div>

<!-- DRAWER -->
<aside class="drawer">

  <div class="drawer-header">
    <h3 id="drawer-title">Nueva Cancha</h3>
    <button type="button" class="drawer-close" id="cerrarPanelCancha">✕</button>
  </div>

  <form class="drawer-form" id="formCancha">
    <input type="hidden" id="edit_cancha_id">

    <div>
      <label>Número de Cancha <span class="required">*</span></label>
      <input type="number" id="cancha_numero" required>
    </div>

    <div>
      <label>Precio por hora <span class="required">*</span></label>
      <input type="number" id="cancha_precio" step="0.01" required>
    </div>

    <div>
      <label>Descripción</label>
      <textarea id="cancha_descripcion" rows="3" style="width:100%;padding:12px 14px;border-radius:12px;border:1px solid rgba(0,0,0,0.1);background:rgba(255,255,255,0.8);font-size:1rem;font-family:inherit"></textarea>
    </div>

    <div>
      <label>Estado <span class="required">*</span></label>
      <select id="cancha_estado" required>
        <option value="1">Disponible</option>
        <option value="2">Mantenimiento</option>
        <option value="3">Inhabilitado</option>
      </select>
    </div>

    <button type="submit" class="drawer-submit">Guardar Cancha</button>
  </form>

</aside>
