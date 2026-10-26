<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../includes/conexion.php";
require_once "../includes/auth.php";
require_once "../includes/permisos.php";

exigirPermiso('gestionar_usuarios');


/* =========================================================
   OBTENER USUARIOS
========================================================= */

$sqlUsuarios = "
    SELECT
        id,
        nombre,
        usuario,
        correo,
        rol,
        password_temporal,
        fecha_expiracion_password_temporal
    FROM usuarios
    ORDER BY id ASC
";

$resultUsuarios = $conn->query($sqlUsuarios);

$listaUsuarios = [];

if ($resultUsuarios) {
    while ($usuario = $resultUsuarios->fetch_assoc()) {
        $listaUsuarios[] = $usuario;
    }
}


/* =========================================================
   OBTENER PERMISOS
========================================================= */

$sqlPermisos = "
    SELECT
        id,
        nombre,
        descripcion
    FROM permisos
    ORDER BY id ASC
";

$resultPermisos = $conn->query($sqlPermisos);

$listaPermisos = [];

if ($resultPermisos) {
    while ($permiso = $resultPermisos->fetch_assoc()) {
        $listaPermisos[] = $permiso;
    }
}


/* =========================================================
   OBTENER PERMISOS ASIGNADOS
========================================================= */

$permisosUsuarios = [];

$sqlAsignados = "
    SELECT
        usuario_id,
        permiso_id
    FROM usuario_permisos
";

$resultAsignados = $conn->query($sqlAsignados);

if ($resultAsignados) {

    while ($fila = $resultAsignados->fetch_assoc()) {

        $usuarioId = (int) $fila['usuario_id'];
        $permisoId = (int) $fila['permiso_id'];

        if (!isset($permisosUsuarios[$usuarioId])) {
            $permisosUsuarios[$usuarioId] = [];
        }

        $permisosUsuarios[$usuarioId][] = $permisoId;
    }
}


/* =========================================================
   GRUPOS DE PERMISOS
========================================================= */

$gruposPermisos = [

    'Dashboard' => [
        'ver_dashboard'
    ],

    'Inventario' => [
        'ver_inventario',
        'crear_producto',
        'editar_producto',
        'eliminar_producto',
        'entrada_stock',
        'salida_stock',
        'ver_movimientos'
    ],

    'Equipos' => [
        'ver_equipos',
        'crear_equipo',
        'editar_equipo',
        'eliminar_equipo'
    ],

    'Mantenimiento' => [
        'ver_mantenimiento',
        'crear_mantenimiento',
        'editar_mantenimiento',
        'eliminar_mantenimiento'
    ],

    'Ubicaciones' => [
        'ver_ubicaciones',
        'crear_ubicacion',
        'editar_ubicacion',
        'eliminar_ubicacion'
    ],

    'Puntos de Red' => [
        'ver_puntos_red',
        'crear_punto_red',
        'editar_punto_red',
        'eliminar_punto_red'
    ],

    'Reportes' => [
        'ver_reportes'
    ],

    'Usuarios' => [
        'gestionar_usuarios'
    ],

    'Perfil' => [
        'ver_perfil',
        'cambiar_password'
    ]

];


/* =========================================================
   PERMISOS POR NOMBRE
========================================================= */

$permisosPorNombre = [];

foreach ($listaPermisos as $permiso) {
    $permisosPorNombre[$permiso['nombre']] = $permiso;
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
        Gestión de Usuarios - JaverStock
    </title>


    <!-- =====================================================
         BOOTSTRAP
    ====================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- =====================================================
         CSS PRINCIPAL
    ====================================================== -->

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <style>

        /* =====================================================
           CONTENEDOR
        ===================================================== */

        .usuarios-container {
            padding: 25px;
        }

        .usuarios-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .usuarios-header h2 {
            margin: 0;
            font-weight: 600;
        }


        /* =====================================================
           TABLA
        ===================================================== */

        .table-container {
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow:
                0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .table-container table {
            margin-bottom: 0;
        }

        .table-container th {
            white-space: nowrap;
        }

        .table-container td {
            vertical-align: middle;
        }


        /* =====================================================
           MODAL DE USUARIOS
        ===================================================== */

        .usuarios-modal-dialog {
            width: 95vw !important;
            max-width: 1450px !important;
            margin: 1.5rem auto !important;
        }

        .usuarios-modal-content {
            height: 92vh;
            max-height: 92vh;
            border-radius: 16px !important;
            overflow: hidden;
        }

        .usuarios-modal-content form {
            height: 100%;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .usuarios-modal-content .modal-header {
            flex-shrink: 0;
            background: #ffffff;
            border-bottom: 1px solid #dee2e6;
            padding: 18px 25px;
        }

        .usuarios-modal-content .modal-title {
            font-weight: 700;
        }

        .usuarios-modal-body {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            padding: 25px !important;
        }

        .usuarios-modal-content .modal-footer {
            flex-shrink: 0;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
            padding: 15px 25px;
        }


        /* =====================================================
           INFORMACIÓN CONTRASEÑA
        ===================================================== */

        .password-info {
            margin-top: 6px;
            font-size: 13px;
            color: #6c757d;
        }


        /* =====================================================
           ACCIONES DE PERMISOS
        ===================================================== */

        .permisos-acciones {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }

        .permisos-acciones-grupo {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .permisos-contador-global {
            font-size: 13px;
            color: #495057;
            background: #f1f3f5;
            border-radius: 999px;
            padding: 6px 11px;
            font-weight: 600;
        }


        /* =====================================================
           CONTENEDOR DE PERMISOS
        ===================================================== */

        .permisos-contenedor {
            width: 100%;
            margin-top: 10px;
            padding: 15px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 14px;
        }

        .permisos-scroll {
            width: 100%;
        }

        .permisos-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 15px;
            width: 100%;
        }


        /* =====================================================
           GRUPOS DE PERMISOS
        ===================================================== */

        .grupo-permisos {
            border: 1px solid #dee2e6;
            border-radius: 12px;
            padding: 15px;
            background: #ffffff;
            height: 100%;
            transition:
                border-color .2s ease,
                box-shadow .2s ease;
        }

        .grupo-permisos:hover {
            border-color: #b8c7d9;
            box-shadow:
                0 3px 12px rgba(0, 0, 0, .05);
        }

        .grupo-permisos h6 {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            font-weight: 700;
            margin: 0 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #002855;
            color: #002855;
            font-size: 15px;
        }

        .grupo-contador {
            font-size: 11px;
            font-weight: 700;
            color: #6c757d;
            background: #f1f3f5;
            padding: 4px 8px;
            border-radius: 999px;
        }


        /* =====================================================
           CHECKBOX DE PERMISOS
        ===================================================== */

        .grupo-permisos .form-check {
            margin-bottom: 10px;
            padding-left: 30px;
        }

        .grupo-permisos .form-check:last-child {
            margin-bottom: 0;
        }

        .grupo-permisos .form-check-input {
            cursor: pointer;
            width: 18px;
            height: 18px;
            margin-left: -30px;
            margin-top: .15rem;
        }

        .grupo-permisos .form-check-label {
            cursor: pointer;
            font-size: 13px;
            line-height: 1.4;
        }

        .grupo-permisos .form-check-input:checked {
            background-color: #002855;
            border-color: #002855;
        }


        /* =====================================================
           SCROLL DEL MODAL
        ===================================================== */

        .usuarios-modal-body::-webkit-scrollbar {
            width: 10px;
        }

        .usuarios-modal-body::-webkit-scrollbar-track {
            background: #eef2f7;
        }

        .usuarios-modal-body::-webkit-scrollbar-thumb {
            background: #002855;
            border-radius: 10px;
        }

        .usuarios-modal-body::-webkit-scrollbar-thumb:hover {
            background: #0d6efd;
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 1200px) {

            .permisos-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

        }


        @media (max-width: 768px) {

            .usuarios-modal-dialog {
                width: 96vw !important;
                max-width: 96vw !important;
                margin: 2vh auto !important;
            }

            .usuarios-modal-content {
                height: 96vh;
                max-height: 96vh;
            }

            .usuarios-modal-body {
                padding: 20px !important;
            }

            .permisos-grid {
                grid-template-columns: 1fr;
            }

            .permisos-acciones {
                align-items: flex-start;
                flex-direction: column;
            }

        }

    </style>

</head>


<body>


<?php include "../includes/header.php"; ?>

<?php include "../includes/sidebar.php"; ?>


<main class="main-content">


    <div class="usuarios-container">


        <!-- =================================================
             ENCABEZADO
        ================================================== -->

        <div class="usuarios-header">

            <div>

                <h2>
                    Gestión de Usuarios
                </h2>

                <p class="text-muted mb-0">
                    Administra los usuarios y sus permisos.
                </p>

            </div>


            <button
                type="button"
                class="btn btn-primary"
                data-bs-toggle="modal"
                data-bs-target="#modalNuevoUsuario"
            >
                + Nuevo Usuario
            </button>

        </div>


        <!-- =================================================
             TABLA
        ================================================== -->

        <div class="table-container">

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead class="table-dark">

                        <tr>

                            <th>ID</th>

                            <th>Nombre</th>

                            <th>Usuario</th>

                            <th>Correo</th>

                            <th>Rol</th>

                            <th>Acciones</th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php if (empty($listaUsuarios)): ?>

                        <tr>

                            <td
                                colspan="6"
                                class="text-center text-muted py-4"
                            >
                                No hay usuarios registrados.
                            </td>

                        </tr>

                    <?php else: ?>


                        <?php foreach ($listaUsuarios as $usuario): ?>

                            <?php

                            $usuarioId =
                                (int) $usuario['id'];

                            $rol =
                                strtolower(
                                    trim(
                                        $usuario['rol'] ?? ''
                                    )
                                );

                            $esAdministrador =
                                (
                                    $rol === 'admin'
                                    ||
                                    $rol === 'administrador'
                                );

                            ?>


                            <tr>


                                <td>
                                    <?= $usuarioId; ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars(
                                        $usuario['nombre']
                                    ); ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars(
                                        $usuario['usuario']
                                    ); ?>
                                </td>


                                <td>
                                    <?= htmlspecialchars(
                                        $usuario['correo']
                                    ); ?>
                                </td>


                                <td>

                                    <?php if ($esAdministrador): ?>

                                        <span class="badge bg-danger">
                                            Administrador
                                        </span>

                                    <?php else: ?>

                                        <span class="badge bg-secondary">
                                            <?= htmlspecialchars(
                                                $usuario['rol']
                                            ); ?>
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-warning"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalEditarUsuario<?= $usuarioId; ?>"
                                    >
                                        Editar
                                    </button>


                                    <?php if (!$esAdministrador): ?>

                                        <form
                                            action="../actions/usuarios/eliminar_usuario.php"
                                            method="POST"
                                            style="display:inline;"
                                            onsubmit="return confirmarEliminarUsuario();"
                                        >

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= $usuarioId; ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-danger"
                                            >
                                                Eliminar
                                            </button>

                                        </form>

                                    <?php endif; ?>


                                </td>

                            </tr>


                        <?php endforeach; ?>


                    <?php endif; ?>


                    </tbody>

                </table>

            </div>

        </div>


    </div>

</main>


<!-- =========================================================
     MODAL NUEVO USUARIO
========================================================= -->

<div
    class="modal fade"
    id="modalNuevoUsuario"
    tabindex="-1"
    aria-labelledby="modalNuevoUsuarioLabel"
    aria-hidden="true"
>


    <div class="modal-dialog usuarios-modal-dialog">


        <div class="modal-content usuarios-modal-content">


            <form
                action="../actions/usuarios/crear_usuario.php"
                method="POST"
            >


                <!-- =========================================
                     HEADER
                ========================================== -->

                <div class="modal-header">

                    <h5
                        class="modal-title"
                        id="modalNuevoUsuarioLabel"
                    >
                        Nuevo Usuario
                    </h5>


                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Cerrar"
                    ></button>

                </div>


                <!-- =========================================
                     BODY
                ========================================== -->

                <div class="modal-body usuarios-modal-body">


                    <h5 class="mb-3">
                        Datos del usuario
                    </h5>


                    <div class="row">


                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Nombre
                            </label>

                            <input
                                type="text"
                                name="nombre"
                                class="form-control"
                                required
                            >

                        </div>


                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Usuario
                            </label>

                            <input
                                type="text"
                                name="usuario"
                                class="form-control"
                                required
                            >

                        </div>


                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Correo
                            </label>

                            <input
                                type="email"
                                name="correo"
                                class="form-control"
                                required
                            >

                        </div>


                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Rol
                            </label>

                            <select
                                name="rol"
                                class="form-select"
                                required
                            >

                                <option value="empleado">
                                    Empleado
                                </option>

                                <option value="administrador">
                                    Administrador
                                </option>

                            </select>

                        </div>


                    </div>


                    <hr>


                    <h5 class="mb-3">
                        Contraseña
                    </h5>


                    <div class="mb-3">

                        <label class="form-label">
                            Contraseña temporal
                        </label>

                        <input
                            type="password"
                            name="password"
                            class="form-control"
                            minlength="8"
                            required
                        >

                        <div class="password-info">
                            La contraseña temporal tendrá una
                            vigencia de 24 horas.
                        </div>

                    </div>


                    <hr>


                    <h5 class="mb-3">
                        Permisos del usuario
                    </h5>


                    <!-- =====================================
                         ACCIONES PERMISOS
                    ====================================== -->

                    <div class="permisos-acciones">


                        <div class="permisos-acciones-grupo">

                            <button
                                type="button"
                                class="btn btn-sm btn-outline-primary"
                                onclick="seleccionarTodosNuevo()"
                            >
                                Seleccionar todos
                            </button>


                            <button
                                type="button"
                                class="btn btn-sm btn-outline-secondary"
                                onclick="quitarTodosNuevo()"
                            >
                                Quitar todos
                            </button>

                        </div>


                        <span
                            class="permisos-contador-global"
                            id="contadorPermisosNuevo"
                        >
                            0 permisos seleccionados
                        </span>


                    </div>


                    <!-- =====================================
                         CONTENEDOR PERMISOS
                    ====================================== -->

                    <div class="permisos-contenedor">


                        <div class="permisos-scroll">


                            <div class="permisos-grid">


                                <?php foreach (
                                    $gruposPermisos
                                    as $grupoNombre => $nombresPermisos
                                ): ?>


                                    <?php

                                    $totalGrupo = 0;

                                    foreach ($nombresPermisos as $nombrePermiso) {

                                        if (
                                            isset(
                                                $permisosPorNombre[$nombrePermiso]
                                            )
                                        ) {
                                            $totalGrupo++;
                                        }

                                    }

                                    ?>


                                    <div class="grupo-permisos">


                                        <h6>

                                            <?= htmlspecialchars(
                                                $grupoNombre
                                            ); ?>

                                            <span class="grupo-contador">
                                                0/<?= $totalGrupo; ?>
                                            </span>

                                        </h6>


                                        <?php foreach (
                                            $nombresPermisos
                                            as $nombrePermiso
                                        ): ?>


                                            <?php if (
                                                isset(
                                                    $permisosPorNombre[$nombrePermiso]
                                                )
                                            ): ?>


                                                <?php

                                                $permiso =
                                                    $permisosPorNombre[
                                                        $nombrePermiso
                                                    ];

                                                $permisoId =
                                                    (int) $permiso['id'];

                                                ?>


                                                <div class="form-check">


                                                    <input
                                                        class="form-check-input permiso-nuevo"
                                                        type="checkbox"
                                                        data-permiso-checkbox="nuevo"
                                                        name="permisos[]"
                                                        value="<?= $permisoId; ?>"
                                                        id="nuevo-permiso-<?= $permisoId; ?>"
                                                    >


                                                    <label
                                                        class="form-check-label"
                                                        for="nuevo-permiso-<?= $permisoId; ?>"
                                                    >

                                                        <?= htmlspecialchars(
                                                            $permiso['descripcion']
                                                                ?: $permiso['nombre']
                                                        ); ?>

                                                    </label>


                                                </div>


                                            <?php endif; ?>


                                        <?php endforeach; ?>


                                    </div>


                                <?php endforeach; ?>


                            </div>


                        </div>


                    </div>


                </div>


                <!-- =========================================
                     FOOTER
                ========================================== -->

                <div class="modal-footer usuarios-modal-footer">


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
                        Guardar Usuario
                    </button>


                </div>


            </form>


        </div>

    </div>

</div>


<!-- =========================================================
     MODALES EDITAR USUARIO
========================================================= -->

<?php foreach ($listaUsuarios as $usuario): ?>


    <?php

    $usuarioId =
        (int) $usuario['id'];

    $rol =
        strtolower(
            trim(
                $usuario['rol'] ?? ''
            )
        );

    $permisosAsignados =
        $permisosUsuarios[$usuarioId] ?? [];

    ?>


    <div
        class="modal fade"
        id="modalEditarUsuario<?= $usuarioId; ?>"
        tabindex="-1"
        aria-labelledby="modalEditarUsuarioLabel<?= $usuarioId; ?>"
        aria-hidden="true"
    >


        <div class="modal-dialog usuarios-modal-dialog">


            <div class="modal-content usuarios-modal-content">


                <form
                    action="../actions/usuarios/actualizar_usuario.php"
                    method="POST"
                >


                    <input
                        type="hidden"
                        name="id"
                        value="<?= $usuarioId; ?>"
                    >


                    <!-- =====================================
                         HEADER
                    ====================================== -->

                    <div class="modal-header">


                        <h5
                            class="modal-title"
                            id="modalEditarUsuarioLabel<?= $usuarioId; ?>"
                        >

                            Editar usuario:

                            <?= htmlspecialchars(
                                $usuario['nombre']
                            ); ?>

                        </h5>


                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Cerrar"
                        ></button>


                    </div>


                    <!-- =====================================
                         BODY
                    ====================================== -->

                    <div class="modal-body usuarios-modal-body">


                        <h5 class="mb-3">
                            Datos del usuario
                        </h5>


                        <div class="row">


                            <div class="col-md-6 mb-3">


                                <label class="form-label">
                                    Nombre
                                </label>


                                <input
                                    type="text"
                                    name="nombre"
                                    class="form-control"
                                    value="<?= htmlspecialchars(
                                        $usuario['nombre']
                                    ); ?>"
                                    required
                                >


                            </div>


                            <div class="col-md-6 mb-3">


                                <label class="form-label">
                                    Usuario
                                </label>


                                <input
                                    type="text"
                                    name="usuario"
                                    class="form-control"
                                    value="<?= htmlspecialchars(
                                        $usuario['usuario']
                                    ); ?>"
                                    required
                                >


                            </div>


                            <div class="col-md-6 mb-3">


                                <label class="form-label">
                                    Correo
                                </label>


                                <input
                                    type="email"
                                    name="correo"
                                    class="form-control"
                                    value="<?= htmlspecialchars(
                                        $usuario['correo']
                                    ); ?>"
                                    required
                                >


                            </div>


                            <div class="col-md-6 mb-3">


                                <label class="form-label">
                                    Rol
                                </label>


                                <select
                                    name="rol"
                                    class="form-select"
                                    required
                                >


                                    <option
                                        value="empleado"
                                        <?= $rol === 'empleado'
                                            ? 'selected'
                                            : ''; ?>
                                    >
                                        Empleado
                                    </option>


                                    <option
                                        value="administrador"
                                        <?= (
                                            $rol === 'admin'
                                            ||
                                            $rol === 'administrador'
                                        )
                                            ? 'selected'
                                            : ''; ?>
                                    >
                                        Administrador
                                    </option>


                                </select>


                            </div>


                        </div>


                        <hr>


                        <h5 class="mb-3">
                            Contraseña
                        </h5>


                        <div class="mb-3">


                            <label class="form-label">
                                Nueva contraseña
                            </label>


                            <input
                                type="password"
                                name="password"
                                class="form-control"
                                minlength="8"
                                placeholder="Dejar vacío para no cambiar"
                            >


                            <div class="password-info">

                                Si se cambia la contraseña de otro
                                usuario, se generará una contraseña
                                temporal con vigencia de 24 horas.

                            </div>


                        </div>


                        <hr>


                        <h5 class="mb-3">
                            Permisos del usuario
                        </h5>


                        <!-- =====================================
                             ACCIONES PERMISOS
                        ====================================== -->

                        <div class="permisos-acciones">


                            <div class="permisos-acciones-grupo">


                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-primary"
                                    onclick="seleccionarTodosUsuario(<?= $usuarioId; ?>)"
                                >
                                    Seleccionar todos
                                </button>


                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-secondary"
                                    onclick="quitarTodosUsuario(<?= $usuarioId; ?>)"
                                >
                                    Quitar todos
                                </button>


                            </div>


                            <span
                                class="permisos-contador-global"
                                id="contadorPermisos<?= $usuarioId; ?>"
                            >
                                0 permisos seleccionados
                            </span>


                        </div>


                        <!-- =====================================
                             CONTENEDOR PERMISOS
                        ====================================== -->

                        <div class="permisos-contenedor">


                            <div class="permisos-scroll">


                                <div class="permisos-grid">


                                    <?php foreach (
                                        $gruposPermisos
                                        as $grupoNombre => $nombresPermisos
                                    ): ?>


                                        <?php

                                        $totalGrupo = 0;
                                        $seleccionadosGrupo = 0;

                                        foreach ($nombresPermisos as $nombrePermiso) {

                                            if (
                                                isset(
                                                    $permisosPorNombre[$nombrePermiso]
                                                )
                                            ) {

                                                $totalGrupo++;

                                                $permisoTemp =
                                                    $permisosPorNombre[
                                                        $nombrePermiso
                                                    ];

                                                if (
                                                    in_array(
                                                        (int) $permisoTemp['id'],
                                                        $permisosAsignados
                                                    )
                                                ) {
                                                    $seleccionadosGrupo++;
                                                }
                                            }

                                        }

                                        ?>


                                        <div class="grupo-permisos">


                                            <h6>

                                                <?= htmlspecialchars(
                                                    $grupoNombre
                                                ); ?>


                                                <span class="grupo-contador">

                                                    <?= $seleccionadosGrupo; ?>/<?= $totalGrupo; ?>

                                                </span>


                                            </h6>


                                            <?php foreach (
                                                $nombresPermisos
                                                as $nombrePermiso
                                            ): ?>


                                                <?php if (
                                                    isset(
                                                        $permisosPorNombre[$nombrePermiso]
                                                    )
                                                ): ?>


                                                    <?php

                                                    $permiso =
                                                        $permisosPorNombre[
                                                            $nombrePermiso
                                                        ];

                                                    $permisoId =
                                                        (int) $permiso['id'];

                                                    $usuarioTienePermiso =
                                                        in_array(
                                                            $permisoId,
                                                            $permisosAsignados
                                                        );

                                                    ?>


                                                    <div class="form-check">


                                                        <input
                                                            class="form-check-input permiso-checkbox-<?= $usuarioId; ?>"
                                                            type="checkbox"
                                                            name="permisos[]"
                                                            value="<?= $permisoId; ?>"
                                                            id="permiso-<?= $usuarioId; ?>-<?= $permisoId; ?>"
                                                            <?= $usuarioTienePermiso
                                                                ? 'checked'
                                                                : ''; ?>
                                                        >


                                                        <label
                                                            class="form-check-label"
                                                            for="permiso-<?= $usuarioId; ?>-<?= $permisoId; ?>"
                                                        >

                                                            <?= htmlspecialchars(
                                                                $permiso['descripcion']
                                                                    ?: $permiso['nombre']
                                                            ); ?>

                                                        </label>


                                                    </div>


                                                <?php endif; ?>


                                            <?php endforeach; ?>


                                        </div>


                                    <?php endforeach; ?>


                                </div>


                            </div>


                        </div>


                    </div>


                    <!-- =====================================
                         FOOTER
                    ====================================== -->

                    <div class="modal-footer usuarios-modal-footer">


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
                            Guardar Cambios
                        </button>


                    </div>


                </form>


            </div>


        </div>


    </div>


<?php endforeach; ?>


<!-- =========================================================
     BOOTSTRAP JS
========================================================= -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


<script>

/* =========================================================
   CONFIRMAR ELIMINACIÓN
========================================================= */

function confirmarEliminarUsuario() {

    return confirm(
        "¿Está seguro de que desea eliminar este usuario?"
    );

}


/* =========================================================
   FUNCIONES DE CONTADORES
========================================================= */

document.addEventListener(
    'DOMContentLoaded',
    function () {


        /* =================================================
           CONTADOR GLOBAL
        ================================================== */

        function actualizarContadorGlobal(
            selector,
            contadorId
        ) {

            const checks =
                document.querySelectorAll(selector);

            const seleccionados =
                Array.from(checks).filter(
                    function (check) {
                        return check.checked;
                    }
                ).length;

            const contador =
                document.getElementById(contadorId);

            if (contador) {

                contador.textContent =
                    seleccionados +
                    (
                        seleccionados === 1
                            ? ' permiso seleccionado'
                            : ' permisos seleccionados'
                    );

            }

        }


        /* =================================================
           CONTADORES POR GRUPO
        ================================================== */

        function actualizarContadoresPorGrupo(modal) {

            if (!modal) {
                return;
            }


            modal.querySelectorAll(
                '.grupo-permisos'
            ).forEach(
                function (grupo) {


                    const checks =
                        grupo.querySelectorAll(
                            'input[type="checkbox"]'
                        );


                    const seleccionados =
                        Array.from(checks).filter(
                            function (check) {
                                return check.checked;
                            }
                        ).length;


                    const contador =
                        grupo.querySelector(
                            '.grupo-contador'
                        );


                    if (contador) {

                        contador.textContent =
                            seleccionados +
                            '/' +
                            checks.length;

                    }

                }
            );

        }


        /* =================================================
           ACTUALIZAR NUEVO USUARIO
        ================================================== */

        function actualizarNuevo() {

            actualizarContadorGlobal(
                '.permiso-nuevo',
                'contadorPermisosNuevo'
            );


            actualizarContadoresPorGrupo(
                document.getElementById(
                    'modalNuevoUsuario'
                )
            );

        }


        /* =================================================
           ACTUALIZAR USUARIO
        ================================================== */

        function actualizarUsuario(usuarioId) {

            actualizarContadorGlobal(
                '.permiso-checkbox-' + usuarioId,
                'contadorPermisos' + usuarioId
            );


            actualizarContadoresPorGrupo(
                document.getElementById(
                    'modalEditarUsuario' + usuarioId
                )
            );

        }


        /* =================================================
           SELECCIONAR TODOS - NUEVO
        ================================================== */

        window.seleccionarTodosNuevo =
            function () {

                document.querySelectorAll(
                    '.permiso-nuevo'
                ).forEach(
                    function (check) {

                        check.checked = true;

                    }
                );


                actualizarNuevo();

            };


        /* =================================================
           QUITAR TODOS - NUEVO
        ================================================== */

        window.quitarTodosNuevo =
            function () {

                document.querySelectorAll(
                    '.permiso-nuevo'
                ).forEach(
                    function (check) {

                        check.checked = false;

                    }
                );


                actualizarNuevo();

            };


        /* =================================================
           SELECCIONAR TODOS - USUARIO
        ================================================== */

        window.seleccionarTodosUsuario =
            function (usuarioId) {

                document.querySelectorAll(
                    '.permiso-checkbox-' + usuarioId
                ).forEach(
                    function (check) {

                        check.checked = true;

                    }
                );


                actualizarUsuario(usuarioId);

            };


        /* =================================================
           QUITAR TODOS - USUARIO
        ================================================== */

        window.quitarTodosUsuario =
            function (usuarioId) {

                document.querySelectorAll(
                    '.permiso-checkbox-' + usuarioId
                ).forEach(
                    function (check) {

                        check.checked = false;

                    }
                );


                actualizarUsuario(usuarioId);

            };


        /* =================================================
           CAMBIOS EN CHECKBOX
        ================================================== */

        document.addEventListener(
            'change',
            function (event) {


                if (
                    event.target.classList.contains(
                        'permiso-nuevo'
                    )
                ) {

                    actualizarNuevo();

                    return;

                }


                const claseUsuario =
                    Array.from(
                        event.target.classList
                    ).find(
                        function (clase) {

                            return clase.indexOf(
                                'permiso-checkbox-'
                            ) === 0;

                        }
                    );


                if (claseUsuario) {

                    const usuarioId =
                        claseUsuario.replace(
                            'permiso-checkbox-',
                            ''
                        );


                    actualizarUsuario(
                        usuarioId
                    );

                }

            }
        );


        /* =================================================
           INICIALIZAR NUEVO
        ================================================== */

        actualizarNuevo();


        /* =================================================
           INICIALIZAR MODALES EDITAR
        ================================================== */

        document.querySelectorAll(
            '[id^="modalEditarUsuario"]'
        ).forEach(
            function (modal) {


                const match =
                    modal.id.match(
                        /^modalEditarUsuario(\d+)$/
                    );


                if (match) {

                    actualizarUsuario(
                        match[1]
                    );

                }


                modal.addEventListener(
                    'shown.bs.modal',
                    function () {

                        if (match) {

                            actualizarUsuario(
                                match[1]
                            );

                        }

                    }
                );

            }
        );


        /* =================================================
           EVENTO MODAL NUEVO
        ================================================== */

        const modalNuevo =
            document.getElementById(
                'modalNuevoUsuario'
            );


        if (modalNuevo) {

            modalNuevo.addEventListener(
                'shown.bs.modal',
                function () {

                    actualizarNuevo();

                }
            );

        }


    }
);

</script>


</body>

</html>