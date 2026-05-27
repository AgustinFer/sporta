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
      </label>

      <input
        type="text"
        name="empleado_nombre"
        id="empleado_nombre"
        required
      >

    </div>

    <!-- APELLIDO -->
    <div>

      <label>
        Apellido
      </label>

      <input
        type="text"
        name="empleado_apellido"
        id="empleado_apellido"
        required
      >

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
      >

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
      >

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
      >

    </div>

    <!-- USUARIO -->
    <div>

      <label>
        Usuario
      </label>

      <input
        type="text"
        name="empleado_usuario"
        id="empleado_usuario"
      >

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