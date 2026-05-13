<!-- OVERLAY -->
<div class="drawer-overlay"></div>

<!-- DRAWER -->
<aside class="drawer">

  <div class="drawer-header">

    <h3>
      Nuevo cliente
    </h3>

    <button class="drawer-close">
      ✕
    </button>

  </div>

  <form
    class="drawer-form"
    method="POST"
    action=""
  >

    <!-- NOMBRE -->
    <div>

      <label>
        Nombre
      </label>

      <input
        type="text"
        name="cliente_nombre"
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