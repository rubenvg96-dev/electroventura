<?php
/**
 * ELECTROVENTURA v2.0 - SISTEMA DE LOGIN
 * =====================================
 * Página de inicio de sesión con redirección basada en roles
 */

require_once 'config/database.php';
require_once 'config/config.php';

// Si ya está logueado, redirigir a su página de inicio
if (isLoggedIn()) {
    redirectToRoleHome();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($username) || empty($password)) {
        $error = 'Por favor, completa todos los campos.';
    } else {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Buscar usuario activo
            $stmt = $db->prepare("
                SELECT id, username, nombre_completo, password, rol, activo 
                FROM usuarios 
                WHERE username = ? AND activo = 1
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // Login exitoso - Iniciar sesión
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'nombre_completo' => $user['nombre_completo'],
                    'rol' => $user['rol']
                ];
                $_SESSION['login_time'] = time();
                $_SESSION['last_activity'] = time();
                
                // Actualizar último acceso
                $stmt = $db->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                // Log del acceso
                logAuditoria('usuarios', $user['id'], 'LOGIN', null, ['username' => $user['username']]);
                
                // Redirigir según el rol
                redirectToRoleHome();
            } else {
                $error = 'Usuario o contraseña incorrectos.';
                
                // Log del intento fallido
                logAuditoria('usuarios', null, 'LOGIN_FAILED', null, ['username' => $username, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
            }
        } catch (Exception $e) {
            error_log("Error en login: " . $e->getMessage());
            $error = 'Error del sistema. Por favor, intenta de nuevo.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title>Electroventura v2.0 - Acceso</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card fade-in">
            <!-- Header del Login -->
            <div class="login-header">
                <i class="fas fa-bolt"></i>
                <h1>Electroventura</h1>
                <div class="version">Gestión de Obras v2.0</div>
            </div>

            <!-- Formulario de Login -->
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="loginForm">
                    <div class="form-group">
                        <label class="form-label" for="username">
                            <i class="fas fa-user"></i> Usuario
                        </label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            class="form-control"
                            value="<?= htmlspecialchars($username ?? '') ?>"
                            placeholder="Introduce tu usuario"
                            required
                            autocomplete="username"
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">
                            <i class="fas fa-lock"></i> Contraseña
                        </label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-control"
                            placeholder="Introduce tu contraseña"
                            required
                            autocomplete="current-password"
                        >
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" id="loginBtn">
                        <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                    </button>
                </form>

                <!-- Información del sistema -->
                <div class="mt-4 text-center" style="font-size: 0.85rem; color: #6c757d;">
                    <i class="fas fa-info-circle"></i> Sistema de gestión integral de obras<br>
                    <small>Para soporte técnico, contacta con el administrador</small>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript para UX mejorada -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');
            const loginBtn = document.getElementById('loginBtn');
            const usernameField = document.getElementById('username');
            const passwordField = document.getElementById('password');

            // Focus automático en el primer campo
            usernameField.focus();

            // Manejar envío del formulario
            form.addEventListener('submit', function() {
                loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Iniciando...';
                loginBtn.disabled = true;
            });

            // Enter para pasar de campo
            usernameField.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    passwordField.focus();
                }
            });

            // Limpiar mensaje de error al escribir
            const inputs = [usernameField, passwordField];
            inputs.forEach(input => {
                input.addEventListener('input', function() {
                    const alert = document.querySelector('.alert-danger');
                    if (alert) {
                        alert.style.opacity = '0.5';
                    }
                });
            });

            // Animación de entrada
            document.querySelector('.login-card').classList.add('fade-in');
        });

        // Prevenir problemas de cache/sesión
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
</body>
</html>
