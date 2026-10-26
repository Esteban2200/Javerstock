<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../includes/conexion.php";
require_once __DIR__ . "/../includes/permisos.php";

exigirPermiso('configuracion');


/*
|--------------------------------------------------------------------------
| ESCAPE
|--------------------------------------------------------------------------
*/


function e($valor)
{
    return htmlspecialchars(
        (string)($valor ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}


/*
|--------------------------------------------------------------------------
| MENSAJES
|--------------------------------------------------------------------------
*/

$msg = $_GET['msg'] ?? '';
$error = $_GET['error'] ?? '';


/*
|--------------------------------------------------------------------------
| CREAR / EDITAR / ELIMINAR
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $accion = $_POST['accion'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | CREAR
    |--------------------------------------------------------------------------
    */

    if ($accion === 'crear') {

        $nombre = trim(
            $_POST['nombre'] ?? ''
        );

        if ($nombre === '') {

            header(
                "Location: categorias.php?error=nombre_vacio"
            );

            exit;
        }


        /*
        | Evitar duplicados
        */

        $stmt = $conn->prepare(
            "SELECT id
             FROM categorias
             WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(?))
             LIMIT 1"
        );

        if (!$stmt) {

            die(
                "Error preparando validación: " .
                e($conn->error)
            );
        }

        $stmt->bind_param(
            "s",
            $nombre
        );

        $stmt->execute();

        $resultado = $stmt->get_result();

        if ($resultado->num_rows > 0) {

            $stmt->close();

            header(
                "Location: categorias.php?error=duplicada"
            );

            exit;
        }

        $stmt->close();


        /*
        | Insertar
        */

        $stmt = $conn->prepare(
            "INSERT INTO categorias (nombre)
             VALUES (?)"
        );

        if (!$stmt) {

            die(
                "Error preparando creación: " .
                e($conn->error)
            );
        }

        $stmt->bind_param(
            "s",
            $nombre
        );

        if (!$stmt->execute()) {

            $mensajeError = $stmt->error;

            $stmt->close();

            die(
                "Error creando categoría: " .
                e($mensajeError)
            );
        }

        $stmt->close();


        header(
            "Location: categorias.php?msg=creada"
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | EDITAR
    |--------------------------------------------------------------------------
    */

    if ($accion === 'editar') {

        $id = (int)(
            $_POST['id'] ?? 0
        );

        $nombre = trim(
            $_POST['nombre'] ?? ''
        );


        if ($id <= 0 || $nombre === '') {

            header(
                "Location: categorias.php?error=datos_invalidos"
            );

            exit;
        }


        /*
        | Evitar duplicado en otra categoría
        */

        $stmt = $conn->prepare(
            "SELECT id
             FROM categorias
             WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(?))
               AND id <> ?
             LIMIT 1"
        );

        if (!$stmt) {

            die(
                "Error preparando validación: " .
                e($conn->error)
            );
        }

        $stmt->bind_param(
            "si",
            $nombre,
            $id
        );

        $stmt->execute();

        $resultado = $stmt->get_result();

        if ($resultado->num_rows > 0) {

            $stmt->close();

            header(
                "Location: categorias.php?error=duplicada"
            );

            exit;
        }

        $stmt->close();


        /*
        | Actualizar
        */

        $stmt = $conn->prepare(
            "UPDATE categorias
             SET nombre = ?
             WHERE id = ?"
        );

        if (!$stmt) {

            die(
                "Error preparando actualización: " .
                e($conn->error)
            );
        }

        $stmt->bind_param(
            "si",
            $nombre,
            $id
        );

        if (!$stmt->execute()) {

            $mensajeError = $stmt->error;

            $stmt->close();

            die(
                "Error actualizando categoría: " .
                e($mensajeError)
            );
        }

        $stmt->close();


        header(
            "Location: categorias.php?msg=editada"
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | ELIMINAR
    |--------------------------------------------------------------------------
    */

    if ($accion === 'eliminar') {

        $id = (int)(
            $_POST['id'] ?? 0
        );


        if ($id <= 0) {

            header(
                "Location: categorias.php?error=datos_invalidos"
            );

            exit;
        }


        /*
        | Verificar si está siendo usada
        */

        $stmt = $conn->prepare(
            "SELECT COUNT(*) AS total
             FROM productos
             WHERE categoria = ?"
        );

        if (!$stmt) {

            die(
                "Error preparando verificación: " .
                e($conn->error)
            );
        }

        $stmt->bind_param(
            "i",
            $id
        );

        $stmt->execute();

        $resultado = $stmt->get_result();

        $fila = $resultado->fetch_assoc();

        $stmt->close();


        $productosAsociados =
            (int)($fila['total'] ?? 0);


        if ($productosAsociados > 0) {

            header(
                "Location: categorias.php" .
                "?error=en_uso" .
                "&cantidad=" .
                $productosAsociados
            );

            exit;
        }


        /*
        | Eliminar
        */

        $stmt = $conn->prepare(
            "DELETE FROM categorias
             WHERE id = ?"
        );

        if (!$stmt) {

            die(
                "Error preparando eliminación: " .
                e($conn->error)
            );
        }

        $stmt->bind_param(
            "i",
            $id
        );

        if (!$stmt->execute()) {

            $mensajeError = $stmt->error;

            $stmt->close();

            die(
                "Error eliminando categoría: " .
                e($mensajeError)
            );
        }

        $stmt->close();


        header(
            "Location: categorias.php?msg=eliminada"
        );

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| CONSULTAR CATEGORÍAS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        c.id,
        c.nombre,
        COUNT(p.id) AS total_productos
    FROM categorias c
    LEFT JOIN productos p
        ON p.categoria = c.id
    GROUP BY
        c.id,
        c.nombre
    ORDER BY
        c.nombre ASC
";


$resultado = $conn->query($sql);


if (!$resultado) {

    die(
        "Error consultando categorías: " .
        e($conn->error)
    );
}


$categorias = [];

while (
    $categoria = $resultado->fetch_assoc()
) {

    $categorias[] = $categoria;
}

?>


<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        JaverStock | Categorías
    </title>
        
<?php include("../includes/sidebar.php"); ?>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <style>

        body {
            background: #f4f7fb;
        }


        .categorias-page {
            padding-bottom: 60px;
        }


        .categorias-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 20px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }


        .categorias-header h2 {
            margin: 0;
            color: #002855;
            font-size: 32px;
            font-weight: 800;
        }


        .categorias-header p {
            margin: 7px 0 0;
            color: #64748b;
        }


        .btn-nueva-categoria {
            border-radius: 11px;
            padding: 11px 17px;
            font-weight: 700;
        }


        /*
        |--------------------------------------------------------------------------
        | TARJETA
        |--------------------------------------------------------------------------
        */

        .categorias-card {
            background: #fff;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0,0,0,.06);
        }


        .categorias-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 22px;
            flex-wrap: wrap;
        }


        .categorias-card-header h4 {
            margin: 0;
            color: #002855;
            font-weight: 800;
        }


        .contador-categorias {
            background: #eaf3fc;
            color: #0056a6;
            padding: 7px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 800;
        }


        /*
        |--------------------------------------------------------------------------
        | TABLA
        |--------------------------------------------------------------------------
        */

        .tabla-categorias {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }


        .tabla-categorias thead th {
            background: #002855;
            color: #fff;
            padding: 14px;
            font-size: 13px;
            border: none;
        }


        .tabla-categorias thead th:first-child {
            border-radius: 10px 0 0 0;
        }


        .tabla-categorias thead th:last-child {
            border-radius: 0 10px 0 0;
        }


        .tabla-categorias tbody td {
            padding: 14px;
            border-bottom: 1px solid #edf1f5;
            vertical-align: middle;
            color: #334155;
        }


        .tabla-categorias tbody tr:hover {
            background: #f8fbff;
        }


        .nombre-categoria {
            color: #002855;
            font-weight: 700;
        }


        .cantidad-productos {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f1f5f9;
            color: #475569;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }


        /*
        |--------------------------------------------------------------------------
        | ACCIONES
        |--------------------------------------------------------------------------
        */

        .acciones-categoria {
            display: flex;
            gap: 7px;
        }


        .btn-accion-categoria {
            width: 36px;
            height: 36px;
            border: none;
            border-radius: 9px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: .2s ease;
        }


        .btn-editar {
            background: #e8f2ff;
            color: #0056a6;
        }


        .btn-editar:hover {
            background: #0056a6;
            color: #fff;
            transform: translateY(-2px);
        }


        .btn-eliminar {
            background: #fee2e2;
            color: #b91c1c;
        }


        .btn-eliminar:hover {
            background: #b91c1c;
            color: #fff;
            transform: translateY(-2px);
        }


        .btn-eliminar-deshabilitado {
            background: #f1f5f9;
            color: #94a3b8;
            cursor: not-allowed;
        }


        /*
        |--------------------------------------------------------------------------
        | ALERTAS
        |--------------------------------------------------------------------------
        */

        .alert {
            border-radius: 12px;
        }


        /*
        |--------------------------------------------------------------------------
        | MODAL
        |--------------------------------------------------------------------------
        */

        .modal-content {
            border: none;
            border-radius: 18px;
            overflow: hidden;
        }


        .modal-header {
            background: #002855;
            color: white;
        }


        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }


        .form-label {
            color: #334155;
            font-weight: 700;
        }


        .form-control {
            border-radius: 10px;
            border-color: #dbe3ec;
        }


        .form-control:focus {
            border-color: #002855;
            box-shadow: 0 0 0 3px rgba(0,40,85,.10);
        }


        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */

        @media(max-width: 768px) {

            .categorias-header h2 {
                font-size: 26px;
            }

            .categorias-card {
                padding: 15px;
            }

        }

    </style>

</head>


<body>


<div class="main categorias-page">


    <?php include("../includes/header.php"); ?>


    <div class="content">


        <!-- =====================================================
             ENCABEZADO
        ====================================================== -->

        <div class="categorias-header">


            <div>

                <h2>

                    <i class="fas fa-tags"></i>

                    Categorías

                </h2>


                <p>

                    Administra las categorías utilizadas
                    en el inventario.

                </p>

            </div>


            <button
                type="button"
                class="btn btn-primary btn-nueva-categoria"
                data-bs-toggle="modal"
                data-bs-target="#modalNuevaCategoria"
            >

                <i class="fas fa-plus"></i>

                Nueva categoría

            </button>


        </div>



        <!-- =====================================================
             MENSAJES
        ====================================================== -->

        <?php if ($msg === 'creada'): ?>

            <div class="alert alert-success">

                <i class="fas fa-circle-check"></i>

                La categoría fue creada correctamente.

            </div>

        <?php endif; ?>


        <?php if ($msg === 'editada'): ?>

            <div class="alert alert-success">

                <i class="fas fa-circle-check"></i>

                La categoría fue actualizada correctamente.

            </div>

        <?php endif; ?>


        <?php if ($msg === 'eliminada'): ?>

            <div class="alert alert-success">

                <i class="fas fa-circle-check"></i>

                La categoría fue eliminada correctamente.

            </div>

        <?php endif; ?>


        <?php if ($error === 'duplicada'): ?>

            <div class="alert alert-warning">

                <i class="fas fa-triangle-exclamation"></i>

                Ya existe una categoría con ese nombre.

            </div>

        <?php endif; ?>


        <?php if ($error === 'nombre_vacio'): ?>

            <div class="alert alert-warning">

                <i class="fas fa-triangle-exclamation"></i>

                Debes escribir el nombre de la categoría.

            </div>

        <?php endif; ?>


        <?php if ($error === 'datos_invalidos'): ?>

            <div class="alert alert-danger">

                <i class="fas fa-circle-exclamation"></i>

                Los datos enviados no son válidos.

            </div>

        <?php endif; ?>


        <?php if ($error === 'en_uso'): ?>

            <div class="alert alert-danger">

                <i class="fas fa-lock"></i>

                Esta categoría no se puede eliminar porque
                tiene
                <?= (int)($_GET['cantidad'] ?? 0); ?>
                producto(s) asociado(s).

            </div>

        <?php endif; ?>


        <!-- =====================================================
             TABLA
        ====================================================== -->

        <div class="categorias-card">


            <div class="categorias-card-header">


                <h4>

                    <i class="fas fa-list"></i>

                    Categorías registradas

                </h4>


                <span class="contador-categorias">

                    <?= count($categorias); ?>

                    categorías

                </span>


            </div>



            <?php if (empty($categorias)): ?>


                <div class="text-center text-muted py-5">

                    <i
                        class="fas fa-tags"
                        style="font-size:45px;color:#94a3b8;"
                    ></i>


                    <h5 class="mt-3">

                        No hay categorías registradas.

                    </h5>


                    <p>

                        Crea la primera categoría
                        utilizando el botón
                        "Nueva categoría".

                    </p>

                </div>


            <?php else: ?>


                <div class="table-responsive">


                    <table class="table tabla-categorias">


                        <thead>

                            <tr>

                                <th>
                                    #
                                </th>

                                <th>
                                    Categoría
                                </th>

                                <th>
                                    Productos asociados
                                </th>

                                <th class="text-center">
                                    Acciones
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php foreach (
                            $categorias
                            as $categoria
                        ): ?>


                            <tr>


                                <td>

                                    <?= (int)$categoria['id']; ?>

                                </td>


                                <td>

                                    <span
                                        class="nombre-categoria"
                                    >

                                        <?= e(
                                            $categoria['nombre']
                                        ); ?>

                                    </span>

                                </td>


                                <td>

                                    <span
                                        class="cantidad-productos"
                                    >

                                        <i
                                            class="fas fa-box"
                                        ></i>

                                        <?= (int)$categoria[
                                            'total_productos'
                                        ]; ?>

                                        producto<?= (
                                            (int)$categoria[
                                                'total_productos'
                                            ] !== 1
                                        )
                                            ? 's'
                                            : ''; ?>

                                    </span>

                                </td>


                                <td>

                                    <div
                                        class="acciones-categoria
                                        justify-content-center"
                                    >


                                        <!-- EDITAR -->

                                        <button
                                            type="button"
                                            class="btn-accion-categoria btn-editar"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalEditarCategoria<?= (int)$categoria['id']; ?>"
                                            title="Editar categoría"
                                        >

                                            <i class="fas fa-pen"></i>

                                        </button>


                                        <!-- ELIMINAR -->

                                        <?php if (
                                            (int)$categoria[
                                                'total_productos'
                                            ] === 0
                                        ): ?>


                                            <form
                                                method="POST"
                                                action="categorias.php"
                                                onsubmit="return confirmarEliminacion();"
                                                style="display:inline;"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="accion"
                                                    value="eliminar"
                                                >


                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= (int)$categoria['id']; ?>"
                                                >


                                                <button
                                                    type="submit"
                                                    class="btn-accion-categoria btn-eliminar"
                                                    title="Eliminar categoría"
                                                >

                                                    <i
                                                        class="fas fa-trash"
                                                    ></i>

                                                </button>

                                            </form>


                                        <?php else: ?>


                                            <button
                                                type="button"
                                                class="btn-accion-categoria btn-eliminar-deshabilitado"
                                                title="No se puede eliminar: tiene productos asociados"
                                                disabled
                                            >

                                                <i
                                                    class="fas fa-lock"
                                                ></i>

                                            </button>


                                        <?php endif; ?>


                                    </div>

                                </td>


                            </tr>



                            <!-- =================================================
                                 MODAL EDITAR
                            ================================================== -->


                            <div
                                class="modal fade"
                                id="modalEditarCategoria<?= (int)$categoria['id']; ?>"
                                tabindex="-1"
                                aria-hidden="true"
                            >

                                <div
                                    class="modal-dialog modal-dialog-centered"
                                >

                                    <div
                                        class="modal-content"
                                    >


                                        <div
                                            class="modal-header"
                                        >

                                            <h5
                                                class="modal-title"
                                            >

                                                <i
                                                    class="fas fa-pen"
                                                ></i>

                                                Editar categoría

                                            </h5>


                                            <button
                                                type="button"
                                                class="btn-close"
                                                data-bs-dismiss="modal"
                                            ></button>

                                        </div>


                                        <form
                                            method="POST"
                                            action="categorias.php"
                                        >


                                            <input
                                                type="hidden"
                                                name="accion"
                                                value="editar"
                                            >


                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= (int)$categoria['id']; ?>"
                                            >


                                            <div
                                                class="modal-body"
                                            >

                                                <label
                                                    class="form-label"
                                                >

                                                    Nombre de la categoría

                                                </label>


                                                <input
                                                    type="text"
                                                    name="nombre"
                                                    class="form-control"
                                                    value="<?= e(
                                                        $categoria['nombre']
                                                    ); ?>"
                                                    maxlength="100"
                                                    required
                                                >

                                            </div>


                                            <div
                                                class="modal-footer"
                                            >

                                                <button
                                                    type="button"
                                                    class="btn btn-secondary"
                                                    data-bs-dismiss="modal"
                                                >

                                                    Cancelar

                                                </button>


                                                <button
                                                    type="submit"
                                                    class="btn btn-primary"
                                                >

                                                    <i
                                                        class="fas fa-save"
                                                    ></i>

                                                    Guardar cambios

                                                </button>

                                            </div>


                                        </form>

                                    </div>

                                </div>

                            </div>


                        <?php endforeach; ?>


                        </tbody>

                    </table>

                </div>


            <?php endif; ?>


        </div>


        <!-- VOLVER -->

        <a
            href="configuracion.php"
            class="btn btn-light mt-4"
        >

            <i class="fas fa-arrow-left"></i>

            Volver a Configuración

        </a>


    </div>

</div>



<!-- =========================================================
     MODAL NUEVA CATEGORÍA
========================================================= -->


<div
    class="modal fade"
    id="modalNuevaCategoria"
    tabindex="-1"
    aria-hidden="true"
>


    <div
        class="modal-dialog modal-dialog-centered"
    >


        <div
            class="modal-content"
        >


            <div
                class="modal-header"
            >

                <h5
                    class="modal-title"
                >

                    <i class="fas fa-plus"></i>

                    Nueva categoría

                </h5>


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                ></button>

            </div>


            <form
                method="POST"
                action="categorias.php"
            >

                <input
                    type="hidden"
                    name="accion"
                    value="crear"
                >


                <div
                    class="modal-body"
                >

                    <label
                        class="form-label"
                    >

                        Nombre de la categoría

                    </label>


                    <input
                        type="text"
                        name="nombre"
                        class="form-control"
                        placeholder="Ej: Cámaras, Proyectores, Sensores..."
                        maxlength="100"
                        required
                    >


                </div>


                <div
                    class="modal-footer"
                >

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal"
                    >

                        Cancelar

                    </button>


                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        <i
                            class="fas fa-save"
                        ></i>

                        Crear categoría

                    </button>

                </div>


            </form>


        </div>

    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


<script>

function confirmarEliminacion()
{
    return confirm(
        '¿Está seguro de eliminar esta categoría?\n\n' +
        'Esta acción no se puede deshacer.'
    );
}

</script>


<?php include("../includes/footer.php"); ?>

</body>

</html>