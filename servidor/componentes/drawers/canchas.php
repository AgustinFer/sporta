<!-- OVERLAY -->
<div class="drawer-overlay"></div>

<!-- DRAWER -->
<aside class="drawer">

  <div class="drawer-header">
    <h3 id="drawer-title">Nueva Cancha</h3>
    <button type="button" class="drawer-close">✕</button>
  </div>

  <form class="drawer-form" id="formCancha">
    <input type="hidden" name="edit_cancha_id" id="edit_cancha_id">

    <div>
      <label>Número de Cancha <span class="required">*</span></label>
      <input
        type="number"
        name="cancha_numero"
        id="cancha_numero"
        min="1"
        pattern="[1-9]\d*"
        title="Debe ser un número positivo"
        data-validate="numeroCancha"
        required
      >
      <span class="field-error" id="error_cancha_numero"></span>
    </div>

    <div>
      <label>Precio por hora <span class="required">*</span></label>
      <input
        type="number"
        name="cancha_precio"
        id="cancha_precio"
        step="0.01"
        min="0"
        pattern="\d+(\.\d{1,2})?"
        title="Ingrese un precio válido (ej: 1500 o 1500.50)"
        data-validate="precio"
        required
      >
      <span class="field-error" id="error_cancha_precio"></span>
    </div>

    <div>
      <label>Descripción</label>
      <textarea
        name="cancha_descripcion"
        id="cancha_descripcion"
        rows="3"
        class="drawer-textarea"
      ></textarea>
    </div>

    <div>
      <label>Estado <span class="required">*</span></label>
      <select
        name="cancha_estado"
        id="cancha_estado"
        class="drawer-select"
        required
      >
        <option value="1">Disponible</option>
        <option value="2">Mantenimiento</option>
        <option value="3">Inhabilitado</option>
      </select>
    </div>

    <button type="submit" class="drawer-submit">Guardar Cancha</button>
  </form>

</aside>
