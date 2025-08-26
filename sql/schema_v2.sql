-- =====================================================
-- ELECTROVENTURA v2.0 - SCHEMA COMPLETO
-- Sistema orientado a OBRAS como eje central
-- =====================================================

-- Crear base de datos
CREATE DATABASE IF NOT EXISTS electroventura_v2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE electroventura_v2;

-- =====================================================
-- TABLAS DE USUARIOS Y SEGURIDAD
-- =====================================================

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nombre_completo VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    telefono VARCHAR(20),
    rol ENUM('admin', 'supervisor', 'trabajador') NOT NULL DEFAULT 'trabajador',
    activo BOOLEAN DEFAULT TRUE,
    ultimo_acceso TIMESTAMP NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    creado_por INT,
    INDEX idx_username (username),
    INDEX idx_rol (rol),
    INDEX idx_activo (activo)
);

-- =====================================================
-- TABLAS MAESTRAS - PROVEEDORES Y CONTACTOS
-- =====================================================

CREATE TABLE proveedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    nif_cif VARCHAR(20),
    direccion TEXT,
    telefono VARCHAR(20),
    email VARCHAR(100),
    observaciones TEXT,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    creado_por INT,
    INDEX idx_nombre (nombre),
    INDEX idx_activo (activo),
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL
);

CREATE TABLE contactos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proveedor_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    email VARCHAR(100),
    cargo VARCHAR(50),
    principal BOOLEAN DEFAULT FALSE,
    observaciones TEXT,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    creado_por INT,
    INDEX idx_proveedor (proveedor_id),
    INDEX idx_principal (principal),
    INDEX idx_activo (activo),
    FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE CASCADE,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- =====================================================
-- TABLA CENTRAL - OBRAS
-- =====================================================

CREATE TABLE obras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) UNIQUE NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    proveedor_id INT,
    direccion_obra TEXT,
    fecha_inicio DATE,
    fecha_fin DATE,
    fecha_fin_prevista DATE,
    presupuesto DECIMAL(10,2),
    estado ENUM('pendiente', 'en_curso', 'finalizada', 'cancelada') DEFAULT 'pendiente',
    observaciones TEXT,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    creado_por INT,
    INDEX idx_codigo (codigo),
    INDEX idx_nombre (nombre),
    INDEX idx_proveedor (proveedor_id),
    INDEX idx_estado (estado),
    INDEX idx_activo (activo),
    INDEX idx_fechas (fecha_inicio, fecha_fin),
    FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE SET NULL,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- =====================================================
-- GESTIÓN DIARIA DE OBRAS
-- =====================================================

CREATE TABLE obras_dia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    obra_id INT NOT NULL,
    fecha DATE NOT NULL,
    activa BOOLEAN DEFAULT TRUE,
    observaciones TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    creado_por INT NOT NULL,
    UNIQUE KEY unique_obra_fecha (obra_id, fecha),
    INDEX idx_fecha (fecha),
    INDEX idx_activa (activa),
    INDEX idx_obra_fecha_activa (obra_id, fecha, activa),
    FOREIGN KEY (obra_id) REFERENCES obras(id) ON DELETE CASCADE,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE RESTRICT
);

-- =====================================================
-- IMPUTACIONES - HORAS DE TRABAJO
-- =====================================================

CREATE TABLE horas_trabajadores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    obra_id INT NOT NULL,
    usuario_id INT NOT NULL,
    fecha DATE NOT NULL,
    hora_entrada TIME,
    hora_salida TIME,
    horas DECIMAL(4,2) NOT NULL,
    descripcion TEXT,
    lugar_trabajo VARCHAR(200),
    origen ENUM('web', 'mobile') DEFAULT 'web',
    latitud DECIMAL(10, 7),
    longitud DECIMAL(10, 7),
    observaciones TEXT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    modificado_por INT,
    INDEX idx_obra (obra_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_fecha (fecha),
    INDEX idx_origen (origen),
    INDEX idx_obra_fecha (obra_id, fecha),
    INDEX idx_usuario_fecha (usuario_id, fecha),
    FOREIGN KEY (obra_id) REFERENCES obras(id) ON DELETE RESTRICT,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    FOREIGN KEY (modificado_por) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- =====================================================
-- FINANZAS - GASTOS E INGRESOS POR OBRA
-- =====================================================

CREATE TABLE gastos_ingresos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    obra_id INT NOT NULL,
    tipo ENUM('gasto', 'ingreso') NOT NULL,
    categoria VARCHAR(50),
    concepto VARCHAR(200) NOT NULL,
    descripcion TEXT,
    cantidad DECIMAL(10,2) NOT NULL,
    fecha DATE NOT NULL,
    proveedor_id INT,
    documento VARCHAR(100), -- Número de factura/recibo
    observaciones TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    creado_por INT,
    modificado_por INT,
    INDEX idx_obra (obra_id),
    INDEX idx_tipo (tipo),
    INDEX idx_fecha (fecha),
    INDEX idx_proveedor (proveedor_id),
    INDEX idx_obra_tipo (obra_id, tipo),
    INDEX idx_obra_fecha (obra_id, fecha),
    FOREIGN KEY (obra_id) REFERENCES obras(id) ON DELETE RESTRICT,
    FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE SET NULL,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (modificado_por) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- =====================================================
-- MATERIALES POR OBRA
-- =====================================================

CREATE TABLE materiales_comprados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    obra_id INT,
    material VARCHAR(200) NOT NULL,
    descripcion TEXT,
    cantidad DECIMAL(10,3) NOT NULL,
    unidad VARCHAR(20) DEFAULT 'ud',
    precio_unitario DECIMAL(8,2),
    precio_total DECIMAL(10,2),
    proveedor_id INT,
    fecha_compra DATE,
    fecha_entrega DATE,
    albaran VARCHAR(50),
    factura VARCHAR(50),
    observaciones TEXT,
    stock_general BOOLEAN DEFAULT FALSE, -- Si es TRUE, no está asociado a obra específica
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    creado_por INT,
    INDEX idx_obra (obra_id),
    INDEX idx_material (material),
    INDEX idx_proveedor (proveedor_id),
    INDEX idx_stock_general (stock_general),
    INDEX idx_fecha_compra (fecha_compra),
    FOREIGN KEY (obra_id) REFERENCES obras(id) ON DELETE SET NULL,
    FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE SET NULL,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL
);

CREATE TABLE gasto_hilo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    obra_id INT,
    tipo_hilo VARCHAR(100) NOT NULL,
    descripcion TEXT,
    metros DECIMAL(10,2) NOT NULL,
    precio_metro DECIMAL(6,3),
    precio_total DECIMAL(10,2),
    proveedor_id INT,
    fecha_uso DATE,
    observaciones TEXT,
    stock_general BOOLEAN DEFAULT FALSE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    creado_por INT,
    INDEX idx_obra (obra_id),
    INDEX idx_tipo (tipo_hilo),
    INDEX idx_stock_general (stock_general),
    FOREIGN KEY (obra_id) REFERENCES obras(id) ON DELETE SET NULL,
    FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE SET NULL,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL
);

CREATE TABLE materiales_varios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    obra_id INT,
    material VARCHAR(200) NOT NULL,
    descripcion TEXT,
    cantidad DECIMAL(10,3) NOT NULL,
    unidad VARCHAR(20) DEFAULT 'ud',
    precio_unitario DECIMAL(8,2),
    precio_total DECIMAL(10,2),
    proveedor_id INT,
    fecha DATE,
    observaciones TEXT,
    stock_general BOOLEAN DEFAULT FALSE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    creado_por INT,
    INDEX idx_obra (obra_id),
    INDEX idx_material (material),
    INDEX idx_stock_general (stock_general),
    FOREIGN KEY (obra_id) REFERENCES obras(id) ON DELETE SET NULL,
    FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE SET NULL,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL
);

CREATE TABLE jung (
    id INT AUTO_INCREMENT PRIMARY KEY,
    obra_id INT,
    producto VARCHAR(200) NOT NULL,
    referencia VARCHAR(50),
    descripcion TEXT,
    cantidad DECIMAL(10,3) NOT NULL,
    precio_unitario DECIMAL(8,2),
    precio_total DECIMAL(10,2),
    proveedor_id INT,
    fecha DATE,
    observaciones TEXT,
    stock_general BOOLEAN DEFAULT FALSE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    creado_por INT,
    INDEX idx_obra (obra_id),
    INDEX idx_producto (producto),
    INDEX idx_referencia (referencia),
    INDEX idx_stock_general (stock_general),
    FOREIGN KEY (obra_id) REFERENCES obras(id) ON DELETE SET NULL,
    FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE SET NULL,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL
);

CREATE TABLE material_iluminacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    obra_id INT,
    tipo_luminaria VARCHAR(100) NOT NULL,
    marca VARCHAR(50),
    modelo VARCHAR(100),
    potencia VARCHAR(20),
    cantidad DECIMAL(10,3) NOT NULL,
    precio_unitario DECIMAL(8,2),
    precio_total DECIMAL(10,2),
    proveedor_id INT,
    fecha DATE,
    observaciones TEXT,
    stock_general BOOLEAN DEFAULT FALSE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    creado_por INT,
    INDEX idx_obra (obra_id),
    INDEX idx_tipo (tipo_luminaria),
    INDEX idx_marca (marca),
    INDEX idx_stock_general (stock_general),
    FOREIGN KEY (obra_id) REFERENCES obras(id) ON DELETE SET NULL,
    FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE SET NULL,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL
);

CREATE TABLE efapel (
    id INT AUTO_INCREMENT PRIMARY KEY,
    obra_id INT,
    producto VARCHAR(200) NOT NULL,
    referencia VARCHAR(50),
    serie VARCHAR(50),
    descripcion TEXT,
    cantidad DECIMAL(10,3) NOT NULL,
    precio_unitario DECIMAL(8,2),
    precio_total DECIMAL(10,2),
    proveedor_id INT,
    fecha DATE,
    observaciones TEXT,
    stock_general BOOLEAN DEFAULT FALSE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    creado_por INT,
    INDEX idx_obra (obra_id),
    INDEX idx_producto (producto),
    INDEX idx_referencia (referencia),
    INDEX idx_stock_general (stock_general),
    FOREIGN KEY (obra_id) REFERENCES obras(id) ON DELETE SET NULL,
    FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE SET NULL,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- =====================================================
-- OTRAS TABLAS DEL SISTEMA
-- =====================================================

CREATE TABLE partes_rapidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    obra_id INT,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT,
    urgencia ENUM('baja', 'media', 'alta', 'critica') DEFAULT 'media',
    estado ENUM('pendiente', 'en_curso', 'finalizado') DEFAULT 'pendiente',
    asignado_a INT,
    fecha_limite DATE,
    observaciones TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    creado_por INT,
    INDEX idx_obra (obra_id),
    INDEX idx_estado (estado),
    INDEX idx_urgencia (urgencia),
    INDEX idx_asignado (asignado_a),
    FOREIGN KEY (obra_id) REFERENCES obras(id) ON DELETE SET NULL,
    FOREIGN KEY (asignado_a) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL
);

CREATE TABLE puntos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    obra_id INT,
    usuario_id INT NOT NULL,
    concepto VARCHAR(200) NOT NULL,
    puntos INT NOT NULL,
    fecha DATE NOT NULL,
    observaciones TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    creado_por INT,
    INDEX idx_obra (obra_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_fecha (fecha),
    FOREIGN KEY (obra_id) REFERENCES obras(id) ON DELETE SET NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL
);

CREATE TABLE vehiculos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    marca VARCHAR(50),
    modelo VARCHAR(50),
    matricula VARCHAR(20) UNIQUE NOT NULL,
    año INT,
    combustible ENUM('gasolina', 'diesel', 'electrico', 'hibrido'),
    kilometraje DECIMAL(10,2),
    fecha_compra DATE,
    precio_compra DECIMAL(10,2),
    observaciones TEXT,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    creado_por INT,
    INDEX idx_matricula (matricula),
    INDEX idx_activo (activo),
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- =====================================================
-- TABLA DE AUDITORÍA
-- =====================================================

CREATE TABLE auditoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tabla VARCHAR(50) NOT NULL,
    registro_id INT NOT NULL,
    accion ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
    datos_anteriores JSON,
    datos_nuevos JSON,
    usuario_id INT,
    ip_address VARCHAR(45),
    fecha_accion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tabla (tabla),
    INDEX idx_registro (registro_id),
    INDEX idx_accion (accion),
    INDEX idx_usuario (usuario_id),
    INDEX idx_fecha (fecha_accion),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- =====================================================
-- DATOS INICIALES
-- =====================================================

-- Usuarios iniciales
INSERT INTO usuarios (username, password, nombre_completo, email, rol, activo, creado_por) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador Sistema', 'admin@electroventura.com', 'admin', TRUE, NULL),
('supervisor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Supervisor General', 'supervisor@electroventura.com', 'supervisor', TRUE, 1),
('david', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'David Trabajador', 'david@electroventura.com', 'trabajador', TRUE, 1),
('sergio', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sergio Trabajador', 'sergio@electroventura.com', 'trabajador', TRUE, 1);

-- Proveedores de ejemplo
INSERT INTO proveedores (nombre, nif_cif, telefono, email, activo, creado_por) VALUES
('Rexel España S.A.', 'A12345678', '912345678', 'comercial@rexel.es', TRUE, 1),
('Schneider Electric', 'B87654321', '913456789', 'ventas@schneider.es', TRUE, 1),
('Legrand Grupo', 'C11223344', '914567890', 'info@legrand.es', TRUE, 1);

-- Contactos de proveedores
INSERT INTO contactos (proveedor_id, nombre, telefono, email, cargo, principal, activo, creado_por) VALUES
(1, 'María García', '612345678', 'maria.garcia@rexel.es', 'Comercial', TRUE, TRUE, 1),
(1, 'Juan Pérez', '613456789', 'juan.perez@rexel.es', 'Técnico', FALSE, TRUE, 1),
(2, 'Ana Martín', '614567890', 'ana.martin@schneider.es', 'Directora Ventas', TRUE, TRUE, 1);

-- Obras de ejemplo
INSERT INTO obras (codigo, nombre, descripcion, proveedor_id, direccion_obra, fecha_inicio, fecha_fin_prevista, presupuesto, estado, activo, creado_por) VALUES
('OB001', 'Instalación Residencial Calle Mayor', 'Instalación eléctrica completa vivienda unifamiliar', 1, 'Calle Mayor 123, Madrid', '2025-08-22', '2025-09-15', 15000.00, 'en_curso', TRUE, 1),
('OB002', 'Reforma Industrial Nave Polígono Sur', 'Renovación instalación eléctrica nave industrial', 2, 'Polígono Sur, Nave 45', '2025-08-20', '2025-10-01', 35000.00, 'en_curso', TRUE, 1),
('OB003', 'Mantenimiento Centro Comercial Plaza', 'Mantenimiento preventivo instalaciones', 3, 'Centro Comercial Plaza Norte', '2025-08-25', '2025-08-30', 5000.00, 'pendiente', TRUE, 1);

-- Activar obras para hoy (ejemplo)
INSERT INTO obras_dia (obra_id, fecha, activa, creado_por) VALUES
(1, CURDATE(), TRUE, 1),
(2, CURDATE(), TRUE, 1);

-- =====================================================
-- PROCEDIMIENTOS ALMACENADOS
-- =====================================================

DELIMITER //

-- Procedimiento para resetear obras diarias automáticamente
CREATE PROCEDURE ResetObrasDiarias()
BEGIN
    -- Solo mantener obras del día actual
    DELETE FROM obras_dia WHERE fecha < CURDATE();
    
    -- Log de la acción
    INSERT INTO auditoria (tabla, registro_id, accion, datos_nuevos, usuario_id, ip_address)
    VALUES ('obras_dia', 0, 'DELETE', JSON_OBJECT('mensaje', 'Reset automático obras diarias', 'fecha', CURDATE()), 1, 'SISTEMA');
END //

DELIMITER ;

-- =====================================================
-- EVENTOS AUTOMÁTICOS
-- =====================================================

-- Habilitar el programador de eventos
SET GLOBAL event_scheduler = ON;

-- Crear evento para reset diario automático a las 00:00
CREATE EVENT IF NOT EXISTS ResetObrasDiariasEvent
ON SCHEDULE EVERY 1 DAY
STARTS (TIMESTAMP(CURDATE()) + INTERVAL 1 DAY)
DO
  CALL ResetObrasDiarias();

-- =====================================================
-- ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- =====================================================

-- Índices compuestos para consultas frecuentes
CREATE INDEX idx_horas_obra_usuario_fecha ON horas_trabajadores (obra_id, usuario_id, fecha);
CREATE INDEX idx_gastos_obra_tipo_fecha ON gastos_ingresos (obra_id, tipo, fecha);
CREATE INDEX idx_materiales_obra_fecha ON materiales_comprados (obra_id, fecha_compra);

-- =====================================================
-- VISTAS ÚTILES
-- =====================================================

-- Vista de obras activas del día
CREATE VIEW v_obras_activas_hoy AS
SELECT 
    o.id,
    o.codigo,
    o.nombre,
    o.descripcion,
    p.nombre as proveedor_nombre,
    od.fecha as fecha_activa,
    od.activa
FROM obras o
LEFT JOIN proveedores p ON o.proveedor_id = p.id
INNER JOIN obras_dia od ON o.id = od.obra_id
WHERE od.fecha = CURDATE() AND od.activa = TRUE AND o.activo = TRUE;

-- Vista de resumen de horas por obra
CREATE VIEW v_resumen_horas_obra AS
SELECT 
    o.id as obra_id,
    o.codigo as obra_codigo,
    o.nombre as obra_nombre,
    COUNT(ht.id) as total_registros,
    SUM(ht.horas) as total_horas,
    COUNT(DISTINCT ht.usuario_id) as trabajadores_distintos,
    MIN(ht.fecha) as fecha_inicio_trabajo,
    MAX(ht.fecha) as fecha_ultimo_trabajo
FROM obras o
LEFT JOIN horas_trabajadores ht ON o.id = ht.obra_id
GROUP BY o.id, o.codigo, o.nombre;

-- Vista de gastos por obra
CREATE VIEW v_gastos_por_obra AS
SELECT 
    o.id as obra_id,
    o.codigo as obra_codigo,
    o.nombre as obra_nombre,
    COALESCE(SUM(CASE WHEN gi.tipo = 'gasto' THEN gi.cantidad ELSE 0 END), 0) as total_gastos,
    COALESCE(SUM(CASE WHEN gi.tipo = 'ingreso' THEN gi.cantidad ELSE 0 END), 0) as total_ingresos,
    COALESCE(SUM(CASE WHEN gi.tipo = 'ingreso' THEN gi.cantidad ELSE -gi.cantidad END), 0) as balance
FROM obras o
LEFT JOIN gastos_ingresos gi ON o.id = gi.obra_id
GROUP BY o.id, o.codigo, o.nombre;

COMMIT;
