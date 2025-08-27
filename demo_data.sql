-- =====================================================
-- ELECTROVENTURA v2.0 - DATOS DE DEMOSTRACIÓN
-- =====================================================
-- Ejecutar después de schema_v2.sql
-- =====================================================

USE electroventura_v2;

-- Desactivar verificación de claves foráneas temporalmente para inserción masiva
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- USUARIOS ADICIONALES DE DEMO
-- =====================================================

INSERT INTO usuarios (username, password, nombre_completo, email, telefono, rol, activo, creado_por) VALUES
('carlos', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Carlos Electricista', 'carlos@electroventura.com', '666111222', 'trabajador', TRUE, 1),
('maria', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'María Supervisora', 'maria@electroventura.com', '666333444', 'supervisor', TRUE, 1),
('antonio', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Antonio Técnico', 'antonio@electroventura.com', '666555666', 'trabajador', TRUE, 1),
('lucia', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lucía Oficial', 'lucia@electroventura.com', '666777888', 'trabajador', TRUE, 1),
('javier', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Javier Administrativo', 'javier@electroventura.com', '666999000', 'admin', TRUE, 1);

-- =====================================================
-- PROVEEDORES Y CONTACTOS ADICIONALES
-- =====================================================

INSERT INTO proveedores (nombre, nif_cif, direccion, telefono, email, observaciones, activo, creado_por) VALUES
('Jung Ibérica S.A.', 'A28123456', 'Parque Empresarial Las Mercedes, Calle Severo Ochoa 2, 28760 Tres Cantos, Madrid', '918034500', 'info@jung.es', 'Mecanismos y domótica Jung', TRUE, 1),
('Efapel España', 'B41987654', 'Polígono Industrial El Pino, Calle de la Innovación 15, 41016 Sevilla', '954987321', 'comercial@efapel.es', 'Especialista en mecanismos Efapel', TRUE, 1),
('ABB Electrificación España', 'A08456789', 'Carretera de Madrid Km 315.8, 50012 Zaragoza', '976503600', 'abb.spain@abb.com', 'Automatización y electrificación', TRUE, 1),
('Siemens España', 'A28000123', 'Ronda de Europa 5, 28760 Tres Cantos, Madrid', '915144444', 'siemens.spain@siemens.com', 'Tecnología industrial y edificios inteligentes', TRUE, 1),
('Hager Servicios S.L.U.', 'B84567890', 'Parque Tecnológico de Álava, Calle Albert Einstein 24, 01510 Miñano, Álava', '945298580', 'info@hager.es', 'Distribución eléctrica y KNX', TRUE, 1),
('Eléctrica Sevillana S.A.', 'A41123789', 'Polígono Sur, Sector 11, Parcela 23, 41013 Sevilla', '954786123', 'ventas@electricasevillana.es', 'Distribuidor mayorista material eléctrico', TRUE, 1),
('Almacenes Cobalto S.L.', 'B28789456', 'Calle de la Industria 67, Polígono Industrial de Coslada, 28820 Coslada, Madrid', '916731245', 'cobalto@almacenescobalto.com', 'Material eléctrico y ferretería industrial', TRUE, 1);

-- Contactos adicionales
INSERT INTO contactos (proveedor_id, nombre, telefono, email, cargo, principal, activo, creado_por) VALUES
(4, 'Pedro Ruiz', '615234567', 'pedro.ruiz@jung.es', 'Delegado Comercial', TRUE, TRUE, 1),
(4, 'Carmen López', '616345678', 'carmen.lopez@jung.es', 'Soporte Técnico', FALSE, TRUE, 1),
(5, 'Fernando Morales', '617456789', 'fernando.morales@efapel.es', 'Director Ventas', TRUE, TRUE, 1),
(6, 'Isabel Santos', '618567890', 'isabel.santos@abb.com', 'Account Manager', TRUE, TRUE, 1),
(7, 'Roberto García', '619678901', 'roberto.garcia@siemens.com', 'Ingeniero Ventas', TRUE, TRUE, 1),
(8, 'Elena Jiménez', '620789012', 'elena.jimenez@hager.es', 'Responsable KNX', TRUE, TRUE, 1),
(9, 'Miguel Herrera', '621890123', 'miguel.herrera@electricasevillana.es', 'Gerente', TRUE, TRUE, 1),
(10, 'Laura Vega', '622901234', 'laura.vega@almacenescobalto.com', 'Comercial', TRUE, TRUE, 1);

-- =====================================================
-- OBRAS ADICIONALES DE DEMOSTRACIÓN
-- =====================================================

INSERT INTO obras (codigo, nombre, descripcion, proveedor_id, direccion_obra, fecha_inicio, fecha_fin_prevista, presupuesto, estado, observaciones, activo, creado_por) VALUES
-- Obras en curso
('EV2025-001', 'Hotel Boutique San Sebastián', 'Instalación eléctrica completa y domótica hotel 4 estrellas', 4, 'Calle Miraconcha 15, 20007 San Sebastián', '2025-07-15', '2025-10-30', 120000.00, 'en_curso', 'Incluye sistema KNX completo', TRUE, 1),
('EV2025-002', 'Oficinas Torre Business Center', 'Renovación instalación eléctrica 15 plantas', 7, 'Paseo de la Castellana 200, 28046 Madrid', '2025-08-01', '2025-12-15', 180000.00, 'en_curso', 'Certificación LEED Gold', TRUE, 1),
('EV2025-003', 'Residencial Las Palmeras', 'Instalación 48 viviendas unifamiliares', 9, 'Urbanización Las Palmeras, Marbella', '2025-08-10', '2025-11-20', 95000.00, 'en_curso', '48 viviendas adosadas', TRUE, 1),
('EV2025-004', 'Centro Comercial Aqua', 'Mantenimiento y mejoras instalación eléctrica', 10, 'Avenida de Europa 26, 28224 Pozuelo de Alarcón', '2025-08-20', '2025-09-10', 25000.00, 'en_curso', 'Mantenimiento trimestral', TRUE, 1),
('EV2025-005', 'Fábrica Textil Moderna', 'Instalación industrial completa nave 5000m²', 6, 'Polígono Industrial Norte, Parcela 15, Alcalá de Henares', '2025-08-25', '2025-11-30', 150000.00, 'en_curso', 'Incluye automatización completa', TRUE, 1),

-- Obras pendientes
('EV2025-006', 'Clínica Dental Dr. Martínez', 'Instalación especializada clínica dental', 4, 'Calle Serrano 95, 28006 Madrid', '2025-09-01', '2025-09-20', 18000.00, 'pendiente', 'Quirófanos y equipos especializados', TRUE, 1),
('EV2025-007', 'Restaurante La Marisquería', 'Renovación instalación y cocina industrial', 8, 'Puerto Deportivo, Local 12, Santander', '2025-09-05', '2025-09-25', 22000.00, 'pendiente', 'Cocina industrial y salón 150 comensales', TRUE, 1),
('EV2025-008', 'Gimnasio FitMax', 'Instalación eléctrica y climatización', 5, 'Avenida de América 45, 28002 Madrid', '2025-09-10', '2025-10-05', 35000.00, 'pendiente', '2000m² instalaciones deportivas', TRUE, 1),

-- Obras finalizadas (para datos históricos)
('EV2024-089', 'Vivienda Particular Calle Alcalá', 'Reforma completa instalación eléctrica', 1, 'Calle Alcalá 234, 28028 Madrid', '2024-12-01', '2024-12-20', 8500.00, 'finalizada', 'Reforma integral vivienda 120m²', TRUE, 1),
('EV2024-090', 'Oficinas Consulting Group', 'Instalación nuevas oficinas', 2, 'Calle Velázquez 134, 28006 Madrid', '2024-11-15', '2024-12-10', 15000.00, 'finalizada', 'Oficinas 400m² con domótica básica', TRUE, 1),
('EV2024-091', 'Nave Industrial MetalCorp', 'Instalación eléctrica nave industrial', 3, 'Polígono Las Américas, Parcela 8, Getafe', '2024-10-01', '2024-11-30', 75000.00, 'finalizada', 'Nave 3000m² con grúas puente', TRUE, 1);

-- =====================================================
-- ACTIVAR OBRAS PARA LOS PRÓXIMOS DÍAS (DEMO)
-- =====================================================

-- Obras activas hoy (usar IDs de las obras recién insertadas: 4-15)
INSERT INTO obras_dia (obra_id, fecha, activa, observaciones, creado_por) VALUES
(4, CURDATE(), TRUE, 'Continuación instalación planta baja', 1),
(5, CURDATE(), TRUE, 'Tendido líneas principales', 1),
(6, CURDATE(), TRUE, 'Instalación cuadros secundarios', 1),
(8, CURDATE(), TRUE, 'Montaje automatización PLC', 1);

-- Obras para mañana
INSERT INTO obras_dia (obra_id, fecha, activa, observaciones, creado_por) VALUES
(7, DATE_ADD(CURDATE(), INTERVAL 1 DAY), TRUE, 'Revisión instalaciones existentes', 1),
(9, DATE_ADD(CURDATE(), INTERVAL 1 DAY), TRUE, 'Instalación primera planta', 1),
(10, DATE_ADD(CURDATE(), INTERVAL 1 DAY), TRUE, 'Planificación y mediciones', 1);

-- Obras para pasado mañana
INSERT INTO obras_dia (obra_id, fecha, activa, observaciones, creado_por) VALUES
(4, DATE_ADD(CURDATE(), INTERVAL 2 DAY), TRUE, 'Instalación segunda planta', 1),
(11, DATE_ADD(CURDATE(), INTERVAL 2 DAY), TRUE, 'Mantenimiento mensual', 1);

-- =====================================================
-- HORAS DE TRABAJO (ÚLTIMAS 2 SEMANAS)
-- =====================================================

-- Datos de la semana pasada
INSERT INTO horas_trabajadores (obra_id, usuario_id, fecha, hora_entrada, hora_salida, horas, descripcion, lugar_trabajo, observaciones) VALUES
-- Lunes semana pasada (usando IDs correctos de obras: 4-15)
(4, 3, DATE_SUB(CURDATE(), INTERVAL 7 DAY), '08:00:00', '16:00:00', 8.0, 'Instalación tomas de corriente planta baja', 'Calle Miraconcha 15, San Sebastián', 'Trabajo completado según planificación'),
(4, 5, DATE_SUB(CURDATE(), INTERVAL 7 DAY), '08:00:00', '16:00:00', 8.0, 'Tendido de canalización principal', 'Calle Miraconcha 15, San Sebastián', 'Coordinación con albañiles'),
(5, 7, DATE_SUB(CURDATE(), INTERVAL 7 DAY), '07:30:00', '15:30:00', 8.0, 'Instalación cuadro general BT', 'Paseo de la Castellana 200, Madrid', 'Cuadro principal instalado y testado'),

-- Martes semana pasada
(4, 3, DATE_SUB(CURDATE(), INTERVAL 6 DAY), '08:00:00', '17:00:00', 9.0, 'Instalación puntos de luz planta baja', 'Calle Miraconcha 15, San Sebastián', 'Hora extra por retraso material'),
(5, 5, DATE_SUB(CURDATE(), INTERVAL 6 DAY), '08:00:00', '16:00:00', 8.0, 'Conexionado cuadros secundarios', 'Paseo de la Castellana 200, Madrid', 'Completadas 3 de 5 líneas'),
(5, 7, DATE_SUB(CURDATE(), INTERVAL 6 DAY), '07:30:00', '15:30:00', 8.0, 'Instalación líneas de fuerza', 'Paseo de la Castellana 200, Madrid', 'Instalación maquinaria zona A'),

-- Miércoles semana pasada
(4, 3, DATE_SUB(CURDATE(), INTERVAL 5 DAY), '08:00:00', '16:00:00', 8.0, 'Conexionado luminarias LED', 'Calle Miraconcha 15, San Sebastián', 'Pruebas de funcionamiento OK'),
(4, 5, DATE_SUB(CURDATE(), INTERVAL 5 DAY), '08:00:00', '16:00:00', 8.0, 'Instalación mecanismos Jung', 'Calle Miraconcha 15, San Sebastián', 'Mecanismos serie LS990'),
(5, 6, DATE_SUB(CURDATE(), INTERVAL 5 DAY), '08:00:00', '16:00:00', 8.0, 'Certificaciones y medidas', 'Paseo de la Castellana 200, Madrid', 'Medidas reglamentarias completadas'),

-- Jueves semana pasada
(4, 3, DATE_SUB(CURDATE(), INTERVAL 4 DAY), '08:00:00', '16:00:00', 8.0, 'Revisión instalación primera planta', 'Calle Miraconcha 15, San Sebastián', 'Pequeñas correcciones realizadas'),
(5, 5, DATE_SUB(CURDATE(), INTERVAL 4 DAY), '08:00:00', '16:00:00', 8.0, 'Instalación sistema emergencia', 'Paseo de la Castellana 200, Madrid', 'Luminarias de emergencia y señalización'),
(14, 7, DATE_SUB(CURDATE(), INTERVAL 4 DAY), '09:00:00', '13:00:00', 4.0, 'Reparación avería urgente', 'Calle Alcalá 234, Madrid', 'Avería resuelta en obra finalizada'),

-- Viernes semana pasada
(4, 3, DATE_SUB(CURDATE(), INTERVAL 3 DAY), '08:00:00', '15:00:00', 7.0, 'Limpieza y recogida material', 'Calle Miraconcha 15, San Sebastián', 'Fin de semana, salida anticipada'),
(5, 5, DATE_SUB(CURDATE(), INTERVAL 3 DAY), '08:00:00', '15:00:00', 7.0, 'Documentación y planos As Built', 'Paseo de la Castellana 200, Madrid', 'Actualización planos instalación'),

-- Esta semana (Lunes a hoy)
-- Lunes esta semana
(4, 3, DATE_SUB(CURDATE(), INTERVAL 2 DAY), '08:00:00', '16:00:00', 8.0, 'Inicio segunda planta', 'Calle Miraconcha 15, San Sebastián', 'Replanteo y marcado puntos'),
(5, 5, DATE_SUB(CURDATE(), INTERVAL 2 DAY), '08:00:00', '16:00:00', 8.0, 'Instalación líneas datos', 'Paseo de la Castellana 200, Madrid', 'Canalización red informática'),
(8, 6, DATE_SUB(CURDATE(), INTERVAL 2 DAY), '07:30:00', '15:30:00', 8.0, 'Montaje cuadros control', 'Polígono Industrial Norte, Alcalá', 'Cuadros automatización PLC'),

-- Ayer
(4, 3, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '08:00:00', '16:30:00', 8.5, 'Canalización segunda planta', 'Calle Miraconcha 15, San Sebastián', 'Media hora extra por finalizar tramo'),
(5, 7, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '07:30:00', '15:30:00', 8.0, 'Instalación alumbrado exterior', 'Paseo de la Castellana 200, Madrid', 'Proyectores LED fachada'),
(8, 6, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '08:00:00', '16:00:00', 8.0, 'Programación PLC Siemens', 'Polígono Industrial Norte, Alcalá', 'Programación lógica control'),
(6, 8, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '09:00:00', '17:00:00', 8.0, 'Supervisión obras múltiples', 'Varios', 'Visitas obras en curso');

-- =====================================================
-- GASTOS E INGRESOS POR OBRA
-- =====================================================

-- Gastos materiales y subcontratación
INSERT INTO gastos_ingresos (obra_id, tipo, categoria, concepto, descripcion, cantidad, fecha, proveedor_id, documento, creado_por) VALUES
-- Hotel San Sebastián (obra 4)
(4, 'gasto', 'Material', 'Cable RZ1-K 0.6/1kV 3x2.5mm', '500 metros cable instalación general', 750.50, DATE_SUB(CURDATE(), INTERVAL 10 DAY), 9, 'F2025-0234', 1),
(4, 'gasto', 'Material', 'Mecanismos Jung LS990', 'Mecanismos serie premium color blanco', 1250.75, DATE_SUB(CURDATE(), INTERVAL 8 DAY), 4, 'F2025-0156', 1),
(4, 'gasto', 'Material', 'Luminarias LED Philips', '45 downlights LED 20W 3000K', 2340.00, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 10, 'F2025-0267', 1),
(4, 'ingreso', 'Facturación', 'Anticipo 40% Hotel San Sebastián', 'Primer pago según contrato', 48000.00, DATE_SUB(CURDATE(), INTERVAL 12 DAY), 4, 'FV2025-001', 1),

-- Torre Business Center (obra 5)
(5, 'gasto', 'Material', 'Cuadros eléctricos Schneider', 'CGP y cuadros secundarios 15 plantas', 8500.00, DATE_SUB(CURDATE(), INTERVAL 15 DAY), 2, 'F2025-0189', 1),
(5, 'gasto', 'Material', 'Bandeja metálica Pemsa', '2000 metros bandeja perforada 200mm', 3200.00, DATE_SUB(CURDATE(), INTERVAL 12 DAY), 9, 'F2025-0201', 1),
(5, 'gasto', 'Subcontrata', 'Grúa para elevación material', 'Grúa torre 3 días instalación cuadros', 1800.00, DATE_SUB(CURDATE(), INTERVAL 7 DAY), NULL, 'F2025-0278', 1),
(5, 'ingreso', 'Facturación', 'Anticipo 30% Torre Business', 'Primer pago contrato', 54000.00, DATE_SUB(CURDATE(), INTERVAL 18 DAY), 10, 'FV2025-002', 1),

-- Residencial Las Palmeras (obra 6)
(6, 'gasto', 'Material', 'Cable manguera H05VV-F', '1200m cable flexible para viviendas', 960.00, DATE_SUB(CURDATE(), INTERVAL 6 DAY), 9, 'F2025-0245', 1),
(6, 'gasto', 'Material', 'Mecanismos Efapel Logic', 'Mecanismos standard 48 viviendas', 3800.00, DATE_SUB(CURDATE(), INTERVAL 4 DAY), 5, 'F2025-0289', 1),

-- Centro Comercial Aqua (obra 7) - Mantenimiento
(7, 'gasto', 'Material', 'Lámparas reposición', 'Reposición luminarias zona food court', 450.00, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 10, 'F2025-0291', 1),
(7, 'gasto', 'Combustible', 'Gasoil furgoneta', 'Combustible desplazamientos mantenimiento', 85.50, DATE_SUB(CURDATE(), INTERVAL 2 DAY), NULL, 'T2025-0067', 1),
(7, 'ingreso', 'Facturación', 'Mantenimiento agosto 2025', 'Facturación mensual mantenimiento', 5200.00, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 10, 'FV2025-003', 1),

-- Fábrica Textil (obra 8)
(8, 'gasto', 'Material', 'Automatización Siemens', 'PLC S7-1200 y módulos expansión', 5600.00, DATE_SUB(CURDATE(), INTERVAL 9 DAY), 7, 'F2025-0223', 1),
(8, 'gasto', 'Material', 'Variadores de frecuencia', '8 variadores para motores textiles', 4200.00, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 6, 'F2025-0256', 1);

-- =====================================================
-- MATERIALES COMPRADOS POR OBRA
-- =====================================================

-- Materiales varios
INSERT INTO materiales_varios (obra_id, material, descripcion, cantidad, unidad, precio_unitario, precio_total, proveedor_id, fecha, observaciones, creado_por) VALUES
(4, 'Tubo corrugado M20', 'Tubo corrugado doble capa M20 rollo 100m', 5.00, 'rollo', 45.50, 227.50, 9, DATE_SUB(CURDATE(), INTERVAL 8 DAY), 'Para instalación empotrada', 1),
(4, 'Caja empotrar universal', 'Caja empotrar 65x65mm mecanismos', 150.00, 'ud', 0.85, 127.50, 9, DATE_SUB(CURDATE(), INTERVAL 8 DAY), 'Cajas para mecanismos Jung', 1),
(5, 'Bridas plástico', 'Bridas blancas 200x4.8mm', 2.00, 'bolsa', 12.50, 25.00, 10, DATE_SUB(CURDATE(), INTERVAL 6 DAY), 'Fijación cables bandeja', 1),
(5, 'Tornillería inox', 'Tornillos M6x25 inox para bandeja', 1.00, 'caja', 18.75, 18.75, 10, DATE_SUB(CURDATE(), INTERVAL 6 DAY), 'Fijación bandejas exteriores', 1),
(6, 'Tubo PVC rígido M25', 'Tubo PVC roscable M25 barra 3m', 80.00, 'barra', 3.25, 260.00, 10, DATE_SUB(CURDATE(), INTERVAL 4 DAY), 'Instalación vista garajes', 1),
(8, 'Canal cableado industrial', 'Canal ranurado metálico 40x40mm', 200.00, 'm', 8.50, 1700.00, 9, DATE_SUB(CURDATE(), INTERVAL 7 DAY), 'Canalización nave industrial', 1);

-- Gasto de hilo
INSERT INTO gasto_hilo (obra_id, tipo_hilo, descripcion, metros, precio_metro, precio_total, proveedor_id, fecha_uso, observaciones, creado_por) VALUES
(4, 'H05V-K 1.5mm azul', 'Hilo flexible azul neutro', 2500.00, 0.15, 375.00, 9, DATE_SUB(CURDATE(), INTERVAL 7 DAY), 'Conexiones generales hotel', 1),
(4, 'H05V-K 1.5mm marrón', 'Hilo flexible marrón fase', 2500.00, 0.15, 375.00, 9, DATE_SUB(CURDATE(), INTERVAL 7 DAY), 'Conexiones generales hotel', 1),
(4, 'H05V-K 1.5mm verde/amarillo', 'Hilo flexible tierra', 2500.00, 0.15, 375.00, 9, DATE_SUB(CURDATE(), INTERVAL 7 DAY), 'Puesta a tierra hotel', 1),
(5, 'H07V-K 2.5mm negro', 'Hilo flexible negro 2.5mm', 1200.00, 0.25, 300.00, 9, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'Conexiones cuadros torre', 1),
(8, 'H07V-K 4mm rojo', 'Hilo flexible rojo 4mm potencia', 800.00, 0.45, 360.00, 9, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'Alimentación motores textiles', 1);

-- Productos Jung
INSERT INTO jung (obra_id, producto, referencia, descripcion, cantidad, precio_unitario, precio_total, proveedor_id, fecha, observaciones, creado_por) VALUES
(4, 'Conmutador simple LS990', 'LS990SW', 'Conmutador simple blanco alpino', 45.00, 8.50, 382.50, 4, DATE_SUB(CURDATE(), INTERVAL 6 DAY), 'Habitaciones hotel', 1),
(4, 'Toma schuko LS990', 'LS990SCHUKO', 'Toma schuko con obturador infantil', 78.00, 12.75, 994.50, 4, DATE_SUB(CURDATE(), INTERVAL 6 DAY), 'Todas las estancias', 1),
(4, 'Toma RJ45 Cat6 LS990', 'LS990RJ45', 'Toma datos RJ45 Cat6 UTP', 35.00, 15.50, 542.50, 4, DATE_SUB(CURDATE(), INTERVAL 6 DAY), 'Red datos habitaciones', 1),
(4, 'Detector presencia Jung', 'KNX180UP', 'Detector presencia KNX empotrar', 25.00, 89.50, 2237.50, 4, DATE_SUB(CURDATE(), INTERVAL 4 DAY), 'Sistema domótico pasillos', 1);

-- Material iluminación
INSERT INTO material_iluminacion (obra_id, tipo_luminaria, marca, modelo, potencia, cantidad, precio_unitario, precio_total, proveedor_id, fecha, observaciones, creado_por) VALUES
(4, 'Downlight LED empotrar', 'Philips', 'CoreLine DN140B', '20W 3000K', 65.00, 28.50, 1852.50, 10, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'Iluminación general habitaciones', 1),
(4, 'Aplique pared exterior', 'Philips', 'Mygarden Creek', '15W LED', 12.00, 45.75, 549.00, 10, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'Iluminación exterior hotel', 1),
(5, 'Pantalla LED 1200mm', 'Osram', 'SubstiTUBE T8', '18W 4000K', 120.00, 32.50, 3900.00, 10, DATE_SUB(CURDATE(), INTERVAL 8 DAY), 'Iluminación oficinas torre', 1),
(6, 'Plafón LED superficie', 'Ledvance', 'Surface-C 300', '24W 4000K', 48.00, 22.50, 1080.00, 10, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'Garajes residencial', 1),
(8, 'Campana LED industrial', 'Philips', 'GentleSpace gen2', '200W 4000K', 15.00, 180.50, 2707.50, 10, DATE_SUB(CURDATE(), INTERVAL 6 DAY), 'Iluminación nave textil', 1);

-- Productos Efapel
INSERT INTO efapel (obra_id, producto, referencia, serie, descripcion, cantidad, precio_unitario, precio_total, proveedor_id, fecha, observaciones, creado_por) VALUES
(6, 'Interruptor simple', '21011', 'Logic', 'Interruptor simple 10A blanco', 96.00, 3.25, 312.00, 5, DATE_SUB(CURDATE(), INTERVAL 3 DAY), '2 por vivienda residencial', 1),
(6, 'Conmutador', '21021', 'Logic', 'Conmutador 10A blanco', 48.00, 4.15, 199.20, 5, DATE_SUB(CURDATE(), INTERVAL 3 DAY), '1 por vivienda', 1),
(6, 'Toma corriente', '21771', 'Logic', 'Toma 2P+T 16A blanco', 192.00, 5.85, 1123.20, 5, DATE_SUB(CURDATE(), INTERVAL 3 DAY), '4 por vivienda media', 1),
(6, 'Marco 2 elementos', '21290', 'Logic', 'Marco 2 elementos horizontal', 48.00, 2.10, 100.80, 5, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'Cocinas y baños', 1);

-- =====================================================
-- PARTES RÁPIDOS Y TAREAS
-- =====================================================

INSERT INTO partes_rapidos (obra_id, titulo, descripcion, urgencia, estado, asignado_a, fecha_limite, observaciones, creado_por) VALUES
(4, 'Revisar conexión cuadro planta 2', 'Salta diferencial al conectar climatización', 'alta', 'pendiente', 3, CURDATE(), 'Cliente reporta problema intermitente', 1),
(5, 'Certificar instalación BT', 'Pendiente certificado instalación baja tensión', 'media', 'en_curso', 7, DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'OCA disponible próxima semana', 1),
(7, 'Cambiar luminarias zona 3', 'Reemplazar 8 luminarias fundidas parking', 'baja', 'pendiente', 5, DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'Dentro mantenimiento programado', 1),
(8, 'Programar PLC línea 3', 'Configurar automatización línea producción 3', 'alta', 'en_curso', 6, DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'Parada producción programada mañana', 1),
(NULL, 'Inventario almacén', 'Revisar stock material Jung y Efapel', 'baja', 'pendiente', 8, DATE_ADD(CURDATE(), INTERVAL 10 DAY), 'Inventario trimestral', 1);

-- =====================================================
-- PUNTOS/BONIFICACIONES TRABAJADORES
-- =====================================================

INSERT INTO puntos (obra_id, usuario_id, concepto, puntos, fecha, observaciones, creado_por) VALUES
(4, 3, 'Trabajo fin de semana voluntario', 50, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'Sábado extra para cumplir plazo', 1),
(5, 7, 'Solución problema técnico complejo', 75, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'Resolvió incompatibilidad PLC-variador', 1),
(8, 6, 'Formación compañeros automatización', 25, DATE_SUB(CURDATE(), INTERVAL 7 DAY), 'Capacitación interna Siemens TIA Portal', 1),
(NULL, 5, 'Propuesta mejora proceso', 40, DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'Optimización ruta materiales almacén', 1),
(4, 3, 'Calidad excepcional instalación', 60, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'Cero defectos revisión cliente', 1);

-- =====================================================
-- VEHÍCULOS DE LA EMPRESA
-- =====================================================

INSERT INTO vehiculos (marca, modelo, matricula, año, combustible, kilometraje, fecha_compra, precio_compra, observaciones, activo, creado_por) VALUES
('Renault', 'Master L2H2', '1234 BBB', 2023, 'diesel', 28500.50, '2023-03-15', 32000.00, 'Furgoneta principal equipada taller móvil', TRUE, 1),
('Ford', 'Transit Connect', '5678 CCC', 2022, 'diesel', 45200.75, '2022-06-10', 25000.00, 'Vehículo trabajos urbanos ligeros', TRUE, 1),
('Volkswagen', 'Crafter 35', '9012 DDD', 2024, 'diesel', 12300.25, '2024-01-20', 38500.00, 'Transporte material pesado y grúa', TRUE, 1),
('Nissan', 'e-NV200', '3456 EEE', 2023, 'electrico', 18700.00, '2023-09-05', 28000.00, 'Vehículo ecológico centro ciudad', TRUE, 1);

-- =====================================================
-- AUDITORÍA - REGISTRO ACTIVIDAD
-- =====================================================

INSERT INTO auditoria (tabla, registro_id, accion, datos_nuevos, usuario_id, ip_address, fecha_accion) VALUES
('obras', 1, 'INSERT', '{"codigo":"EV2025-001","nombre":"Hotel Boutique San Sebastián","estado":"en_curso"}', 1, '192.168.1.100', DATE_SUB(NOW(), INTERVAL 15 DAY)),
('obras', 2, 'INSERT', '{"codigo":"EV2025-002","nombre":"Oficinas Torre Business Center","estado":"en_curso"}', 1, '192.168.1.100', DATE_SUB(NOW(), INTERVAL 14 DAY)),
('horas_trabajadores', 1, 'INSERT', '{"obra_id":1,"usuario_id":3,"horas":8.0,"fecha":"2025-08-20"}', 3, '192.168.1.105', DATE_SUB(NOW(), INTERVAL 7 DAY)),
('gastos_ingresos', 1, 'INSERT', '{"obra_id":1,"tipo":"gasto","cantidad":750.50,"concepto":"Cable RZ1-K"}', 1, '192.168.1.100', DATE_SUB(NOW(), INTERVAL 10 DAY)),
('materiales_varios', 1, 'INSERT', '{"obra_id":1,"material":"Tubo corrugado M20","cantidad":5}', 1, '192.168.1.100', DATE_SUB(NOW(), INTERVAL 8 DAY));

-- Reactivar verificación de claves foráneas
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- ESTADÍSTICAS FINALES
-- =====================================================

-- Mostrar resumen de datos insertados
SELECT 
    'RESUMEN DATOS DEMO' as info,
    (SELECT COUNT(*) FROM usuarios) as usuarios,
    (SELECT COUNT(*) FROM proveedores) as proveedores,
    (SELECT COUNT(*) FROM obras) as obras,
    (SELECT COUNT(*) FROM horas_trabajadores) as registros_horas,
    (SELECT COUNT(*) FROM gastos_ingresos) as gastos_ingresos,
    ((SELECT COUNT(*) FROM materiales_varios) + 
     (SELECT COUNT(*) FROM jung) + 
     (SELECT COUNT(*) FROM efapel) + 
     (SELECT COUNT(*) FROM material_iluminacion) + 
     (SELECT COUNT(*) FROM gasto_hilo)) as materiales_total,
    (SELECT COUNT(*) FROM partes_rapidos) as partes_rapidos,
    (SELECT COUNT(*) FROM vehiculos) as vehiculos;

-- Mostrar obras activas hoy
SELECT 'OBRAS ACTIVAS HOY' as info;
SELECT * FROM v_obras_activas_hoy;

-- Mostrar resumen horas por obra
SELECT 'RESUMEN HORAS POR OBRA' as info;
SELECT * FROM v_resumen_horas_obra WHERE total_horas > 0;

-- Mostrar balance financiero por obra
SELECT 'BALANCE FINANCIERO POR OBRA' as info;
SELECT * FROM v_gastos_por_obra WHERE total_gastos > 0 OR total_ingresos > 0;

COMMIT;

-- =====================================================
-- INSTRUCCIONES DE USO
-- =====================================================
/*
DATOS DE ACCESO DEMO:
===================

USUARIOS:
- admin / password (Administrador completo)
- supervisor / password (Supervisor general) 
- maria / password (Supervisora)
- david / password (Trabajador)
- sergio / password (Trabajador)
- carlos / password (Trabajador)
- antonio / password (Trabajador)
- lucia / password (Trabajadora)

OBRAS ACTIVAS EJEMPLO:
- EV2025-001: Hotel Boutique San Sebastián (120.000€)
- EV2025-002: Torre Business Center (180.000€)
- EV2025-005: Fábrica Textil Moderna (150.000€)

CARACTERÍSTICAS DEMO:
- 8 usuarios diferentes roles
- 10 proveedores con contactos
- 12 obras (5 en curso, 3 pendientes, 4 finalizadas)
- 25+ registros horas trabajo últimas 2 semanas
- Gastos/ingresos realistas por obra
- Materiales diversos (Jung, Efapel, iluminación, cables)
- Tareas pendientes y completadas
- Sistema puntos trabajadores
- 4 vehículos empresa
- Registro auditoría actividad

Para probar todas las funcionalidades, usar diferentes usuarios
y explorar las distintas obras con sus datos asociados.
*/
