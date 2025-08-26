<?php
/**
 * ELECTROVENTURA v2.0 - GESTIONAR OBRAS DEL DÍA
 * =============================================
 * Página para que los administradores seleccionen qué obras están disponibles cada día
 */

require_once 'config/config.php';
requireLogin();

// Solo administradores pueden acceder
if (!canManageObrasDelDia()) {
    redirect('login.php');
}

$mensaje = '';
$tipo_mensaje = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = Database::getInstance()->getConnection();
        $db->beginTransaction();
        
        $fecha = $_POST['fecha'] ?? date('Y-m-d');
        $obras_seleccionadas = $_POST['obras'] ?? [];
        
        // Desactivar todas las obras del día seleccionado
        $stmt = $db->prepare("UPDATE obras_dia SET activa = 0 WHERE fecha = ?");
        $stmt->execute([$fecha]);
        
        // Activar las obras seleccionadas
        foreach ($obras_seleccionadas as $obra_id) {
            $stmt = $db->prepare("
                INSERT INTO obras_dia (fecha, obra_id, creado_por, activa) 
                VALUES (?, ?, ?, 1)
                ON DUPLICATE KEY UPDATE 
                    activa = 1, 
                    creado_por = ?, 
                    fecha_creacion = NOW()
            ");
            $stmt->execute([$fecha, $obra_id, $_SESSION['user']['id'], $_SESSION['user']['id']]);
        }
        
        $db->commit();
        
        // Log de la actividad
        $total_obras = count($obras_seleccionadas);
        logActivity('OBRAS_DIA_CONFIG', 'obras_dia', null, null, 
                   "Configuradas $total_obras obras para el día $fecha");
        
        $mensaje = count($obras_seleccionadas) . " obras configuradas correctamente para el " . 
                  date('d/m/Y', strtotime($fecha));
        $tipo_mensaje = 'success';
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error configurando obras del día: " . $e->getMessage());
        $mensaje = 'Error al configurar las obras. Por favor, inténtalo de nuevo.';
        $tipo_mensaje = 'danger';
    }
}

// Fecha por defecto (hoy) - Definir siempre antes del try-catch
$fecha_seleccionada = $_GET['fecha'] ?? date('Y-m-d');

try {
    $db = Database::getInstance()->getConnection();
    
    // Obtener todas las obras activas
    $stmt = $db->prepare("
        SELECT o.id as obra_id, o.codigo, o.nombre, o.direccion_obra as direccion, 
               COALESCE(p.nombre, 'Sin cliente') as cliente, o.estado,
               DATE(o.fecha_inicio) as fecha_inicio,
               DATE(o.fecha_fin_prevista) as fecha_fin_estimada
        FROM obras o
        LEFT JOIN proveedores p ON o.proveedor_id = p.id
        WHERE o.estado IN ('pendiente', 'en_curso') AND o.activo = 1
        ORDER BY o.nombre ASC
    ");
    $stmt->execute();
    $todas_obras = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener obras ya configuradas para la fecha seleccionada
    $stmt = $db->prepare("
        SELECT od.obra_id, o.nombre, o.codigo,
               od.creado_por, u.nombre_completo as configurado_por_nombre,
               od.fecha_creacion
        FROM obras_dia od
        INNER JOIN obras o ON od.obra_id = o.id
        LEFT JOIN usuarios u ON od.creado_por = u.id
        WHERE od.fecha = ? AND od.activa = 1
        ORDER BY o.nombre
    ");
    $stmt->execute([$fecha_seleccionada]);
    $obras_configuradas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $obras_configuradas_ids = array_column($obras_configuradas, 'obra_id');
    
} catch (Exception $e) {
    error_log("Error cargando obras: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    $mensaje = 'Error al cargar los datos: ' . $e->getMessage();
    $tipo_mensaje = 'danger';
    $todas_obras = [];
    $obras_configuradas = [];
    $obras_configuradas_ids = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Gestionar Obras del Día - Electroventura v2.0</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #e8f5f4 0%, #f0f9f8 50%, #e8f5f4 100%) !important;
            padding-top: 0 !important;
            margin: 0;
        }
        
        .obras-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            padding-top: 90px; /* Espacio para el navbar */
        }
        
        .obras-header {
            background-color: #2a9d8f;
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .obras-header h1 {
            color: white;
            margin-bottom: 0.5rem;
        }
        
        .obras-header p {
            color: rgba(255, 255, 255, 0.9);
        }
        
        .date-selector {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .date-input {
            padding: 0.75rem 1rem;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1rem;
        }
        
        .obras-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .obra-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }
        
        .obra-card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transform: translateY(-5px);
        }
        
        .obra-card.selected {
            border-color: var(--primary-color);
            background: linear-gradient(135deg, #f8f9fa 0%, #e3f2fd 100%);
            box-shadow: 0 5px 20px rgba(42, 157, 143, 0.2);
        }
        
        .obra-card .checkbox {
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: 20px;
            height: 20px;
        }
        
        .obra-codigo {
            font-weight: bold;
            color: var(--primary-color);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .obra-nombre {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0.5rem 0;
            color: var(--dark-color);
        }
        
        .obra-cliente {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 0.5rem;
        }
        
        .obra-direccion {
            color: #888;
            font-size: 0.85rem;
            line-height: 1.3;
            margin-bottom: 1rem;
        }
        
        .obra-estado {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .estado-pendiente {
            background: #fff3cd;
            color: #856404;
        }
        
        .estado-en_curso {
            background: #d4edda;
            color: #155724;
        }
        
        .estado-finalizada {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .estado-cancelada {
            background: #f8d7da;
            color: #721c24;
        }
        
        .obras-configuradas {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .obra-configurada {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            margin-bottom: 0.5rem;
            background: #f8f9fa;
        }
        
        .controls-panel {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 2rem;
        }
        
        @media (max-width: 768px) {
            .obras-container {
                padding: 0 1rem;
            }
            
            .obras-grid {
                grid-template-columns: 1fr;
            }
            
            .date-selector {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body class="internal-page">
    <!-- Header de navegación -->
    <nav class="navbar">
        <div class="d-flex" style="width: 100%; justify-content: space-between; align-items: center;">
            <a href="admin_home.php" class="navbar-brand">
                <i class="fas fa-arrow-left" style="margin-right: 0.5rem;"></i>
                <i class="fas fa-bolt"></i>
                Electroventura v2.0
            </a>
            <div class="navbar-user">
                <span class="user-name"><?= htmlspecialchars(getCurrentUser()['nombre_completo'] ?? 'Usuario') ?></span>
                <span class="user-role">Administrador</span>
                <a href="logout.php" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Salir
                </a>
            </div>
        </div>
    </nav>

    <div class="obras-container">
        <!-- Header de la página -->
        <div class="obras-header fade-in">
            <h1><i class="fas fa-hard-hat"></i> Gestionar Obras del Día</h1>
            <p>Selecciona las obras que estarán disponibles para imputaciones en la fecha elegida.</p>
            
            <?php if ($mensaje): ?>
                <div class="alert alert-<?= $tipo_mensaje ?>">
                    <i class="fas fa-<?= $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
                    <?= htmlspecialchars($mensaje) ?>
                </div>
            <?php endif; ?>

            <!-- Selector de fecha -->
            <div class="date-selector">
                <label for="fecha"><i class="fas fa-calendar-alt"></i> <strong>Fecha:</strong></label>
                <input type="date" id="fecha" class="date-input" value="<?= $fecha_seleccionada ?>" 
                       onchange="cambiarFecha(this.value)">
                <button type="button" class="btn btn-primary" onclick="cambiarFecha(document.getElementById('fecha').value)">
                    <i class="fas fa-search"></i> Ver obras
                </button>
            </div>
        </div>

        <?php if (!empty($obras_configuradas)): ?>
        <!-- Obras ya configuradas para esta fecha -->
        <div class="obras-configuradas fade-in">
            <h3><i class="fas fa-check-circle" style="color: var(--success-color);"></i> 
                Obras configuradas para el <?= date('d/m/Y', strtotime($fecha_seleccionada)) ?>
            </h3>
            <?php foreach ($obras_configuradas as $obra): ?>
                <div class="obra-configurada">
                    <div>
                        <strong><?= htmlspecialchars($obra['codigo']) ?></strong> - 
                        <?= htmlspecialchars($obra['nombre']) ?>
                    </div>
                    <small class="text-muted">
                        Configurado por <?= htmlspecialchars($obra['configurado_por_nombre']) ?> 
                        el <?= date('d/m/Y H:i', strtotime($obra['fecha_creacion'])) ?>
                    </small>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Formulario de selección -->
        <form method="POST" id="obrasForm">
            <input type="hidden" name="fecha" value="<?= $fecha_seleccionada ?>">
            
            <!-- Lista de todas las obras disponibles -->
            <div class="obras-grid fade-in">
                <?php foreach ($todas_obras as $obra): ?>
                    <div class="obra-card <?= in_array($obra['obra_id'], $obras_configuradas_ids) ? 'selected' : '' ?>" 
                         onclick="toggleObra(<?= $obra['obra_id'] ?>)">
                        
                        <input type="checkbox" 
                               name="obras[]" 
                               value="<?= $obra['obra_id'] ?>"
                               id="obra_<?= $obra['obra_id'] ?>"
                               class="checkbox"
                               <?= in_array($obra['obra_id'], $obras_configuradas_ids) ? 'checked' : '' ?>>
                        
                        <div class="obra-codigo"><?= htmlspecialchars($obra['codigo']) ?></div>
                        <div class="obra-nombre"><?= htmlspecialchars($obra['nombre']) ?></div>
                        <div class="obra-cliente">
                            <i class="fas fa-building"></i> <?= htmlspecialchars($obra['cliente']) ?>
                        </div>
                        <div class="obra-direccion">
                            <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($obra['direccion']) ?>
                        </div>
                        <div class="obra-estado estado-<?= $obra['estado'] ?>">
                            <?= ucfirst(str_replace('_', ' ', $obra['estado'])) ?>
                        </div>
                        
                        <?php if ($obra['fecha_inicio']): ?>
                        <div style="margin-top: 1rem; font-size: 0.8rem; color: #666;">
                            <i class="fas fa-calendar-alt"></i> 
                            Inicio: <?= date('d/m/Y', strtotime($obra['fecha_inicio'])) ?>
                            <?php if ($obra['fecha_fin_estimada']): ?>
                            | Fin est.: <?= date('d/m/Y', strtotime($obra['fecha_fin_estimada'])) ?>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (empty($todas_obras)): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    No hay obras activas disponibles para configurar.
                </div>
            <?php endif; ?>

            <!-- Panel de controles -->
            <div class="controls-panel fade-in">
                <div class="d-flex" style="justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <div>
                        <strong>Obras seleccionadas: <span id="contador">0</span></strong>
                    </div>
                    <div>
                        <button type="button" onclick="seleccionarTodas()" class="btn" style="background: #f8f9fa; border: 1px solid #ddd; margin-right: 0.5rem;">
                            <i class="fas fa-check-square"></i> Todas
                        </button>
                        <button type="button" onclick="limpiarSeleccion()" class="btn" style="background: #f8f9fa; border: 1px solid #ddd;">
                            <i class="fas fa-times"></i> Ninguna
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block" id="guardarBtn">
                    <i class="fas fa-save"></i> Guardar configuración para el <?= date('d/m/Y', strtotime($fecha_seleccionada)) ?>
                </button>
                
                <div class="mt-2 text-center">
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i>
                        Las obras seleccionadas estarán disponibles para imputaciones en la fecha indicada
                    </small>
                </div>
            </div>
        </form>
    </div>

    <!-- JavaScript para funcionalidad interactiva -->
    <script>
        let obrasSeleccionadas = <?= json_encode($obras_configuradas_ids) ?>;
        
        document.addEventListener('DOMContentLoaded', function() {
            actualizarContador();
            
            // Animaciones de entrada
            const elementos = document.querySelectorAll('.fade-in');
            elementos.forEach((el, index) => {
                setTimeout(() => {
                    el.style.opacity = '1';
                    el.style.transform = 'translateY(0)';
                }, index * 150);
            });
        });

        function toggleObra(obraId) {
            const checkbox = document.getElementById('obra_' + obraId);
            const card = checkbox.closest('.obra-card');
            
            checkbox.checked = !checkbox.checked;
            
            if (checkbox.checked) {
                card.classList.add('selected');
                if (!obrasSeleccionadas.includes(obraId)) {
                    obrasSeleccionadas.push(obraId);
                }
            } else {
                card.classList.remove('selected');
                obrasSeleccionadas = obrasSeleccionadas.filter(id => id !== obraId);
            }
            
            actualizarContador();
        }

        function seleccionarTodas() {
            const checkboxes = document.querySelectorAll('input[name="obras[]"]');
            const cards = document.querySelectorAll('.obra-card');
            
            checkboxes.forEach((checkbox, index) => {
                checkbox.checked = true;
                cards[index].classList.add('selected');
            });
            
            obrasSeleccionadas = Array.from(checkboxes).map(cb => parseInt(cb.value));
            actualizarContador();
        }

        function limpiarSeleccion() {
            const checkboxes = document.querySelectorAll('input[name="obras[]"]');
            const cards = document.querySelectorAll('.obra-card');
            
            checkboxes.forEach((checkbox, index) => {
                checkbox.checked = false;
                cards[index].classList.remove('selected');
            });
            
            obrasSeleccionadas = [];
            actualizarContador();
        }

        function actualizarContador() {
            document.getElementById('contador').textContent = obrasSeleccionadas.length;
            
            const guardarBtn = document.getElementById('guardarBtn');
            if (obrasSeleccionadas.length === 0) {
                guardarBtn.innerHTML = '<i class="fas fa-times"></i> No hay obras seleccionadas';
                guardarBtn.disabled = true;
            } else {
                const fecha = document.getElementById('fecha').value;
                const fechaFormateada = new Date(fecha).toLocaleDateString('es-ES');
                guardarBtn.innerHTML = `<i class="fas fa-save"></i> Guardar ${obrasSeleccionadas.length} obra${obrasSeleccionadas.length !== 1 ? 's' : ''} para el ${fechaFormateada}`;
                guardarBtn.disabled = false;
            }
        }

        function cambiarFecha(nuevaFecha) {
            window.location.href = 'gestionar_obras.php?fecha=' + nuevaFecha;
        }

        // Confirmar cambios antes de salir
        window.addEventListener('beforeunload', function(e) {
            const form = document.getElementById('obrasForm');
            const formData = new FormData(form);
            const obrasFormulario = formData.getAll('obras[]').map(id => parseInt(id));
            
            // Comparar si hay cambios pendientes
            if (JSON.stringify(obrasFormulario.sort()) !== JSON.stringify([...obrasSeleccionadas].sort())) {
                e.preventDefault();
                e.returnValue = 'Tienes cambios sin guardar. ¿Estás seguro de que quieres salir?';
            }
        });

        // Manejar envío del formulario
        document.getElementById('obrasForm').addEventListener('submit', function() {
            const btn = document.getElementById('guardarBtn');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            btn.disabled = true;
        });
    </script>

    <style>
        .fade-in {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.5s ease;
        }
    </style>
</body>
</html>
