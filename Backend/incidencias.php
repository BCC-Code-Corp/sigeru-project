<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'conexion.php';

$input = json_decode(file_get_contents('php://input'), true);
$accion = isset($input['accion']) ? $input['accion'] : (isset($_POST['accion']) ? $_POST['accion'] : '');

try {
    // A. CREAR INCIDENCIA (VECINO)
    if ($accion === 'crear') {
        $ubicacion = isset($input['ubicacion']) ? $input['ubicacion'] : $_POST['ubicacion'];
        $estado_cont = isset($input['estado_contenedor']) ? $input['estado_contenedor'] : $_POST['estado_contenedor'];
        $tipo_basura = isset($input['tipo_basura']) ? $input['tipo_basura'] : $_POST['tipo_basura'];

        $stmt = $pdo->prepare("INSERT INTO incidencias (ubicacion, estado_contenedor, tipo_basura, estado_incidencia) VALUES (?, ?, ?, 'abierta')");
        $stmt->execute([$ubicacion, $estado_cont, $tipo_basura]);

        echo json_encode(["status" => "success", "message" => "Reporte ciudadano registrado con éxito."]);
        exit;
    }

    // B. LISTAR INCIDENCIAS (OPERARIO / CUADRILLA)
    if ($accion === 'listar') {
        $stmt = $pdo->query("SELECT * FROM incidencias ORDER BY id DESC");
        $data = $stmt->fetchAll();
        echo json_encode(["status" => "success", "data" => $data]);
        exit;
    }

    // C. DESPACHAR Y ASIGNAR LOGÍSTICA (OPERARIO)
    if ($accion === 'asignar') {
        $id = $input['id'];
        $cuadrilla_id = $input['cuadrilla_id'];
        $matricula = $input['matricula_camion'];
        $comentario = $input['comentario_operario'];

        // Transacción para garantizar consistencia: Asigna incidencia y pasa camión a "En Ruta"
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("UPDATE incidencias SET cuadrilla_id = ?, matricula_camion = ?, comentario_operario = ?, estado_incidencia = 'en curso' WHERE id = ?");
        $stmt->execute([$cuadrilla_id, $matricula, $comentario, $id]);

        $stmtCamion = $pdo->prepare("UPDATE camiones SET estado = 'En Ruta' WHERE matricula = ?");
        $stmtCamion->execute([$matricula]);

        $pdo->commit();
        echo json_encode(["status" => "success", "message" => "Logística asignada correctamente. Estado: En curso."]);
        exit;
    }

    // D. FINALIZAR TAREA (CUADRILLA)
    if ($accion === 'resolver') {
        $id = $input['id'];

        // Obtener la matrícula antes de cerrar para liberar el camión
        $stmtGet = $pdo->prepare("SELECT matricula_camion FROM incidencias WHERE id = ?");
        $stmtGet->execute([$id]);
        $inc = $stmtGet->fetch();

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("UPDATE incidencias SET estado_incidencia = 'incidencia solucionada' WHERE id = ?");
        $stmt->execute([$id]);

        if ($inc && !empty($inc['matricula_camion'])) {
            $stmtCamion = $pdo->prepare("UPDATE camiones SET estado = 'Disponible' WHERE matricula = ?");
            $stmtCamion->execute([$inc['matricula_camion']]);
        }

        $pdo->commit();
        echo json_encode(["status" => "success", "message" => "Incidencia marcada como solucionada y camión liberado."]);
        exit;
    }

    echo json_encode(["status" => "error", "message" => "Acción no válida."]);

} catch (\Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(["status" => "error", "message" => "Error procesando solicitud: " . $e->getMessage()]);
}