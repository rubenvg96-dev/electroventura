<?php
/**
 * ELECTROVENTURA v2.0 - PÁGINA DE INICIO TRABAJADOR
 * =================================================
 * Los trabajadores van directamente a Imputaciones, pero mostramos
 * una pantalla de bienvenida rápida antes de redirigir automáticamente
 */

require_once 'config/config.php';
requireLogin();

// Solo trabajadores pueden acceder aquí
if (!isTrabajador()) {
    if (canManageObrasDelDia()) {
        redirect('admin_home.php');
    } else {
        redirect('supervisor_home.php');
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
    <title>Área de Trabajo - Electroventura v2.0</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Header de navegación -->
    <nav class="navbar">
        <div class="d-flex" style="width: 100%; justify-content: space-between; align-items: center;">
            <a href="#" class="navbar-brand">
                <i class="fas fa-bolt"></i>
                Electroventura v2.0
            </a>
            <div class="navbar-user">
                <span class="user-name"><?= htmlspecialchars(getCurrentUser()['nombre_completo'] ?? 'Usuario') ?></span>
                <span class="user-role">Trabajador</span>
                <a href="logout.php" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Salir
                </a>
            </div>
        </div>
    </nav>

    <!-- Contenido principal -->
    <div class="inicio-container">
        <div class="inicio-card fade-in">
            <!-- Encabezado -->
            <div class="inicio-header">
                <h1>Área de Trabajo</h1>
                <div class="user-info">
                    <i class="fas fa-hard-hat"></i> 
                    Bienvenido, <?= htmlspecialchars(getCurrentUser()['nombre_completo'] ?? 'Usuario') ?>
                </div>
                <div class="user-role">Registro de horas y actividades</div>
            </div>

            <!-- Opción única - Imputaciones (auto-redirige) -->
            <div class="opciones-grid trabajador">
                <a href="imputaciones.php" class="opcion-card imputaciones" id="imputacionesCard">
                    <div class="opcion-content">
                        <div class="icon">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <h3>Registro de Imputaciones</h3>
                        <p>Registra tus horas trabajadas y actividades en las obras asignadas para el día de hoy.</p>
                    </div>
                </a>
            </div>

            <!-- Mensaje informativo -->
            <div class="mt-4">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>¡Listo para trabajar!</strong> Serás redirigido automáticamente al registro de imputaciones 
                    donde podrás anotar tus horas y actividades del día.
                </div>
            </div>

            <!-- Contador de redirección -->
            <div class="mt-3 text-center">
                <div id="contador" style="font-size: 1.1rem; color: var(--primary-color);">
                    <i class="fas fa-clock"></i> Redirigiendo en <span id="segundos">3</span> segundos...
                </div>
                <div class="mt-2">
                    <button onclick="redirectNow()" class="btn btn-primary" style="padding: 0.5rem 1.5rem;">
                        <i class="fas fa-forward"></i> Ir ahora
                    </button>
                </div>
            </div>

            <!-- Información de acceso -->
            <div class="mt-4 text-center">
                <small style="color: #6c757d;">
                    <i class="fas fa-clock"></i> Último acceso: <?= date('d/m/Y H:i', $_SESSION['login_time']) ?>
                </small>
            </div>
        </div>
    </div>

    <!-- JavaScript para redirección automática y UX -->
    <script>
        let segundosRestantes = 3;
        let intervalo;

        document.addEventListener('DOMContentLoaded', function() {
            // Animación de entrada
            document.querySelector('.inicio-card').classList.add('fade-in');
            
            // Iniciar contador de redirección
            iniciarContador();
            
            // Efecto hover en la tarjeta
            const tarjeta = document.getElementById('imputacionesCard');
            tarjeta.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-10px) scale(1.02)';
            });
            
            tarjeta.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });

        function iniciarContador() {
            intervalo = setInterval(function() {
                segundosRestantes--;
                document.getElementById('segundos').textContent = segundosRestantes;
                
                if (segundosRestantes <= 0) {
                    redirectNow();
                }
            }, 1000);
        }

        function redirectNow() {
            clearInterval(intervalo);
            
            // Efecto de salida
            document.getElementById('contador').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cargando...';
            document.querySelector('.inicio-card').style.opacity = '0.7';
            
            // Redirigir
            setTimeout(function() {
                window.location.href = 'imputaciones.php';
            }, 500);
        }

        // Pausar contador si el usuario interactúa
        document.addEventListener('click', function() {
            if (segundosRestantes > 0) {
                clearInterval(intervalo);
                document.getElementById('contador').innerHTML = '<i class="fas fa-pause"></i> Contador pausado - Haz clic para continuar';
            }
        });

        // Reanudar contador si hace clic en el área de contador
        document.getElementById('contador').addEventListener('click', function() {
            if (segundosRestantes > 0 && this.innerHTML.includes('pausado')) {
                iniciarContador();
            }
        });

        // Prevenir acceso directo sin sesión
        if (!document.querySelector('.navbar-user')) {
            window.location.href = 'login.php';
        }
    </script>
</body>
</html>
