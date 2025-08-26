<?php
/**
 * ELECTROVENTURA v2.0 - API DE RESUMEN DEL DÍA
 * =============================================
 * Proporciona información resumida sobre las actividades del día actual
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';
require_once '../config/config.php';

// Verificar sesión activa
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Obtener obras activas del día con detalles
    $stmt = $db->prepare("
        SELECT o.obra_id, o.nombre, o.codigo, o.direccion,
               od.configurado_por, u.nombre_completo as configurado_por_nombre,
               od.created_at as configurado_en
        FROM obras_dia od
        INNER JOIN obras o ON od.obra_id = o.obra_id
        LEFT JOIN usuarios u ON od.configurado_por = u.user_id
        WHERE od.fecha = CURDATE() 
        AND od.activo = 1
        AND o.estado = 'activa'
        ORDER BY o.nombre
    ");
    $stmt->execute();
    $obras_activas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener total de trabajadores activos
    $stmt = $db->prepare("
        SELECT COUNT(*) as total_trabajadores 
        FROM usuarios 
        WHERE role = 'trabajador' AND activo = 1
    ");
    $stmt->execute();
    $total_trabajadores = $stmt->fetch(PDO::FETCH_ASSOC)['total_trabajadores'];
    
    // Obtener actividad de hoy por obra
    $stmt = $db->prepare("
        SELECT ht.obra_id, o.nombre as obra_nombre,
               COUNT(DISTINCT ht.user_id) as trabajadores_trabajando,
               COUNT(*) as total_registros,
               SUM(ht.horas) as total_horas,
               MAX(ht.fecha_hora) as ultima_actividad
        FROM horas_trabajadas ht
        INNER JOIN obras o ON ht.obra_id = o.obra_id
        WHERE DATE(ht.fecha_hora) = CURDATE()
        GROUP BY ht.obra_id, o.nombre
        ORDER BY total_horas DESC
    ");
    $stmt->execute();
    $actividad_por_obra = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener resumen de materiales del día
    $stmt = $db->prepare("
        SELECT COUNT(*) as total_registros_materiales,
               SUM(importe_total) as gasto_total_materiales
        FROM (
            SELECT importe_total FROM materiales_varios WHERE DATE(fecha) = CURDATE()
            UNION ALL
            SELECT importe_total FROM materiales_jung WHERE DATE(fecha) = CURDATE()
            UNION ALL
            SELECT importe_total FROM materiales_iluminacion WHERE DATE(fecha) = CURDATE()
            UNION ALL
            SELECT importe_total FROM materiales_efapel WHERE DATE(fecha) = CURDATE()
            UNION ALL
            SELECT importe_total FROM materiales_comprados WHERE DATE(fecha) = CURDATE()
        ) materiales_hoy
    ");
    $stmt->execute();
    $resumen_materiales = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Obtener gastos e ingresos del día
    $stmt = $db->prepare("
        SELECT 
            SUM(CASE WHEN tipo = 'gasto' THEN importe ELSE 0 END) as gastos_hoy,
            SUM(CASE WHEN tipo = 'ingreso' THEN importe ELSE 0 END) as ingresos_hoy,
            COUNT(*) as total_movimientos
        FROM gastos_ingresos 
        WHERE DATE(fecha) = CURDATE()
    ");
    $stmt->execute();
    $resumen_financiero = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Calcular estadísticas generales
    $total_horas_hoy = array_sum(array_column($actividad_por_obra, 'total_horas'));
    $trabajadores_trabajando = count(array_unique(array_column($actividad_por_obra, 'trabajadores_trabajando')));
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'fecha' => date('Y-m-d'),
        'timestamp' => date('Y-m-d H:i:s'),
        'obras_activas' => count($obras_activas),
        'obras_detalle' => $obras_activas,
        'total_trabajadores' => (int)$total_trabajadores,
        'trabajadores_trabajando_hoy' => $trabajadores_trabajando,
        'actividad' => [
            'total_horas' => (float)$total_horas_hoy,
            'por_obra' => $actividad_por_obra
        ],
        'materiales' => [
            'total_registros' => (int)($resumen_materiales['total_registros_materiales'] ?? 0),
            'gasto_total' => (float)($resumen_materiales['gasto_total_materiales'] ?? 0)
        ],
        'financiero' => [
            'gastos' => (float)($resumen_financiero['gastos_hoy'] ?? 0),
            'ingresos' => (float)($resumen_financiero['ingresos_hoy'] ?? 0),
            'movimientos' => (int)($resumen_financiero['total_movimientos'] ?? 0),
            'balance' => (float)(($resumen_financiero['ingresos_hoy'] ?? 0) - ($resumen_financiero['gastos_hoy'] ?? 0))
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error en day_summary API: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error del servidor'
    ]);
}
?>
