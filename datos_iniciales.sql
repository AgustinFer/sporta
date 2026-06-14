--🌍 1. Países
INSERT INTO paises (pais_id, pais_nombre) VALUES
(1, 'Argentina'),
(2, 'Uruguay');

--🗺️ 2. Provincias
INSERT INTO provincias (provincia_id, provincia_nombre, pais_id) VALUES
(1, 'Buenos Aires', 1),
(2, 'CABA', 1),
(3, 'Montevideo', 2);

--📍 3. Localidades
INSERT INTO localidades (localidad_id, localidad_nombre, provincia_id) VALUES
(1, 'Berazategui', 1),
(2, 'Quilmes', 1),
(3, 'La Plata', 1),
(4, 'Ciudad Autónoma de Buenos Aires', 2),
(5, 'Montevideo', 3);

--👥 4. Roles
INSERT INTO roles (rol_id, rol_nombre, rol_descripcion) VALUES
(1, 'Administrador', 'Acceso total al sistema'),
(2, 'Empleado', 'Gestión de reservas'),
(3, 'Cliente', 'Usuario final');

--👤 5. Usuarios
INSERT INTO usuarios (
  usu_id, usu_usuario, usu_contrasena, usu_rol,
  usu_nombre, usu_apellido, usu_email,
  usu_celular, usu_dni,
  usu_direccion, usu_localidad_id, usu_provincia_id, usu_pais_id,
  usu_fecha_alta, usu_estado
) VALUES
(1, 'admin', '1234', 1, 'Juan', 'Perez', 'admin@mail.com', '1122334455', '30111222', 'Calle 123', 1, 1, 1, '2026-01-01', true),
(2, 'empleado1', '1234', 2, 'Ana', 'Gomez', 'ana@mail.com', '1166677788', '28999888', 'Av Siempre Viva 742', 2, 1, 1, '2026-01-10', true);

--🧍 6. Clientes
INSERT INTO clientes (
  cliente_id, cliente_nombre, cliente_apellido,
  cliente_email, cliente_celular, cliente_dni,
  cliente_estado,
  cliente_localidad_id, cliente_provincia_id, cliente_pais_id
) VALUES
(1, 'Carlos', 'Lopez', 'carlos@mail.com', '1199998888', '33444555', true, 1, 1, 1),
(2, 'Maria', 'Fernandez', 'maria@mail.com', '1177776666', '30123456', true, 2, 1, 1);

--⚽ 7. Tipo de cancha
INSERT INTO tipo_cancha (tipo_cancha_id, descripcion) VALUES
(1, 'Fútbol 5'),
(2, 'Fútbol 7'),
(3, 'Fútbol 11');

--🟢 8. Estado de cancha
INSERT INTO estado_cancha (estado_cancha_id, descripcion, observaciones) VALUES
(1, 'Disponible', ''),
(2, 'En mantenimiento', 'No disponible temporalmente');

--🏟️ 9. Canchas
INSERT INTO canchas (
  cancha_id, cancha_numero, cancha_tipo,
  cancha_precio, cancha_estado, descripcion
) VALUES
(1, 1, 1, 10000, 1, 'Cancha techada'),
(2, 2, 2, 15000, 1, 'Cancha abierta'),
(3, 3, 1, 9000, 2, 'En reparación');

--📅 10. Turnos
INSERT INTO turnos (
  tur_id, id_cancha, tur_fecha, tur_hora_inicio, tur_hora_fin
) VALUES
(1, 1, '2026-04-15', '18:00', '19:00'),
(2, 1, '2026-04-15', '19:00', '20:00'),
(3, 2, '2026-04-15', '18:00', '19:00');

--📌 11. Estado de reserva
INSERT INTO estado_reserva (estado_reserva_id, estado_reserva_descripcion, observaciones) VALUES
(1, 'Pendiente', ''),
(2, 'Confirmada', ''),
(3, 'Cancelada', '');

--📖 12. Reservas
INSERT INTO reservas (
  reserva_id, usu_id, cliente_id, tur_id,
  reser_fecha, reser_estado, reser_observaciones
) VALUES
(1, 2, 1, 1, '2026-04-10', 2, 'Pago en efectivo'),
(2, 2, 2, 2, '2026-04-11', 1, 'Pendiente de pago');

--💳 13. Método de pago
INSERT INTO metodo_pago (metodo_pago_id, metodo_nombre, metodo_descripcion, metodo_estado) VALUES
(1, 'Efectivo', '', true),
(2, 'Transferencia', '', true),
(3, 'Tarjeta de crédito', '', true),
(4, 'Tarjeta de débito', '', true),
(5, 'Mercado Pago', '', true);

--🧾 14. Facturación
INSERT INTO facturacion (
  factura_id, reserva_id, factura_fecha_emision,
  factura_total, factura_estado
) VALUES
(1, 1, '2026-04-10', 10000, 'Pagada'),
(2, 2, '2026-04-11', 15000, 'Pendiente');

--💰 15. Pagos
INSERT INTO pagos (
  pago_id, factura_id, metodo_pago_id,
  pago_fecha_pago, pago_monto
) VALUES
(1, 1, 1, '2026-04-10', 10000);

insert into estado_cancha (estado_cancha_id,descripcion,observaciones) values (default, "Inhabilitado",null);
