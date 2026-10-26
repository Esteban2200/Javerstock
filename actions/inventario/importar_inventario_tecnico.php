<?php
/**
 * Importación del Inventario Técnico desde Excel.
 *
 * Optimización principal:
 * - Se prepara el INSERT una sola vez.
 * - Se reutiliza el mismo statement para todas las filas.
 * - Se utiliza una transacción para evitar dejar el inventario incompleto
 *   si ocurre un error durante la importación.
 * - Se lee cada hoja a memoria una sola vez con toArray().
 */

@set_time_limit(600);

require_once '../../includes/auth.php';
require_once '../../includes/conexion.php';
require_once '../../includes/permisos.php';
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

exigirPermiso('ver_inventario');

function redirigirImportacion($parametros = [])
{
    $url = '../../pages/inventario_tecnico.php';

    if (!empty($parametros)) {
        $url .= '?' . http_build_query($parametros);
    }

    header('Location: ' . $url);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigirImportacion(['error' => 'Solicitud no válida.']);
}

if (!isset($_FILES['archivo_excel'])) {
    redirigirImportacion(['error' => 'No se recibió ningún archivo.']);
}

$archivo = $_FILES['archivo_excel'];

if (!isset($archivo['error']) || $archivo['error'] !== UPLOAD_ERR_OK) {
    $erroresUpload = [
        UPLOAD_ERR_INI_SIZE   => 'El archivo supera el tamaño permitido por PHP.',
        UPLOAD_ERR_FORM_SIZE  => 'El archivo supera el tamaño permitido por el formulario.',
        UPLOAD_ERR_PARTIAL    => 'El archivo se cargó parcialmente.',
        UPLOAD_ERR_NO_FILE    => 'No se seleccionó ningún archivo.',
        UPLOAD_ERR_NO_TMP_DIR => 'No existe el directorio temporal de PHP.',
        UPLOAD_ERR_CANT_WRITE => 'No fue posible guardar el archivo temporal.',
        UPLOAD_ERR_EXTENSION  => 'Una extensión de PHP detuvo la carga del archivo.'
    ];

    redirigirImportacion([
        'error' => $erroresUpload[$archivo['error']] ?? 'Error desconocido al cargar el archivo.'
    ]);
}

$extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

if (!in_array($extension, ['xlsx', 'xls'], true)) {
    redirigirImportacion([
        'error' => 'Formato no permitido. Selecciona un archivo XLSX o XLS.'
    ]);
}

if (!isset($archivo['tmp_name']) || !is_uploaded_file($archivo['tmp_name'])) {
    redirigirImportacion(['error' => 'El archivo recibido no es válido.']);
}

$spreadsheet = null;
$stmt = null;
$registrosImportados = 0;

try {
    /* =========================================================
       CARGA OPTIMIZADA DEL EXCEL
    ========================================================= */
    $reader = IOFactory::createReaderForFile($archivo['tmp_name']);
    $reader->setReadDataOnly(true);
    $spreadsheet = $reader->load($archivo['tmp_name']);

    /* =========================================================
       STATEMENT DE INSERCIÓN
       Se prepara UNA sola vez y se reutiliza.
    ========================================================= */
    $stmt = $conn->prepare("
        INSERT INTO inventario_tecnico (
            sistema,
            categoria,
            hoja_origen,
            item,
            edificio,
            dispositivo,
            descripcion,
            tipo,
            codigo_activo,
            ubicacion,
            direccion,
            ip,
            mac,
            referencia,
            cantidad,
            detalles,
            estado,
            datos_extra
        )
        VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
    ");

    if (!$stmt) {
        throw new RuntimeException(
            'Error preparando la inserción: ' . $conn->error
        );
    }

    /* =========================================================
       TRANSACCIÓN
    ========================================================= */
    $conn->begin_transaction();

    /*
     * DELETE se utiliza en lugar de TRUNCATE para que la transacción
     * pueda hacer rollback si el Excel presenta un error.
     */
    if (!$conn->query("DELETE FROM inventario_tecnico")) {
        throw new RuntimeException(
            'No fue posible limpiar el inventario técnico: ' . $conn->error
        );
    }

    /* =========================================================
       RECORRER HOJAS
    ========================================================= */
    foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
        $nombreHoja = trim($sheet->getTitle());

        /*
         * Cargamos la hoja una sola vez. Esto es mucho más rápido que
         * ejecutar rangeToArray() para cada fila.
         */
        $filas = $sheet->toArray(null, true, true, true);

        if (empty($filas)) {
            continue;
        }

        /* =====================================================
           BUSCAR ENCABEZADOS
        ====================================================== */
        $filaEncabezados = null;

        $limiteBusqueda = min(count($filas), 10);

        for ($indiceFila = 1; $indiceFila <= $limiteBusqueda; $indiceFila++) {
            if (!isset($filas[$indiceFila])) {
                continue;
            }

            $fila = $filas[$indiceFila];
            $valores = [];

            foreach ($fila as $valor) {
                $valores[] = trim((string)$valor);
            }

            if (empty($valores)) {
                continue;
            }

            $texto = strtoupper(implode(' ', $valores));

            if (
                strpos($texto, 'ITEM') !== false ||
                strpos($texto, 'DISPOSITIVO') !== false ||
                strpos($texto, 'CODIGO') !== false ||
                strpos($texto, 'EQUIPOS') !== false ||
                strpos($texto, 'ID ACTIVO') !== false
            ) {
                $filaEncabezados = $indiceFila;
                break;
            }
        }

        if (!$filaEncabezados) {
            $filaEncabezados = 1;
        }

        if (!isset($filas[$filaEncabezados])) {
            continue;
        }

        /* =====================================================
           MAPEAR ENCABEZADOS
        ====================================================== */
        $mapaHeaders = [];

        foreach ($filas[$filaEncabezados] as $columna => $header) {
            $header = trim((string)$header);

            if ($header === '') {
                continue;
            }

            $mapaHeaders[$columna] = normalizarTexto($header);
        }

        $sistema = determinarSistema($nombreHoja);
        $categoria = determinarCategoria($nombreHoja);

        /* =====================================================
           RECORRER REGISTROS
        ====================================================== */
        foreach ($filas as $numeroFila => $datosFila) {
            if ($numeroFila <= $filaEncabezados) {
                continue;
            }

            if (filaVacia($datosFila)) {
                continue;
            }

            $datos = [];

            foreach ($datosFila as $columna => $valor) {
                if (!isset($mapaHeaders[$columna])) {
                    continue;
                }

                $header = $mapaHeaders[$columna];

                if ($header === '') {
                    continue;
                }

                $valor = is_scalar($valor) || $valor === null
                    ? trim((string)$valor)
                    : '';

                $datos[$header] = $valor;
            }

            $datosExtra = json_encode(
                $datos,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );

            if ($datosExtra === false) {
                $datosExtra = '{}';
            }

            $item = obtenerDato($datos, [
                'item',
                'id activo / inventario',
                'id activo inventario'
            ]);

            $edificio = obtenerDato($datos, [
                'edificio'
            ]);

            if (!$edificio) {
                $edificio = determinarEdificioPorHoja($nombreHoja);
            }

            $dispositivo = obtenerDato($datos, [
                'dispositivo',
                'equipos',
                'nombre',
                'item'
            ]);

            $descripcion = obtenerDato($datos, [
                'descripcion',
                'detalle'
            ]);

            $tipo = obtenerDato($datos, [
                'tipo',
                'tipo dispositivo'
            ]);

            $codigoActivo = obtenerDato($datos, [
                'codigo activo',
                'codigo'
            ]);

            $ubicacion = obtenerDato($datos, [
                'ubicacion'
            ]);

            $direccion = obtenerDato($datos, [
                'direcion',
                'direccion'
            ]);

            $ip = obtenerDato($datos, [
                'ip'
            ]);

            $mac = obtenerDato($datos, [
                'mac'
            ]);

            $referencia = obtenerDato($datos, [
                'referencia',
                'ref',
                'modelo/ref',
                'modelo/ref.'
            ]);

            $cantidad = obtenerDato($datos, [
                'cantidad'
            ]);

            $detalles = obtenerDato($datos, [
                'detalles',
                'detalle'
            ]);

            $estado = 'Registrado';

            /* Evitamos insertar filas sin información útil. */
            if (
                !$dispositivo &&
                !$descripcion &&
                !$codigoActivo &&
                !$ubicacion &&
                !$item
            ) {
                continue;
            }

            $cantidadNumerica = null;

            if ($cantidad !== null && $cantidad !== '' && is_numeric($cantidad)) {
                $cantidadNumerica = (int)$cantidad;
            }

            /*
             * IMPORTANTE:
             * bind_param ocurre después de calcular todos los valores.
             */
            $stmt->bind_param(
                'ssssssssssssssisss',
                $sistema,
                $categoria,
                $nombreHoja,
                $item,
                $edificio,
                $dispositivo,
                $descripcion,
                $tipo,
                $codigoActivo,
                $ubicacion,
                $direccion,
                $ip,
                $mac,
                $referencia,
                $cantidadNumerica,
                $detalles,
                $estado,
                $datosExtra
            );

            if (!$stmt->execute()) {
                throw new RuntimeException(
                    'Error insertando la fila ' . $numeroFila .
                    ' de la hoja "' . $nombreHoja . '": ' . $stmt->error
                );
            }

            $registrosImportados++;
        }
    }

    /* =========================================================
       CONFIRMAR
    ========================================================= */
    $conn->commit();

    if ($stmt) {
        $stmt->close();
        $stmt = null;
    }

    $spreadsheet->disconnectWorksheets();
    $spreadsheet = null;

    redirigirImportacion([
        'importado' => '1',
        'registros' => $registrosImportados
    ]);

} catch (Throwable $e) {
    /* =========================================================
       REVERTIR SI OCURRIÓ UN ERROR
    ========================================================= */
    try {
        if ($conn->errno === 0 || $conn->ping()) {
            $conn->rollback();
        }
    } catch (Throwable $rollbackError) {
        // No reemplazamos el error original.
    }

    if ($stmt) {
        try {
            $stmt->close();
        } catch (Throwable $closeError) {
            // Ignorar error de cierre.
        }
    }

    if ($spreadsheet) {
        try {
            $spreadsheet->disconnectWorksheets();
        } catch (Throwable $closeSpreadsheetError) {
            // Ignorar error de liberación.
        }
    }

    redirigirImportacion([
        'error' => $e->getMessage()
    ]);
}

/* =============================================================
   FUNCIONES
============================================================= */

function normalizarTexto($texto)
{
    $texto = trim((string)$texto);

    $texto = mb_strtolower($texto, 'UTF-8');

    $buscar = [
        'á', 'é', 'í', 'ó', 'ú', 'ñ'
    ];

    $reemplazar = [
        'a', 'e', 'i', 'o', 'u', 'n'
    ];

    $texto = str_replace($buscar, $reemplazar, $texto);
    $texto = preg_replace('/\s+/', ' ', $texto);

    return $texto;
}

function obtenerDato($datos, $posibles)
{
    foreach ($posibles as $campo) {
        $campo = normalizarTexto($campo);

        if (
            isset($datos[$campo]) &&
            trim((string)$datos[$campo]) !== ''
        ) {
            return trim((string)$datos[$campo]);
        }
    }

    return null;
}

function filaVacia($fila)
{
    foreach ($fila as $valor) {
        if (
            $valor !== null &&
            trim((string)$valor) !== ''
        ) {
            return false;
        }
    }

    return true;
}

function determinarSistema($hoja)
{
    $h = normalizarTexto($hoja);

    if (
        strpos($h, 'controladores acceso') !== false ||
        strpos($h, 'torniquetes') !== false ||
        strpos($h, 'perifericos ca') !== false
    ) {
        return 'Control de acceso';
    }

    if (strpos($h, 'cctv') !== false) {
        return 'CCTV';
    }

    if (
        strpos($h, 'incendio') !== false ||
        strpos($h, 'deteccion') !== false
    ) {
        return 'Incendio y detección';
    }

    if (strpos($h, 'intrusion') !== false) {
        return 'Intrusión y control';
    }

    return 'Otros';
}

function determinarCategoria($hoja)
{
    $h = normalizarTexto($hoja);

    if (strpos($h, 'controladores') !== false) {
        return 'Controladores';
    }

    if (strpos($h, 'torniquetes') !== false) {
        return 'Torniquetes';
    }

    if (strpos($h, 'perifericos') !== false) {
        return 'Periféricos';
    }

    if (strpos($h, 'cctv') !== false) {
        return 'CCTV';
    }

    if (
        strpos($h, 'incendio') !== false ||
        strpos($h, 'deteccion') !== false
    ) {
        return 'Incendio';
    }

    if (strpos($h, 'intrusion') !== false) {
        return 'Intrusión';
    }

    return 'Inventario por edificio';
}

function determinarEdificioPorHoja($hoja)
{
    $mapa = [
        'Artes 1 y 2' => 'Artes 1 y 2',
        'Datacenter' => 'Datacenter',
        'Bioterio' => 'Bioterio',
        'Maldonado' => 'Maldonado',
        'Hoyos' => 'Hoyos',
        'Atico' => 'Ático',
        'Extension' => 'Extensión',
        'Ingenieria' => 'Ingeniería',
        'Biblioteca' => 'Biblioteca'
    ];

    foreach ($mapa as $clave => $edificio) {
        if (normalizarTexto($hoja) === normalizarTexto($clave)) {
            return $edificio;
        }
    }

    return null;
}
