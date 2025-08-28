# ACTUALIZACIÓN BBDD - ELECTROVENTURA v2.1

## 📋 RESUMEN DE CAMBIOS ESTRUCTURALES

### 🗑️ **ELIMINACIÓN DE TABLAS**
- ❌ `proveedores` → Se migra datos relevantes a `clientes`
- ❌ `contactos` → Se reemplaza por `clientes`  
- ❌ `obras_dia` → Ya no se necesita gestión diaria específica
- ❌ `partes_rapidos` → Funcionalidad obsoleta

### 🔄 **MODIFICACIÓN DE TABLAS EXISTENTES**

#### **Tabla `obras`**
```sql
ALTER TABLE obras 
ADD COLUMN porcentaje_avance DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Seguimiento manual del progreso de obra';
```

#### **Tabla `horas_trabajadores`**
```sql
ALTER TABLE horas_trabajadores 
ADD COLUMN tipo_trabajo ENUM('obra', 'oficina', 'excepcion') DEFAULT 'obra' 
COMMENT 'Integra horas de oficina y excepciones en la misma tabla';
```

#### **Tabla `gastos_ingresos`**
```sql
-- Cambiar referencia de proveedor_id por cliente_id
ALTER TABLE gastos_ingresos 
CHANGE COLUMN proveedor_id cliente_id INT,
ADD CONSTRAINT FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL;
```

### 🗂️ **UNIFICACIÓN DE MATERIALES**

#### **Nueva tabla `materiales` (unifica 6 tablas existentes)**
```sql
CREATE TABLE materiales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    obra_id INT,
    categoria ENUM('general', 'hilo', 'jung', 'efapel', 'iluminacion', 'varios') NOT NULL,
    material VARCHAR(200) NOT NULL,
    referencia VARCHAR(50),
    descripcion TEXT,
    marca VARCHAR(50),
    modelo VARCHAR(100),
    cantidad DECIMAL(10,3) NOT NULL,
    unidad VARCHAR(20) DEFAULT 'ud',
    precio_unitario DECIMAL(8,2),
    precio_total DECIMAL(10,2),
    cliente_id INT,
    fecha DATE,
    stock_general BOOLEAN DEFAULT FALSE,
    observaciones TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    creado_por INT,
    INDEX idx_obra_categoria (obra_id, categoria),
    INDEX idx_material (material),
    INDEX idx_stock_general (stock_general),
    FOREIGN KEY (obra_id) REFERENCES obras(id),
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    FOREIGN KEY (creado_por) REFERENCES usuarios(id)
);
```

**Tablas que se unifican:**
- `materiales_comprados` → categoria: 'general'
- `materiales_varios` → categoria: 'varios'
- `gasto_hilo` → categoria: 'hilo'
- `jung` → categoria: 'jung'
- `efapel` → categoria: 'efapel'
- `material_iluminacion` → categoria: 'iluminacion'

---

## 🆕 **NUEVAS TABLAS A CREAR**

### 1. **Tabla `clientes`** (Reemplaza proveedores/contactos)
```sql
CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    nif_cif VARCHAR(20),
    direccion TEXT,
    telefono VARCHAR(20),
    email VARCHAR(100),
    contacto_principal VARCHAR(100),
    cargo_contacto VARCHAR(50),
    observaciones TEXT,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    creado_por INT,
    INDEX idx_nombre (nombre),
    INDEX idx_activo (activo),
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL
);
```

### 2. **Tabla `obras_clientes`** (Relación múltiple)
```sql
CREATE TABLE obras_clientes (
    obra_id INT,
    cliente_id INT,
    fecha_asociacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    principal BOOLEAN DEFAULT FALSE COMMENT 'Cliente principal de la obra',
    creado_por INT,
    PRIMARY KEY (obra_id, cliente_id),
    INDEX idx_principal (principal),
    FOREIGN KEY (obra_id) REFERENCES obras(id) ON DELETE CASCADE,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL
);
```

### 3. **Tabla `contratos_mantenimiento`** (Gestión mensual)
```sql
CREATE TABLE contratos_mantenimiento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    mes ENUM('enero','febrero','marzo','abril','mayo','junio',
             'julio','agosto','septiembre','octubre','noviembre','diciembre') NOT NULL,
    año INT NOT NULL,
    descripcion TEXT,
    precio_mensual DECIMAL(10,2),
    activo BOOLEAN DEFAULT TRUE,
    observaciones TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    creado_por INT,
    UNIQUE KEY unique_cliente_mes_año (cliente_id, mes, año),
    INDEX idx_cliente_mes_año (cliente_id, mes, año),
    INDEX idx_activo (activo),
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL
);
```

### 4. **Tabla `uso_vehiculos`** (Gestión completa vehículos)
```sql
CREATE TABLE uso_vehiculos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehiculo_id INT NOT NULL,
    usuario_id INT NOT NULL,
    fecha DATE NOT NULL,
    km_inicial DECIMAL(10,2),
    km_final DECIMAL(10,2),
    km_recorridos DECIMAL(10,2) GENERATED ALWAYS AS (km_final - km_inicial) STORED,
    combustible_litros DECIMAL(8,2),
    coste_combustible DECIMAL(8,2),
    tipo_uso ENUM('obra', 'mantenimiento', 'administrativo', 'personal') DEFAULT 'obra',
    obra_id INT,
    descripcion TEXT,
    observaciones TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_vehiculo_fecha (vehiculo_id, fecha),
    INDEX idx_usuario_fecha (usuario_id, fecha),
    INDEX idx_tipo_uso (tipo_uso),
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id) ON DELETE RESTRICT,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    FOREIGN KEY (obra_id) REFERENCES obras(id) ON DELETE SET NULL
);
```

### 5. **Tabla `vehiculos_mantenimiento`** (ITV, seguros, revisiones)
```sql
CREATE TABLE vehiculos_mantenimiento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehiculo_id INT NOT NULL,
    tipo ENUM('itv', 'seguro', 'mantenimiento', 'revision', 'reparacion', 'multa') NOT NULL,
    fecha_programada DATE,
    fecha_realizada DATE,
    coste DECIMAL(10,2),
    proveedor VARCHAR(100),
    descripcion TEXT,
    documento VARCHAR(100) COMMENT 'Número factura/recibo',
    proximo_mantenimiento DATE,
    kilometraje_realizado DECIMAL(10,2),
    estado ENUM('pendiente', 'realizado', 'vencido') DEFAULT 'pendiente',
    observaciones TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    creado_por INT,
    INDEX idx_vehiculo_tipo (vehiculo_id, tipo),
    INDEX idx_fecha_programada (fecha_programada),
    INDEX idx_estado (estado),
    INDEX idx_proximo (proximo_mantenimiento),
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id) ON DELETE CASCADE,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL
);
```

### 6. **Tabla `bajas_vacaciones`** (Gestión ausencias)
```sql
CREATE TABLE bajas_vacaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo ENUM('vacaciones', 'baja_medica', 'baja_laboral', 'permiso', 'festivo', 'formacion') NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    dias_totales INT GENERATED ALWAYS AS (DATEDIFF(fecha_fin, fecha_inicio) + 1) STORED,
    motivo TEXT,
    documento VARCHAR(100),
    aprobado_por INT,
    estado ENUM('pendiente', 'aprobado', 'denegado', 'cancelado') DEFAULT 'pendiente',
    fecha_aprobacion TIMESTAMP NULL,
    observaciones TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    creado_por INT,
    INDEX idx_usuario_fechas (usuario_id, fecha_inicio, fecha_fin),
    INDEX idx_tipo_estado (tipo, estado),
    INDEX idx_fechas_activas (fecha_inicio, fecha_fin, estado),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (aprobado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL
);
```

### 7. **Tabla `control_gastos_personales`** (Sistema puntos mejorado)
```sql
CREATE TABLE control_gastos_personales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL COMMENT 'Filtro: Sergio, David, Ventura, Oficina',
    fecha DATE NOT NULL,
    tipo ENUM('cobro_cliente', 'gasto_empresa', 'anticipo', 'devolucion') NOT NULL,
    concepto VARCHAR(200) NOT NULL,
    cliente VARCHAR(100),
    cantidad DECIMAL(10,2) NOT NULL,
    descripcion TEXT,
    documento VARCHAR(100),
    categoria ENUM('jung', 'efapel', 'material', 'combustible', 'dietas', 'otros') DEFAULT 'otros',
    mes_liquidacion INT,
    año_liquidacion INT,
    liquidado BOOLEAN DEFAULT FALSE,
    fecha_liquidacion TIMESTAMP NULL,
    observaciones TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    creado_por INT,
    INDEX idx_usuario_mes_año (usuario_id, mes_liquidacion, año_liquidacion),
    INDEX idx_usuario_fecha (usuario_id, fecha),
    INDEX idx_liquidado (liquidado),
    INDEX idx_tipo_categoria (tipo, categoria),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT chk_usuarios_especiales CHECK (usuario_id IN (
        SELECT id FROM usuarios WHERE username IN ('sergio', 'david', 'ventura', 'admin')
    ))
);
```

---

## 📊 **NUEVAS VISTAS Y FUNCIONALIDADES**

### Vista: Control gastos mensuales por trabajador
```sql
CREATE VIEW v_control_gastos_mensual AS
SELECT 
    u.username,
    u.nombre_completo,
    cgp.mes_liquidacion,
    cgp.año_liquidacion,
    SUM(CASE WHEN cgp.tipo = 'cobro_cliente' THEN cgp.cantidad ELSE 0 END) as total_cobros,
    SUM(CASE WHEN cgp.tipo = 'gasto_empresa' THEN cgp.cantidad ELSE 0 END) as total_gastos,
    SUM(CASE WHEN cgp.tipo = 'cobro_cliente' THEN cgp.cantidad 
             ELSE -cgp.cantidad END) as balance_mes,
    COUNT(*) as total_movimientos,
    cgp.liquidado
FROM usuarios u
INNER JOIN control_gastos_personales cgp ON u.id = cgp.usuario_id
GROUP BY u.id, u.username, u.nombre_completo, cgp.mes_liquidacion, cgp.año_liquidacion, cgp.liquidado;
```

### Vista: Materiales unificados por obra
```sql
CREATE VIEW v_materiales_obra AS
SELECT 
    m.id,
    o.codigo as obra_codigo,
    o.nombre as obra_nombre,
    m.categoria,
    m.material,
    m.referencia,
    m.marca,
    m.modelo,
    m.cantidad,
    m.unidad,
    m.precio_unitario,
    m.precio_total,
    c.nombre as cliente_nombre,
    m.fecha,
    m.stock_general
FROM materiales m
LEFT JOIN obras o ON m.obra_id = o.id
LEFT JOIN clientes c ON m.cliente_id = c.id;
```

### Vista: Vehículos con próximos mantenimientos
```sql
CREATE VIEW v_vehiculos_mantenimientos_proximos AS
SELECT 
    v.matricula,
    v.marca,
    v.modelo,
    vm.tipo as tipo_mantenimiento,
    vm.fecha_programada,
    vm.proximo_mantenimiento,
    DATEDIFF(vm.proximo_mantenimiento, CURDATE()) as dias_restantes,
    vm.estado,
    vm.descripcion
FROM vehiculos v
INNER JOIN vehiculos_mantenimiento vm ON v.id = vm.vehiculo_id
WHERE vm.proximo_mantenimiento IS NOT NULL 
  AND vm.estado != 'realizado'
  AND v.activo = TRUE
ORDER BY dias_restantes ASC;
```

---

## 📝 **PLAN DE IMPLEMENTACIÓN**

### **FASE 1: PREPARACIÓN Y BACKUP**
```sql
-- 1. Crear backup de seguridad
CREATE DATABASE electroventura_v2_backup_20250828 AS (SELECT * FROM electroventura_v2);

-- 2. Desactivar verificaciones temporalmente
SET FOREIGN_KEY_CHECKS = 0;
SET AUTOCOMMIT = 0;
START TRANSACTION;
```

### **FASE 2: CREACIÓN DE NUEVAS ESTRUCTURAS**
1. Crear tabla `clientes`
2. Crear tabla `obras_clientes`  
3. Crear tabla `contratos_mantenimiento`
4. Crear tabla `uso_vehiculos`
5. Crear tabla `vehiculos_mantenimiento`
6. Crear tabla `bajas_vacaciones`
7. Crear tabla `control_gastos_personales`
8. Crear tabla `materiales` (unificada)

### **FASE 3: MIGRACIÓN DE DATOS**
```sql
-- 3.1 Migrar proveedores relevantes a clientes
INSERT INTO clientes (nombre, nif_cif, direccion, telefono, email, contacto_principal, cargo_contacto, observaciones, activo, creado_por)
SELECT 
    p.nombre, 
    p.nif_cif, 
    p.direccion, 
    p.telefono, 
    p.email,
    COALESCE(c.nombre, 'Sin contacto'),
    COALESCE(c.cargo, ''),
    p.observaciones, 
    p.activo, 
    p.creado_por
FROM proveedores p
LEFT JOIN contactos c ON p.id = c.proveedor_id AND c.principal = TRUE;

-- 3.2 Crear relación obras-clientes basada en proveedor_id actual
INSERT INTO obras_clientes (obra_id, cliente_id, principal, creado_por)
SELECT 
    o.id as obra_id,
    cl.id as cliente_id,
    TRUE as principal,
    o.creado_por
FROM obras o
INNER JOIN proveedores p ON o.proveedor_id = p.id
INNER JOIN clientes cl ON p.nombre = cl.nombre;

-- 3.3 Migrar todas las tablas de materiales a la tabla unificada
-- Materiales comprados
INSERT INTO materiales (obra_id, categoria, material, descripcion, cantidad, unidad, precio_unitario, precio_total, cliente_id, fecha, stock_general, observaciones, creado_por)
SELECT obra_id, 'general', material, descripcion, cantidad, unidad, precio_unitario, precio_total, 
       (SELECT cl.id FROM clientes cl INNER JOIN proveedores p ON cl.nombre = p.nombre WHERE p.id = mc.proveedor_id LIMIT 1),
       fecha_compra, stock_general, observaciones, creado_por
FROM materiales_comprados mc;

-- Materiales varios
INSERT INTO materiales (obra_id, categoria, material, descripcion, cantidad, unidad, precio_unitario, precio_total, cliente_id, fecha, stock_general, observaciones, creado_por)
SELECT obra_id, 'varios', material, descripcion, cantidad, unidad, precio_unitario, precio_total,
       (SELECT cl.id FROM clientes cl INNER JOIN proveedores p ON cl.nombre = p.nombre WHERE p.id = mv.proveedor_id LIMIT 1),
       fecha, stock_general, observaciones, creado_por
FROM materiales_varios mv;

-- Gasto hilo
INSERT INTO materiales (obra_id, categoria, material, descripcion, cantidad, unidad, precio_unitario, precio_total, cliente_id, fecha, stock_general, observaciones, creado_por)
SELECT obra_id, 'hilo', tipo_hilo, descripcion, metros, 'm', precio_metro, precio_total,
       (SELECT cl.id FROM clientes cl INNER JOIN proveedores p ON cl.nombre = p.nombre WHERE p.id = gh.proveedor_id LIMIT 1),
       fecha_uso, stock_general, observaciones, creado_por
FROM gasto_hilo gh;

-- Jung
INSERT INTO materiales (obra_id, categoria, material, referencia, descripcion, cantidad, unidad, precio_unitario, precio_total, cliente_id, fecha, stock_general, observaciones, creado_por)
SELECT obra_id, 'jung', producto, referencia, descripcion, cantidad, 'ud', precio_unitario, precio_total,
       (SELECT cl.id FROM clientes cl INNER JOIN proveedores p ON cl.nombre = p.nombre WHERE p.id = j.proveedor_id LIMIT 1),
       fecha, stock_general, observaciones, creado_por
FROM jung j;

-- Efapel
INSERT INTO materiales (obra_id, categoria, material, referencia, descripcion, marca, cantidad, unidad, precio_unitario, precio_total, cliente_id, fecha, stock_general, observaciones, creado_por)
SELECT obra_id, 'efapel', producto, referencia, descripcion, serie, cantidad, 'ud', precio_unitario, precio_total,
       (SELECT cl.id FROM clientes cl INNER JOIN proveedores p ON cl.nombre = p.nombre WHERE p.id = e.proveedor_id LIMIT 1),
       fecha, stock_general, observaciones, creado_por
FROM efapel e;

-- Material iluminación
INSERT INTO materiales (obra_id, categoria, material, descripcion, marca, modelo, cantidad, unidad, precio_unitario, precio_total, cliente_id, fecha, stock_general, observaciones, creado_por)
SELECT obra_id, 'iluminacion', tipo_luminaria, potencia, marca, modelo, cantidad, 'ud', precio_unitario, precio_total,
       (SELECT cl.id FROM clientes cl INNER JOIN proveedores p ON cl.nombre = p.nombre WHERE p.id = mi.proveedor_id LIMIT 1),
       fecha, stock_general, observaciones, creado_por
FROM material_iluminacion mi;
```

### **FASE 4: MODIFICACIÓN DE TABLAS EXISTENTES**
```sql
-- 4.1 Añadir columnas a tablas existentes
ALTER TABLE obras ADD COLUMN porcentaje_avance DECIMAL(5,2) DEFAULT 0.00;

ALTER TABLE horas_trabajadores 
ADD COLUMN tipo_trabajo ENUM('obra', 'oficina', 'excepcion') DEFAULT 'obra';

-- 4.2 Actualizar referencias de proveedor_id a cliente_id en gastos_ingresos
ALTER TABLE gastos_ingresos ADD COLUMN cliente_id INT;

UPDATE gastos_ingresos gi
SET cliente_id = (
    SELECT cl.id 
    FROM clientes cl 
    INNER JOIN proveedores p ON cl.nombre = p.nombre 
    WHERE p.id = gi.proveedor_id 
    LIMIT 1
);

ALTER TABLE gastos_ingresos 
ADD CONSTRAINT FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL;
```

### **FASE 5: ELIMINACIÓN DE TABLAS OBSOLETAS**
```sql
-- 5.1 Eliminar foreign keys que apuntan a tablas obsoletas
ALTER TABLE obras DROP FOREIGN KEY obras_ibfk_1; -- proveedor_id constraint
ALTER TABLE gastos_ingresos DROP FOREIGN KEY gastos_ingresos_ibfk_2; -- proveedor_id constraint

-- 5.2 Eliminar columnas obsoletas
ALTER TABLE obras DROP COLUMN proveedor_id;
ALTER TABLE gastos_ingresos DROP COLUMN proveedor_id;

-- 5.3 Eliminar tablas obsoletas
DROP TABLE IF EXISTS obras_dia;
DROP TABLE IF EXISTS partes_rapidos;
DROP TABLE IF EXISTS materiales_comprados;
DROP TABLE IF EXISTS materiales_varios;
DROP TABLE IF EXISTS gasto_hilo;
DROP TABLE IF EXISTS jung;
DROP TABLE IF EXISTS efapel;
DROP TABLE IF EXISTS material_iluminacion;
DROP TABLE IF EXISTS contactos;
DROP TABLE IF EXISTS proveedores;
```

### **FASE 6: DATOS INICIALES Y CONFIGURACIÓN**
```sql
-- 6.1 Crear obra especial "Excepciones"
INSERT INTO obras (codigo, nombre, descripcion, fecha_inicio, estado, activo, creado_por) VALUES
('EXC-001', 'Excepciones', 'Obra especial para imputaciones de atascos, formación y otros conceptos especiales', CURDATE(), 'en_curso', TRUE, 1);

-- 6.2 Crear usuarios especiales para control gastos si no existen
INSERT IGNORE INTO usuarios (username, password, nombre_completo, email, rol, activo, creado_por) VALUES
('ventura', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ventura Electricista', 'ventura@electroventura.com', 'trabajador', TRUE, 1);

-- 6.3 Insertar datos demo contratos mantenimiento
INSERT INTO contratos_mantenimiento (cliente_id, mes, año, descripcion, precio_mensual, activo, creado_por) VALUES
(1, 'enero', 2025, 'Mantenimiento preventivo mensual instalaciones', 500.00, TRUE, 1),
(1, 'febrero', 2025, 'Mantenimiento preventivo mensual instalaciones', 500.00, TRUE, 1),
(2, 'enero', 2025, 'Revisión cuadros eléctricos', 750.00, TRUE, 1);
```

### **FASE 7: CREACIÓN DE VISTAS Y PROCEDIMIENTOS**
```sql
-- Crear las vistas definidas anteriormente
-- Actualizar procedimientos existentes
-- Crear triggers si son necesarios
```

### **FASE 8: FINALIZACIÓN**
```sql
-- 8.1 Reactivar verificaciones
SET FOREIGN_KEY_CHECKS = 1;

-- 8.2 Commit de todos los cambios
COMMIT;

-- 8.3 Análizar tablas para optimizar índices
ANALYZE TABLE clientes, materiales, uso_vehiculos, bajas_vacaciones;

-- 8.4 Verificación final
SELECT 'Migración completada exitosamente' as status;
```

---

## 🧪 **PLAN DE TESTING**

### **Verificaciones Post-Migración**
```sql
-- Test 1: Verificar integridad de datos migrados
SELECT 
    'Clientes' as tabla, COUNT(*) as registros FROM clientes
UNION ALL
SELECT 
    'Materiales_Unificados' as tabla, COUNT(*) as registros FROM materiales
UNION ALL
SELECT 
    'Obras_Clientes_Relacion' as tabla, COUNT(*) as registros FROM obras_clientes;

-- Test 2: Verificar obras con porcentaje de avance
SELECT codigo, nombre, porcentaje_avance FROM obras LIMIT 5;

-- Test 3: Verificar horas con tipos de trabajo
SELECT tipo_trabajo, COUNT(*) as cantidad FROM horas_trabajadores GROUP BY tipo_trabajo;

-- Test 4: Verificar gastos con nuevas referencias
SELECT COUNT(*) as gastos_con_cliente FROM gastos_ingresos WHERE cliente_id IS NOT NULL;
```

---

## 🔄 **PLAN DE ROLLBACK**

### **En caso de problemas durante la migración:**
```sql
-- 1. Rollback de transacción
ROLLBACK;

-- 2. Restaurar desde backup
DROP DATABASE electroventura_v2;
CREATE DATABASE electroventura_v2;
-- Restaurar desde electroventura_v2_backup_20250828

-- 3. Verificar integridad post-rollback
USE electroventura_v2;
SHOW TABLES;
SELECT COUNT(*) FROM proveedores; -- Debe devolver datos originales
```

---

## 📅 **CRONOGRAMA ESTIMADO**

| Fase | Duración | Descripción |
|------|----------|-------------|
| **Preparación** | 30 min | Backup y configuración inicial |
| **Creación estructuras** | 45 min | Nuevas tablas y relaciones |
| **Migración datos** | 60 min | Transferir datos existentes |
| **Modificaciones** | 30 min | Alterar tablas existentes |
| **Eliminaciones** | 15 min | Limpiar estructuras obsoletas |
| **Configuración** | 30 min | Datos iniciales y vistas |
| **Testing** | 45 min | Verificaciones y pruebas |
| **Total estimado** | **4-5 horas** | Migración completa |

---

## ⚠️ **CONSIDERACIONES IMPORTANTES**

1. **Backup obligatorio** antes de ejecutar cualquier cambio
2. **Ejecutar en entorno de prueba** primero
3. **Verificar espacio en disco** suficiente para duplicar datos temporalmente
4. **Planificar ventana de mantenimiento** para evitar interrupciones
5. **Actualizar aplicación PHP** para usar nuevas estructuras
6. **Revisar permisos de usuario** supervisor tras los cambios

---

## 🎯 **BENEFICIOS POST-MIGRACIÓN**

- ✅ **Gestión simplificada** de materiales en una sola tabla
- ✅ **Control completo** de vehículos (km, combustible, mantenimientos)
- ✅ **Sistema robusto** de ausencias y vacaciones
- ✅ **Control financiero** detallado por trabajador
- ✅ **Contratos de mantenimiento** organizados por meses
- ✅ **Eliminación** de funcionalidades obsoletas
- ✅ **Base de datos** más limpia y eficiente

---

*Documento generado el 28/08/2025 para migración ELECTROVENTURA v2.0 → v2.1*
