<!-- OVERLAY -->
<div class="drawer-overlay"></div>

<!-- DRAWER -->
<aside class="drawer">

  <!-- HEADER -->
  <div class="drawer-header">

    <h3>
      Nuevo cliente
    </h3>

    <button class="drawer-close">
      ✕
    </button>

  </div>

  <!-- FORM -->
  <form
    class="drawer-form"
    method="POST"
    action="/clientes/index.php"
  >

    <!-- NOMBRE -->
    <div>

      <label for="cliente_nombre">
        Nombre
      </label>

      <input
        type="text"
        id="cliente_nombre"
        name="cliente_nombre"
        placeholder="Ingresar nombre"
        required
      >

    </div>

    <!-- APELLIDO -->
    <div>

      <label for="cliente_apellido">
        Apellido
      </label>

      <input
        type="text"
        id="cliente_apellido"
        name="cliente_apellido"
        placeholder="Ingresar apellido"
      >

    </div>

    <!-- TELÉFONO -->
    <div>

      <label for="cliente_telefono">
        Teléfono
      </label>

      <input
        type="text"
        id="cliente_telefono"
        name="cliente_telefono"
        placeholder="Ingresar teléfono"
      >

    </div>

    <!-- EMAIL -->
    <div>

      <label for="cliente_email">
        Email
      </label>

      <input
        type="email"
        id="cliente_email"
        name="cliente_email"
        placeholder="Ingresar email"
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