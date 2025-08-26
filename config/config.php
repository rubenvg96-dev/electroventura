<?php
/**
 * Configuración general del sistema - Electroventura v2.0
 */

// Configuración de sesión
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_lifetime', 7200); // 2 horas
    ini_set('session.gc_maxlifetime', 7200);
    session_start();
}

// Configuración de errores
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Configuración de zona horaria
date_default_timezone_set('Europe/Madrid');

// Constantes del sistema
define('APP_NAME', 'Electroventura v2.0');
define('APP_VERSION', '2.0.0');
define('BASE_URL', 'http://localhost:8080');
define('SYSTEM_EMAIL', 'sistema@electroventura.com');

// Configuración de seguridad
define('SESSION_TIMEOUT', 7200); // 2 horas en segundos
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutos

// Configuración de obras
define('AUTO_RESET_OBRAS_DIARIAS', true);
define('TRABAJADOR_VE_SEMANA_ACTUAL', true);

// Rutas del sistema
define('LOGS_PATH', __DIR__ . '/../logs/');
define('UPLOADS_PATH', __DIR__ . '/../uploads/');

// Crear directorios si no existen
if (!is_dir(LOGS_PATH)) {
    mkdir(LOGS_PATH, 0755, true);
}
if (!is_dir(UPLOADS_PATH)) {
    mkdir(UPLOADS_PATH, 0755, true);
}

// Incluir dependencias
require_once __DIR__ . '/database.php';

/**
 * Funciones de utilidad
 */

/**
 * Verificar si el usuario está logueado
 */
function isLoggedIn() {
    return isset($_SESSION['user']) && isset($_SESSION['user']['id']);
}

/**
 * Requerir login - redirige si no está logueado
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Función de redirección simple
 */
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * Obtener usuario actual
 */
function getCurrentUser() {
    return $_SESSION['user'] ?? null;
}

/**
 * Verificar rol del usuario
 */
function hasRole($role) {
    if (!isLoggedIn()) return false;
    
    $userRole = $_SESSION['user']['rol'] ?? '';
    
    // Admin tiene acceso a todo
    if ($userRole === 'admin') return true;
    
    // Supervisor tiene acceso a todo excepto gestión de obras diarias
    if ($userRole === 'supervisor' && $role !== 'admin') return true;
    
    // Verificar rol específico
    return $userRole === $role;
}

/**
 * Verificar si puede gestionar obras diarias (solo admin)
 */
function canManageObrasDelDia() {
    return hasRole('admin');
}

/**
 * Verificar si puede acceder al dashboard
 */
function canAccessDashboard() {
    return hasRole('admin') || hasRole('supervisor');
}

/**
 * Verificar si es trabajador (rol exacto)
 */
function isTrabajador() {
    if (!isLoggedIn()) return false;
    return $_SESSION['user']['rol'] === 'trabajador';
}

/**
 * Verificar si es supervisor (rol exacto)
 */
function isSupervisor() {
    if (!isLoggedIn()) return false;
    return $_SESSION['user']['rol'] === 'supervisor';
}

/**
 * Verificar si es admin (rol exacto)
 */
function isAdmin() {
    if (!isLoggedIn()) return false;
    return $_SESSION['user']['rol'] === 'admin';
}

/**
 * Verificar si puede gestionar gastos y materiales (supervisor o admin)
 */
function canManageGastosYMateriales() {
    return isAdmin() || isSupervisor();
}

/**
 * Limpiar y sanitizar entrada
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Validar CSRF token
 */
function validateCSRFToken($token) {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/**
 * Generar CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Registrar en log de auditoría
 */
function logAuditoria($tabla, $registro_id, $accion, $datos_anteriores = null, $datos_nuevos = null) {
    try {
        $db = Database::getInstance();
        $usuario_id = getCurrentUser()['id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        $sql = "INSERT INTO auditoria (tabla, registro_id, accion, datos_anteriores, datos_nuevos, usuario_id, ip_address) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $tabla,
            $registro_id,
            $accion,
            $datos_anteriores ? json_encode($datos_anteriores) : null,
            $datos_nuevos ? json_encode($datos_nuevos) : null,
            $usuario_id,
            $ip
        ];
        
        $db->query($sql, $params);
    } catch (Exception $e) {
        error_log("Error en logAuditoria: " . $e->getMessage());
    }
}

/**
 * Función alias para compatibilidad
 */
function logActivity($action, $table = null, $recordId = null, $oldData = null, $newData = null) {
    return logAuditoria($table, $recordId, $action, $oldData, $newData);
}

/**
 * Obtener obras activas del día
 */
function getObrasActivasHoy() {
    try {
        $db = Database::getInstance();
        $sql = "SELECT * FROM v_obras_activas_hoy ORDER BY nombre";
        $stmt = $db->query($sql);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error obteniendo obras activas: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtener fechas de la semana actual
 */
function getSemanaActual() {
    $hoy = new DateTime();
    $inicio_semana = clone $hoy;
    $inicio_semana->modify('monday this week');
    $fin_semana = clone $inicio_semana;
    $fin_semana->modify('+6 days');
    
    return [
        'inicio' => $inicio_semana->format('Y-m-d'),
        'fin' => $fin_semana->format('Y-m-d'),
        'texto' => $inicio_semana->format('d/m/Y') . ' - ' . $fin_semana->format('d/m/Y')
    ];
}

/**
 * Formatear fecha para mostrar
 */
function formatearFecha($fecha, $formato = 'd/m/Y') {
    if (!$fecha) return '-';
    try {
        return (new DateTime($fecha))->format($formato);
    } catch (Exception $e) {
        return $fecha;
    }
}

/**
 * Formatear moneda
 */
function formatearMoneda($cantidad) {
    return number_format($cantidad, 2, ',', '.') . ' €';
}

/**
 * Redireccionar según rol
 */
function redirectToRoleHome() {
    if (hasRole('admin')) {
        header('Location: admin_home.php');
    } elseif (hasRole('supervisor')) {
        header('Location: supervisor_home.php');
    } else {
        header('Location: imputaciones.php');
    }
    exit;
}

/**
 * Verificar timeout de sesión
 */
function checkSessionTimeout() {
    if (!isLoggedIn()) return;
    
    $lastActivity = $_SESSION['last_activity'] ?? 0;
    $now = time();
    
    if (($now - $lastActivity) > SESSION_TIMEOUT) {
        session_destroy();
        header('Location: login.php?timeout=1');
        exit;
    }
    
    $_SESSION['last_activity'] = $now;
}

/**
 * Actualizar último acceso del usuario
 */
function updateLastAccess() {
    if (!isLoggedIn()) return;
    
    try {
        $db = Database::getInstance();
        $sql = "UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?";
        $db->query($sql, [getCurrentUser()['id']]);
    } catch (Exception $e) {
        error_log("Error actualizando último acceso: " . $e->getMessage());
    }
}

// Verificar timeout en cada carga de página
checkSessionTimeout();

// Actualizar último acceso si está logueado
if (isLoggedIn()) {
    updateLastAccess();
}
?>
