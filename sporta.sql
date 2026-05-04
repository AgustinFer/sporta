CREATE DATABASE IF NOT EXISTS sporta;
USE sporta;

-- =========================
-- 1. TABLAS BASE
-- =========================

CREATE TABLE paises (
    pais_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    pais_nombre VARCHAR(100) NOT NULL
);

CREATE TABLE provincias (
    provincia_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    provincia_nombre VARCHAR(100) NOT NULL,
    pais_id INT(11) NOT NULL,
    FOREIGN KEY (pais_id) REFERENCES paises(pais_id)
);

CREATE TABLE localidades (
    localidad_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    localidad_nombre VARCHAR(100) NOT NULL,
    provincia_id INT(11) NOT NULL,
    FOREIGN KEY (provincia_id) REFERENCES provincias(provincia_id)
);

CREATE TABLE roles (
    rol_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    rol_nombre VARCHAR(50) NOT NULL,
    rol_descripcion VARCHAR(255)
);

CREATE TABLE tipo_cancha (
    tipo_cancha_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    descripcion VARCHAR(100) NOT NULL
);

CREATE TABLE estado_cancha (
    estado_cancha_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    descripcion VARCHAR(100) NOT NULL,
    observaciones VARCHAR(255)
);

CREATE TABLE estado_reserva (
    estado_reserva_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    estado_reserva_descripcion VARCHAR(100) NOT NULL,
    observaciones VARCHAR(255)
);

CREATE TABLE metodo_pago (
    metodo_pago_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    metodo_nombre VARCHAR(100) NOT NULL,
    metodo_descripcion VARCHAR(255),
    metodo_estado BOOLEAN DEFAULT TRUE
);

-- =========================
-- 2. TABLAS PRINCIPALES
-- =========================

CREATE TABLE usuarios (
    usu_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    usu_usuario VARCHAR(50) NOT NULL,
    usu_contrasena VARCHAR(255) NOT NULL,
    usu_rol INT(11) NOT NULL,
    usu_nombre VARCHAR(100),
    usu_apellido VARCHAR(100),
    usu_email VARCHAR(100),
    usu_celular VARCHAR(20),
    usu_dni VARCHAR(20),
    usu_direccion VARCHAR(255),
    usu_localidad_id INT(11),
    usu_provincia_id INT(11),
    usu_pais_id INT(11),
    usu_fecha_alta DATE,
    usu_estado BOOLEAN DEFAULT TRUE,

    FOREIGN KEY (usu_rol) REFERENCES roles(rol_id),
    FOREIGN KEY (usu_localidad_id) REFERENCES localidades(localidad_id),
    FOREIGN KEY (usu_provincia_id) REFERENCES provincias(provincia_id),
    FOREIGN KEY (usu_pais_id) REFERENCES paises(pais_id)
);

CREATE TABLE clientes (
    cliente_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    cliente_nombre VARCHAR(100) NOT NULL,
    cliente_apellido VARCHAR(100) NOT NULL,
    cliente_email VARCHAR(100),
    cliente_celular VARCHAR(20),
    cliente_dni VARCHAR(20),
    cliente_estado BOOLEAN DEFAULT TRUE,
    cliente_localidad_id INT(11),
    cliente_provincia_id INT(11),
    cliente_pais_id INT(11),

    FOREIGN KEY (cliente_localidad_id) REFERENCES localidades(localidad_id),
    FOREIGN KEY (cliente_provincia_id) REFERENCES provincias(provincia_id),
    FOREIGN KEY (cliente_pais_id) REFERENCES paises(pais_id)
);

CREATE TABLE canchas (
    cancha_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    cancha_numero INT(11) NOT NULL,
    cancha_tipo INT(11) NOT NULL,
    cancha_precio DECIMAL(10,2) NOT NULL,
    cancha_estado INT(11) NOT NULL,
    descripcion VARCHAR(255),

    FOREIGN KEY (cancha_tipo) REFERENCES tipo_cancha(tipo_cancha_id),
    FOREIGN KEY (cancha_estado) REFERENCES estado_cancha(estado_cancha_id)
);

-- =========================
-- 3. OPERATIVAS
-- =========================

CREATE TABLE turnos (
    tur_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    id_cancha INT(11) NOT NULL,
    tur_fecha DATE NOT NULL,
    tur_hora_inicio TIME NOT NULL,
    tur_hora_fin TIME NOT NULL,

    FOREIGN KEY (id_cancha) REFERENCES canchas(cancha_id)
);

CREATE TABLE reservas (
    reserva_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    usu_id INT(11),
    cliente_id INT(11),
    tur_id INT(11) NOT NULL,
    reser_fecha DATE NOT NULL,
    reser_estado INT(11) NOT NULL,
    reser_observaciones VARCHAR(255),

    FOREIGN KEY (usu_id) REFERENCES usuarios(usu_id),
    FOREIGN KEY (cliente_id) REFERENCES clientes(cliente_id),
    FOREIGN KEY (tur_id) REFERENCES turnos(tur_id),
    FOREIGN KEY (reser_estado) REFERENCES estado_reserva(estado_reserva_id)
);

-- =========================
-- 4. FACTURACIÓN
-- =========================

CREATE TABLE facturacion (
    factura_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    reserva_id INT(11) NOT NULL,
    factura_fecha_emision DATE NOT NULL,
    factura_total DECIMAL(10,2) NOT NULL,
    factura_estado VARCHAR(50),

    FOREIGN KEY (reserva_id) REFERENCES reservas(reserva_id)
);

CREATE TABLE pagos (
    pago_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    factura_id INT(11) NOT NULL,
    metodo_pago_id INT(11) NOT NULL,
    pago_fecha_pago DATE NOT NULL,
    pago_monto DECIMAL(10,2) NOT NULL,

    FOREIGN KEY (factura_id) REFERENCES facturacion(factura_id),
    FOREIGN KEY (metodo_pago_id) REFERENCES metodo_pago(metodo_pago_id)
);