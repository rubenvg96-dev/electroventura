<?php
/**
 * Punto de entrada principal - Electroventura v2.0
 * Redirige al dashboard o login
 */

require_once __DIR__ . '/config/config.php';

if (isLoggedIn()) {
    // Redirigir según el rol
    $user = getCurrentUser();
    if ($user['rol'] === 'admin') {
        header('Location: admin_home.php');
    } elseif ($user['rol'] === 'supervisor') {
        header('Location: supervisor_home.php');
    } else {
        header('Location: imputaciones.php');
    }
} else {
    header('Location: login.php');
}
exit;
?>
