-- MySan Database Schema
-- Sistema de Administración de Ahorros Grupales (San/Susu)

CREATE DATABASE IF NOT EXISTS mysan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mysan;

-- Tabla de Usuarios
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    pregunta_secreta TEXT,
    respuesta_secreta VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabla de Categorías de Productos
CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    descripcion TEXT,
    color VARCHAR(20) -- violeta, menta, salmon
) ENGINE=InnoDB;

-- Tabla de Productos
CREATE TABLE productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    marca VARCHAR(50),
    modelo VARCHAR(50),
    descripcion TEXT,
    valor_total DECIMAL(10, 2) NOT NULL,
    imagen VARCHAR(255),
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabla de Grupos San
CREATE TABLE grupos_san (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    fecha_inicio DATE NOT NULL,
    frecuencia ENUM('quincenal', 'mensual') NOT NULL,
    numero_cuotas INT NOT NULL,
    cupos_totales INT NOT NULL,
    cupos_ocupados INT DEFAULT 0,
    monto_cuota DECIMAL(10, 2) NOT NULL,
    estado ENUM('abierto', 'en_curso', 'finalizado') DEFAULT 'abierto',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabla de Participantes
CREATE TABLE participantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grupo_san_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    cedula VARCHAR(20) UNIQUE NOT NULL,
    telefono VARCHAR(20),
    direccion TEXT,
    fecha_inscripcion DATE NOT NULL,
    ha_recibido BOOLEAN DEFAULT FALSE,
    fecha_entrega DATE,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (grupo_san_id) REFERENCES grupos_san(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabla de Pagos
CREATE TABLE pagos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    participante_id INT NOT NULL,
    numero_cuota INT NOT NULL,
    monto DECIMAL(10, 2) NOT NULL,
    fecha_pago DATE NOT NULL,
    fecha_vencimiento DATE NOT NULL,
    estado ENUM('pendiente', 'pagado', 'atrasado') DEFAULT 'pendiente',
    metodo_pago VARCHAR(50),
    comprobante VARCHAR(255),
    notas TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (participante_id) REFERENCES participantes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabla de Turnos (Sorteos)
CREATE TABLE turnos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grupo_san_id INT NOT NULL,
    participante_id INT,
    numero_turno INT NOT NULL,
    fecha_turno DATE NOT NULL,
    metodo_asignacion ENUM('aleatorio', 'manual') NOT NULL,
    estado ENUM('pendiente', 'asignado', 'entregado') DEFAULT 'pendiente',
    fecha_asignacion TIMESTAMP,
    fecha_entrega TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (grupo_san_id) REFERENCES grupos_san(id) ON DELETE CASCADE,
    FOREIGN KEY (participante_id) REFERENCES participantes(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Tabla de Comprobantes
CREATE TABLE comprobantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('pago', 'entrega') NOT NULL,
    pago_id INT,
    turno_id INT,
    codigo_qr TEXT,
    datos_json TEXT,
    generado_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pago_id) REFERENCES pagos(id) ON DELETE CASCADE,
    FOREIGN KEY (turno_id) REFERENCES turnos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Insertar categorías predeterminadas
INSERT INTO categorias (nombre, descripcion, color) VALUES
('Electrodomésticos', 'Neveras, lavadoras, televisores, etc.', 'violeta'),
('Telefonía', 'Smartphones de alta gama', 'menta'),
('Motocicletas', 'Motos de diferentes marcas y cilindradas', 'salmon');

-- Insertar usuario administrador por defecto
-- Usuario: admin, Password: 1234 (bcrypt hash)
INSERT INTO usuarios (username, password, nombre, email, pregunta_secreta, respuesta_secreta) VALUES
('admin', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'Administrador', 'admin@mysan.local', '¿Cuál es tu color favorito?', 'azul');

-- Productos de ejemplo
INSERT INTO productos (categoria_id, nombre, marca, modelo, valor_total) VALUES
(1, 'Nevera Samsung', 'Samsung', 'RT38K5930SL', 15000.00),
(1, 'Lavadora LG', 'LG', 'WM3900HWA', 12000.00),
(1, 'Televisor 55"', 'Sony', 'XBR-55X900H', 18000.00),
(2, 'iPhone 15 Pro Max', 'Apple', '256GB', 35000.00),
(2, 'Samsung Galaxy S24 Ultra', 'Samsung', '512GB', 32000.00),
(3, 'Yamaha FZ', 'Yamaha', 'FZ-150', 45000.00),
(3, 'Honda CB190R', 'Honda', 'CB190R', 42000.00),
(3, 'Suzuki Gixxer', 'Suzuki', 'Gixxer 250', 48000.00);
