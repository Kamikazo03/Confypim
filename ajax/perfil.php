<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_once ROOT_PATH . '/app/services/PerfilService.php';
require_once ROOT_PATH . '/app/helpers/AuthHelper.php';

header('Content-Type: application/json; charset=utf-8');

exigirAdministradorAPI();

$service = new PerfilService();

try {
    $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($metodo === 'GET') {

        // Entregar la fotografía de perfil almacenada.
        if (($_GET['accion'] ?? '') === 'foto') {
            $foto = $service->obtenerFotoPerfil();

            if ($foto === null) {
                http_response_code(404);
                exit;
            }

            // Comprobar que la versión solicitada corresponde a la fotografía actual.
            $versionSolicitada = $_GET['v'] ?? '';
            $versionActual = basename($foto['ruta']);

            if (
                !is_string($versionSolicitada) ||
                $versionSolicitada === '' ||
                !hash_equals($versionActual, $versionSolicitada)
            ) {
                http_response_code(404);
                exit;
            }

            // Limpiar cualquier salida previa antes de entregar la imagen.
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            $tamanoArchivo = filesize($foto['ruta']);

            if ($tamanoArchivo === false || $tamanoArchivo <= 0) {
                http_response_code(404);
                exit;
            }

            // Eliminar las cabeceras de no-caché que puede enviar la sesión PHP.
            header_remove('Cache-Control');
            header_remove('Pragma');
            header_remove('Expires');

            header('Content-Type: ' . $foto['mime']);
            header('Content-Length: ' . $tamanoArchivo);
            header('Content-Disposition: inline');

            // La URL cambia cuando se reemplaza la foto, por lo que puede almacenarse
            // de forma privada sin reutilizar una fotografía anterior.
            header('Cache-Control: private, max-age=604800, immutable', true);
            header('X-Content-Type-Options: nosniff');

            readfile($foto['ruta']);
            exit;
        }

        // Consulta normal del perfil.
        echo json_encode([
            'success' => true,
            'data' => $service->obtenerPerfil()
        ]);

        exit;
    }

    if ($metodo !== 'POST') {
        http_response_code(405);

        echo json_encode([
            'success' => false,
            'message' => 'Método no permitido.'
        ]);

        exit;
    }

    $accion = $_POST['accion'] ?? '';

    switch ($accion) {
        case 'actualizar':
            $resultado = $service->actualizarPerfil($_POST);
            break;

        case 'avatar':
            $avatar = $_POST['avatar'] ?? null;
            $resultado = $service->seleccionarAvatar(
                $avatar !== '' ? $avatar : null
            );
            break;

        case 'subir-foto':
            if (!isset($_FILES['foto'])) {
                throw new InvalidArgumentException(
                    'Debes seleccionar una fotografía.'
                );
            }

            $resultado = $service->subirFoto($_FILES['foto']);
            break;
        
        case 'encuadre':
            $resultado = $service->actualizarEncuadre($_POST);
            break;

        case 'eliminar-foto':
            $resultado = $service->eliminarFoto();
            break;

        default:
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => 'La acción solicitada no es válida.'
            ]);

            exit;
    }

    echo json_encode(
        $resultado,
        JSON_UNESCAPED_UNICODE
    );

} catch (InvalidArgumentException $e) {
    http_response_code(422);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);

    error_log('Error en ajax/perfil.php: ' . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Ocurrió un error al procesar el perfil.'
    ], JSON_UNESCAPED_UNICODE);
}