<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../includes/conexion.php";
require_once "../includes/permisos.php";

exigirPermiso('ver_inventario');

/* =========================================================
   ÁREA SELECCIONADA
========================================================= */

$areaSeleccionada = strtolower(trim($_GET['area'] ?? 'audiovisuales'));

$areasPermitidas = [
    'audiovisuales',
    'automatizacion'
];

if (!in_array($areaSeleccionada, $areasPermitidas, true)) {
    $areaSeleccionada = 'audiovisuales';
}

/* =========================================================
   NOMBRE DEL ÁREA
========================================================= */

$nombreArea = (
    $areaSeleccionada === 'audiovisuales'
)
    ? 'Audiovisuales'
    : 'Automatización';

/* =========================================================
   CONSULTAR PRODUCTOS DEL ÁREA
========================================================= */

$sqlProductos = "
    SELECT
        p.id,
        p.codigo,
        p.nombre,
        p.area,
        p.categoria,
        p.marca,
        p.referencia,
        p.cantidad,
        p.stock_minimo,
        p.ubicacion,
        p.imagen,
        p.estado,
        c.nombre AS categoria_nombre
    FROM productos p
    LEFT JOIN categorias c
        ON c.id = p.categoria
    WHERE LOWER(TRIM(p.area)) = ?
    ORDER BY p.nombre ASC
";

$stmtProductos = $conn->prepare($sqlProductos);

if (!$stmtProductos) {
    die(
        "Error preparando consulta de inventario: " .
        htmlspecialchars(
            $conn->error,
            ENT_QUOTES,
            'UTF-8'
        )
    );
}

$stmtProductos->bind_param(
    "s",
    $areaSeleccionada
);

if (!$stmtProductos->execute()) {
    die(
        "Error ejecutando consulta de inventario: " .
        htmlspecialchars(
            $stmtProductos->error,
            ENT_QUOTES,
            'UTF-8'
        )
    );
}

$resultadoProductos = $stmtProductos->get_result();

$productos = [];

while ($producto = $resultadoProductos->fetch_assoc()) {
    $productos[] = $producto;
}

$stmtProductos->close();

/* =========================================================
   CATEGORÍAS
========================================================= */

$categorias = [];

$sqlCategorias = "
    SELECT
        id,
        nombre
    FROM categorias
    ORDER BY nombre ASC
";

$resultadoCategorias = $conn->query($sqlCategorias);

if ($resultadoCategorias) {

    while ($categoria = $resultadoCategorias->fetch_assoc()) {
        $categorias[] = $categoria;
    }

}

/* =========================================================
   UBICACIONES
========================================================= */

$ubicaciones = [];

$sqlUbicaciones = "
    SELECT
        id,
        edificio,
        piso,
        salon,
        area,
        estado
    FROM ubicaciones
    WHERE estado = 'Activo'
    ORDER BY
        edificio ASC,
        piso ASC,
        salon ASC
";

$resultadoUbicaciones = $conn->query($sqlUbicaciones);

if (!$resultadoUbicaciones) {
    die(
        "Error consultando ubicaciones: " .
        htmlspecialchars(
            $conn->error,
            ENT_QUOTES,
            'UTF-8'
        )
    );
}

while ($ubicacion = $resultadoUbicaciones->fetch_assoc()) {
    $ubicaciones[] = $ubicacion;
}

/* =========================================================
   SIDEBAR
   IMPORTANTE:
   Se incluye UNA SOLA VEZ.
========================================================= */

include("../includes/sidebar.php");

?>

<style>
html,body{width:100%;max-width:100%;overflow-x:hidden!important}
*,*::before,*::after{box-sizing:border-box}
:root{--navy:#002855;--blue:#0d6efd;--gold:#f5c400;--green:#159f70;--yellow:#f2b800;--red:#e34b55;--bg:#f4f7fb;--border:#e3eaf3;--text:#18344f;--muted:#718096}
.inventario-page{min-height:100vh;width:100%;min-width:0;background:linear-gradient(180deg,#f8fbff 0%,#f2f6fb 100%);padding-bottom:40px;overflow-x:hidden}
.inventario-page .content{padding:20px 28px 40px;max-width:1600px;margin:0 auto}
.inventory-header{display:flex;justify-content:space-between;align-items:center;gap:20px;margin-bottom:18px;padding:22px 24px;background:#fff;border:1px solid var(--border);border-radius:18px;box-shadow:0 8px 24px rgba(0,40,85,.07)}
.inventory-title h2{margin:0;color:var(--navy);font-size:30px;font-weight:800}.inventory-title h2 i{margin-right:10px}.inventory-title p{margin:7px 0 0;color:var(--muted);font-size:15px}
.inventory-actions{display:flex;flex-wrap:wrap;gap:9px;justify-content:flex-end}.inventory-actions .btn{border-radius:10px;font-weight:700;padding:10px 14px}
.inventario-areas{display:flex;gap:10px;margin-bottom:18px}.btn-area-inventario{display:inline-flex;align-items:center;gap:8px;padding:10px 17px;border-radius:11px;text-decoration:none;font-weight:800;border:1px solid transparent}.btn-area-activa{background:var(--navy);color:#fff}.btn-area-activa:hover{background:#073d78;color:#fff}.btn-area-inactiva{background:#fff;color:var(--navy);border-color:#d8e2ed}.btn-area-inactiva:hover{background:#eef5fc;color:var(--navy)}
.inventario-resumen{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:20px}.inv-stat{background:#fff;border:1px solid var(--border);border-radius:15px;padding:15px 17px;display:flex;align-items:center;gap:13px;box-shadow:0 6px 18px rgba(0,40,85,.05)}.inv-stat-icon{width:45px;height:45px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:19px;background:#eef4fb;color:var(--navy)}.inv-stat-value{display:block;color:var(--navy);font-size:24px;font-weight:850;line-height:1.1}.inv-stat-label{display:block;color:var(--muted);font-size:13px;margin-top:3px}.inv-stat.available .inv-stat-icon{color:var(--green);background:#e8f8f1}.inv-stat.low .inv-stat-icon{color:#b57900;background:#fff6dc}.inv-stat.empty .inv-stat-icon{color:var(--red);background:#fdecef}
.inventario-filtros{background:#fff;border:1px solid var(--border);border-radius:16px;padding:16px;margin-bottom:20px;box-shadow:0 6px 18px rgba(0,40,85,.05)}.filtros-title{display:flex;align-items:center;gap:8px;color:var(--navy);font-weight:800;margin-bottom:12px}.filtros-grid{display:grid;grid-template-columns:minmax(240px,2fr) repeat(3,minmax(150px,1fr)) auto;gap:11px;align-items:end}.filtro label{display:block;font-size:12px;font-weight:800;color:#526579;margin-bottom:6px}.filtro input,.filtro select{width:100%;height:42px;border:1px solid #d6e0eb;border-radius:9px;padding:0 12px;color:var(--text);outline:none;background:#fff}.filtro input:focus,.filtro select:focus{border-color:#6ea7e6;box-shadow:0 0 0 3px rgba(13,110,253,.09)}.btn-limpiar-filtros{height:42px;border-radius:9px;font-weight:700;white-space:nowrap}
.inventario-card{background:#fff;border:1px solid var(--border);border-radius:18px;box-shadow:0 8px 24px rgba(0,40,85,.06);overflow:hidden}.inventario-card-header{padding:17px 20px;border-bottom:1px solid #edf1f6;display:flex;justify-content:space-between;align-items:center;gap:12px}.inventario-card-header h4{margin:0;color:var(--navy);font-size:19px;font-weight:800}.inventario-card-header .badge{font-size:12px;padding:7px 10px;border-radius:20px}
.productos-grid{padding:20px;display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:18px}.producto-card{border:1px solid #e1e9f2;border-radius:16px;background:#fff;overflow:hidden;display:flex;flex-direction:column;min-width:0;transition:transform .18s ease,box-shadow .18s ease,border-color .18s ease}.producto-card:hover{transform:translateY(-3px);box-shadow:0 12px 26px rgba(0,40,85,.11);border-color:#cbdbea}.producto-imagen-wrap{height:190px;background:linear-gradient(135deg,#edf3f9,#f8fafc);display:flex;align-items:center;justify-content:center;position:relative;border-bottom:1px solid #e7edf4}.producto-imagen-card{width:100%;height:100%;object-fit:contain;padding:14px;cursor:zoom-in;transition:transform .25s ease}.producto-imagen-wrap:hover .producto-imagen-card{transform:scale(1.04)}.producto-imagen-abrir{position:absolute;bottom:10px;right:10px;width:38px;height:38px;border:0;border-radius:50%;background:rgba(0,40,85,.9);color:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;opacity:.92;box-shadow:0 4px 12px rgba(0,0,0,.18);transition:.2s}.producto-imagen-abrir:hover{background:#f5c400;color:#002855;transform:scale(1.06)}.visor-imagen-modal .modal-dialog{max-width:950px}.visor-imagen-modal .modal-content{background:#fff;border-radius:18px;overflow:hidden}.visor-imagen-modal .modal-header{background:#002855;color:#fff;padding:14px 18px}.visor-imagen-modal .modal-body{padding:18px;background:#f3f6fa}.visor-imagen-grande-wrap{min-height:520px;display:flex;align-items:center;justify-content:center;background:#fff;border-radius:14px;border:1px solid #dce5ee;padding:20px}.visor-imagen-grande{max-width:100%;max-height:70vh;width:auto;height:auto;object-fit:contain;border-radius:10px}.visor-imagen-info{padding:15px 4px 2px}.visor-imagen-info h4{margin:0 0 5px;color:#002855;font-weight:800}.visor-imagen-info p{margin:0;color:#63768a}.visor-imagen-icono{margin-right:8px}.producto-codigo{position:absolute;top:11px;left:11px;background:rgba(0,40,85,.93);color:#fff;border-radius:8px;padding:6px 9px;font-size:11px;font-weight:800;letter-spacing:.3px}.producto-estado{position:absolute;top:11px;right:11px}.badge-estado{display:inline-flex;align-items:center;gap:5px;border-radius:20px;padding:6px 9px;font-size:11px;font-weight:800}.badge-disponible{background:#e8f8f1;color:#117553}.badge-bajo{background:#fff4d6;color:#9a6900}.badge-agotado{background:#fdebed;color:#b72f3b}.producto-card-body{padding:16px;display:flex;flex-direction:column;gap:10px;flex:1}.producto-nombre-card{margin:0;color:var(--navy);font-size:18px;line-height:1.25;font-weight:850}.producto-meta{display:grid;grid-template-columns:1fr 1fr;gap:8px}.producto-meta-item{background:#f7f9fc;border-radius:9px;padding:8px 9px;min-width:0}.producto-meta-label{display:block;color:#7a8998;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.3px;margin-bottom:3px}.producto-meta-value{display:block;color:#29435b;font-size:13px;font-weight:650;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.stock-line{display:flex;align-items:center;justify-content:space-between;padding:9px 10px;border-radius:10px;background:#f2f6fb}.stock-line span:first-child{color:#5c7085;font-size:12px;font-weight:700}.stock-numero-card{font-size:20px;font-weight:900;color:var(--navy)}.stock-min{font-size:11px;color:#718096}/* =========================================================
   ACCIONES DE PRODUCTO - DISEÑO DINÁMICO
========================================================= */
.acciones-producto{display:grid;grid-template-columns:1fr 1fr;gap:10px;padding-top:4px;margin-top:2px}
.acciones-producto .btn{
  position:relative;overflow:hidden;flex:1;min-height:46px;border:0!important;border-radius:13px!important;
  display:flex;align-items:center;justify-content:center;gap:8px;padding:11px 14px;
  font-family:'Montserrat',sans-serif;font-size:12px;font-weight:800;letter-spacing:.1px;
  text-decoration:none!important;cursor:pointer;transition:transform .25s ease,box-shadow .25s ease,filter .25s ease;
}
.acciones-producto .btn::before{
  content:"";position:absolute;top:0;left:-130%;width:75%;height:100%;
  background:linear-gradient(105deg,transparent,rgba(255,255,255,.38),transparent);
  transform:skewX(-22deg);transition:left .6s ease;
}
.acciones-producto .btn::after{
  content:"";position:absolute;inset:1px;border-radius:12px;border:1px solid rgba(255,255,255,.18);pointer-events:none;
}
.acciones-producto .btn i,.acciones-producto .btn span{position:relative;z-index:2}
.acciones-producto .btn i{margin:0!important;font-size:13px}
.btn-actualizar-producto{
  background:linear-gradient(135deg,#1683ff 0%,#0864dc 48%,#0046a8 100%)!important;
  color:#fff!important;box-shadow:0 7px 16px rgba(0,92,205,.25),inset 0 1px 0 rgba(255,255,255,.24)!important;
}
.btn-eliminar-producto{
  background:linear-gradient(135deg,#ff6262 0%,#e63b42 48%,#bd1f2d 100%)!important;
  color:#fff!important;box-shadow:0 7px 16px rgba(210,42,55,.24),inset 0 1px 0 rgba(255,255,255,.24)!important;
}
.btn-actualizar-producto:hover,.btn-eliminar-producto:hover{transform:translateY(-3px) scale(1.015);filter:brightness(1.07)}
.btn-actualizar-producto:hover{box-shadow:0 12px 24px rgba(0,92,205,.36),inset 0 1px 0 rgba(255,255,255,.28)!important}
.btn-eliminar-producto:hover{box-shadow:0 12px 24px rgba(210,42,55,.36),inset 0 1px 0 rgba(255,255,255,.28)!important}
.acciones-producto .btn:hover::before{left:145%}
.acciones-producto .btn:active{transform:translateY(0) scale(.97)}
@media(max-width:560px){.acciones-producto{grid-template-columns:1fr}.acciones-producto .btn{min-height:44px}}.producto-vacio{padding:45px 20px;text-align:center;color:#74869a}.producto-vacio i{font-size:45px;display:block;margin-bottom:12px;color:#b8c6d5}.producto-vacio h5{color:var(--navy);font-weight:800}
.modal-content{border:0;border-radius:15px;overflow:hidden}.modal-header{border:0}.modal-footer{border-top:1px solid #edf1f6}.form-label{font-weight:700;color:#40566c}.form-control,.form-select{border-radius:9px;border-color:#d6e0eb}.form-control:focus,.form-select:focus{border-color:#7aaee7;box-shadow:0 0 0 3px rgba(13,110,253,.08)}
@media(max-width:1100px){.filtros-grid{grid-template-columns:1fr 1fr 1fr}.filtros-grid .filtro:first-child{grid-column:1/-1}.btn-limpiar-filtros{width:100%}.inventario-resumen{grid-template-columns:repeat(2,1fr)}}
@media(max-width:800px){.inventario-page .content{padding:15px}.inventory-header{align-items:flex-start;flex-direction:column}.inventory-actions{width:100%;justify-content:flex-start}.inventario-areas{flex-wrap:wrap}.filtros-grid{grid-template-columns:1fr}.filtros-grid .filtro:first-child{grid-column:auto}.productos-grid{grid-template-columns:repeat(auto-fill,minmax(250px,1fr));padding:14px;gap:14px}}
@media(max-width:560px){.inventario-resumen{grid-template-columns:1fr}.inventory-title h2{font-size:24px}.inventory-actions .btn{width:100%}.inventario-areas .btn-area-inventario{flex:1;justify-content:center}.productos-grid{grid-template-columns:1fr}.producto-imagen-wrap{height:210px}}
</style>
<div class="main inventario-page">
<?php include("../includes/header.php"); ?>
<div class="content">

<div class="inventory-header">
  <div class="inventory-title">
    <h2><i class="fas <?= $areaSeleccionada === 'audiovisuales' ? 'fa-video' : 'fa-gears'; ?>"></i>Inventario de <?= htmlspecialchars($nombreArea,ENT_QUOTES,'UTF-8'); ?></h2>
    <p>Consulta, filtra y administra los productos registrados en esta área.</p>
  </div>
  <div class="inventory-actions">
    <?php if (tienePermiso('entrada_stock')): ?><button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalEntradaGeneral"><i class="fas fa-arrow-down"></i> Entrada</button><?php endif; ?>
    <?php if (tienePermiso('salida_stock')): ?><button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modalSalidaGeneral"><i class="fas fa-arrow-up"></i> Salida</button><?php endif; ?>
    <?php if (tienePermiso('ver_movimientos')): ?><a href="movimientos.php?area=<?= urlencode($areaSeleccionada); ?>" class="btn btn-dark"><i class="fas fa-history"></i> Movimientos</a><?php endif; ?>
    <?php if (tienePermiso('crear_producto')): ?><button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalProducto"><i class="fas fa-plus"></i> Nuevo producto</button><?php endif; ?>
  </div>
</div>

<div class="inventario-areas">
  <a href="inventario.php?area=audiovisuales" class="btn-area-inventario <?= $areaSeleccionada==='audiovisuales'?'btn-area-activa':'btn-area-inactiva'; ?>"><i class="fas fa-video"></i>Audiovisuales</a>
  <a href="inventario.php?area=automatizacion" class="btn-area-inventario <?= $areaSeleccionada==='automatizacion'?'btn-area-activa':'btn-area-inactiva'; ?>"><i class="fas fa-gears"></i>Automatización</a>
</div>

<?php if (isset($_GET['msg'])): ?>
<script>document.addEventListener('DOMContentLoaded',function(){if(typeof Swal==='undefined')return;const m=<?= json_encode($_GET['msg']); ?>;const cfg={entrada_ok:['Entrada registrada','La entrada de inventario fue registrada correctamente.'],salida_ok:['Salida registrada','La salida de inventario fue registrada correctamente.'],producto_actualizado:['Producto actualizado','El producto fue actualizado correctamente.'],producto_registrado:['Producto registrado','El producto fue agregado correctamente.']};if(cfg[m])Swal.fire({icon:'success',title:cfg[m][0],text:cfg[m][1],confirmButtonColor:'#002855'});});</script>
<?php endif; ?>
<?php if (isset($_GET['error']) && $_GET['error']==='producto_tiene_movimientos'): ?>
<script>document.addEventListener('DOMContentLoaded',function(){if(typeof Swal!=='undefined')Swal.fire({icon:'warning',title:'No se puede eliminar',text:'El producto tiene movimientos registrados y debe conservarse para mantener el historial.',confirmButtonColor:'#f59e0b'});});</script>
<?php endif; ?>

<?php
$totalProductos=count($productos);$disponibles=0;$bajoStock=0;$agotados=0;
foreach($productos as $itemResumen){$c=(int)($itemResumen['cantidad']??0);$m=(int)($itemResumen['stock_minimo']??0);if($c<=0)$agotados++;elseif($c<=$m)$bajoStock++;else$disponibles++;}
?>
<div class="inventario-resumen">
  <div class="inv-stat total"><div class="inv-stat-icon"><i class="fas fa-boxes-stacked"></i></div><div><span class="inv-stat-value"><?= $totalProductos; ?></span><span class="inv-stat-label">Total de productos</span></div></div>
  <div class="inv-stat available"><div class="inv-stat-icon"><i class="fas fa-circle-check"></i></div><div><span class="inv-stat-value"><?= $disponibles; ?></span><span class="inv-stat-label">Disponibles</span></div></div>
  <div class="inv-stat low"><div class="inv-stat-icon"><i class="fas fa-triangle-exclamation"></i></div><div><span class="inv-stat-value"><?= $bajoStock; ?></span><span class="inv-stat-label">Bajo stock</span></div></div>
  <div class="inv-stat empty"><div class="inv-stat-icon"><i class="fas fa-circle-xmark"></i></div><div><span class="inv-stat-value"><?= $agotados; ?></span><span class="inv-stat-label">Agotados</span></div></div>
</div>

<div class="inventario-filtros">
  <div class="filtros-title"><i class="fas fa-filter"></i>Filtros del inventario</div>
  <div class="filtros-grid">
    <div class="filtro"><label for="filtroTexto">Buscar producto</label><input type="search" id="filtroTexto" placeholder="Código, producto, marca, referencia..."></div>
    <div class="filtro"><label for="filtroCategoria">Categoría</label><select id="filtroCategoria"><option value="">Todas las categorías</option><?php foreach($categorias as $categoria): ?><option value="<?= htmlspecialchars(mb_strtolower($categoria['nombre'],'UTF-8'),ENT_QUOTES,'UTF-8'); ?>"><?= htmlspecialchars($categoria['nombre'],ENT_QUOTES,'UTF-8'); ?></option><?php endforeach; ?></select></div>
    <div class="filtro"><label for="filtroEstado">Estado de stock</label><select id="filtroEstado"><option value="">Todos</option><option value="disponible">Disponible</option><option value="bajo">Bajo Stock</option><option value="agotado">Agotado</option></select></div>
    <div class="filtro"><label for="filtroStock">Cantidad</label><select id="filtroStock"><option value="">Cualquier cantidad</option><option value="con">Con stock</option><option value="sin">Sin stock</option></select></div>
    <button type="button" class="btn btn-outline-secondary btn-limpiar-filtros" id="limpiarFiltros"><i class="fas fa-rotate-left"></i> Limpiar</button>
  </div>
</div>

<div class="inventario-card">
  <div class="inventario-card-header"><h4><i class="fas <?= $areaSeleccionada==='audiovisuales'?'fa-video':'fa-gears'; ?> text-primary"></i> Productos de <?= htmlspecialchars($nombreArea,ENT_QUOTES,'UTF-8'); ?></h4><span class="badge bg-primary" id="contadorProductos"><?= count($productos); ?> productos</span></div>
  <div class="productos-grid" id="productosGrid">
  <?php foreach($productos as $producto): ?>
    <?php
      $cantidad=(int)($producto['cantidad']??0);$stockMinimo=(int)($producto['stock_minimo']??0);
      if($cantidad<=0){$estadoClase='badge-agotado';$estadoTexto='Agotado';$estadoIcono='fa-times-circle';$estadoFiltro='agotado';}
      elseif($cantidad<=$stockMinimo){$estadoClase='badge-bajo';$estadoTexto='Bajo Stock';$estadoIcono='fa-exclamation-triangle';$estadoFiltro='bajo';}
      else{$estadoClase='badge-disponible';$estadoTexto='Disponible';$estadoIcono='fa-check-circle';$estadoFiltro='disponible';}
      $imagen=!empty($producto['imagen'])?basename($producto['imagen']):'no-image.png';
      $categoriaTexto=$producto['categoria_nombre']??$producto['categoria']??'';
      $buscar=mb_strtolower(trim(($producto['codigo']??'').' '.($producto['nombre']??'').' '.$categoriaTexto.' '.($producto['marca']??'').' '.($producto['referencia']??'')),'UTF-8');
    ?>
    <article class="producto-card" data-texto="<?= htmlspecialchars($buscar,ENT_QUOTES,'UTF-8'); ?>" data-categoria="<?= htmlspecialchars(mb_strtolower($categoriaTexto,'UTF-8'),ENT_QUOTES,'UTF-8'); ?>" data-estado="<?= $estadoFiltro; ?>" data-stock="<?= $cantidad>0?'con':'sin'; ?>">
      <div class="producto-imagen-wrap">
        <img src="../assets/uploads/productos/<?= htmlspecialchars($imagen,ENT_QUOTES,'UTF-8'); ?>" class="producto-imagen-card js-abrir-imagen" loading="lazy" alt="<?= htmlspecialchars($producto['nombre']??'Producto',ENT_QUOTES,'UTF-8'); ?>" data-imagen="../assets/uploads/productos/<?= htmlspecialchars($imagen,ENT_QUOTES,'UTF-8'); ?>" data-nombre="<?= htmlspecialchars($producto['nombre']??'Producto',ENT_QUOTES,'UTF-8'); ?>" data-codigo="<?= htmlspecialchars($producto['codigo']??'',ENT_QUOTES,'UTF-8'); ?>" onerror="this.onerror=null;this.src='../assets/uploads/productos/no-image.png';this.dataset.imagen='../assets/uploads/productos/no-image.png';">
        <button type="button" class="producto-imagen-abrir js-abrir-imagen" aria-label="Ver imagen de <?= htmlspecialchars($producto['nombre']??'Producto',ENT_QUOTES,'UTF-8'); ?>" data-imagen="../assets/uploads/productos/<?= htmlspecialchars($imagen,ENT_QUOTES,'UTF-8'); ?>" data-nombre="<?= htmlspecialchars($producto['nombre']??'Producto',ENT_QUOTES,'UTF-8'); ?>" data-codigo="<?= htmlspecialchars($producto['codigo']??'',ENT_QUOTES,'UTF-8'); ?>"><i class="fas fa-expand"></i></button>
        <span class="producto-codigo"><?= htmlspecialchars($producto['codigo']??'',ENT_QUOTES,'UTF-8'); ?></span>
        <span class="producto-estado"><span class="badge-estado <?= $estadoClase; ?>"><i class="fas <?= $estadoIcono; ?>"></i><?= $estadoTexto; ?></span></span>
      </div>
      <div class="producto-card-body">
        <h3 class="producto-nombre-card"><?= htmlspecialchars($producto['nombre']??'',ENT_QUOTES,'UTF-8'); ?></h3>
        <div class="producto-meta">
          <div class="producto-meta-item"><span class="producto-meta-label">Categoría</span><span class="producto-meta-value" title="<?= htmlspecialchars($categoriaTexto,ENT_QUOTES,'UTF-8'); ?>"><?= htmlspecialchars($categoriaTexto,ENT_QUOTES,'UTF-8'); ?></span></div>
          <div class="producto-meta-item"><span class="producto-meta-label">Marca</span><span class="producto-meta-value" title="<?= htmlspecialchars($producto['marca']??'',ENT_QUOTES,'UTF-8'); ?>"><?= htmlspecialchars($producto['marca']??'Sin marca',ENT_QUOTES,'UTF-8'); ?></span></div>
          <div class="producto-meta-item"><span class="producto-meta-label">Referencia</span><span class="producto-meta-value" title="<?= htmlspecialchars($producto['referencia']??'',ENT_QUOTES,'UTF-8'); ?>"><?= htmlspecialchars($producto['referencia']??'Sin referencia',ENT_QUOTES,'UTF-8'); ?></span></div>
          <div class="producto-meta-item"><span class="producto-meta-label">Ubicación</span><span class="producto-meta-value" title="<?= htmlspecialchars($producto['ubicacion']??'',ENT_QUOTES,'UTF-8'); ?>"><?= htmlspecialchars($producto['ubicacion']??'Sin ubicación',ENT_QUOTES,'UTF-8'); ?></span></div>
        </div>
        <div class="stock-line"><span>Stock actual</span><span class="stock-numero-card"><?= $cantidad; ?></span><span class="stock-min">Mín. <?= $stockMinimo; ?></span></div>
        <div class="acciones-producto">
          <?php if(tienePermiso('editar_producto')): ?><button type="button" class="btn btn-actualizar-producto" data-bs-toggle="modal" data-bs-target="#editarProducto<?= (int)$producto['id']; ?>"><i class="fas fa-pen"></i><span>Actualizar</span></button><?php endif; ?>
          <?php if(tienePermiso('eliminar_producto')): ?><button type="button" class="btn btn-eliminar-producto" onclick="eliminarProducto(<?= (int)$producto['id']; ?>)"><i class="fas fa-trash-can"></i><span>Eliminar</span></button><?php endif; ?>
        </div>
      </div>
    </article>
  <?php endforeach; ?>
  <div class="producto-vacio" id="sinResultados" style="display:none;grid-column:1/-1"><i class="fas fa-box-open"></i><h5>No se encontraron productos</h5><p>Prueba cambiando los filtros de búsqueda.</p></div>
  <?php if(empty($productos)): ?><div class="producto-vacio" style="grid-column:1/-1"><i class="fas fa-box-open"></i><h5>No hay productos registrados</h5><p>No existen productos para esta área.</p></div><?php endif; ?>
  </div>
</div>
</div></div>

<!-- =========================================================
     VISOR DE IMAGEN DEL PRODUCTO
========================================================= -->
<div class="modal fade visor-imagen-modal" id="modalVisorImagen" tabindex="-1" aria-labelledby="modalVisorImagenLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalVisorImagenLabel"><i class="fas fa-image visor-imagen-icono"></i>Imagen del producto</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="visor-imagen-grande-wrap">
                    <img id="imagenVisorProducto" class="visor-imagen-grande" src="../assets/uploads/productos/no-image.png" alt="Producto">
                </div>
                <div class="visor-imagen-info">
                    <h4 id="nombreVisorProducto">Producto</h4>
                    <p id="codigoVisorProducto"></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================
     MODALES DE EDICIÓN
========================================================= -->

<?php foreach ($productos as $producto): ?>

<div
    class="modal fade"
    id="editarProducto<?= (int)$producto['id']; ?>"
    tabindex="-1"
    aria-hidden="true">

    <div class="modal-dialog modal-lg">

        <div class="modal-content">

            <form
                action="../actions/productos/actualizar_producto.php"
                method="POST"
                enctype="multipart/form-data">

                <div class="modal-header bg-primary text-white">

                    <h5 class="modal-title">

                        <i class="fas fa-edit"></i>
                        Editar producto

                    </h5>

                    <button
                        type="button"
                        class="btn-close btn-close-white"
                        data-bs-dismiss="modal">
                    </button>

                </div>


                <div class="modal-body">

                    <input
                        type="hidden"
                        name="id"
                        value="<?= (int)$producto['id']; ?>">

                    <div class="row g-3">

                        <div class="col-md-6">

                            <label class="form-label">
                                Código
                            </label>

                            <input
                                type="text"
                                name="codigo"
                                class="form-control"
                                value="<?= htmlspecialchars(
                                    $producto['codigo'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>"
                                required>

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                Área
                            </label>

                            <select
                                name="area"
                                class="form-select"
                                required>

                                <option
                                    value="audiovisuales"
                                    <?= strtolower(trim(
                                        $producto['area'] ?? ''
                                    )) === 'audiovisuales'
                                        ? 'selected'
                                        : ''; ?>>

                                    Audiovisuales

                                </option>

                                <option
                                    value="automatizacion"
                                    <?= strtolower(trim(
                                        $producto['area'] ?? ''
                                    )) === 'automatizacion'
                                        ? 'selected'
                                        : ''; ?>>

                                    Automatización

                                </option>

                            </select>

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                Producto
                            </label>

                            <input
                                type="text"
                                name="nombre"
                                class="form-control"
                                value="<?= htmlspecialchars(
                                    $producto['nombre'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>"
                                required>

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                Categoría
                            </label>

                            <select
                                name="categoria"
                                class="form-select"
                                required>

                                <option value="">
                                    Seleccionar categoría
                                </option>

                                <?php foreach ($categorias as $categoria): ?>

                                    <option
                                        value="<?= (int)$categoria['id']; ?>"
                                        <?= (string)(
                                            $producto['categoria'] ?? ''
                                        ) ===
                                        (string)$categoria['id']
                                            ? 'selected'
                                            : ''; ?>>

                                        <?= htmlspecialchars(
                                            $categoria['nombre'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                Marca
                            </label>

                            <input
                                type="text"
                                name="marca"
                                class="form-control"
                                value="<?= htmlspecialchars(
                                    $producto['marca'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>">

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                Referencia
                            </label>

                            <input
                                type="text"
                                name="referencia"
                                class="form-control"
                                value="<?= htmlspecialchars(
                                    $producto['referencia'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>">

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                Cantidad
                            </label>

                            <input
                                type="number"
                                name="cantidad"
                                class="form-control"
                                min="0"
                                value="<?= (int)(
                                    $producto['cantidad'] ?? 0
                                ); ?>"
                                required>

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                Stock mínimo
                            </label>

                            <input
                                type="number"
                                name="stock_minimo"
                                class="form-control"
                                min="0"
                                value="<?= (int)(
                                    $producto['stock_minimo'] ?? 0
                                ); ?>"
                                required>

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                Ubicación
                            </label>

                            <select
                                name="ubicacion"
                                class="form-select">

                                <option value="">
                                    Seleccionar ubicación
                                </option>

                                <?php foreach ($ubicaciones as $ubicacion): ?>

                                    <?php

                                    $textoUbicacion =
                                        $ubicacion['edificio'] ?? '';

                                    if (!empty($ubicacion['piso'])) {

                                        $textoUbicacion .=
                                            ' | Piso ' .
                                            $ubicacion['piso'];

                                    }

                                    if (!empty($ubicacion['salon'])) {

                                        $textoUbicacion .=
                                            ' | ' .
                                            $ubicacion['salon'];

                                    }

                                    $ubicacionProducto =
                                        trim(
                                            $producto['ubicacion'] ?? ''
                                        );

                                    ?>

                                    <option
                                        value="<?= htmlspecialchars(
                                            $textoUbicacion,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>"
                                        <?= $ubicacionProducto ===
                                            $textoUbicacion
                                            ? 'selected'
                                            : ''; ?>>

                                        <?= htmlspecialchars(
                                            $textoUbicacion,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div class="col-12">

                            <label class="form-label">
                                Imagen
                            </label>

                            <input
                                type="file"
                                name="imagen"
                                class="form-control"
                                accept=".jpg,.jpeg,.png,.webp">

                        </div>

                    </div>

                </div>


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
                        Guardar cambios

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<?php endforeach; ?>


<!-- =========================================================
     MODAL NUEVO PRODUCTO
========================================================= -->

<?php if (tienePermiso('crear_producto')): ?>

<div
    class="modal fade"
    id="modalProducto"
    tabindex="-1"
    aria-hidden="true">

    <div class="modal-dialog modal-lg">

        <div class="modal-content">

            <form
                action="../actions/productos/guardar_producto.php"
                method="POST"
                enctype="multipart/form-data">

                <div class="modal-header bg-primary text-white">

                    <h5 class="modal-title">

                        <i class="fas fa-plus-circle"></i>
                        Nuevo Producto

                    </h5>

                    <button
                        type="button"
                        class="btn-close btn-close-white"
                        data-bs-dismiss="modal">
                    </button>

                </div>


                <div class="modal-body">

                    <div class="row g-3">

                        <div class="col-md-6">

                            <label class="form-label">
                                Código
                            </label>

                            <input
                                type="text"
                                name="codigo"
                                class="form-control"
                                placeholder="Ej: AUDIO-001"
                                required>

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                Área
                            </label>

                            <select
                                name="area"
                                class="form-select"
                                required>

                                <option
                                    value="audiovisuales"
                                    <?= $areaSeleccionada ===
                                        'audiovisuales'
                                        ? 'selected'
                                        : ''; ?>>

                                    Audiovisuales

                                </option>

                                <option
                                    value="automatizacion"
                                    <?= $areaSeleccionada ===
                                        'automatizacion'
                                        ? 'selected'
                                        : ''; ?>>

                                    Automatización

                                </option>

                            </select>

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                Producto
                            </label>

                            <input
                                type="text"
                                name="nombre"
                                class="form-control"
                                placeholder="Ej: PLC Siemens"
                                required>

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                Categoría
                            </label>

                            <select
                                name="categoria"
                                class="form-select"
                                required>

                                <option value="">
                                    Seleccionar categoría
                                </option>

                                <?php foreach ($categorias as $categoria): ?>

                                    <option
                                        value="<?= (int)$categoria['id']; ?>">

                                        <?= htmlspecialchars(
                                            $categoria['nombre'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                Marca
                            </label>

                            <input
                                type="text"
                                name="marca"
                                class="form-control"
                                placeholder="Ej: Siemens">

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                Referencia
                            </label>

                            <input
                                type="text"
                                name="referencia"
                                class="form-control"
                                placeholder="Referencia del producto">

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                Cantidad
                            </label>

                            <input
                                type="number"
                                name="cantidad"
                                class="form-control"
                                min="0"
                                value="0"
                                required>

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                Stock mínimo
                            </label>

                            <input
                                type="number"
                                name="stock_minimo"
                                class="form-control"
                                min="0"
                                value="1"
                                required>

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                Ubicación
                            </label>

                            <select
                                name="ubicacion"
                                class="form-select">

                                <option value="">
                                    Seleccionar ubicación
                                </option>

                                <?php foreach ($ubicaciones as $ubicacion): ?>

                                    <?php

                                    $textoUbicacion =
                                        $ubicacion['edificio'] ?? '';

                                    if (!empty($ubicacion['piso'])) {

                                        $textoUbicacion .=
                                            ' | Piso ' .
                                            $ubicacion['piso'];

                                    }

                                    if (!empty($ubicacion['salon'])) {

                                        $textoUbicacion .=
                                            ' | ' .
                                            $ubicacion['salon'];

                                    }

                                    ?>

                                    <option
                                        value="<?= htmlspecialchars(
                                            $textoUbicacion,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>">

                                        <?= htmlspecialchars(
                                            $textoUbicacion,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div class="col-12">

                            <label class="form-label">
                                Imagen
                            </label>

                            <input
                                type="file"
                                name="imagen"
                                class="form-control"
                                accept=".jpg,.jpeg,.png,.webp">

                        </div>

                    </div>

                </div>


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
                        Registrar producto

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<?php endif; ?>


<!-- =========================================================
     MODAL ENTRADA
========================================================= -->

<?php if (tienePermiso('entrada_stock')): ?>

<div
    class="modal fade"
    id="modalEntradaGeneral"
    tabindex="-1"
    aria-labelledby="modalEntradaGeneralLabel"
    aria-hidden="true">

    <div class="modal-dialog">

        <div class="modal-content">

            <form
                action="../actions/productos/entrada_stock.php"
                method="POST">

                <div class="modal-header bg-success text-white">

                    <h5
                        class="modal-title"
                        id="modalEntradaGeneralLabel">

                        <i class="fas fa-arrow-down"></i>

                        Entrada de stock -
                        <?= htmlspecialchars(
                            $nombreArea,
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>

                    </h5>

                    <button
                        type="button"
                        class="btn-close btn-close-white"
                        data-bs-dismiss="modal"
                        aria-label="Cerrar">
                    </button>

                </div>


                <div class="modal-body">

                    <div class="mb-3">

                        <label class="form-label">
                            Producto
                        </label>

                        <select
                            name="producto_id"
                            class="form-select"
                            required>

                            <option value="">
                                Seleccionar producto
                            </option>

                            <?php foreach ($productos as $producto): ?>

                                <option
                                    value="<?= (int)$producto['id']; ?>">

                                    <?= htmlspecialchars(
                                        ($producto['codigo'] ?? '') .
                                        ' - ' .
                                        ($producto['nombre'] ?? ''),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                    - Stock:
                                    <?= (int)(
                                        $producto['cantidad'] ?? 0
                                    ); ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="mb-3">

                        <label class="form-label">
                            Cantidad
                        </label>

                        <input
                            type="number"
                            name="cantidad"
                            class="form-control"
                            min="1"
                            required>

                    </div>


                    <input
                        type="hidden"
                        name="area"
                        value="<?= htmlspecialchars(
                            $areaSeleccionada,
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>">

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">

                        Cancelar

                    </button>

                    <button
                        type="submit"
                        class="btn btn-success">

                        <i class="fas fa-arrow-down"></i>

                        Registrar entrada

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<?php endif; ?>


<!-- =========================================================
     MODAL SALIDA
========================================================= -->

<?php if (tienePermiso('salida_stock')): ?>

<div
    class="modal fade"
    id="modalSalidaGeneral"
    tabindex="-1"
    aria-labelledby="modalSalidaGeneralLabel"
    aria-hidden="true">

    <div class="modal-dialog">

        <div class="modal-content">

            <form
                action="../actions/productos/salida_stock.php"
                method="POST">

                <div class="modal-header bg-warning">

                    <h5
                        class="modal-title"
                        id="modalSalidaGeneralLabel">

                        <i class="fas fa-arrow-up"></i>

                        Salida de stock -
                        <?= htmlspecialchars(
                            $nombreArea,
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>

                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Cerrar">
                    </button>

                </div>


                <div class="modal-body">

                    <div class="mb-3">

                        <label class="form-label">
                            Producto
                        </label>

                        <select
                            name="producto_id"
                            class="form-select"
                            required>

                            <option value="">
                                Seleccionar producto
                            </option>

                            <?php foreach ($productos as $producto): ?>

                                <option
                                    value="<?= (int)$producto['id']; ?>">

                                    <?= htmlspecialchars(
                                        ($producto['codigo'] ?? '') .
                                        ' - ' .
                                        ($producto['nombre'] ?? ''),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                    - Stock:
                                    <?= (int)(
                                        $producto['cantidad'] ?? 0
                                    ); ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="mb-3">

                        <label class="form-label">
                            Cantidad
                        </label>

                        <input
                            type="number"
                            name="cantidad"
                            class="form-control"
                            min="1"
                            required>

                    </div>


                    <input
                        type="hidden"
                        name="area"
                        value="<?= htmlspecialchars(
                            $areaSeleccionada,
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>">

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">

                        Cancelar

                    </button>

                    <button
                        type="submit"
                        class="btn btn-warning">

                        <i class="fas fa-arrow-up"></i>

                        Registrar salida

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<?php endif; ?>


<!-- =========================================================
     JAVASCRIPT
========================================================= -->
<script>
function eliminarProducto(id){
    if(typeof Swal==='undefined'){
        if(confirm('¿Desea eliminar este producto?')) window.location.href='../actions/productos/eliminar_producto.php?id='+encodeURIComponent(id);
        return;
    }
    Swal.fire({icon:'warning',title:'¿Eliminar producto?',text:'Esta acción no se puede deshacer.',showCancelButton:true,confirmButtonText:'Sí, eliminar',cancelButtonText:'Cancelar',confirmButtonColor:'#dc3545',cancelButtonColor:'#6c757d'}).then(function(r){if(r.isConfirmed)window.location.href='../actions/productos/eliminar_producto.php?id='+encodeURIComponent(id);});
}

document.addEventListener('DOMContentLoaded',function(){
    const cards=Array.from(document.querySelectorAll('.producto-card'));
    const texto=document.getElementById('filtroTexto');
    const categoria=document.getElementById('filtroCategoria');
    const estado=document.getElementById('filtroEstado');
    const stock=document.getElementById('filtroStock');
    const limpiar=document.getElementById('limpiarFiltros');
    const contador=document.getElementById('contadorProductos');
    const sinResultados=document.getElementById('sinResultados');
    function filtrar(){
        const q=(texto?.value||'').trim().toLowerCase();
        const cat=(categoria?.value||'').toLowerCase();
        const est=(estado?.value||'').toLowerCase();
        const stk=(stock?.value||'').toLowerCase();
        let visibles=0;
        cards.forEach(function(card){
            const ok=(card.dataset.texto||'').toLowerCase().includes(q)&&(!cat||card.dataset.categoria===cat)&&(!est||card.dataset.estado===est)&&(!stk||card.dataset.stock===stk);
            card.style.display=ok?'flex':'none';
            if(ok)visibles++;
        });
        if(contador)contador.textContent=visibles+' '+(visibles===1?'producto':'productos');
        if(sinResultados)sinResultados.style.display=(visibles===0&&cards.length>0)?'block':'none';
    }
    [texto,categoria,estado,stock].forEach(function(el){if(el)el.addEventListener(el.tagName==='INPUT'?'input':'change',filtrar);});
    if(limpiar)limpiar.addEventListener('click',function(){if(texto)texto.value='';if(categoria)categoria.value='';if(estado)estado.value='';if(stock)stock.value='';filtrar();});

    /* =====================================================
       VISOR DE IMÁGENES
    ===================================================== */
    const modalImagen=document.getElementById('modalVisorImagen');
    const imagenVisor=document.getElementById('imagenVisorProducto');
    const nombreVisor=document.getElementById('nombreVisorProducto');
    const codigoVisor=document.getElementById('codigoVisorProducto');

    document.querySelectorAll('.js-abrir-imagen').forEach(function(elemento){
        elemento.addEventListener('click',function(){
            const imagen=this.dataset.imagen || '../assets/uploads/productos/no-image.png';
            const nombre=this.dataset.nombre || 'Producto';
            const codigo=this.dataset.codigo || '';

            if(imagenVisor){
                imagenVisor.src=imagen;
                imagenVisor.alt=nombre;
                imagenVisor.onerror=function(){
                    this.onerror=null;
                    this.src='../assets/uploads/productos/no-image.png';
                };
            }
            if(nombreVisor) nombreVisor.textContent=nombre;
            if(codigoVisor) codigoVisor.textContent=codigo ? 'Código: '+codigo : '';

            if(modalImagen && typeof bootstrap!=='undefined'){
                bootstrap.Modal.getOrCreateInstance(modalImagen).show();
            }
        });
    });
});
</script>

<?php include("../includes/footer.php"); ?>
