<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../includes/conexion.php";
require_once "../includes/permisos.php";


/*
|--------------------------------------------------------------------------
| PROTEGER LA PÁGINA
|--------------------------------------------------------------------------
*/

exigirPermiso('gestionar_usuarios');


/*
|--------------------------------------------------------------------------
| OBTENER USUARIOS
|--------------------------------------------------------------------------
*/

$sqlUsuarios = mysqli_query(
    $conn,
    "SELECT id, nombre, usuario, rol
     FROM usuarios
     ORDER BY id ASC"
);

if (!$sqlUsuarios) {
    die(
        "Error al consultar usuarios: " .
        mysqli_error($conn)
    );
}


/*
|--------------------------------------------------------------------------
| OBTENER PERMISOS
|--------------------------------------------------------------------------
*/

$sqlPermisos = mysqli_query(
    $conn,
    "SELECT id, nombre, descripcion
     FROM permisos
     ORDER BY id ASC"
);

if (!$sqlPermisos) {
    die(
        "Error al consultar permisos: " .
        mysqli_error($conn)
    );
}


/*
|--------------------------------------------------------------------------
| GUARDAR PERMISOS EN UN ARRAY
|--------------------------------------------------------------------------
*/

$listaPermisos = [];

while ($permiso = mysqli_fetch_assoc($sqlPermisos)) {

    $listaPermisos[] = $permiso;

}


/*
|--------------------------------------------------------------------------
| OBTENER PERMISOS DE CADA USUARIO
|--------------------------------------------------------------------------
*/

$permisosUsuarios = [];


$sqlUsuarioPermisos = mysqli_query(
    $conn,
    "SELECT usuario_id, permiso_id
     FROM usuario_permisos"
);


if ($sqlUsuarioPermisos) {

    while ($fila = mysqli_fetch_assoc($sqlUsuarioPermisos)) {

        $usuario_id = (int) $fila['usuario_id'];

        $permiso_id = (int) $fila['permiso_id'];


        if (!isset($permisosUsuarios[$usuario_id])) {

            $permisosUsuarios[$usuario_id] = [];

        }


        $permisosUsuarios[$usuario_id][] = $permiso_id;

    }

}


/*
|--------------------------------------------------------------------------
| SIDEBAR
|--------------------------------------------------------------------------
*/

include("../includes/sidebar.php");

?>



<style>
/* =========================================================
   GESTIÓN DE USUARIOS - JAVERSTOCK
   ========================================================= */
.usuarios-page .inventory-header{display:flex;justify-content:space-between;align-items:flex-end;gap:24px;margin-bottom:22px;flex-wrap:wrap}
.usuarios-page .inventory-title h2{margin:0;color:#002855;font-size:32px;font-weight:700}
.usuarios-page .inventory-title p{margin:6px 0 0;color:#64748b;font-size:15px}
.usuarios-page .inventory-top-actions{display:flex;justify-content:flex-end;align-items:center}
.usuarios-page .inventory-top-actions .btn{min-height:46px;padding:0 20px;border-radius:11px;font-weight:700;display:inline-flex;align-items:center;gap:8px}
.usuarios-page .table-box{width:100%;background:#fff;border-radius:18px;overflow:hidden;box-shadow:0 8px 25px rgba(0,0,0,.08)}
.usuarios-page .table-box table{width:100%;margin:0;border-collapse:separate;border-spacing:0}
.usuarios-page .table-box thead th{background:#002855;color:#fff;border:0;padding:14px 16px;font-size:13px;font-weight:700;white-space:nowrap}
.usuarios-page .table-box thead th:first-child{border-top-left-radius:18px}
.usuarios-page .table-box thead th:last-child{border-top-right-radius:18px}
.usuarios-page .table-box tbody td{padding:14px 16px;border-bottom:1px solid #edf0f4;vertical-align:middle}
.usuarios-page .table-box tbody tr:last-child td{border-bottom:0}
.usuarios-page .table-box tbody tr:hover{background:#f8fbff}
.usuarios-page .acciones-usuarios{white-space:nowrap;text-align:center}
.usuarios-page .acciones-usuarios .btn{width:38px;height:38px;padding:0;margin:2px;border-radius:9px;display:inline-flex;align-items:center;justify-content:center}
.usuarios-page .usuario-id{color:#64748b;font-weight:700}
.usuarios-page .usuario-nombre{color:#0f172a;font-weight:600}
.usuarios-page .alert{border-radius:12px;margin-bottom:18px}
.usuarios-page .modal-content{border:0;border-radius:18px;overflow:hidden;box-shadow:0 20px 50px rgba(0,0,0,.18)}
.usuarios-page .modal-header{padding:18px 22px;border-bottom:0}
.usuarios-page .modal-body{padding:22px}
.usuarios-page .modal-footer{padding:16px 22px}
.usuarios-page .permiso-card{height:100%;border:1px solid #e2e8f0;border-radius:12px;transition:.15s ease}
.usuarios-page .permiso-card:hover{background:#f8fbff;border-color:#bfd2e8}
.usuarios-page .permiso-card .card-body{padding:14px}
.usuarios-page .permiso-card .form-check{margin:0}
.usuarios-page .permiso-card small{line-height:1.35}
.usuarios-page .permisos-toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:14px}
.usuarios-page .permisos-toolbar .btn{border-radius:9px;font-weight:600}
@media(max-width:768px){.usuarios-page .inventory-header{align-items:flex-start}.usuarios-page .inventory-top-actions{width:100%;justify-content:flex-start}.usuarios-page .inventory-top-actions .btn{width:100%}.usuarios-page .table-box{overflow-x:auto;border-radius:12px}.usuarios-page .table-box table{min-width:760px}.usuarios-page .table-box thead th:first-child{border-top-left-radius:12px}.usuarios-page .table-box thead th:last-child{border-top-right-radius:12px}}

</style>

<div class="main usuarios-page">


    <?php include("../includes/header.php"); ?>


    <div class="content">


        <!-- =====================================================
             ENCABEZADO
        ====================================================== -->

        <div class="inventory-header">


            <div class="inventory-title">

                <h2>

                    <i class="fas fa-users"></i>

                    Gestión de Usuarios

                </h2>


                <p>

                    Administra usuarios, roles y permisos del sistema.

                </p>

            </div>



            <div class="inventory-top-actions">


                <button
                    type="button"
                    class="btn btn-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#modalNuevoUsuario">

                    <i class="fas fa-user-plus"></i>

                    Nuevo Usuario

                </button>


            </div>


        </div>



        <!-- =====================================================
             MENSAJES
        ====================================================== -->


        <?php if (isset($_GET['msg'])): ?>


            <?php if ($_GET['msg'] === 'usuario_creado'): ?>


                <div class="alert alert-success alert-dismissible fade show">

                    <i class="fas fa-check-circle"></i>

                    Usuario creado correctamente.

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert">
                    </button>

                </div>


            <?php elseif ($_GET['msg'] === 'usuario_actualizado'): ?>


                <div class="alert alert-success alert-dismissible fade show">

                    <i class="fas fa-check-circle"></i>

                    Usuario actualizado correctamente.

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert">
                    </button>

                </div>


            <?php elseif ($_GET['msg'] === 'usuario_eliminado'): ?>


                <div class="alert alert-success alert-dismissible fade show">

                    <i class="fas fa-check-circle"></i>

                    Usuario eliminado correctamente.

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert">
                    </button>

                </div>


            <?php elseif ($_GET['msg'] === 'usuario_existente'): ?>


                <div class="alert alert-warning alert-dismissible fade show">

                    <i class="fas fa-exclamation-triangle"></i>

                    Ese nombre de usuario ya existe.

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert">
                    </button>

                </div>


            <?php elseif ($_GET['msg'] === 'no_puede_eliminarse'): ?>


                <div class="alert alert-warning alert-dismissible fade show">

                    <i class="fas fa-exclamation-triangle"></i>

                    No puedes eliminar el usuario con el que estás conectado.

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert">
                    </button>

                </div>


            <?php elseif ($_GET['msg'] === 'error'): ?>


                <div class="alert alert-danger alert-dismissible fade show">

                    <i class="fas fa-times-circle"></i>

                    Ocurrió un error.

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert">
                    </button>

                </div>


            <?php endif; ?>


        <?php endif; ?>



        <!-- =====================================================
             TABLA
        ====================================================== -->


        <div class="table-box">


            <table class="table table-hover align-middle mb-0">


                <thead>

                    <tr>

                        <th>
                            ID
                        </th>

                        <th>
                            Nombre
                        </th>

                        <th>
                            Usuario
                        </th>

                        <th>
                            Rol
                        </th>

                        <th class="text-center">
                            Acciones
                        </th>

                    </tr>

                </thead>


                <tbody>


                    <?php while ($u = mysqli_fetch_assoc($sqlUsuarios)): ?>


                        <?php

                        $usuarioId = (int) $u['id'];

                        $rolUsuario = strtolower(
                            trim($u['rol'])
                        );


                        $permisosAsignados =
                            $permisosUsuarios[$usuarioId]
                            ?? [];

                        ?>


                        <tr>


                            <!-- ID -->

                            <td>

                                <strong>

                                    <?= $usuarioId; ?>

                                </strong>

                            </td>



                            <!-- NOMBRE -->

                            <td>

                                <i class="fas fa-user text-primary"></i>

                                <?= htmlspecialchars(
                                    $u['nombre'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>

                            </td>



                            <!-- USUARIO -->

                            <td>

                                <?= htmlspecialchars(
                                    $u['usuario'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>

                            </td>



                            <!-- ROL -->

                            <td>


                                <?php if (
                                    $rolUsuario === 'admin' ||
                                    $rolUsuario === 'administrador'
                                ): ?>


                                    <span class="badge bg-primary">

                                        <i class="fas fa-crown"></i>

                                        Administrador

                                    </span>


                                <?php else: ?>


                                    <span class="badge bg-secondary">

                                        <i class="fas fa-user"></i>

                                        Empleado

                                    </span>


                                <?php endif; ?>


                            </td>



                            <!-- ACCIONES -->

                            <td class="text-center acciones-usuarios">


                                <!-- EDITAR -->

                                <button
                                    type="button"
                                    class="btn btn-primary btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalEditarUsuario<?= $usuarioId; ?>"
                                    title="Editar usuario">

                                    <i class="fas fa-edit"></i>

                                </button>



                                <!-- ELIMINAR -->

                                <?php if (
                                    !isset($_SESSION['id']) ||
                                    $usuarioId !== (int) $_SESSION['id']
                                ): ?>


                                    <button
                                        type="button"
                                        class="btn btn-danger btn-sm"
                                        onclick="eliminarUsuario(<?= $usuarioId; ?>)"
                                        title="Eliminar usuario">

                                        <i class="fas fa-trash"></i>

                                    </button>


                                <?php endif; ?>


                                <!-- PERMISOS -->

                                <button
                                    type="button"
                                    class="btn btn-warning btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalEditarUsuario<?= $usuarioId; ?>"
                                    title="Administrar permisos">

                                    <i class="fas fa-key"></i>

                                </button>


                            </td>


                        </tr>


                    <?php endwhile; ?>


                </tbody>


            </table>


        </div>


    </div>


</div>



<!-- =========================================================
     MODAL NUEVO USUARIO
========================================================= -->


<div
    class="modal fade"
    id="modalNuevoUsuario"
    tabindex="-1"
    aria-hidden="true">


    <div class="modal-dialog modal-lg">


        <div class="modal-content">


            <form
                action="../actions/usuarios/crear_usuario.php"
                method="POST">


                <!-- HEADER -->

                <div class="modal-header bg-primary text-white">


                    <h5 class="modal-title">

                        <i class="fas fa-user-plus"></i>

                        Nuevo Usuario

                    </h5>


                    <button
                        type="button"
                        class="btn-close btn-close-white"
                        data-bs-dismiss="modal">
                    </button>


                </div>



                <!-- BODY -->

                <div class="modal-body">


                    <div class="row g-3">


                        <!-- NOMBRE -->

                        <div class="col-md-6">


                            <label class="form-label">

                                Nombre completo

                            </label>


                            <input
                                type="text"
                                name="nombre"
                                class="form-control"
                                placeholder="Ej: John Pérez"
                                required>


                        </div>



                        <!-- USUARIO -->

                        <div class="col-md-6">


                            <label class="form-label">

                                Usuario

                            </label>


                            <input
                                type="text"
                                name="usuario"
                                class="form-control"
                                placeholder="Ej: john"
                                required>


                        </div>



                        <!-- PASSWORD -->

                        <div class="col-md-6">


                            <label class="form-label">

                                Contraseña

                            </label>


                            <input
                                type="password"
                                name="password"
                                class="form-control"
                                placeholder="Contraseña"
                                required>


                        </div>



                        <!-- ROL -->

                        <div class="col-md-6">


                            <label class="form-label">

                                Rol

                            </label>


                            <select
                                name="rol"
                                class="form-select"
                                required>


                                <option value="empleado">

                                    Empleado

                                </option>


                                <option value="admin">

                                    Administrador

                                </option>


                            </select>


                        </div>


                    </div>



                    <hr class="my-4">



                    <!-- PERMISOS -->

                    <h5 class="mb-3">

                        <i class="fas fa-key text-warning"></i>

                        Permisos del usuario

                    </h5>



                    <div class="row g-3">


                        <?php foreach ($listaPermisos as $permiso): ?>


                            <div class="col-md-6">


                                <div class="card permiso-card h-100 border">


                                    <div class="card-body">


                                        <div class="form-check">


                                            <input
                                                class="form-check-input"
                                                type="checkbox"
                                                name="permisos[]"
                                                value="<?= (int) $permiso['id']; ?>"
                                                id="nuevo_permiso_<?= (int) $permiso['id']; ?>">


                                            <label
                                                class="form-check-label"
                                                for="nuevo_permiso_<?= (int) $permiso['id']; ?>">


                                                <strong>

                                                    <?= htmlspecialchars(
                                                        $permiso['nombre'],
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ); ?>

                                                </strong>


                                                <br>


                                                <small class="text-muted">

                                                    <?= htmlspecialchars(
                                                        $permiso['descripcion'],
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ); ?>

                                                </small>


                                            </label>


                                        </div>


                                    </div>


                                </div>


                            </div>


                        <?php endforeach; ?>


                    </div>


                </div>



                <!-- FOOTER -->

                <div class="modal-footer">


                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">

                        Cancelar

                    </button>


                    <button
                        type="submit"
                        class="btn btn-primary">

                        <i class="fas fa-save"></i>

                        Crear Usuario

                    </button>


                </div>


            </form>


        </div>


    </div>


</div>



<!-- =========================================================
     MODALES EDITAR USUARIOS
========================================================= -->


<?php

/*
|--------------------------------------------------------------------------
| Volvemos a consultar los usuarios
|--------------------------------------------------------------------------
*/

$sqlModales = mysqli_query(
    $conn,
    "SELECT id, nombre, usuario, rol
     FROM usuarios
     ORDER BY id ASC"
);

?>


<?php while ($u = mysqli_fetch_assoc($sqlModales)): ?>


    <?php

    $usuarioId = (int) $u['id'];

    $rolUsuario = strtolower(
        trim($u['rol'])
    );

    $permisosAsignados =
        $permisosUsuarios[$usuarioId]
        ?? [];

    ?>


    <div
        class="modal fade"
        id="modalEditarUsuario<?= $usuarioId; ?>"
        tabindex="-1"
        aria-hidden="true">


        <div class="modal-dialog modal-lg">


            <div class="modal-content">


                <form
                    action="../actions/usuarios/actualizar_usuario.php"
                    method="POST">


                    <!-- HEADER -->

                    <div class="modal-header bg-primary text-white">


                        <h5 class="modal-title">

                            <i class="fas fa-user-edit"></i>

                            Editar Usuario

                        </h5>


                        <button
                            type="button"
                            class="btn-close btn-close-white"
                            data-bs-dismiss="modal">
                        </button>


                    </div>



                    <!-- BODY -->

                    <div class="modal-body">


                        <input
                            type="hidden"
                            name="id"
                            value="<?= $usuarioId; ?>">



                        <div class="row g-3">


                            <!-- NOMBRE -->

                            <div class="col-md-6">


                                <label class="form-label">

                                    Nombre completo

                                </label>


                                <input
                                    type="text"
                                    name="nombre"
                                    class="form-control"
                                    value="<?= htmlspecialchars(
                                        $u['nombre'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>"
                                    required>


                            </div>



                            <!-- USUARIO -->

                            <div class="col-md-6">


                                <label class="form-label">

                                    Usuario

                                </label>


                                <input
                                    type="text"
                                    name="usuario"
                                    class="form-control"
                                    value="<?= htmlspecialchars(
                                        $u['usuario'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>"
                                    required>


                            </div>



                            <!-- PASSWORD -->

                            <div class="col-md-6">


                                <label class="form-label">

                                    Nueva contraseña

                                </label>


                                <input
                                    type="password"
                                    name="password"
                                    class="form-control"
                                    placeholder="Dejar vacío para conservar la actual">


                            </div>



                            <!-- ROL -->

                            <div class="col-md-6">


                                <label class="form-label">

                                    Rol

                                </label>


                                <select
                                    name="rol"
                                    class="form-select">


                                    <option
                                        value="empleado"
                                        <?= $rolUsuario === 'empleado'
                                            ? 'selected'
                                            : ''; ?>>

                                        Empleado

                                    </option>


                                    <option
                                        value="admin"
                                        <?= (
                                            $rolUsuario === 'admin' ||
                                            $rolUsuario === 'administrador'
                                        )
                                            ? 'selected'
                                            : ''; ?>>

                                        Administrador

                                    </option>


                                </select>


                            </div>


                        </div>



                        <hr class="my-4">



                        <!-- PERMISOS -->

                        <div class="d-flex justify-content-between align-items-center mb-3">


                            <h5 class="mb-0">

                                <i class="fas fa-key text-warning"></i>

                                Permisos

                            </h5>


                            <div>


                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-primary"
                                    onclick="seleccionarTodos(<?= $usuarioId; ?>)">

                                    Seleccionar todos

                                </button>


                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-secondary"
                                    onclick="quitarTodos(<?= $usuarioId; ?>)">

                                    Quitar todos

                                </button>


                            </div>


                        </div>



                        <div class="row g-3">


                            <?php foreach ($listaPermisos as $permiso): ?>


                                <?php

                                $permisoId =
                                    (int) $permiso['id'];

                                $marcado =
                                    in_array(
                                        $permisoId,
                                        $permisosAsignados
                                    );

                                ?>


                                <div class="col-md-6">


                                    <div class="card permiso-card h-100 border">


                                        <div class="card-body">


                                            <div class="form-check">


                                                <input
                                                    class="form-check-input permiso-usuario-<?= $usuarioId; ?>"
                                                    type="checkbox"
                                                    name="permisos[]"
                                                    value="<?= $permisoId; ?>"
                                                    id="usuario_<?= $usuarioId; ?>_permiso_<?= $permisoId; ?>"
                                                    <?= $marcado ? 'checked' : ''; ?>>


                                                <label
                                                    class="form-check-label"
                                                    for="usuario_<?= $usuarioId; ?>_permiso_<?= $permisoId; ?>">


                                                    <strong>

                                                        <?= htmlspecialchars(
                                                            $permiso['nombre'],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ); ?>

                                                    </strong>


                                                    <br>


                                                    <small class="text-muted">

                                                        <?= htmlspecialchars(
                                                            $permiso['descripcion'],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ); ?>

                                                    </small>


                                                </label>


                                            </div>


                                        </div>


                                    </div>


                                </div>


                            <?php endforeach; ?>


                        </div>



                    </div>



                    <!-- FOOTER -->

                    <div class="modal-footer">


                        <button
                            type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">

                            Cancelar

                        </button>


                        <button
                            type="submit"
                            class="btn btn-primary">

                            <i class="fas fa-save"></i>

                            Guardar Cambios

                        </button>


                    </div>


                </form>


            </div>


        </div>


    </div>


<?php endwhile; ?>



<!-- =========================================================
     JAVASCRIPT
========================================================= -->


<script>


/*
|--------------------------------------------------------------------------
| ELIMINAR USUARIO
|--------------------------------------------------------------------------
*/

function eliminarUsuario(id)
{

    if (!confirm(
        "¿Seguro que deseas eliminar este usuario?"
    )) {

        return;

    }


    window.location.href =
        "../actions/usuarios/eliminar_usuario.php?id=" + id;

}



/*
|--------------------------------------------------------------------------
| SELECCIONAR TODOS
|--------------------------------------------------------------------------
*/

function seleccionarTodos(usuarioId)
{

    const permisos =
        document.querySelectorAll(
            ".permiso-usuario-" + usuarioId
        );


    permisos.forEach(function(checkbox) {

        checkbox.checked = true;

    });

}



/*
|--------------------------------------------------------------------------
| QUITAR TODOS
|--------------------------------------------------------------------------
*/

function quitarTodos(usuarioId)
{

    const permisos =
        document.querySelectorAll(
            ".permiso-usuario-" + usuarioId
        );


    permisos.forEach(function(checkbox) {

        checkbox.checked = false;

    });

}


</script>



<?php include("../includes/footer.php"); ?>