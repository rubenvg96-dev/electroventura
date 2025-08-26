<?php
/**
 * ELECTROVENTURA v2.0 - DASHBOARD
 * ===============================
 * Panel de control con informes y análisis por obra o vista general
 */

require_once 'config/config.php';
requireLogin();

// Solo admin y supervisor pueden acceder al dashboard
if (!canAccessDashboard()) {
    redirect('trabajador_home.php');
}

// Obtener filtros
$obra_filtro = $_GET['obra'] ?? 'todas';
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01'); // Primer día del mes
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d'); // Hoy

// Inicializar variables por defecto
$resumen_obras = [];
$resumen_financiero = [];
$resumen_materiales = [];
$top_trabajadores = [];
$actividad_diaria = [];
$todas_obras = [];

$total_horas_periodo = 0;
$total_gastos_periodo = 0;
$total_ingresos_periodo = 0;
$total_materiales_periodo = 0;
$total_trabajadores_activos = 0;
$balance_periodo = 0;

try {
    $db = Database::getInstance()->getConnection();
    
    // Obtener todas las obras para el filtro
    $stmt = $db->prepare("
        SELECT o.id as obra_id, o.codigo, o.nombre, o.estado,
               COALESCE(p.nombre, 'Sin cliente') as cliente
        FROM obras o
        LEFT JOIN proveedores p ON o.proveedor_id = p.id
        WHERE o.activo = 1
        ORDER BY 
            CASE o.estado 
                WHEN 'en_curso' THEN 1 
                WHEN 'pendiente' THEN 2 
                WHEN 'finalizada' THEN 3 
                ELSE 4 
            END, o.nombre
    ");
    $stmt->execute();
    $todas_obras = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Construir condición WHERE para los filtros (ya no se usa en las consultas principales)
    // Se mantiene para compatibilidad con otras partes del código que puedan usarla
    $where_conditions = [];
    $params = [$fecha_inicio, $fecha_fin];
    
    if ($obra_filtro !== 'todas' && is_numeric($obra_filtro)) {
        $where_conditions[] = "o.id = ?";
        $params[] = (int)$obra_filtro;
    }
    
    $where_clause = empty($where_conditions) ? "" : "AND " . implode(" AND ", $where_conditions);
    
    // ESTADÍSTICAS GENERALES
    // =====================
    
    // Resumen de horas por obra
    $stmt = $db->prepare("
        SELECT o.id as obra_id, o.codigo, o.nombre, 
               COALESCE(p.nombre, 'Sin cliente') as cliente,
               COUNT(DISTINCT ht.usuario_id) as trabajadores,
               COUNT(ht.id) as total_registros,
               COALESCE(SUM(ht.horas), 0) as total_horas,
               ROUND(AVG(ht.horas), 2) as promedio_horas
        FROM obras o
        LEFT JOIN proveedores p ON o.proveedor_id = p.id
        LEFT JOIN horas_trabajadores ht ON o.id = ht.obra_id 
                  AND ht.fecha BETWEEN ? AND ?
        WHERE o.estado IN ('pendiente', 'en_curso', 'finalizada') AND o.activo = 1
        " . ($obra_filtro !== 'todas' && is_numeric($obra_filtro) ? "AND o.id = ?" : "") . "
        GROUP BY o.id, o.codigo, o.nombre, p.nombre
        ORDER BY total_horas DESC
    ");
    
    $exec_params = [$fecha_inicio, $fecha_fin];
    if ($obra_filtro !== 'todas' && is_numeric($obra_filtro)) {
        $exec_params[] = (int)$obra_filtro;
    }
    
    $stmt->execute($exec_params);
    $resumen_obras = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Gastos e ingresos por obra
    $stmt = $db->prepare("
        SELECT o.id as obra_id, o.codigo, o.nombre,
               COALESCE(SUM(CASE WHEN gi.tipo = 'gasto' THEN gi.cantidad ELSE 0 END), 0) as total_gastos,
               COALESCE(SUM(CASE WHEN gi.tipo = 'ingreso' THEN gi.cantidad ELSE 0 END), 0) as total_ingresos,
               COUNT(gi.id) as total_movimientos
        FROM obras o
        LEFT JOIN gastos_ingresos gi ON o.id = gi.obra_id 
                  AND gi.fecha BETWEEN ? AND ?
        WHERE o.estado IN ('pendiente', 'en_curso', 'finalizada') AND o.activo = 1
        " . ($obra_filtro !== 'todas' && is_numeric($obra_filtro) ? "AND o.id = ?" : "") . "
        GROUP BY o.id, o.codigo, o.nombre
        ORDER BY (COALESCE(SUM(CASE WHEN gi.tipo = 'ingreso' THEN gi.cantidad ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN gi.tipo = 'gasto' THEN gi.cantidad ELSE 0 END), 0)) DESC
    ");
    
    $stmt->execute($exec_params);
    $resumen_financiero = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Materiales por obra (suma de todas las tablas)
    $stmt = $db->prepare("
        SELECT o.id as obra_id, o.codigo, o.nombre,
               COALESCE(materiales.total_materiales, 0) as total_materiales,
               COALESCE(materiales.registros_materiales, 0) as registros_materiales
        FROM obras o
        LEFT JOIN (
            SELECT obra_id, 
                   SUM(precio_total) as total_materiales,
                   COUNT(*) as registros_materiales
            FROM (
                SELECT obra_id, precio_total FROM materiales_varios WHERE fecha BETWEEN ? AND ?
                UNION ALL
                SELECT obra_id, precio_total FROM jung WHERE fecha BETWEEN ? AND ?
                UNION ALL
                SELECT obra_id, precio_total FROM material_iluminacion WHERE fecha BETWEEN ? AND ?
                UNION ALL
                SELECT obra_id, precio_total FROM efapel WHERE fecha BETWEEN ? AND ?
                UNION ALL
                SELECT obra_id, precio_total FROM materiales_comprados WHERE fecha_compra BETWEEN ? AND ?
                UNION ALL
                SELECT obra_id, precio_total FROM gasto_hilo WHERE fecha_uso BETWEEN ? AND ?
            ) todos_materiales
            " . ($obra_filtro !== 'todas' && is_numeric($obra_filtro) ? "WHERE obra_id = ?" : "") . "
            GROUP BY obra_id
        ) materiales ON o.id = materiales.obra_id
        WHERE o.estado IN ('pendiente', 'en_curso', 'finalizada') AND o.activo = 1
        " . ($obra_filtro !== 'todas' && is_numeric($obra_filtro) ? "AND o.id = ?" : "") . "
        ORDER BY total_materiales DESC
    ");
    
    $params_materiales = [$fecha_inicio, $fecha_fin, $fecha_inicio, $fecha_fin, 
                         $fecha_inicio, $fecha_fin, $fecha_inicio, $fecha_fin,
                         $fecha_inicio, $fecha_fin, $fecha_inicio, $fecha_fin];
    
    if ($obra_filtro !== 'todas' && is_numeric($obra_filtro)) {
        $params_materiales[] = (int)$obra_filtro;  // Para el WHERE dentro del subquery
        $params_materiales[] = (int)$obra_filtro;  // Para el WHERE de obras
    }
    
    $stmt->execute($params_materiales);
    $resumen_materiales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ESTADÍSTICAS AGREGADAS
    // ======================
    $total_horas_periodo = array_sum(array_column($resumen_obras, 'total_horas'));
    $total_gastos_periodo = array_sum(array_column($resumen_financiero, 'total_gastos'));
    $total_ingresos_periodo = array_sum(array_column($resumen_financiero, 'total_ingresos'));
    $total_materiales_periodo = array_sum(array_column($resumen_materiales, 'total_materiales'));
    $balance_periodo = $total_ingresos_periodo - $total_gastos_periodo - $total_materiales_periodo;
    
    // Calcular trabajadores activos únicos correctamente
    $stmt = $db->prepare("
        SELECT COUNT(DISTINCT ht.usuario_id) as trabajadores_unicos
        FROM horas_trabajadores ht
        WHERE ht.fecha BETWEEN ? AND ?
        " . ($obra_filtro !== 'todas' && is_numeric($obra_filtro) ? "AND ht.obra_id = ?" : "") . "
    ");
    $stmt->execute($exec_params);
    $result = $stmt->fetch();
    $total_trabajadores_activos = $result['trabajadores_unicos'];
    
    // TOP 5 TRABAJADORES MÁS ACTIVOS
    $stmt = $db->prepare("
        SELECT u.nombre_completo, u.username,
               COUNT(ht.id) as total_registros,
               SUM(ht.horas) as total_horas,
               COUNT(DISTINCT ht.obra_id) as obras_trabajadas
        FROM usuarios u
        INNER JOIN horas_trabajadores ht ON u.id = ht.usuario_id
        WHERE DATE(ht.fecha) BETWEEN ? AND ?
        " . ($obra_filtro !== 'todas' && is_numeric($obra_filtro) ? "AND ht.obra_id = ?" : "") . "
        GROUP BY u.id, u.nombre_completo, u.username
        ORDER BY total_horas DESC
        LIMIT 5
    ");
    
    $stmt->execute($exec_params);
    $top_trabajadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ACTIVIDAD DIARIA (últimos 7 días)
    $stmt = $db->prepare("
        SELECT DATE(ht.fecha) as fecha,
               COUNT(DISTINCT ht.usuario_id) as trabajadores,
               COALESCE(SUM(ht.horas), 0) as horas_dia,
               COUNT(ht.id) as registros_dia
        FROM horas_trabajadores ht
        WHERE ht.fecha >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        " . ($obra_filtro !== 'todas' && is_numeric($obra_filtro) ? "AND ht.obra_id = ?" : "") . "
        GROUP BY DATE(ht.fecha)
        ORDER BY fecha ASC
    ");
    
    $actividad_params = [];
    if ($obra_filtro !== 'todas' && is_numeric($obra_filtro)) {
        $actividad_params[] = (int)$obra_filtro;
    }
    
    $stmt->execute($actividad_params);
    $actividad_diaria_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Crear array con todos los días de los últimos 7 días para evitar días faltantes
    $actividad_diaria = [];
    for ($i = 6; $i >= 0; $i--) {
        $fecha_dia = date('Y-m-d', strtotime("-{$i} days"));
        $encontrado = false;
        
        foreach ($actividad_diaria_raw as $dia_raw) {
            if ($dia_raw['fecha'] === $fecha_dia) {
                $actividad_diaria[] = $dia_raw;
                $encontrado = true;
                break;
            }
        }
        
        if (!$encontrado) {
            $actividad_diaria[] = [
                'fecha' => $fecha_dia,
                'trabajadores' => 0,
                'horas_dia' => 0,
                'registros_dia' => 0
            ];
        }
    }
    
} catch (Exception $e) {
    error_log("Error en dashboard: " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine());
    
    // Solo inicializar variables que no estén definidas para evitar más errores
    if (!isset($resumen_obras)) $resumen_obras = [];
    if (!isset($resumen_financiero)) $resumen_financiero = [];  
    if (!isset($resumen_materiales)) $resumen_materiales = [];
    if (!isset($top_trabajadores)) $top_trabajadores = [];
    if (!isset($actividad_diaria)) $actividad_diaria = [];
    if (!isset($todas_obras)) $todas_obras = [];
    
    if (!isset($total_horas_periodo)) $total_horas_periodo = 0;
    if (!isset($total_gastos_periodo)) $total_gastos_periodo = 0;
    if (!isset($total_ingresos_periodo)) $total_ingresos_periodo = 0;
    if (!isset($total_materiales_periodo)) $total_materiales_periodo = 0;
    if (!isset($total_trabajadores_activos)) $total_trabajadores_activos = 0;
    if (!isset($balance_periodo)) $balance_periodo = 0;
}

// Obtener nombre de la obra seleccionada
$nombre_obra_filtro = 'Todas las obras';
if ($obra_filtro !== 'todas' && is_numeric($obra_filtro)) {
    foreach ($todas_obras as $obra) {
        if ($obra['obra_id'] == $obra_filtro) {
            $nombre_obra_filtro = $obra['codigo'] . ' - ' . $obra['nombre'];
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Electroventura v2.0</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #e8f5f4 0%, #f0f9f8 50%, #e8f5f4 100%) !important;
            padding-top: 0 !important;
            margin: 0;
        }
        
        .dashboard-container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 0 2rem;
            padding-top: 90px; /* Espacio para el navbar */
        }
        
        .filters-panel {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 1rem;
            align-items: end;
        }
        
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-icon {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 2rem;
            color: var(--primary-color);
            opacity: 0.3;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .chart-panel, .info-panel {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .chart-panel {
            position: relative;
            height: 450px;
            max-height: 450px;
            overflow: hidden;
        }
        
        #actividadChart {
            max-height: 400px !important;
            height: 400px !important;
        }
        
        .tables-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 2rem;
        }
        
        .table-panel {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .data-table th, .data-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        
        .data-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: var(--dark-color);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .data-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 0.5rem;
        }
        
        .progress-fill {
            height: 100%;
            background: var(--gradient-primary);
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        
        .positive { color: var(--success-color); }
        .negative { color: var(--warning-color); }
        
        .no-data {
            text-align: center;
            color: #666;
            padding: 3rem;
            font-style: italic;
        }
        
        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .tables-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 0 1rem;
            }
            
            .filters-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-overview {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .stat-value {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body class="internal-page">
    <!-- Header de navegación -->
    <nav class="navbar">
        <div class="d-flex" style="width: 100%; justify-content: space-between; align-items: center;">
            <a href="<?= canManageObrasDelDia() ? 'admin_home.php' : 'supervisor_home.php' ?>" class="navbar-brand">
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

    <div class="dashboard-container">
        <!-- Header -->
        <div style="text-align: center; margin-bottom: 2rem;">
            <h1><i class="fas fa-chart-line"></i> Dashboard</h1>
            <p>Análisis y seguimiento de obras</p>
        </div>

        <!-- Panel de filtros -->
        <div class="filters-panel">
            <form method="GET" id="filtersForm">
                <div class="filters-grid">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-hard-hat"></i> Obra
                        </label>
                        <select name="obra" class="form-control">
                            <option value="todas">📊 Todas las obras</option>
                            <?php if (empty($todas_obras)): ?>
                                <option disabled>⚠️ No hay obras disponibles</option>
                            <?php else: ?>
                                <?php foreach ($todas_obras as $obra): ?>
                                    <option value="<?= $obra['obra_id'] ?>" 
                                            <?= $obra_filtro == $obra['obra_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($obra['codigo']) ?> - <?= htmlspecialchars($obra['nombre']) ?>
                                        <?php if ($obra['estado'] !== 'en_curso'): ?>
                                            (<?= ucfirst(str_replace('_', ' ', $obra['estado'])) ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Desde</label>
                        <input type="date" name="fecha_inicio" class="form-control" 
                               value="<?= $fecha_inicio ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Hasta</label>
                        <input type="date" name="fecha_fin" class="form-control" 
                               value="<?= $fecha_fin ?>" max="<?= date('Y-m-d') ?>">
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                    </div>
                </div>
            </form>
            
            <div class="mt-2">
                <strong>Vista actual:</strong> <?= htmlspecialchars($nombre_obra_filtro) ?> 
                | <?= date('d/m/Y', strtotime($fecha_inicio)) ?> - <?= date('d/m/Y', strtotime($fecha_fin)) ?>
            </div>
        </div>

        <!-- Estadísticas generales -->
        <div class="stats-overview">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-value"><?= number_format($total_horas_periodo, 1) ?></div>
                <div class="stat-label">Horas totales</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-value"><?= $total_trabajadores_activos ?></div>
                <div class="stat-label">Trabajadores activos</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-euro-sign"></i></div>
                <div class="stat-value <?= $total_ingresos_periodo > 0 ? 'positive' : '' ?>">
                    €<?= number_format($total_ingresos_periodo, 0) ?>
                </div>
                <div class="stat-label">Ingresos</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
                <div class="stat-value negative">
                    €<?= number_format($total_gastos_periodo + $total_materiales_periodo, 0) ?>
                </div>
                <div class="stat-label">Gastos + Materiales</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                <div class="stat-value <?= $balance_periodo >= 0 ? 'positive' : 'negative' ?>">
                    €<?= number_format($balance_periodo, 0) ?>
                </div>
                <div class="stat-label">Balance neto</div>
            </div>
        </div>

        <!-- Dashboard principal con gráficos -->
        <div class="dashboard-grid">
            <!-- Panel de gráfico principal -->
            <div class="chart-panel">
                <h3><i class="fas fa-chart-bar"></i> Actividad de los últimos 7 días</h3>
                <?php if (!empty($actividad_diaria)): ?>
                    <canvas id="actividadChart" height="400"></canvas>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-chart-bar" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                        <p>No hay datos de actividad en el período seleccionado</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Panel de información lateral -->
            <div class="info-panel">
                <h3><i class="fas fa-trophy"></i> Top Trabajadores</h3>
                <?php if (!empty($top_trabajadores)): ?>
                    <?php foreach ($top_trabajadores as $index => $trabajador): ?>
                        <div style="margin-bottom: 1.5rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <strong>#<?= $index + 1 ?> <?= htmlspecialchars($trabajador['nombre_completo']) ?></strong>
                                <span style="color: var(--primary-color); font-weight: bold;">
                                    <?= number_format($trabajador['total_horas'], 1) ?>h
                                </span>
                            </div>
                            <div style="font-size: 0.85rem; color: #666; margin-bottom: 0.5rem;">
                                <?= $trabajador['total_registros'] ?> registros • 
                                <?= $trabajador['obras_trabajadas'] ?> obra<?= $trabajador['obras_trabajadas'] !== 1 ? 's' : '' ?>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" 
                                     style="width: <?= $top_trabajadores[0]['total_horas'] > 0 ? ($trabajador['total_horas'] / $top_trabajadores[0]['total_horas'] * 100) : 0 ?>%">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-users" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                        <p>No hay actividad de trabajadores</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tablas de datos -->
        <div class="tables-grid">
            <!-- Tabla de resumen por obras -->
            <div class="table-panel">
                <h3><i class="fas fa-hard-hat"></i> Resumen por Obras - Horas</h3>
                <?php if (!empty($resumen_obras)): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Obra</th>
                                <th>Cliente</th>
                                <th>Trabajadores</th>
                                <th>Horas</th>
                                <th>Registros</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($resumen_obras as $obra): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($obra['codigo']) ?></strong><br>
                                        <small><?= htmlspecialchars($obra['nombre']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($obra['cliente']) ?></td>
                                    <td>
                                        <span style="background: var(--primary-color); color: white; padding: 0.25rem 0.5rem; border-radius: 12px; font-size: 0.8rem;">
                                            <?= $obra['trabajadores'] ?>
                                        </span>
                                    </td>
                                    <td><strong><?= number_format($obra['total_horas'], 1) ?>h</strong></td>
                                    <td><?= $obra['total_registros'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">No hay datos de horas para mostrar</div>
                <?php endif; ?>
            </div>

            <!-- Tabla financiera -->
            <div class="table-panel">
                <h3><i class="fas fa-euro-sign"></i> Resumen Financiero</h3>
                <?php if (!empty($resumen_financiero)): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Obra</th>
                                <th>Ingresos</th>
                                <th>Gastos</th>
                                <th>Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($resumen_financiero as $obra): ?>
                                <?php 
                                    $balance = $obra['total_ingresos'] - $obra['total_gastos'];
                                    $balance_class = $balance >= 0 ? 'positive' : 'negative';
                                ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($obra['codigo']) ?></strong><br>
                                        <small><?= htmlspecialchars($obra['nombre']) ?></small>
                                    </td>
                                    <td class="positive">€<?= number_format($obra['total_ingresos'], 2) ?></td>
                                    <td class="negative">€<?= number_format($obra['total_gastos'], 2) ?></td>
                                    <td class="<?= $balance_class ?>">
                                        <strong>€<?= number_format($balance, 2) ?></strong>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">No hay datos financieros para mostrar</div>
                <?php endif; ?>
            </div>

            <!-- Tabla de materiales -->
            <div class="table-panel">
                <h3><i class="fas fa-boxes"></i> Materiales por Obra</h3>
                <?php if (!empty($resumen_materiales) && array_sum(array_column($resumen_materiales, 'total_materiales')) > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Obra</th>
                                <th>Total Materiales</th>
                                <th>Registros</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($resumen_materiales as $obra): ?>
                                <?php if ($obra['total_materiales'] > 0): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($obra['codigo']) ?></strong><br>
                                            <small><?= htmlspecialchars($obra['nombre']) ?></small>
                                        </td>
                                        <td><strong>€<?= number_format($obra['total_materiales'], 2) ?></strong></td>
                                        <td><?= $obra['registros_materiales'] ?></td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">No hay registros de materiales para mostrar</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- JavaScript para gráficos y funcionalidad -->
    <script>
        // Configurar gráfico de actividad
        <?php if (!empty($actividad_diaria)): ?>
        const actividadData = {
            labels: [
                <?php foreach ($actividad_diaria as $dia): ?>
                    '<?= date('d/m', strtotime($dia['fecha'])) ?>',
                <?php endforeach; ?>
            ],
            datasets: [
                {
                    label: 'Horas trabajadas',
                    data: [
                        <?php foreach ($actividad_diaria as $dia): ?>
                            <?= floatval($dia['horas_dia']) ?>,
                        <?php endforeach; ?>
                    ],
                    backgroundColor: 'rgba(42, 157, 143, 0.8)',
                    borderColor: 'rgba(42, 157, 143, 1)',
                    borderWidth: 2,
                    yAxisID: 'y'
                },
                {
                    label: 'Trabajadores',
                    data: [
                        <?php foreach ($actividad_diaria as $dia): ?>
                            <?= intval($dia['trabajadores']) ?>,
                        <?php endforeach; ?>
                    ],
                    backgroundColor: 'rgba(244, 162, 97, 0.8)',
                    borderColor: 'rgba(244, 162, 97, 1)',
                    borderWidth: 2,
                    yAxisID: 'y1'
                }
            ]
        };

        const actividadConfig = {
            type: 'bar',
            data: actividadData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                layout: {
                    padding: {
                        top: 20,
                        bottom: 20,
                        left: 10,
                        right: 10
                    }
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Días'
                        },
                        maxBarThickness: 80
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Horas'
                        },
                        beginAtZero: true,
                        suggestedMax: 25
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Trabajadores'
                        },
                        beginAtZero: true,
                        suggestedMax: 5,
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    title: {
                        display: false
                    }
                }
            }
        };

        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('actividadChart');
            if (ctx) {
                const chart = new Chart(ctx, actividadConfig);
            }
        });
        <?php else: ?>
        // No hay datos de actividad diaria disponibles
        <?php endif; ?>

        // Auto-submit del formulario al cambiar filtros
        document.getElementById('filtersForm').addEventListener('change', function() {
            this.submit();
        });

        // Accesos directos por teclado
        document.addEventListener('keydown', function(e) {
            if (e.altKey) {
                switch(e.key) {
                    case 't': // Alt+T = Todas las obras
                        document.querySelector('select[name="obra"]').value = 'todas';
                        document.getElementById('filtersForm').submit();
                        break;
                    case 'm': // Alt+M = Este mes
                        document.querySelector('input[name="fecha_inicio"]').value = '<?= date('Y-m-01') ?>';
                        document.querySelector('input[name="fecha_fin"]').value = '<?= date('Y-m-d') ?>';
                        document.getElementById('filtersForm').submit();
                        break;
                }
            }
        });

        // Actualizar datos cada 5 minutos
        setTimeout(function() {
            location.reload();
        }, 300000);
    </script>
</body>
</html>
