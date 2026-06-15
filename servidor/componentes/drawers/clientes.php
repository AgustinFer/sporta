<!-- OVERLAY -->
<div class="drawer-overlay"></div>

<!-- DRAWER -->
<aside class="drawer">

  <div class="drawer-header">

    <h3 id="drawer-title">
      Nuevo cliente
    </h3>

    <button
      type="button"
      class="drawer-close"
    >
      ✕
    </button>

  </div>

  <form
    class="drawer-form"
    method="POST"
    action=""
  >

    <!-- ID OCULTO -->
    <input
      type="hidden"
      name="edit_cliente_id"
      id="edit_cliente_id"
    >

    <!-- NOMBRE -->
    <div>

      <label>
        Nombre
        <span class="required">*</span>
      </label>

      <input
        type="text"
        name="cliente_nombre"
        id="cliente_nombre"
        maxlength="100"
        pattern="[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s]+"
        title="Solo se permiten letras"
        data-validate="soloLetras"
        required
      >

      <span class="field-error" id="error_cliente_nombre"></span>

    </div>

    <!-- APELLIDO -->
    <div>

      <label>
        Apellido
        <span class="required">*</span>
      </label>

      <input
        type="text"
        name="cliente_apellido"
        id="cliente_apellido"
        maxlength="100"
        pattern="[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s]+"
        title="Solo se permiten letras"
        data-validate="soloLetras"
        required
      >

      <span class="field-error" id="error_cliente_apellido"></span>

    </div>

    <!-- CELULAR -->
    <div>

      <label>
        Teléfono
      </label>

      <input
        type="text"
        name="cliente_celular"
        id="cliente_celular"
        maxlength="20"
        pattern="[\d\s\+\-\(\)]+"
        title="Solo se permiten números, +, -, ( ) y espacios"
        data-validate="telefono"
      >

      <span class="field-error" id="error_cliente_celular"></span>

    </div>

    <!-- EMAIL -->
    <div>

      <label>
        Email
      </label>

      <input
        type="email"
        name="cliente_email"
        id="cliente_email"
        maxlength="100"
        data-validate="email"
      >

      <span class="field-error" id="error_cliente_email"></span>

    </div>

    <!-- DNI -->
    <div>

      <label>
        DNI
      </label>

      <input
        type="text"
        name="cliente_dni"
        id="cliente_dni"
        maxlength="20"
        pattern="\d+"
        title="Solo se permiten números"
        data-validate="soloNumeros"
      >

      <span class="field-error" id="error_cliente_dni"></span>

    </div>

    <!-- SUBMIT -->
    <button
      type="submit"
      class="drawer-submit"
    >
      Guardar cliente
    </button>

  </form>

</aside>