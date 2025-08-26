<?php
require_once '../config/config.php';
requireLogin();

// Configurar headers CORS y JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    $action = $_REQUEST['action'] ?? '';
    $table = $_REQUEST['table'] ?? '';
    
    // Validar tabla permitida
    $allowedTables = [
        'usuarios', 'proveedores', 'contactos', 'obras', 'obras_dia',
        'horas_trabajadores', 'gastos_ingresos', 'materiales_comprados',
        'gasto_hilo', 'materiales_varios', 'jung', 'material_iluminacion',
        'efapel', 'partes_rapidos', 'puntos', 'vehiculos', 'auditoria'
    ];
    
    if (!in_array($table, $allowedTables)) {
        throw new Exception('Tabla no permitida');
    }
    
    // Configuración de campos por tabla
    $tableConfigs = [
        'usuarios' => [
            'fields' => ['id', 'username', 'password', 'nombre_completo', 'email', 'telefono', 'rol', 'activo', 'created_at', 'updated_at'],
            'required' => ['username', 'nombre_completo', 'rol'],
            'searchable' => ['username', 'nombre_completo', 'email', 'telefono'],
            'display_name' => 'nombre_completo'
        ],
        'proveedores' => [
            'fields' => ['id', 'nombre', 'nif_cif', 'direccion', 'telefono', 'email', 'observaciones', 'activo', 'created_at', 'updated_at'],
            'required' => ['nombre'],
            'searchable' => ['nombre', 'nif_cif', 'email', 'telefono'],
            'display_name' => 'nombre'
        ],
        'contactos' => [
            'fields' => ['id', 'proveedor_id', 'nombre', 'telefono', 'email', 'cargo', 'principal', 'observaciones', 'activo', 'created_at', 'updated_at'],
            'required' => ['proveedor_id', 'nombre'],
            'searchable' => ['nombre', 'email', 'telefono', 'cargo'],
            'joins' => ['proveedores' => 'proveedor_id']
        ],
        'obras' => [
            'fields' => ['id', 'codigo', 'nombre', 'descripcion', 'proveedor_id', 'direccion_obra', 'fecha_inicio', 'fecha_fin', 'fecha_fin_prevista', 'presupuesto', 'estado', 'observaciones', 'activo', 'created_at', 'updated_at'],
            'required' => ['codigo', 'nombre'],
            'searchable' => ['codigo', 'nombre', 'descripcion', 'direccion_obra'],
            'joins' => ['proveedores' => 'proveedor_id'],
            'display_name' => 'nombre'
        ],
        'obras_dia' => [
            'fields' => ['id', 'obra_id', 'fecha', 'activa', 'observaciones', 'created_at', 'updated_at'],
            'required' => ['obra_id', 'fecha'],
            'searchable' => ['fecha', 'observaciones'],
            'joins' => ['obras' => 'obra_id']
        ],
        'horas_trabajadores' => [
            'fields' => ['id', 'obra_id', 'usuario_id', 'fecha', 'hora_entrada', 'hora_salida', 'horas', 'descripcion', 'lugar_trabajo', 'origen', 'observaciones', 'created_at', 'updated_at'],
            'required' => ['obra_id', 'usuario_id', 'fecha', 'horas'],
            'searchable' => ['fecha', 'descripcion', 'lugar_trabajo'],
            'joins' => ['obras' => 'obra_id', 'usuarios' => 'usuario_id']
        ],
        'gastos_ingresos' => [
            'fields' => ['id', 'obra_id', 'tipo', 'categoria', 'concepto', 'descripcion', 'cantidad', 'fecha', 'proveedor_id', 'documento', 'observaciones', 'created_at', 'updated_at'],
            'required' => ['obra_id', 'tipo', 'concepto', 'cantidad', 'fecha'],
            'searchable' => ['concepto', 'descripcion', 'categoria', 'documento'],
            'joins' => ['obras' => 'obra_id', 'proveedores' => 'proveedor_id']
        ],
        'materiales_comprados' => [
            'fields' => ['id', 'obra_id', 'material', 'descripcion', 'cantidad', 'unidad', 'precio_unitario', 'precio_total', 'proveedor_id', 'fecha_compra', 'fecha_entrega', 'albaran', 'factura', 'observaciones', 'stock_general', 'created_at', 'updated_at'],
            'required' => ['material', 'cantidad'],
            'searchable' => ['material', 'descripcion', 'albaran', 'factura'],
            'joins' => ['obras' => 'obra_id', 'proveedores' => 'proveedor_id']
        ],
        'gasto_hilo' => [
            'fields' => ['id', 'obra_id', 'tipo_hilo', 'descripcion', 'metros', 'precio_metro', 'precio_total', 'proveedor_id', 'fecha_uso', 'observaciones', 'stock_general', 'created_at', 'updated_at'],
            'required' => ['tipo_hilo', 'metros'],
            'searchable' => ['tipo_hilo', 'descripcion'],
            'joins' => ['obras' => 'obra_id', 'proveedores' => 'proveedor_id']
        ],
        'materiales_varios' => [
            'fields' => ['id', 'obra_id', 'material', 'descripcion', 'cantidad', 'unidad', 'precio_unitario', 'precio_total', 'proveedor_id', 'fecha', 'observaciones', 'stock_general', 'created_at', 'updated_at'],
            'required' => ['material', 'cantidad'],
            'searchable' => ['material', 'descripcion'],
            'joins' => ['obras' => 'obra_id', 'proveedores' => 'proveedor_id']
        ],
        'jung' => [
            'fields' => ['id', 'obra_id', 'producto', 'referencia', 'descripcion', 'cantidad', 'precio_unitario', 'precio_total', 'proveedor_id', 'fecha', 'observaciones', 'stock_general', 'created_at', 'updated_at'],
            'required' => ['producto', 'cantidad'],
            'searchable' => ['producto', 'referencia', 'descripcion'],
            'joins' => ['obras' => 'obra_id', 'proveedores' => 'proveedor_id']
        ],
        'material_iluminacion' => [
            'fields' => ['id', 'obra_id', 'tipo_luminaria', 'marca', 'modelo', 'potencia', 'cantidad', 'precio_unitario', 'precio_total', 'proveedor_id', 'fecha', 'observaciones', 'stock_general', 'created_at', 'updated_at'],
            'required' => ['tipo_luminaria', 'cantidad'],
            'searchable' => ['tipo_luminaria', 'marca', 'modelo'],
            'joins' => ['obras' => 'obra_id', 'proveedores' => 'proveedor_id']
        ],
        'efapel' => [
            'fields' => ['id', 'obra_id', 'producto', 'referencia', 'serie', 'descripcion', 'cantidad', 'precio_unitario', 'precio_total', 'proveedor_id', 'fecha', 'observaciones', 'stock_general', 'created_at', 'updated_at'],
            'required' => ['producto', 'cantidad'],
            'searchable' => ['producto', 'referencia', 'serie', 'descripcion'],
            'joins' => ['obras' => 'obra_id', 'proveedores' => 'proveedor_id']
        ],
        'partes_rapidos' => [
            'fields' => ['id', 'obra_id', 'titulo', 'descripcion', 'urgencia', 'estado', 'asignado_a', 'fecha_limite', 'observaciones', 'created_at', 'updated_at'],
            'required' => ['titulo'],
            'searchable' => ['titulo', 'descripcion'],
            'joins' => ['obras' => 'obra_id', 'usuarios' => 'asignado_a']
        ],
        'puntos' => [
            'fields' => ['id', 'obra_id', 'usuario_id', 'concepto', 'puntos', 'fecha', 'observaciones', 'created_at', 'updated_at'],
            'required' => ['usuario_id', 'concepto', 'puntos', 'fecha'],
            'searchable' => ['concepto', 'observaciones'],
            'joins' => ['obras' => 'obra_id', 'usuarios' => 'usuario_id']
        ],
        'vehiculos' => [
            'fields' => ['id', 'marca', 'modelo', 'matricula', 'año', 'combustible', 'kilometraje', 'fecha_compra', 'precio_compra', 'observaciones', 'activo', 'created_at', 'updated_at'],
            'required' => ['matricula'],
            'searchable' => ['marca', 'modelo', 'matricula']
        ],
        'auditoria' => [
            'fields' => ['id', 'tabla', 'registro_id', 'accion', 'datos_anteriores', 'datos_nuevos', 'usuario_id', 'ip_address', 'fecha_accion'],
            'required' => [],
            'searchable' => ['tabla', 'accion', 'ip_address'],
            'readonly' => true
        ]
    ];
    
    $config = $tableConfigs[$table] ?? [];
    
    switch ($action) {
        case 'read':
            handleRead($db, $table, $config);
            break;
        case 'create':
            if (isset($config['readonly']) && $config['readonly']) {
                throw new Exception('Esta tabla es de solo lectura');
            }
            handleCreate($db, $table, $config);
            break;
        case 'update':
            if (isset($config['readonly']) && $config['readonly']) {
                throw new Exception('Esta tabla es de solo lectura');
            }
            handleUpdate($db, $table, $config);
            break;
        case 'delete':
            if (isset($config['readonly']) && $config['readonly']) {
                throw new Exception('Esta tabla es de solo lectura');
            }
            handleDelete($db, $table, $config);
            break;
        case 'export':
            handleExport($db, $table, $config);
            break;
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function handleRead($db, $table, $config) {
    $page = intval($_GET['page'] ?? 1);
    $pageSize = intval($_GET['pageSize'] ?? 50);
    $search = $_GET['search'] ?? '';
    
    $offset = ($page - 1) * $pageSize;
    
    // Construir consulta base
    $fields = $config['fields'] ?? ['*'];
    $selectFields = [];
    
    // Agregar campos principales
    foreach ($fields as $field) {
        $selectFields[] = "$table.$field";
    }
    
    // Agregar campos de joins si existen
    $joins = '';
    if (isset($config['joins'])) {
        global $tableConfigs;
        foreach ($config['joins'] as $joinTable => $joinField) {
            $joins .= " LEFT JOIN $joinTable ON $table.$joinField = $joinTable.id";
            if (isset($tableConfigs[$joinTable]['display_name'])) {
                $selectFields[] = "$joinTable.{$tableConfigs[$joinTable]['display_name']} as {$joinTable}_name";
            } else {
                $selectFields[] = "$joinTable.nombre as {$joinTable}_name";
            }
        }
    }
    
    $sql = "SELECT " . implode(', ', $selectFields) . " FROM $table" . $joins;
    
    // Agregar búsqueda
    $whereConditions = [];
    $params = [];
    
    if (!empty($search) && isset($config['searchable'])) {
        $searchConditions = [];
        foreach ($config['searchable'] as $field) {
            $searchConditions[] = "$table.$field LIKE ?";
            $params[] = "%$search%";
        }
        if (!empty($searchConditions)) {
            $whereConditions[] = '(' . implode(' OR ', $searchConditions) . ')';
        }
    }
    
    if (!empty($whereConditions)) {
        $sql .= " WHERE " . implode(' AND ', $whereConditions);
    }
    
    // Contar total
    $countSql = "SELECT COUNT(*) as total FROM $table" . $joins;
    if (!empty($whereConditions)) {
        $countSql .= " WHERE " . implode(' AND ', $whereConditions);
    }
    
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Obtener datos paginados
    $sql .= " ORDER BY $table.id DESC LIMIT ? OFFSET ?";
    $params[] = $pageSize;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'total' => intval($total),
        'page' => $page,
        'pageSize' => $pageSize
    ]);
}

function handleCreate($db, $table, $config) {
    $data = $_POST;
    unset($data['action'], $data['table'], $data['id']);
    
    // Validar campos requeridos
    if (isset($config['required'])) {
        foreach ($config['required'] as $field) {
            if (empty($data[$field]) && $data[$field] !== '0') {
                throw new Exception("El campo $field es requerido");
            }
        }
    }
    
    // Encriptar contraseña si existe
    if (isset($data['password']) && !empty($data['password'])) {
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
    } elseif (isset($data['password']) && empty($data['password'])) {
        unset($data['password']); // No actualizar contraseña vacía
    }
    
    // Procesar checkboxes
    foreach ($data as $key => $value) {
        if (in_array($key, ['activo', 'principal', 'stock_general', 'activa']) && $value === null) {
            $data[$key] = 0;
        }
    }
    
    // Agregar timestamps si existen
    if (in_array('created_at', $config['fields'] ?? [])) {
        $data['created_at'] = date('Y-m-d H:i:s');
    }
    if (in_array('updated_at', $config['fields'] ?? [])) {
        $data['updated_at'] = date('Y-m-d H:i:s');
    }
    
    $fields = array_keys($data);
    $placeholders = array_fill(0, count($fields), '?');
    
    $sql = "INSERT INTO $table (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
    $stmt = $db->prepare($sql);
    
    if ($stmt->execute(array_values($data))) {
        $insertId = $db->lastInsertId();
        
        // Registrar auditoría
        logAudit($db, $table, $insertId, 'INSERT', null, $data);
        
        echo json_encode([
            'success' => true,
            'message' => 'Registro creado correctamente',
            'id' => $insertId
        ]);
    } else {
        throw new Exception('Error al crear el registro');
    }
}

function handleUpdate($db, $table, $config) {
    $id = $_POST['id'] ?? null;
    if (!$id) {
        throw new Exception('ID requerido para actualizar');
    }
    
    // Obtener datos anteriores para auditoría
    $stmt = $db->prepare("SELECT * FROM $table WHERE id = ?");
    $stmt->execute([$id]);
    $oldData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$oldData) {
        throw new Exception('Registro no encontrado');
    }
    
    $data = $_POST;
    unset($data['action'], $data['table'], $data['id']);
    
    // Validar campos requeridos
    if (isset($config['required'])) {
        foreach ($config['required'] as $field) {
            if (empty($data[$field]) && $data[$field] !== '0') {
                throw new Exception("El campo $field es requerido");
            }
        }
    }
    
    // Encriptar contraseña si se proporciona
    if (isset($data['password']) && !empty($data['password'])) {
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
    } elseif (isset($data['password']) && empty($data['password'])) {
        unset($data['password']); // No actualizar contraseña vacía
    }
    
    // Procesar checkboxes
    foreach ($config['fields'] ?? [] as $field) {
        if (in_array($field, ['activo', 'principal', 'stock_general', 'activa']) && !isset($data[$field])) {
            $data[$field] = 0;
        }
    }
    
    // Agregar timestamp si existe
    if (in_array('updated_at', $config['fields'] ?? [])) {
        $data['updated_at'] = date('Y-m-d H:i:s');
    }
    
    $fields = array_keys($data);
    $setClause = implode(' = ?, ', $fields) . ' = ?';
    
    $sql = "UPDATE $table SET $setClause WHERE id = ?";
    $params = array_values($data);
    $params[] = $id;
    
    $stmt = $db->prepare($sql);
    
    if ($stmt->execute($params)) {
        // Registrar auditoría
        logAudit($db, $table, $id, 'UPDATE', $oldData, $data);
        
        echo json_encode([
            'success' => true,
            'message' => 'Registro actualizado correctamente'
        ]);
    } else {
        throw new Exception('Error al actualizar el registro');
    }
}

function handleDelete($db, $table, $config) {
    $id = $_POST['id'] ?? null;
    if (!$id) {
        throw new Exception('ID requerido para eliminar');
    }
    
    // Obtener datos para auditoría
    $stmt = $db->prepare("SELECT * FROM $table WHERE id = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$data) {
        throw new Exception('Registro no encontrado');
    }
    
    $stmt = $db->prepare("DELETE FROM $table WHERE id = ?");
    
    if ($stmt->execute([$id])) {
        // Registrar auditoría
        logAudit($db, $table, $id, 'DELETE', $data, null);
        
        echo json_encode([
            'success' => true,
            'message' => 'Registro eliminado correctamente'
        ]);
    } else {
        throw new Exception('Error al eliminar el registro');
    }
}

function handleExport($db, $table, $config) {
    // Requerir PhpSpreadsheet para exportación
    require_once '../vendor/autoload.php'; // Ajustar ruta según instalación
    
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
    
    $search = $_GET['search'] ?? '';
    
    // Construir consulta similar a handleRead pero sin paginación
    $fields = $config['fields'] ?? ['*'];
    $selectFields = [];
    
    foreach ($fields as $field) {
        $selectFields[] = "$table.$field";
    }
    
    $joins = '';
    if (isset($config['joins'])) {
        foreach ($config['joins'] as $joinTable => $joinField) {
            $joins .= " LEFT JOIN $joinTable ON $table.$joinField = $joinTable.id";
            $selectFields[] = "$joinTable.nombre as {$joinTable}_name";
        }
    }
    
    $sql = "SELECT " . implode(', ', $selectFields) . " FROM $table" . $joins;
    
    $whereConditions = [];
    $params = [];
    
    if (!empty($search) && isset($config['searchable'])) {
        $searchConditions = [];
        foreach ($config['searchable'] as $field) {
            $searchConditions[] = "$table.$field LIKE ?";
            $params[] = "%$search%";
        }
        if (!empty($searchConditions)) {
            $whereConditions[] = '(' . implode(' OR ', $searchConditions) . ')';
        }
    }
    
    if (!empty($whereConditions)) {
        $sql .= " WHERE " . implode(' AND ', $whereConditions);
    }
    
    $sql .= " ORDER BY $table.id DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Crear Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Headers
    if (!empty($data)) {
        $headers = array_keys($data[0]);
        $col = 1;
        foreach ($headers as $header) {
            $sheet->setCellValueByColumnAndRow($col, 1, ucfirst(str_replace('_', ' ', $header)));
            $col++;
        }
        
        // Data
        $row = 2;
        foreach ($data as $record) {
            $col = 1;
            foreach ($record as $value) {
                $sheet->setCellValueByColumnAndRow($col, $row, $value);
                $col++;
            }
            $row++;
        }
    }
    
    // Configurar headers para descarga
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $table . '_' . date('Y-m-d') . '.xlsx"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

function logAudit($db, $table, $registroId, $accion, $datosAnteriores, $datosNuevos) {
    try {
        $sql = "INSERT INTO auditoria (tabla, registro_id, accion, datos_anteriores, datos_nuevos, usuario_id, ip_address, fecha_accion) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $table,
            $registroId,
            $accion,
            $datosAnteriores ? json_encode($datosAnteriores) : null,
            $datosNuevos ? json_encode($datosNuevos) : null,
            $_SESSION['user_id'] ?? null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        // Fallar silenciosamente para no interrumpir operación principal
        error_log("Error al registrar auditoría: " . $e->getMessage());
    }
}
?>
