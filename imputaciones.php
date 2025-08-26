<?php
/**
 * ELECTROVENTURA v2.0 - IMPUTACIONES
 * ==================================
 * Página principal para registro de horas, gastos y materiales por obra
 */

require_once 'config/config.php';
requireLogin();

$mensaje = '';
$tipo_mensaje = '';

// Procesar formulario de imputación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    try {
        $db = Database::getInstance()->getConnection();
        $accion = $_POST['accion'];
        
        if ($accion === 'registrar_horas') {
            // Registrar horas trabajadas
            $obra_id = (int)$_POST['obra_id'];
            $horas = (float)$_POST['horas'];
            $descripcion = trim($_POST['descripcion']);
            $fecha = $_POST['fecha'] ?? date('Y-m-d');
            $hora_entrada = $_POST['hora_entrada'] ?? null;
            $hora_salida = $_POST['hora_salida'] ?? null;
            
            if ($obra_id && $horas > 0) {
                $stmt = $db->prepare("
                    INSERT INTO horas_trabajadores (usuario_id, obra_id, fecha, hora_entrada, hora_salida, horas, descripcion, origen)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'web')
                ");
                $stmt->execute([$_SESSION['user']['id'], $obra_id, $fecha, $hora_entrada, $hora_salida, $horas, $descripcion]);
                
                logActivity('HORAS_REGISTRADAS', 'horas_trabajadores', null, null,
                           "Registradas $horas horas en obra $obra_id");
                
                $mensaje = "Horas registradas correctamente: $horas h";
                $tipo_mensaje = 'success';
            } else {
                $mensaje = 'Datos incompletos para el registro de horas.';
                $tipo_mensaje = 'danger';
            }
            
        } elseif ($accion === 'registrar_gasto') {
            // Registrar gasto/ingreso - Solo Admin y Supervisor
            if (!canManageGastosYMateriales()) {
                $mensaje = 'No tienes permisos para registrar gastos o ingresos.';
                $tipo_mensaje = 'danger';
            } else {
                $obra_id = (int)$_POST['obra_id'];
                $tipo = $_POST['tipo'];
                $concepto = trim($_POST['concepto']);
                $importe = (float)$_POST['importe'];
                $fecha = $_POST['fecha'];
                $observaciones = trim($_POST['observaciones'] ?? '');
                
                if ($obra_id && $importe > 0 && in_array($tipo, ['gasto', 'ingreso'])) {
                    $stmt = $db->prepare("
                        INSERT INTO gastos_ingresos (obra_id, tipo, concepto, cantidad, fecha, observaciones, creado_por)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$obra_id, $tipo, $concepto, $importe, $fecha, $observaciones, $_SESSION['user']['id']]);
                    
                    logActivity('GASTO_REGISTRADO', 'gastos_ingresos', null, null,
                               "Registrado $tipo de €$importe en obra $obra_id");
                    
                    $mensaje = ucfirst($tipo) . " registrado correctamente: €" . number_format($importe, 2);
                    $tipo_mensaje = 'success';
                } else {
                    $mensaje = 'Datos incompletos para el registro del movimiento.';
                    $tipo_mensaje = 'danger';
                }
            }
            
        } elseif ($accion === 'registrar_material') {
            // Registrar material - Solo Admin y Supervisor
            if (!canManageGastosYMateriales()) {
                $mensaje = 'No tienes permisos para registrar materiales.';
                $tipo_mensaje = 'danger';
            } else {
                $obra_id = (int)$_POST['obra_id'];
                $tipo_material = $_POST['tipo_material'];
                $descripcion = trim($_POST['descripcion']);
                $cantidad = (float)$_POST['cantidad'];
                $precio_unitario = (float)$_POST['precio_unitario'];
                $importe_total = $cantidad * $precio_unitario;
                $proveedor = trim($_POST['proveedor'] ?? '');
                $fecha = $_POST['fecha'];
                
                if ($obra_id && $cantidad > 0 && $precio_unitario > 0) {
                    // Determinar tabla según tipo de material
                    $tabla = match($tipo_material) {
                        'varios' => 'materiales_varios',
                        'jung' => 'jung', 
                        'iluminacion' => 'material_iluminacion',
                        'efapel' => 'efapel',
                        'comprados' => 'materiales_comprados',
                        'hilo' => 'gasto_hilo',
                        default => 'materiales_varios'
                    };
                    
                    // Insertar según el tipo de material con campos correctos del schema
                    if ($tipo_material === 'comprados') {
                        $stmt = $db->prepare("
                            INSERT INTO materiales_comprados (obra_id, material, descripcion, cantidad, precio_unitario, precio_total, fecha_compra, creado_por)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([$obra_id, $descripcion, $descripcion, $cantidad, $precio_unitario, $importe_total, $fecha, $_SESSION['user']['id']]);
                    } elseif ($tipo_material === 'hilo') {
                        $stmt = $db->prepare("
                            INSERT INTO gasto_hilo (obra_id, tipo_hilo, descripcion, metros, precio_metro, precio_total, fecha_uso, creado_por)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([$obra_id, $descripcion, $descripcion, $cantidad, $precio_unitario, $importe_total, $fecha, $_SESSION['user']['id']]);
                    } else {
                        // Para otros tipos (varios, jung, iluminacion, efapel)
                        $stmt = $db->prepare("
                            INSERT INTO $tabla (obra_id, producto, descripcion, cantidad, precio_unitario, precio_total, fecha, creado_por)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([$obra_id, $descripcion, $descripcion, $cantidad, $precio_unitario, $importe_total, $fecha, $_SESSION['user']['id']]);
                    }
                    
                    logActivity('MATERIAL_REGISTRADO', $tabla, null, null,
                               "Registrado material $tipo_material por €$importe_total en obra $obra_id");
                    
                    $mensaje = "Material registrado: €" . number_format($importe_total, 2) . " ($cantidad x €" . number_format($precio_unitario, 2) . ")";
                    $tipo_mensaje = 'success';
                } else {
                    $mensaje = 'Datos incompletos para el registro del material.';
                    $tipo_mensaje = 'danger';
                }
            }
        }
        
    } catch (Exception $e) {
        error_log("Error en imputaciones: " . $e->getMessage());
        $mensaje = 'Error al procesar la solicitud. Por favor, inténtalo de nuevo.';
        $tipo_mensaje = 'danger';
    }
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Obtener obras disponibles para hoy
    $stmt = $db->prepare("
        SELECT o.id as obra_id, o.codigo, o.nombre, 
               COALESCE(p.nombre, 'Sin cliente') as cliente, o.direccion_obra as direccion
        FROM obras_dia od
        INNER JOIN obras o ON od.obra_id = o.id
        LEFT JOIN proveedores p ON o.proveedor_id = p.id
        WHERE od.fecha = CURDATE() 
        AND od.activa = 1
        AND o.estado IN ('pendiente', 'en_curso')
        ORDER BY o.nombre ASC
    ");
    $stmt->execute();
    $obras_disponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener imputaciones del usuario para hoy
    $stmt = $db->prepare("
        SELECT ht.*, o.codigo as obra_codigo, o.nombre as obra_nombre
        FROM horas_trabajadores ht
        INNER JOIN obras o ON ht.obra_id = o.id
        WHERE ht.usuario_id = ? AND ht.fecha = CURDATE()
        ORDER BY ht.fecha_registro DESC
        LIMIT 10
    ");
    $stmt->execute([$_SESSION['user']['id']]);
    $horas_hoy = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Estadísticas del día para el usuario
    $stmt = $db->prepare("
        SELECT COUNT(*) as total_registros, SUM(horas) as total_horas
        FROM horas_trabajadores 
        WHERE usuario_id = ? AND fecha = CURDATE()
    ");
    $stmt->execute([$_SESSION['user']['id']]);
    $stats_hoy = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Error cargando datos imputaciones: " . $e->getMessage());
    $obras_disponibles = [];
    $horas_hoy = [];
    $stats_hoy = ['total_registros' => 0, 'total_horas' => 0];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Imputaciones - Electroventura v2.0</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #e8f5f4 0%, #f0f9f8 50%, #e8f5f4 100%) !important;
            padding-top: 0 !important;
            margin: 0;
        }
        
        .imputaciones-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            padding-top: 90px; /* Espacio para el navbar */
        }
        
        .imputaciones-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .main-panel {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .side-panel {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            height: fit-content;
            position: sticky;
            top: 2rem;
        }
        
        .tabs {
            display: flex;
            border-bottom: 2px solid #f1f3f4;
            margin-bottom: 0;
        }
        
        .tab {
            padding: 1rem 2rem;
            border: none;
            background: transparent;
            cursor: pointer;
            font-weight: 500;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }
        
        .tab.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }
        
        .tab:hover {
            background: #f8f9fa;
        }
        
        .tab-content {
            display: none;
            padding: 2rem;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-row.full {
            grid-template-columns: 1fr;
        }
        
        .select-obra {
            padding: 0.875rem;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1rem;
            background: white;
            margin-bottom: 1.5rem;
        }
        
        .select-obra:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: linear-gradient(135deg, var(--primary-color) 0%, #457b9d 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .recent-item {
            padding: 1rem;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            background: #f8f9fa;
        }
        
        .recent-item .time {
            font-size: 0.8rem;
            color: #666;
            float: right;
        }
        
        .recent-item .obra {
            font-weight: 500;
            color: var(--primary-color);
        }
        
        @media (max-width: 1024px) {
            .imputaciones-grid {
                grid-template-columns: 1fr;
            }
            
            .side-panel {
                position: static;
            }
        }
        
        @media (max-width: 768px) {
            .imputaciones-container {
                padding: 0 0.5rem;
                padding-top: 95px; /* Más espacio para evitar solapamiento */
                margin-top: 10px; /* Margen adicional para separación visual */
            }
            
            .tabs {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                margin: 0 -0.5rem;
                padding: 0 0.5rem;
            }
            
            .tab {
                padding: 0.75rem 1rem;
                white-space: nowrap;
                min-width: fit-content;
            }
            
            .tab-content {
                padding: 1rem;
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .form-group {
                margin-bottom: 1rem;
            }
            
            .btn {
                padding: 0.75rem 1rem;
                width: 100%;
                margin-top: 0.5rem;
            }
            
            /* Header más compacto en móvil */
            .navbar {
                padding: 0.5rem 0.75rem;
                height: auto;
                min-height: 70px; /* Altura mínima más generosa */
                box-shadow: 0 2px 10px rgba(0,0,0,0.1); /* Sombra para separación visual */
            }
            
            .navbar-brand {
                font-size: 1rem;
                flex: 1;
            }
            
            .navbar-user {
                flex-direction: column;
                align-items: flex-end;
                gap: 0.2rem;
            }
            
            .user-name, .user-role {
                font-size: 0.8rem;
                line-height: 1.2;
            }
            
            .btn-logout {
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
            }
            
            /* Controles de horas más grandes en móvil */
            .hours-controls {
                display: flex;
                align-items: center;
                gap: 1rem;
                margin: 1rem 0;
            }
            
            .hours-controls button {
                width: 50px;
                height: 50px;
                border-radius: 50%;
                border: none;
                font-size: 1.5rem;
                font-weight: bold;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .hours-display {
                flex: 1;
                text-align: center;
                font-size: 2rem;
                font-weight: bold;
                color: var(--primary-color);
                padding: 1rem;
                background: #f8f9fa;
                border-radius: 10px;
            }
            
            /* Evitar scroll horizontal */
            .container, .row, .col {
                max-width: 100%;
                overflow-x: hidden;
            }
            
            body {
                overflow-x: hidden;
            }
            
            /* Stats más compactas */
            .stats-summary .stat {
                padding: 1rem;
                text-align: center;
            }
            
            .stats-summary .stat-value {
                font-size: 1.5rem;
            }
            
            .recent-activity {
                margin-top: 1rem;
            }
            
            .recent-item {
                padding: 0.75rem;
                margin-bottom: 0.5rem;
            }
        }
        
        @media (max-width: 480px) {
            .imputaciones-container {
                padding: 0 0.25rem;
                padding-top: 90px; /* Más espacio en pantallas muy pequeñas */
                margin-top: 8px; /* Margen adicional */
            }
            
            .tab-content {
                padding: 0.75rem;
            }
            
            .navbar {
                padding: 0.25rem 0.5rem;
                min-height: 65px; /* Altura mínima en pantallas muy pequeñas */
            }
            
            .navbar-brand {
                font-size: 0.9rem;
            }
            
            .hours-display {
                font-size: 1.5rem;
                padding: 0.75rem;
            }
            
            .hours-controls button {
                width: 40px;
                height: 40px;
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body class="internal-page">
    <!-- Header de navegación -->
    <nav class="navbar">
        <div class="d-flex" style="width: 100%; justify-content: space-between; align-items: center;">
            <a href="<?= canManageObrasDelDia() ? 'admin_home.php' : (isTrabajador() ? 'trabajador_home.php' : 'supervisor_home.php') ?>" class="navbar-brand">
                <i class="fas fa-arrow-left" style="margin-right: 0.5rem;"></i>
                <i class="fas fa-bolt"></i>
                Electroventura v2.0
            </a>
            <div class="navbar-user">
                <span class="user-name"><?= htmlspecialchars(getCurrentUser()['nombre_completo'] ?? 'Usuario') ?></span>
                <span class="user-role"><?= ucfirst(getCurrentUser()['rol'] ?? 'usuario') ?></span>
                <a href="logout.php" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Salir
                </a>
            </div>
        </div>
    </nav>

    <div class="imputaciones-container">
        <!-- Header de página -->
        <div style="text-align: center; margin-bottom: 2rem;">
            <h1><i class="fas fa-clipboard-list"></i> Imputaciones</h1>
            <?php if (isTrabajador()): ?>
                <p>Registra las horas trabajadas en las obras del día</p>
            <?php else: ?>
                <p>Registra horas trabajadas, gastos y materiales en las obras del día</p>
            <?php endif; ?>
            
            <?php if ($mensaje): ?>
                <div class="alert alert-<?= $tipo_mensaje ?>">
                    <i class="fas fa-<?= $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
                    <?= htmlspecialchars($mensaje) ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (empty($obras_disponibles)): ?>
            <!-- No hay obras disponibles -->
            <div class="alert alert-warning text-center">
                <i class="fas fa-exclamation-triangle" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                <h3>No hay obras disponibles para hoy</h3>
                <p>
                    <?php if (canManageObrasDelDia()): ?>
                        <a href="gestionar_obras.php" class="btn btn-primary">
                            <i class="fas fa-cog"></i> Configurar obras del día
                        </a>
                    <?php else: ?>
                        Contacta con el administrador para configurar las obras del día.
                    <?php endif; ?>
                </p>
            </div>
        <?php else: ?>
            <div class="imputaciones-grid">
                <!-- Panel principal con tabs -->
                <div class="main-panel">
                    <!-- Tabs de navegación -->
                    <div class="tabs">
                        <button class="tab active" onclick="showTab('horas')">
                            <i class="fas fa-clock"></i> Horas
                        </button>
                        <?php if (!isTrabajador()): ?>
                            <button class="tab" onclick="showTab('gastos')">
                                <i class="fas fa-euro-sign"></i> Gastos/Ingresos
                            </button>
                            <button class="tab" onclick="showTab('materiales')">
                                <i class="fas fa-boxes"></i> Materiales
                            </button>
                        <?php endif; ?>
                    </div>

                    <!-- Tab de Horas -->
                    <div id="tab-horas" class="tab-content active">
                        <h3><i class="fas fa-clock"></i> Registrar Horas Trabajadas</h3>
                        
                        <form method="POST" id="formHoras">
                            <input type="hidden" name="accion" value="registrar_horas">
                            
                            <select name="obra_id" class="select-obra" required>
                                <option value="">Selecciona una obra...</option>
                                <?php foreach ($obras_disponibles as $obra): ?>
                                    <option value="<?= $obra['obra_id'] ?>">
                                        <?= htmlspecialchars($obra['codigo']) ?> - <?= htmlspecialchars($obra['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Fecha</label>
                                    <input type="date" name="fecha" class="form-control" 
                                           value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Hora</label>
                                    <input type="time" name="hora" class="form-control" 
                                           value="<?= date('H:i') ?>">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-clock"></i> Horas trabajadas
                                    </label>
                                    
                                    <!-- Control mejorado para móvil -->
                                    <div class="hours-control-modern d-block d-md-none">
                                        <div class="hours-buttons-container">
                                            <button type="button" class="btn btn-hours-modern btn-decrement" onclick="decreaseHours()">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <div class="hours-display-modern" id="hoursDisplay">8.0h</div>
                                            <button type="button" class="btn btn-hours-modern btn-increment" onclick="increaseHours()">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                        <!-- Botones de horas predefinidas para móvil -->
                                        <div class="hours-presets-mobile">
                                            <button type="button" class="btn btn-preset-mobile" onclick="setHours(4)">4h</button>
                                            <button type="button" class="btn btn-preset-mobile" onclick="setHours(6)">6h</button>
                                            <button type="button" class="btn btn-preset-mobile active" onclick="setHours(8)">8h</button>
                                            <button type="button" class="btn btn-preset-mobile" onclick="setHours(10)">10h</button>
                                        </div>
                                    </div>
                                    
                                    <!-- Control mejorado para desktop -->
                                    <div class="hours-control-desktop d-none d-md-flex">
                                        <button type="button" class="btn btn-hours-desktop" onclick="decreaseHours()">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <input type="number" name="horas" id="hoursInput" class="form-control hours-input-desktop" 
                                               step="0.25" min="0.25" max="24" value="8.0" required
                                               onchange="updateHoursDisplay(this.value)">
                                        <button type="button" class="btn btn-hours-desktop" onclick="increaseHours()">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                        <!-- Presets para desktop -->
                                        <div class="hours-presets-desktop ml-3">
                                            <button type="button" class="btn btn-preset-desktop" onclick="setHours(4)">4h</button>
                                            <button type="button" class="btn btn-preset-desktop" onclick="setHours(6)">6h</button>
                                            <button type="button" class="btn btn-preset-desktop active" onclick="setHours(8)">8h</button>
                                            <button type="button" class="btn btn-preset-desktop" onclick="setHours(10)">10h</button>
                                        </div>
                                    </div>
                                    
                                    <input type="hidden" name="horas_hidden" id="hoursHidden" value="8.0">
                                </div>
                                <div></div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Descripción del trabajo realizado</label>
                                <textarea name="descripcion" class="form-control" rows="3" 
                                         placeholder="Describe brevemente el trabajo realizado..."></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-save"></i> Registrar Horas
                            </button>
                        </form>
                    </div>

                    <!-- Tab de Gastos/Ingresos - Solo para Admin y Supervisor -->
                    <?php if (!isTrabajador()): ?>
                    <div id="tab-gastos" class="tab-content">
                        <h3><i class="fas fa-euro-sign"></i> Registrar Gasto/Ingreso</h3>
                        
                        <form method="POST" id="formGastos">
                            <input type="hidden" name="accion" value="registrar_gasto">
                            
                            <select name="obra_id" class="select-obra" required>
                                <option value="">Selecciona una obra...</option>
                                <?php foreach ($obras_disponibles as $obra): ?>
                                    <option value="<?= $obra['obra_id'] ?>">
                                        <?= htmlspecialchars($obra['codigo']) ?> - <?= htmlspecialchars($obra['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Tipo</label>
                                    <select name="tipo" class="form-control" required onchange="updateTipoIcon(this)">
                                        <option value="">Selecciona tipo...</option>
                                        <option value="gasto">💸 Gasto</option>
                                        <option value="ingreso">💰 Ingreso</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Fecha</label>
                                    <input type="date" name="fecha" class="form-control" 
                                           value="<?= date('Y-m-d') ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Concepto</label>
                                    <input type="text" name="concepto" class="form-control" 
                                           placeholder="Ej: Material eléctrico, Comida, Pago cliente..." required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Importe (€)</label>
                                    <input type="number" name="importe" class="form-control" 
                                           step="0.01" min="0.01" placeholder="0.00" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Observaciones</label>
                                <textarea name="observaciones" class="form-control" rows="2" 
                                         placeholder="Detalles adicionales (opcional)..."></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-save"></i> Registrar Movimiento
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>

                    <!-- Tab de Materiales - Solo para Admin y Supervisor -->
                    <?php if (!isTrabajador()): ?>
                    <div id="tab-materiales" class="tab-content">
                        <h3><i class="fas fa-boxes"></i> Registrar Material</h3>
                        
                        <form method="POST" id="formMateriales">
                            <input type="hidden" name="accion" value="registrar_material">
                            
                            <select name="obra_id" class="select-obra" required>
                                <option value="">Selecciona una obra...</option>
                                <?php foreach ($obras_disponibles as $obra): ?>
                                    <option value="<?= $obra['obra_id'] ?>">
                                        <?= htmlspecialchars($obra['codigo']) ?> - <?= htmlspecialchars($obra['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Tipo de Material</label>
                                    <select name="tipo_material" class="form-control" required>
                                        <option value="">Selecciona tipo...</option>
                                        <option value="varios">🔧 Varios</option>
                                        <option value="jung">⚡ Jung</option>
                                        <option value="iluminacion">💡 Iluminación</option>
                                        <option value="efapel">🔌 Efapel</option>
                                        <option value="comprados">📦 Comprados</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Fecha</label>
                                    <input type="date" name="fecha" class="form-control" 
                                           value="<?= date('Y-m-d') ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Descripción del Material</label>
                                <input type="text" name="descripcion" class="form-control" 
                                       placeholder="Ej: Cable 2.5mm, Interruptor Jung, Lámpara LED..." required>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Cantidad</label>
                                    <input type="number" name="cantidad" class="form-control" 
                                           step="0.01" min="0.01" placeholder="1.00" 
                                           onchange="calcularTotal()" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Precio Unitario (€)</label>
                                    <input type="number" name="precio_unitario" class="form-control" 
                                           step="0.01" min="0.01" placeholder="0.00" 
                                           onchange="calcularTotal()" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Proveedor (opcional)</label>
                                    <input type="text" name="proveedor" class="form-control" 
                                           placeholder="Nombre del proveedor...">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Total (€)</label>
                                    <input type="text" id="total_material" class="form-control" 
                                           readonly style="background: #f8f9fa; font-weight: bold;">
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-save"></i> Registrar Material
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Panel lateral con estadísticas -->
                <div class="side-panel">
                    <h4><i class="fas fa-chart-bar"></i> Tu actividad de hoy</h4>
                    
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-value"><?= number_format($stats_hoy['total_horas'] ?? 0, 1) ?></div>
                            <div class="stat-label">Horas registradas</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?= $stats_hoy['total_registros'] ?? 0 ?></div>
                            <div class="stat-label">Registros totales</div>
                        </div>
                    </div>
                    
                    <?php if (!empty($horas_hoy)): ?>
                        <h5><i class="fas fa-history"></i> Últimos registros</h5>
                        <div style="max-height: 300px; overflow-y: auto;">
                            <?php foreach ($horas_hoy as $hora): ?>
                                <div class="recent-item">
                                    <div class="time"><?= date('H:i', strtotime($hora['fecha_registro'] ?? $hora['fecha'] . ' 12:00:00')) ?></div>
                                    <div class="obra"><?= htmlspecialchars($hora['obra_codigo']) ?></div>
                                    <div><?= $hora['horas'] ?>h - <?= htmlspecialchars(substr($hora['descripcion'], 0, 40)) ?><?= strlen($hora['descripcion']) > 40 ? '...' : '' ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-3 text-center">
                        <small class="text-muted">
                            <i class="fas fa-calendar-day"></i> 
                            <?= date('d/m/Y') ?> • <?= count($obras_disponibles) ?> obra<?= count($obras_disponibles) !== 1 ? 's' : '' ?> disponible<?= count($obras_disponibles) !== 1 ? 's' : '' ?>
                        </small>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- JavaScript para funcionalidad interactiva -->
    <script>
        function showTab(tabName) {
            // Ocultar todos los tabs
            const allTabs = document.querySelectorAll('.tab');
            const allContents = document.querySelectorAll('.tab-content');
            
            allTabs.forEach(tab => tab.classList.remove('active'));
            allContents.forEach(content => content.classList.remove('active'));
            
            // Mostrar el tab seleccionado
            document.querySelector(`.tab[onclick="showTab('${tabName}')"]`).classList.add('active');
            document.getElementById(`tab-${tabName}`).classList.add('active');
        }

        function calcularTotal() {
            const cantidad = parseFloat(document.querySelector('input[name="cantidad"]').value) || 0;
            const precio = parseFloat(document.querySelector('input[name="precio_unitario"]').value) || 0;
            const total = cantidad * precio;
            
            document.getElementById('total_material').value = '€' + total.toFixed(2);
        }

        function updateTipoIcon(select) {
            // Cambiar color del select según el tipo
            if (select.value === 'gasto') {
                select.style.borderColor = '#e76f51';
                select.style.color = '#e76f51';
            } else if (select.value === 'ingreso') {
                select.style.borderColor = '#2a9d8f';
                select.style.color = '#2a9d8f';
            } else {
                select.style.borderColor = '#e9ecef';
                select.style.color = 'inherit';
            }
        }

        // Controles de horas para móvil
        let currentHours = 8.0;

        function increaseHours() {
            if (currentHours < 24) {
                currentHours += 0.25;
                updateHoursDisplay();
            }
        }

        function decreaseHours() {
            if (currentHours > 0.25) {
                currentHours -= 0.25;
                updateHoursDisplay();
            }
        }

        function updateHoursDisplay(value = null) {
            if (value !== null) {
                currentHours = parseFloat(value) || 8.0;
            }
            
            const display = document.getElementById('hoursDisplay');
            const hiddenInput = document.getElementById('hoursHidden');
            const normalInput = document.getElementById('hoursInput');
            
            if (display) display.textContent = currentHours.toFixed(1) + 'h';
            if (hiddenInput) hiddenInput.value = currentHours;
            if (normalInput) normalInput.value = currentHours;
        }

        function setHours(hours) {
            currentHours = hours;
            updateHoursDisplay();
            
            // Actualizar botones activos
            document.querySelectorAll('.btn-preset-mobile, .btn-preset-desktop').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Activar botón correspondiente
            document.querySelectorAll('.btn-preset-mobile, .btn-preset-desktop').forEach(btn => {
                if (btn.onclick && btn.onclick.toString().includes(hours + ')')) {
                    btn.classList.add('active');
                }
            });
        }

        // Sincronizar input normal con controles móviles
        function syncHours() {
            const normalInput = document.getElementById('hoursInput');
            if (normalInput && normalInput.value) {
                updateHoursDisplay(normalInput.value);
            }
        }

        // Configurar formularios
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar controles de horas para móvil
            updateHoursDisplay();
            
            // Sincronizar input normal con controles móviles
            const normalInput = document.getElementById('hoursInput');
            if (normalInput) {
                normalInput.addEventListener('input', syncHours);
            }
            
            // Actualizar campo name correcto antes de enviar formulario
            const formHoras = document.getElementById('formHoras');
            if (formHoras) {
                formHoras.addEventListener('submit', function(e) {
                    // En móvil, usar el valor de los controles; en desktop, el input normal
                    const isMobile = window.innerWidth <= 768;
                    if (isMobile) {
                        // Crear un input temporal con el valor correcto
                        const tempInput = document.createElement('input');
                        tempInput.type = 'hidden';
                        tempInput.name = 'horas';
                        tempInput.value = currentHours;
                        this.appendChild(tempInput);
                    }
                });
            }
            
            // Auto-focus en primer campo de cada tab
            const forms = ['formHoras'];
            <?php if (!isTrabajador()): ?>
                forms.push('formGastos', 'formMateriales');
            <?php endif; ?>
            
            forms.forEach(formId => {
                const form = document.getElementById(formId);
                if (form) {
                    form.addEventListener('submit', function() {
                        const btn = form.querySelector('button[type="submit"]');
                        const originalText = btn.innerHTML;
                        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
                        btn.disabled = true;
                        
                        // Restaurar después de 3 segundos por si hay error
                        setTimeout(() => {
                            btn.innerHTML = originalText;
                            btn.disabled = false;
                        }, 3000);
                    });
                }
            });

            <?php if (!isTrabajador()): ?>
                // Inicializar cálculo de total en materiales
                calcularTotal();
            <?php endif; ?>
        });

        // Navegación por teclado en tabs
        document.addEventListener('keydown', function(e) {
            if (e.altKey) {
                switch(e.key) {
                    case '1': showTab('horas'); e.preventDefault(); break;
                    <?php if (!isTrabajador()): ?>
                        case '2': showTab('gastos'); e.preventDefault(); break;
                        case '3': showTab('materiales'); e.preventDefault(); break;
                    <?php endif; ?>
                }
            }
        });

        // =====================================================
        // MEJORAS ESPECÍFICAS PARA MÓVIL
        // =====================================================
        
        // Detectar si es móvil y aplicar mejoras
        if (window.innerWidth <= 768) {
            // Mejorar campos de entrada para móvil
            const selects = document.querySelectorAll('select');
            selects.forEach(select => {
                select.style.fontSize = '16px'; // Evita zoom en iOS
            });
            
            const inputs = document.querySelectorAll('input, textarea');
            inputs.forEach(input => {
                input.style.fontSize = '16px'; // Evita zoom en iOS
                
                // Seleccionar todo el texto al hacer focus en campos numéricos
                if (input.type === 'number') {
                    input.addEventListener('focus', function() {
                        this.select();
                    });
                }
            });
            
            // Mejorar el campo de horas con botones +/-
            const horasInput = document.querySelector('input[name="horas"]');
            if (horasInput) {
                const wrapper = document.createElement('div');
                wrapper.style.cssText = 'display:flex;align-items:center;border:2px solid #e9ecef;border-radius:8px;overflow:hidden;background:white;';
                
                const minusBtn = document.createElement('button');
                minusBtn.type = 'button';
                minusBtn.innerHTML = '−';
                minusBtn.style.cssText = 'padding:0.75rem 1rem;background:var(--primary-color);color:white;border:none;font-size:1.2rem;cursor:pointer;min-width:48px;';
                
                const plusBtn = document.createElement('button');
                plusBtn.type = 'button';
                plusBtn.innerHTML = '+';
                plusBtn.style.cssText = 'padding:0.75rem 1rem;background:var(--primary-color);color:white;border:none;font-size:1.2rem;cursor:pointer;min-width:48px;';
                
                horasInput.style.cssText = 'border:none;flex:1;text-align:center;font-size:1.1rem;font-weight:bold;background:transparent;padding:0.75rem;';
                
                horasInput.parentNode.insertBefore(wrapper, horasInput);
                wrapper.appendChild(minusBtn);
                wrapper.appendChild(horasInput);
                wrapper.appendChild(plusBtn);
                
                minusBtn.addEventListener('click', function() {
                    const currentValue = parseFloat(horasInput.value) || 0;
                    const newValue = Math.max(0.25, currentValue - 0.25);
                    horasInput.value = newValue.toFixed(2);
                    
                    if (navigator.vibrate) navigator.vibrate(25);
                });
                
                plusBtn.addEventListener('click', function() {
                    const currentValue = parseFloat(horasInput.value) || 0;
                    const newValue = Math.min(24, currentValue + 0.25);
                    horasInput.value = newValue.toFixed(2);
                    
                    if (navigator.vibrate) navigator.vibrate(25);
                });
            }
            
            // Auto-resize para textareas
            const textareas = document.querySelectorAll('textarea');
            textareas.forEach(textarea => {
                textarea.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = this.scrollHeight + 'px';
                });
            });
            
            // Vibración al enviar formularios
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function() {
                    if (navigator.vibrate) {
                        navigator.vibrate([50, 50, 50]); // Patrón de vibración
                    }
                });
            });
            
            // Swipe entre tabs (solo si no es trabajador)
            <?php if (!isTrabajador()): ?>
            let startX = 0;
            let currentTabIndex = 0;
            const tabNames = ['horas', 'gastos', 'materiales'];
            
            document.addEventListener('touchstart', function(e) {
                startX = e.touches[0].clientX;
            }, { passive: true });
            
            document.addEventListener('touchend', function(e) {
                const endX = e.changedTouches[0].clientX;
                const diffX = startX - endX;
                
                if (Math.abs(diffX) > 100) { // Swipe mínimo de 100px
                    if (diffX > 0 && currentTabIndex < tabNames.length - 1) {
                        // Swipe izquierda - siguiente tab
                        currentTabIndex++;
                        showTab(tabNames[currentTabIndex]);
                    } else if (diffX < 0 && currentTabIndex > 0) {
                        // Swipe derecha - tab anterior
                        currentTabIndex--;
                        showTab(tabNames[currentTabIndex]);
                    }
                    
                    if (navigator.vibrate) navigator.vibrate(25);
                }
            }, { passive: true });
            <?php endif; ?>
            
            // Scroll suave en tabs
            const tabsContainer = document.querySelector('.tabs');
            if (tabsContainer) {
                tabsContainer.style.scrollBehavior = 'smooth';
            }
        }
    </script>
</body>
</html>
