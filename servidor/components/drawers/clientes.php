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
      </label>

      <input
        type="text"
        name="cliente_nombre"
        id="cliente_nombre"
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
        name="cliente_apellido"
        id="cliente_apellido"
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
        name="cliente_celular"
        id="cliente_celular"
      >

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
      >

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
      >

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