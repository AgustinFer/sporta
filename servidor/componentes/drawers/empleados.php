<!-- OVERLAY -->
<div class="drawer-overlay"></div>

<!-- DRAWER -->
<aside class="drawer">

  <div class="drawer-header">

    <h3 id="drawer-title">
      Nuevo empleado
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
      name="edit_empleado_id"
      id="edit_empleado_id"
    >

    <!-- NOMBRE -->
    <div>

      <label>
        Nombre
        <span class="required">*</span>
      </label>

      <input
        type="text"
        name="empleado_nombre"
        id="empleado_nombre"
        maxlength="100"
        pattern="[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s]+"
        title="Solo se permiten letras"
        data-validate="soloLetras"
        required
      >

      <span class="field-error" id="error_empleado_nombre"></span>

    </div>

    <!-- APELLIDO -->
    <div>

      <label>
        Apellido
        <span class="required">*</span>
      </label>

      <input
        type="text"
        name="empleado_apellido"
        id="empleado_apellido"
        maxlength="100"
        pattern="[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s]+"
        title="Solo se permiten letras"
        data-validate="soloLetras"
        required
      >

      <span class="field-error" id="error_empleado_apellido"></span>

    </div>

    <!-- USUARIO -->
    <div>

      <label>
        Usuario
        <span class="required">*</span>
      </label>

      <input
        type="text"
        name="empleado_usuario"
        id="empleado_usuario"
        maxlength="50"
        required
      >

    </div>

    <!-- ROL -->
    <div>

      <label>
        Rol
        <span class="required">*</span>
      </label>

      <select
        name="empleado_rol"
        id="empleado_rol"
        required
      >

        <option value="">
          Seleccionar rol
        </option>

        <!-- Ajustar IDs según tu tabla roles -->
        <option value="1">
          Administrador
        </option>

        <option value="2">
          Empleado
        </option>

      </select>

    </div>

    <!-- CELULAR -->
    <div>

      <label>
        Teléfono
      </label>

      <input
        type="text"
        name="empleado_celular"
        id="empleado_celular"
        maxlength="20"
        pattern="[\d\s\+\-\(\)]+"
        title="Solo se permiten números, +, -, ( ) y espacios"
        data-validate="telefono"
      >

      <span class="field-error" id="error_empleado_celular"></span>

    </div>

    <!-- EMAIL -->
    <div>

      <label>
        Email
      </label>

      <input
        type="email"
        name="empleado_email"
        id="empleado_email"
        maxlength="100"
        data-validate="email"
      >

      <span class="field-error" id="error_empleado_email"></span>

    </div>

    <!-- DNI -->
    <div>

      <label>
        DNI
      </label>

      <input
        type="text"
        name="empleado_dni"
        id="empleado_dni"
        maxlength="20"
        pattern="\d+"
        title="Solo se permiten números"
        data-validate="soloNumeros"
      >

      <span class="field-error" id="error_empleado_dni"></span>

    </div>

    <!-- DIRECCIÓN -->
    <div>

      <label>
        Dirección
      </label>

      <input
        type="text"
        name="empleado_direccion"
        id="empleado_direccion"
        maxlength="255"
      >

    </div>

    <!-- SUBMIT -->
    <button
      type="submit"
      class="drawer-submit"
    >
      Guardar empleado
    </button>

  </form>

</aside>