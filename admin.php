<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'cajero') {
    header("Location: principal.php");
    exit;
}
include('app.php');
include('csrf.php');

$seccion = $_GET['s'] ?? 'productos';
$secciones_validas = ['productos', 'mozos', 'cajeros', 'mesas', 'config'];
if (!in_array($seccion, $secciones_validas)) $seccion = 'productos';

$ok    = $_GET['ok']    ?? '';
$error = $_GET['error'] ?? '';

// ============================================================
// PROCESAR FORMULARIOS POST
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    $accion = $_POST['accion'] ?? '';

    // ── PRODUCTOS ────────────────────────────────────────────
    if ($accion === 'nuevo_producto') {
        $tipo   = $_POST['tipo']   ?? '';
        $nombre = trim($_POST['nombre'] ?? '');
        $precio = intval($_POST['precio'] ?? 0);
        $desc   = trim($_POST['desc'] ?? '');
        $tablas = ['plato' => ['plato','nom_plato','descr_plato','precio_plato'],
                   'bebida'=> ['bebida','nom_bebida','desc_bebida','precio_bebida'],
                   'postre'=> ['postre','nom_postre','desc_postre','precio_postre']];
        if (isset($tablas[$tipo]) && $nombre && $precio > 0) {
            [$t, $cn, $cd, $cp] = $tablas[$tipo];
            $stmt = $conexion->prepare("INSERT INTO $t ($cn, $cd, $cp) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $nombre, $desc, $precio);
            $stmt->execute() ? $ok = 'producto_creado' : $error = 'error_db';
            $stmt->close();
        } else { $error = 'datos_invalidos'; }
        header("Location: admin.php?s=productos&ok=$ok&error=$error"); exit;
    }

    if ($accion === 'editar_producto') {
        $tipo   = $_POST['tipo']   ?? '';
        $id     = intval($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $precio = intval($_POST['precio'] ?? 0);
        $desc   = trim($_POST['desc'] ?? '');
        $tablas = ['plato' => ['plato','nom_plato','descr_plato','precio_plato','id_plato'],
                   'bebida'=> ['bebida','nom_bebida','desc_bebida','precio_bebida','id_bebida'],
                   'postre'=> ['postre','nom_postre','desc_postre','precio_postre','id_postre']];
        if (isset($tablas[$tipo]) && $id && $nombre && $precio > 0) {
            [$t, $cn, $cd, $cp, $ci] = $tablas[$tipo];
            $stmt = $conexion->prepare("UPDATE $t SET $cn=?, $cd=?, $cp=? WHERE $ci=?");
            $stmt->bind_param("ssii", $nombre, $desc, $precio, $id);
            $stmt->execute() ? $ok = 'producto_editado' : $error = 'error_db';
            $stmt->close();
        } else { $error = 'datos_invalidos'; }
        header("Location: admin.php?s=productos&ok=$ok&error=$error"); exit;
    }

    if ($accion === 'eliminar_producto') {
        $tipo = $_POST['tipo'] ?? '';
        $id   = intval($_POST['id'] ?? 0);
        $tablas = ['plato'=>['plato','id_plato'],'bebida'=>['bebida','id_bebida'],'postre'=>['postre','id_postre']];
        if (isset($tablas[$tipo]) && $id) {
            [$t, $ci] = $tablas[$tipo];
            $stmt = $conexion->prepare("DELETE FROM $t WHERE $ci=?");
            $stmt->bind_param("i", $id);
            $stmt->execute() ? $ok = 'producto_eliminado' : $error = 'error_db';
            $stmt->close();
        }
        header("Location: admin.php?s=productos&ok=$ok&error=$error"); exit;
    }

    // ── MOZOS ────────────────────────────────────────────────
    if ($accion === 'nuevo_mozo') {
        $nom = trim($_POST['nom'] ?? '');
        $ape = trim($_POST['ape'] ?? '');
        $usu = trim($_POST['usu'] ?? '');
        $pas = trim($_POST['pas'] ?? '');
        if ($nom && $ape && $usu && $pas) {
            $hash = password_hash($pas, PASSWORD_DEFAULT);
            $stmt = $conexion->prepare("INSERT INTO mozo (nom_mozo,ape_mozo,usu_mozo,pass_mozo) VALUES (?,?,?,?)");
            $stmt->bind_param("ssss", $nom, $ape, $usu, $hash);
            $stmt->execute() ? $ok = 'mozo_creado' : $error = 'error_db';
            $stmt->close();
        } else { $error = 'datos_invalidos'; }
        header("Location: admin.php?s=mozos&ok=$ok&error=$error"); exit;
    }

    if ($accion === 'eliminar_mozo') {
        $id = intval($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $conexion->prepare("UPDATE mozo SET estado = 'Inactivo' WHERE id_mozo = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute() ? $ok = 'mozo_desactivado' : $error = 'error_db';
            $stmt->close();
        }
        header("Location: admin.php?s=mozos&ok=$ok&error=$error"); exit;
    }

    if ($accion === 'reactivar_mozo') {
        $id = intval($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $conexion->prepare("UPDATE mozo SET estado = 'Activo' WHERE id_mozo = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute() ? $ok = 'mozo_reactivado' : $error = 'error_db';
            $stmt->close();
        }
        header("Location: admin.php?s=mozos&ok=$ok&error=$error"); exit;
    }

    if ($accion === 'cambiar_pass_mozo') {
        $id  = intval($_POST['id']  ?? 0);
        $pas = trim($_POST['pas']   ?? '');
        if ($id && strlen($pas) >= 4) {
            $hash = password_hash($pas, PASSWORD_DEFAULT);
            $stmt = $conexion->prepare("UPDATE mozo SET pass_mozo = ? WHERE id_mozo = ?");
            $stmt->bind_param("si", $hash, $id);
            $stmt->execute() ? $ok = 'pass_cambiada' : $error = 'error_db';
            $stmt->close();
        } else { $error = 'pass_corta'; }
        header("Location: admin.php?s=mozos&ok=$ok&error=$error"); exit;
    }

    // ── CAJEROS ──────────────────────────────────────────────
    if ($accion === 'nuevo_cajero') {
        $nom = trim($_POST['nom'] ?? '');
        $ape = trim($_POST['ape'] ?? '');
        $usu = trim($_POST['usu'] ?? '');
        $pas = trim($_POST['pas'] ?? '');
        if ($nom && $ape && $usu && $pas) {
            $hash = password_hash($pas, PASSWORD_DEFAULT);
            $stmt = $conexion->prepare("INSERT INTO cajero (nom_cajero,ape_cajero,usu_cajero,pass_cajero) VALUES (?,?,?,?)");
            $stmt->bind_param("ssss", $nom, $ape, $usu, $hash);
            $stmt->execute() ? $ok = 'cajero_creado' : $error = 'error_db';
            $stmt->close();
        } else { $error = 'datos_invalidos'; }
        header("Location: admin.php?s=cajeros&ok=$ok&error=$error"); exit;
    }

    // ── CONFIGURACIÓN ────────────────────────────────────────
    if ($accion === 'guardar_config') {
        $nombre_rest  = trim($_POST['cfg_nombre']      ?? '');
        $moneda       = trim($_POST['cfg_moneda']      ?? '$');
        if ($nombre_rest) {
            $pares = [
                'nombre' => $nombre_rest,
                'moneda' => $moneda ?: '$',
                // contacto_email y contacto_whatsapp son del desarrollador — no modificables por el cliente.
            ];
            foreach ($pares as $ck => $cv) {
                $cv_str = (string)$cv;
                $stmt = $conexion->prepare("INSERT INTO configuracion (clave,valor) VALUES (?,?) ON DUPLICATE KEY UPDATE valor=?");
                $stmt->bind_param("sss", $ck, $cv_str, $cv_str);
                $stmt->execute(); $stmt->close();
            }
            $ok = 'config_guardada';
        } else { $error = 'datos_invalidos'; }
        header("Location: admin.php?s=config&ok=$ok&error=$error"); exit;
    }

    if ($accion === 'subir_logo') {
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $file  = $_FILES['logo'];
            $tipos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            $info  = @getimagesize($file['tmp_name']);
            if ($info && isset($tipos[$info['mime']])) {
                if ($file['size'] <= 2 * 1024 * 1024) {
                    $ext  = $tipos[$info['mime']];
                    // Eliminar logos anteriores
                    foreach (['jpg','png','webp'] as $e) {
                        $old = __DIR__ . "/uploads/logo.$e";
                        if (file_exists($old)) @unlink($old);
                    }
                    $dest = __DIR__ . "/uploads/logo.$ext";
                    if (move_uploaded_file($file['tmp_name'], $dest)) {
                        $path = "uploads/logo.$ext";
                        $stmt = $conexion->prepare("INSERT INTO configuracion (clave,valor) VALUES ('logo_path',?) ON DUPLICATE KEY UPDATE valor=?");
                        $stmt->bind_param("ss", $path, $path);
                        $stmt->execute(); $stmt->close();
                        $ok = 'logo_subido';
                    } else { $error = 'error_upload'; }
                } else { $error = 'archivo_grande'; }
            } else { $error = 'tipo_invalido'; }
        } else { $error = 'sin_archivo'; }
        header("Location: admin.php?s=config&ok=$ok&error=$error"); exit;
    }

    if ($accion === 'subir_imagen_producto') {
        $tipo = $_POST['tipo'] ?? '';
        $id   = intval($_POST['id'] ?? 0);
        $tablas = ['plato'=>['plato','id_plato'],'bebida'=>['bebida','id_bebida'],'postre'=>['postre','id_postre']];

        if (isset($tablas[$tipo]) && $id && isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            [$tabla, $col_id] = $tablas[$tipo];
            $file  = $_FILES['imagen'];
            $tipos = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
            $info  = @getimagesize($file['tmp_name']);

            if ($info && isset($tipos[$info['mime']]) && $file['size'] <= 2 * 1024 * 1024) {
                $ext  = $tipos[$info['mime']];
                $dir  = __DIR__ . '/uploads/productos/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);

                // Eliminar imagen anterior de este producto
                foreach (['jpg','png','webp'] as $e) {
                    $old = $dir . "{$tipo}_{$id}.{$e}";
                    if (file_exists($old)) @unlink($old);
                }

                $dest = $dir . "{$tipo}_{$id}.{$ext}";
                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    $path = "uploads/productos/{$tipo}_{$id}.{$ext}";
                    $stmt = $conexion->prepare("UPDATE $tabla SET imagen=? WHERE $col_id=?");
                    $stmt->bind_param("si", $path, $id);
                    $stmt->execute(); $stmt->close();
                    $ok = 'imagen_subida';
                } else { $error = 'error_upload'; }
            } else { $error = $file['size'] > 2*1024*1024 ? 'archivo_grande' : 'tipo_invalido'; }
        } else { $error = 'datos_invalidos'; }
        header("Location: admin.php?s=productos&ok=$ok&error=$error"); exit;
    }

    if ($accion === 'activar_licencia_admin') {
        $clave_lic = trim($_POST['licencia_clave'] ?? '');
        if ($clave_lic) {
            require_once __DIR__ . '/licencia.php';
            $res_lic = validar_clave_licencia($clave_lic);
            if ($res_lic['valida']) {
                guardar_clave_licencia($clave_lic, $conexion);
                $ok = 'licencia_activada';
            } else { $error = 'clave_invalida'; }
        } else { $error = 'datos_invalidos'; }
        header("Location: admin.php?s=config&ok=$ok&error=$error"); exit;
    }

    // ── MESAS ────────────────────────────────────────────────
    if ($accion === 'agregar_mesa') {
        $zona_nueva = trim($_POST['zona'] ?? '');
        if ($zona_nueva) {
            // Buscar el primer número libre de la secuencia global (rellena huecos).
            // Ej: si existen 1,2,3,5,6 → el siguiente es 4, no 7.
            $stmt = $conexion->prepare("
                INSERT INTO mesa (id_mesa, dispo_mesa, zona)
                SELECT MIN(candidate), 'Disponible', ?
                FROM (
                    SELECT 1 AS candidate
                    UNION ALL
                    SELECT id_mesa + 1 FROM mesa
                ) AS candidatos
                WHERE candidate NOT IN (SELECT id_mesa FROM mesa)
            ");
            $stmt->bind_param("s", $zona_nueva);
            $stmt->execute() ? $ok = 'mesa_creada' : $error = 'error_db';
            $stmt->close();
        } else { $error = 'datos_invalidos'; }
        header("Location: admin.php?s=mesas&ok=$ok&error=$error"); exit;
    }

    if ($accion === 'eliminar_mesa') {
        $id = intval($_POST['id'] ?? 0);
        if ($id) {
            // Solo eliminar si está disponible
            $stmt = $conexion->prepare("DELETE FROM mesa WHERE id_mesa = ? AND dispo_mesa = 'Disponible'");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            if ($stmt->affected_rows > 0) { $ok = 'mesa_eliminada'; }
            else { $error = 'mesa_ocupada'; }
            $stmt->close();
        }
        header("Location: admin.php?s=mesas&ok=$ok&error=$error"); exit;
    }

    if ($accion === 'cambiar_zona_mesa') {
        $id   = intval($_POST['id'] ?? 0);
        $zona = trim($_POST['zona'] ?? '');
        if ($id && $zona) {
            $stmt = $conexion->prepare("UPDATE mesa SET zona = ? WHERE id_mesa = ? AND dispo_mesa = 'Disponible'");
            $stmt->bind_param("si", $zona, $id);
            $stmt->execute();
            if ($stmt->affected_rows > 0) { $ok = 'zona_cambiada'; }
            else { $error = 'mesa_ocupada'; }
            $stmt->close();
        } else { $error = 'datos_invalidos'; }
        header("Location: admin.php?s=mesas&ok=$ok&error=$error"); exit;
    }

    if ($accion === 'eliminar_cajero') {
        $id = intval($_POST['id'] ?? 0);
        if ($id && $id !== intval($_SESSION['id_usuario'])) {
            $stmt = $conexion->prepare("UPDATE cajero SET estado = 'Inactivo' WHERE id_cajero = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute() ? $ok = 'cajero_desactivado' : $error = 'error_db';
            $stmt->close();
        } else { $error = 'no_autoeliminacion'; }
        header("Location: admin.php?s=cajeros&ok=$ok&error=$error"); exit;
    }

    if ($accion === 'reactivar_cajero') {
        $id = intval($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $conexion->prepare("UPDATE cajero SET estado = 'Activo' WHERE id_cajero = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute() ? $ok = 'cajero_reactivado' : $error = 'error_db';
            $stmt->close();
        }
        header("Location: admin.php?s=cajeros&ok=$ok&error=$error"); exit;
    }

    if ($accion === 'cambiar_pass_cajero') {
        $id  = intval($_POST['id']  ?? 0);
        $pas = trim($_POST['pas']   ?? '');
        if ($id && strlen($pas) >= 4) {
            $hash = password_hash($pas, PASSWORD_DEFAULT);
            $stmt = $conexion->prepare("UPDATE cajero SET pass_cajero = ? WHERE id_cajero = ?");
            $stmt->bind_param("si", $hash, $id);
            $stmt->execute() ? $ok = 'pass_cambiada' : $error = 'error_db';
            $stmt->close();
        } else { $error = 'pass_corta'; }
        header("Location: admin.php?s=cajeros&ok=$ok&error=$error"); exit;
    }
}

// ============================================================
// CARGAR DATOS PARA MOSTRAR
// ============================================================
$platos  = $conexion->query("SELECT * FROM plato  ORDER BY nom_plato")->fetch_all(MYSQLI_ASSOC);
$bebidas = $conexion->query("SELECT * FROM bebida ORDER BY nom_bebida")->fetch_all(MYSQLI_ASSOC);
$postres = $conexion->query("SELECT * FROM postre ORDER BY nom_postre")->fetch_all(MYSQLI_ASSOC);
// Cargar todos (activos e inactivos) para mostrar en admin
$mozos   = $conexion->query("SELECT * FROM mozo   ORDER BY estado DESC, nom_mozo")->fetch_all(MYSQLI_ASSOC);
$cajeros = $conexion->query("SELECT * FROM cajero ORDER BY estado DESC, nom_cajero")->fetch_all(MYSQLI_ASSOC);
$mesas_admin    = $conexion->query("SELECT id_mesa, dispo_mesa, zona FROM mesa ORDER BY zona, id_mesa")->fetch_all(MYSQLI_ASSOC);
$zonas_existentes = [];
foreach ($mesas_admin as $m) {
    if (!in_array($m['zona'], $zonas_existentes)) $zonas_existentes[] = $m['zona'];
}
// Agrupar mesas por zona para la vista admin
$mesas_por_zona = [];
foreach ($mesas_admin as $m) $mesas_por_zona[$m['zona']][] = $m;

$mensajes_ok = [
    'producto_creado'   => 'Producto creado correctamente.',
    'producto_editado'  => 'Producto actualizado.',
    'producto_eliminado'=> 'Producto eliminado.',
    'mozo_creado'       => 'Mozo creado correctamente.',
    'mozo_desactivado'  => 'Mozo desactivado.',
    'mozo_reactivado'   => 'Mozo reactivado.',
    'cajero_creado'     => 'Cajero creado correctamente.',
    'cajero_desactivado'=> 'Cajero desactivado.',
    'cajero_reactivado' => 'Cajero reactivado.',
    'pass_cambiada'     => 'Contraseña actualizada correctamente.',
    'mesa_creada'       => 'Mesa agregada correctamente.',
    'mesa_eliminada'    => 'Mesa eliminada.',
    'zona_cambiada'     => 'Zona de la mesa actualizada.',
    'config_guardada'   => 'Configuración guardada correctamente.',
    'logo_subido'       => 'Logo actualizado correctamente.',
    'licencia_activada' => 'Licencia activada. ¡Gracias!',
];
$mensajes_error = [
    'datos_invalidos'    => 'Completá todos los campos correctamente.',
    'error_db'           => 'Error en la base de datos.',
    'no_autoeliminacion' => 'No podés desactivar tu propio usuario.',
    'mesa_ocupada'       => 'No se puede modificar una mesa que está ocupada.',
    'pass_corta'         => 'La contraseña debe tener al menos 4 caracteres.',
    'error_upload'       => 'Error al subir el archivo.',
    'archivo_grande'     => 'El archivo supera el límite de 2 MB.',
    'tipo_invalido'      => 'Solo se permiten imágenes JPG, PNG o WebP.',
    'sin_archivo'        => 'No se seleccionó ningún archivo.',
    'clave_invalida'     => 'La clave de licencia no es válida o está vencida.',
    'imagen_subida'      => 'Imagen del producto actualizada correctamente.',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración — <?= htmlspecialchars($app['nombre']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family:'Poppins',sans-serif; background:#f0f2f5; }
        .admin-card { background:white; border-radius:14px; box-shadow:0 2px 10px rgba(0,0,0,.07); }
        .admin-card .card-header { background:transparent; border-bottom:1px solid #f0f0f0; font-weight:700; padding:16px 20px; }
        .seccion-tab { border-radius:10px; font-weight:500; font-size:.9rem; }
        .seccion-tab.active { background:#0d6efd; color:white; }
        .precio-input { max-width:150px; }
        @media(max-width:576px) { .precio-input { max-width:100%; } }
    </style>
    <?php include 'pwa_head.php'; ?>
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container-fluid px-3 px-md-4 pb-5">

    <h4 class="fw-bold mb-1"><i class="bi bi-gear"></i> Administración</h4>
    <p class="text-muted small mb-4">Solo visible para cajeros</p>

    <!-- Alertas -->
    <?php if ($ok && isset($mensajes_ok[$ok])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle"></i> <?= $mensajes_ok[$ok] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php elseif ($error && isset($mensajes_error[$error])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle"></i> <?= $mensajes_error[$error] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Pestañas de sección -->
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="?s=productos" class="btn seccion-tab <?= $seccion==='productos' ? 'active' : 'btn-outline-secondary' ?>">
            <i class="bi bi-egg-fried"></i> Productos
        </a>
        <a href="?s=mozos" class="btn seccion-tab <?= $seccion==='mozos' ? 'active' : 'btn-outline-secondary' ?>">
            <i class="bi bi-person-badge"></i> Mozos
        </a>
        <a href="?s=cajeros" class="btn seccion-tab <?= $seccion==='cajeros' ? 'active' : 'btn-outline-secondary' ?>">
            <i class="bi bi-cash-register"></i> Cajeros
        </a>
        <a href="?s=mesas" class="btn seccion-tab <?= $seccion==='mesas' ? 'active' : 'btn-outline-secondary' ?>">
            <i class="bi bi-grid-3x3"></i> Mesas
        </a>
        <a href="?s=config" class="btn seccion-tab <?= $seccion==='config' ? 'active' : 'btn-outline-secondary' ?>">
            <i class="bi bi-sliders"></i> Configuración
        </a>
    </div>

    <!-- ══════════════════════════════════════════════════════
         SECCIÓN: PRODUCTOS
    ══════════════════════════════════════════════════════ -->
    <?php if ($seccion === 'productos'): ?>

    <!-- Formulario nuevo producto -->
    <div class="admin-card mb-4">
        <div class="card-header"><i class="bi bi-plus-circle text-success"></i> Nuevo producto</div>
        <div class="p-3 p-md-4">
            <form method="POST" class="row g-3">
                <?= csrf_field() ?>
                <input type="hidden" name="accion" value="nuevo_producto">
                <div class="col-12 col-sm-4 col-lg-2">
                    <label class="form-label fw-semibold">Tipo</label>
                    <select name="tipo" class="form-select" required>
                        <option value="">— elegí —</option>
                        <option value="plato">🍽 Plato</option>
                        <option value="bebida">🥤 Bebida</option>
                        <option value="postre">🍰 Postre</option>
                    </select>
                </div>
                <div class="col-12 col-sm-5 col-lg-3">
                    <label class="form-label fw-semibold">Nombre</label>
                    <input type="text" name="nombre" class="form-control" placeholder="Ej: Milanesa napolitana" required>
                </div>
                <div class="col-12 col-sm-5 col-lg-3">
                    <label class="form-label fw-semibold">Descripción <span class="text-muted fw-normal">(opcional)</span></label>
                    <input type="text" name="desc" class="form-control" placeholder="Ej: con papas fritas">
                </div>
                <div class="col-12 col-sm-3 col-lg-2">
                    <label class="form-label fw-semibold">Precio ($)</label>
                    <input type="number" name="precio" class="form-control" placeholder="0" min="1" required>
                </div>
                <div class="col-12 col-lg-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-plus-lg"></i> Agregar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Listados por tipo -->
    <?php
    $grupos = [
        ['titulo'=>'Platos',  'icono'=>'egg-fried',  'badge'=>'danger',    'items'=>$platos,  'tipo'=>'plato',  'col_id'=>'id_plato',  'col_nom'=>'nom_plato',  'col_desc'=>'descr_plato',  'col_precio'=>'precio_plato'],
        ['titulo'=>'Bebidas', 'icono'=>'cup-straw',  'badge'=>'info',      'items'=>$bebidas, 'tipo'=>'bebida', 'col_id'=>'id_bebida', 'col_nom'=>'nom_bebida', 'col_desc'=>'desc_bebida',  'col_precio'=>'precio_bebida'],
        ['titulo'=>'Postres', 'icono'=>'cake2',      'badge'=>'secondary', 'items'=>$postres, 'tipo'=>'postre', 'col_id'=>'id_postre', 'col_nom'=>'nom_postre', 'col_desc'=>'desc_postre',  'col_precio'=>'precio_postre'],
    ];
    foreach ($grupos as $g):
    ?>
    <div class="admin-card mb-4">
        <div class="card-header">
            <i class="bi bi-<?= $g['icono'] ?>"></i> <?= $g['titulo'] ?>
            <span class="badge bg-<?= $g['badge'] ?> ms-2"><?= count($g['items']) ?></span>
        </div>
        <div class="p-3">
            <?php if (empty($g['items'])): ?>
                <p class="text-muted text-center py-3">No hay <?= strtolower($g['titulo']) ?> cargados.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:52px"></th>
                            <th>Nombre</th>
                            <th class="d-none d-md-table-cell">Descripción</th>
                            <th class="text-end">Precio</th>
                            <th class="text-end" style="width:180px">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($g['items'] as $item):
                        $img = $item['imagen'] ?? '';
                    ?>
                        <tr>
                            <!-- Miniatura / subir foto -->
                            <td>
                                <label class="d-block" style="cursor:pointer" title="Cambiar foto">
                                    <?php if ($img && file_exists(__DIR__ . '/' . $img)): ?>
                                        <img src="<?= htmlspecialchars($img) ?>?v=<?= filemtime(__DIR__.'/'.$img) ?>"
                                             class="rounded" style="width:40px;height:40px;object-fit:cover;">
                                    <?php else: ?>
                                        <div class="rounded bg-light d-flex align-items-center justify-content-center"
                                             style="width:40px;height:40px;color:#aaa;">
                                            <i class="bi bi-camera"></i>
                                        </div>
                                    <?php endif; ?>
                                    <form method="POST" enctype="multipart/form-data"
                                          id="imgform_<?= $g['tipo'] ?>_<?= $item[$g['col_id']] ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="accion" value="subir_imagen_producto">
                                        <input type="hidden" name="tipo"   value="<?= $g['tipo'] ?>">
                                        <input type="hidden" name="id"     value="<?= $item[$g['col_id']] ?>">
                                        <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp"
                                               class="d-none"
                                               onchange="this.form.submit()">
                                    </form>
                                </label>
                            </td>
                            <td class="fw-semibold"><?= htmlspecialchars($item[$g['col_nom']]) ?></td>
                            <td class="text-muted small d-none d-md-table-cell"><?= htmlspecialchars($item[$g['col_desc']] ?? '') ?></td>
                            <td class="text-end">$<?= number_format($item[$g['col_precio']], 0, ',', '.') ?></td>
                            <td class="text-end">
                                <!-- Editar -->
                                <button class="btn btn-sm btn-outline-primary me-1"
                                        onclick="abrirEditar('<?= $g['tipo'] ?>',<?= $item[$g['col_id']] ?>,'<?= addslashes($item[$g['col_nom']]) ?>','<?= addslashes($item[$g['col_desc']] ?? '') ?>',<?= $item[$g['col_precio']] ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <!-- Eliminar -->
                                <form method="POST" class="d-inline"
                                      onsubmit="return confirm('¿Eliminar <?= htmlspecialchars($item[$g['col_nom']]) ?>?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="accion" value="eliminar_producto">
                                    <input type="hidden" name="tipo"   value="<?= $g['tipo'] ?>">
                                    <input type="hidden" name="id"     value="<?= $item[$g['col_id']] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Modal editar producto -->
    <div class="modal fade" id="modalEditar" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Editar producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <?= csrf_field() ?>
                        <input type="hidden" name="accion" value="editar_producto">
                        <input type="hidden" name="tipo" id="edit_tipo">
                        <input type="hidden" name="id"   id="edit_id">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nombre</label>
                            <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Descripción <span class="text-muted fw-normal">(opcional)</span></label>
                            <input type="text" name="desc" id="edit_desc" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Precio ($)</label>
                            <input type="number" name="precio" id="edit_precio" class="form-control" min="1" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         SECCIÓN: MOZOS
    ══════════════════════════════════════════════════════ -->
    <?php elseif ($seccion === 'mozos'): ?>

    <div class="row g-4">
        <div class="col-12 col-lg-5">
            <div class="admin-card">
                <div class="card-header"><i class="bi bi-plus-circle text-success"></i> Nuevo mozo</div>
                <div class="p-3 p-md-4">
                    <form method="POST" class="row g-3">
                        <?= csrf_field() ?>
                        <input type="hidden" name="accion" value="nuevo_mozo">
                        <div class="col-12 col-sm-6">
                            <label class="form-label fw-semibold">Nombre</label>
                            <input type="text" name="nom" class="form-control" placeholder="Nombre" required>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label fw-semibold">Apellido</label>
                            <input type="text" name="ape" class="form-control" placeholder="Apellido" required>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label fw-semibold">Usuario</label>
                            <input type="text" name="usu" class="form-control" placeholder="@usuario" required>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label fw-semibold">Contraseña</label>
                            <input type="text" name="pas" class="form-control" placeholder="Contraseña" required>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-person-plus"></i> Agregar mozo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-7">
            <div class="admin-card">
                <div class="card-header">
                    <i class="bi bi-people"></i> Mozos
                    <span class="badge bg-secondary ms-2"><?= count(array_filter($mozos, fn($m)=>$m['estado']==='Activo')) ?> activos</span>
                </div>
                <div class="p-3">
                    <?php if (empty($mozos)): ?>
                        <p class="text-muted text-center py-4">No hay mozos registrados.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr><th>Nombre</th><th>Usuario</th><th>Estado</th><th class="text-end">Acciones</th></tr>
                            </thead>
                            <tbody>
                            <?php foreach ($mozos as $m): ?>
                                <tr class="<?= $m['estado'] !== 'Activo' ? 'table-secondary text-muted' : '' ?>">
                                    <td class="fw-semibold"><?= htmlspecialchars($m['nom_mozo'].' '.$m['ape_mozo']) ?></td>
                                    <td class="text-muted small">@<?= htmlspecialchars($m['usu_mozo']) ?></td>
                                    <td>
                                        <?php if ($m['estado'] === 'Activo'): ?>
                                            <span class="badge bg-success">Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($m['estado'] === 'Activo'): ?>
                                        <button class="btn btn-sm btn-outline-secondary me-1"
                                                onclick="abrirCambiarPass('mozo',<?= $m['id_mozo'] ?>,'<?= htmlspecialchars(addslashes($m['nom_mozo'])) ?>')"
                                                title="Cambiar contraseña">
                                            <i class="bi bi-key"></i>
                                        </button>
                                        <form method="POST" class="d-inline"
                                              onsubmit="return confirm('¿Desactivar a <?= htmlspecialchars($m['nom_mozo']) ?>?')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="accion" value="eliminar_mozo">
                                            <input type="hidden" name="id"     value="<?= $m['id_mozo'] ?>">
                                            <button class="btn btn-sm btn-outline-danger" title="Desactivar">
                                                <i class="bi bi-person-slash"></i>
                                            </button>
                                        </form>
                                        <?php else: ?>
                                        <form method="POST" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="accion" value="reactivar_mozo">
                                            <input type="hidden" name="id"     value="<?= $m['id_mozo'] ?>">
                                            <button class="btn btn-sm btn-outline-success" title="Reactivar">
                                                <i class="bi bi-person-check"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal cambiar contraseña mozo -->
    <div class="modal fade" id="modalPassMozo" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title fw-bold"><i class="bi bi-key"></i> Cambiar contraseña</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <?= csrf_field() ?>
                        <input type="hidden" name="accion" value="cambiar_pass_mozo">
                        <input type="hidden" name="id"     id="pass_mozo_id">
                        <p class="small text-muted mb-2">Mozo: <strong id="pass_mozo_nombre"></strong></p>
                        <input type="password" name="pas" class="form-control"
                               placeholder="Nueva contraseña (mín. 4 caracteres)" required minlength="4">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-sm btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         SECCIÓN: CAJEROS
    ══════════════════════════════════════════════════════ -->
    <?php elseif ($seccion === 'cajeros'): ?>

    <div class="row g-4">
        <div class="col-12 col-lg-5">
            <div class="admin-card">
                <div class="card-header"><i class="bi bi-plus-circle text-success"></i> Nuevo cajero</div>
                <div class="p-3 p-md-4">
                    <form method="POST" class="row g-3">
                        <?= csrf_field() ?>
                        <input type="hidden" name="accion" value="nuevo_cajero">
                        <div class="col-12 col-sm-6">
                            <label class="form-label fw-semibold">Nombre</label>
                            <input type="text" name="nom" class="form-control" placeholder="Nombre" required>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label fw-semibold">Apellido</label>
                            <input type="text" name="ape" class="form-control" placeholder="Apellido" required>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label fw-semibold">Usuario</label>
                            <input type="text" name="usu" class="form-control" placeholder="@usuario" required>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label fw-semibold">Contraseña</label>
                            <input type="text" name="pas" class="form-control" placeholder="Contraseña" required>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-person-plus"></i> Agregar cajero
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-7">
            <div class="admin-card">
                <div class="card-header">
                    <i class="bi bi-people"></i> Cajeros
                    <span class="badge bg-secondary ms-2"><?= count(array_filter($cajeros, fn($c)=>$c['estado']==='Activo')) ?> activos</span>
                </div>
                <div class="p-3">
                    <?php if (empty($cajeros)): ?>
                        <p class="text-muted text-center py-4">No hay cajeros registrados.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr><th>Nombre</th><th>Usuario</th><th>Estado</th><th class="text-end">Acciones</th></tr>
                            </thead>
                            <tbody>
                            <?php foreach ($cajeros as $c): ?>
                                <tr class="<?= $c['estado'] !== 'Activo' ? 'table-secondary text-muted' : '' ?>">
                                    <td class="fw-semibold">
                                        <?= htmlspecialchars($c['nom_cajero'].' '.$c['ape_cajero']) ?>
                                        <?php if ($c['id_cajero'] == $_SESSION['id_usuario']): ?>
                                            <span class="badge bg-primary ms-1">vos</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted small">@<?= htmlspecialchars($c['usu_cajero']) ?></td>
                                    <td>
                                        <?php if ($c['estado'] === 'Activo'): ?>
                                            <span class="badge bg-success">Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($c['estado'] === 'Activo'): ?>
                                        <button class="btn btn-sm btn-outline-secondary me-1"
                                                onclick="abrirCambiarPass('cajero',<?= $c['id_cajero'] ?>,'<?= htmlspecialchars(addslashes($c['nom_cajero'])) ?>')"
                                                title="Cambiar contraseña">
                                            <i class="bi bi-key"></i>
                                        </button>
                                        <?php if ($c['id_cajero'] != $_SESSION['id_usuario']): ?>
                                        <form method="POST" class="d-inline"
                                              onsubmit="return confirm('¿Desactivar a <?= htmlspecialchars($c['nom_cajero']) ?>?')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="accion" value="eliminar_cajero">
                                            <input type="hidden" name="id"     value="<?= $c['id_cajero'] ?>">
                                            <button class="btn btn-sm btn-outline-danger" title="Desactivar">
                                                <i class="bi bi-person-slash"></i>
                                            </button>
                                        </form>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                        <?php else: ?>
                                        <form method="POST" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="accion" value="reactivar_cajero">
                                            <input type="hidden" name="id"     value="<?= $c['id_cajero'] ?>">
                                            <button class="btn btn-sm btn-outline-success" title="Reactivar">
                                                <i class="bi bi-person-check"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal cambiar contraseña cajero -->
    <div class="modal fade" id="modalPassCajero" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title fw-bold"><i class="bi bi-key"></i> Cambiar contraseña</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <?= csrf_field() ?>
                        <input type="hidden" name="accion" value="cambiar_pass_cajero">
                        <input type="hidden" name="id"     id="pass_cajero_id">
                        <p class="small text-muted mb-2">Cajero: <strong id="pass_cajero_nombre"></strong></p>
                        <input type="password" name="pas" class="form-control"
                               placeholder="Nueva contraseña (mín. 4 caracteres)" required minlength="4">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-sm btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         SECCIÓN: MESAS
    ══════════════════════════════════════════════════════ -->
    <?php elseif ($seccion === 'mesas'): ?>

    <div class="row g-4">

        <!-- Agregar mesa -->
        <div class="col-12 col-lg-4">
            <div class="admin-card">
                <div class="card-header"><i class="bi bi-plus-circle text-success"></i> Agregar mesa</div>
                <div class="p-3 p-md-4">
                    <form method="POST" class="row g-3" id="form_agregar_mesa">
                        <?= csrf_field() ?>
                        <input type="hidden" name="accion" value="agregar_mesa">

                        <?php if ($zonas_existentes): ?>
                        <!-- Selector de zona existente -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">Zona</label>
                            <select name="zona" id="sel_zona_nueva" class="form-select"
                                    onchange="toggleNuevaZona(this)" required>
                                <?php foreach ($zonas_existentes as $z): ?>
                                <option value="<?= htmlspecialchars($z) ?>"><?= htmlspecialchars($z) ?></option>
                                <?php endforeach; ?>
                                <option value="__nueva__">+ Crear nueva zona…</option>
                            </select>
                        </div>
                        <!-- Input nueva zona (oculto por defecto) -->
                        <div class="col-12" id="wrap_nueva_zona" style="display:none">
                            <label class="form-label fw-semibold">Nombre de la nueva zona</label>
                            <input type="text" id="input_nueva_zona" class="form-control"
                                   placeholder="Ej: Terraza, VIP…" maxlength="50">
                            <div class="form-text">Se creará automáticamente al agregar la mesa.</div>
                        </div>
                        <?php else: ?>
                        <!-- Sin zonas aún: texto libre directamente -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">Nombre de la zona</label>
                            <input type="text" name="zona" class="form-control"
                                   placeholder="Ej: Salón, Patio, Terraza…" required maxlength="50" autofocus>
                            <div class="form-text">Primera zona del sistema.</div>
                        </div>
                        <?php endif; ?>

                        <div class="col-12">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-plus-lg"></i> Agregar mesa
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Listado de mesas por zona -->
        <div class="col-12 col-lg-8">
            <?php if (empty($mesas_por_zona)): ?>
            <div class="admin-card p-4 text-muted text-center">No hay mesas cargadas.</div>
            <?php else: ?>
            <?php foreach ($mesas_por_zona as $nombre_zona => $mesas_z): ?>
            <div class="admin-card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-grid-3x3"></i> <?= htmlspecialchars($nombre_zona) ?></span>
                    <span class="badge bg-secondary"><?= count($mesas_z) ?> mesa<?= count($mesas_z) != 1 ? 's' : '' ?></span>
                </div>
                <div class="p-3">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Mesa N°</th>
                                    <th>Estado</th>
                                    <th>Cambiar zona</th>
                                    <th class="text-end">Eliminar</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($mesas_z as $mesa_r): ?>
                                <tr>
                                    <td class="fw-semibold">Mesa <?= $mesa_r['id_mesa'] ?></td>
                                    <td>
                                        <?php if ($mesa_r['dispo_mesa'] === 'Disponible'): ?>
                                            <span class="badge bg-success">Disponible</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Ocupada</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($mesa_r['dispo_mesa'] === 'Disponible'): ?>
                                        <form method="POST" class="d-flex gap-1">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="accion" value="cambiar_zona_mesa">
                                            <input type="hidden" name="id" value="<?= $mesa_r['id_mesa'] ?>">
                                            <select name="zona" class="form-select form-select-sm" style="max-width:150px" required>
                                                <?php foreach ($zonas_existentes as $z): ?>
                                                <option value="<?= htmlspecialchars($z) ?>"
                                                    <?= $z === $mesa_r['zona'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($z) ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-outline-primary" title="Guardar zona">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        </form>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($mesa_r['dispo_mesa'] === 'Disponible'): ?>
                                        <form method="POST" class="d-inline"
                                              onsubmit="return confirm('¿Eliminar Mesa <?= $mesa_r['id_mesa'] ?>? Esta acción no se puede deshacer.')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="accion" value="eliminar_mesa">
                                            <input type="hidden" name="id" value="<?= $mesa_r['id_mesa'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                        <?php else: ?>
                                            <span class="text-muted small" title="Desocupá la mesa antes de eliminarla">
                                                <i class="bi bi-lock"></i>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>

    <!-- ══════════════════════════════════════════════════════
         SECCIÓN: CONFIGURACIÓN
    ══════════════════════════════════════════════════════ -->
    <?php elseif ($seccion === 'config'): ?>

    <div class="row g-4">

        <!-- Datos del restaurante -->
        <div class="col-12 col-lg-6">
            <div class="admin-card">
                <div class="card-header"><i class="bi bi-shop text-primary"></i> Datos del restaurante</div>
                <div class="p-3 p-md-4">
                    <form method="POST" class="row g-3">
                        <?= csrf_field() ?>
                        <input type="hidden" name="accion" value="guardar_config">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Nombre del restaurante</label>
                            <input type="text" name="cfg_nombre" class="form-control"
                                   value="<?= htmlspecialchars($app['nombre']) ?>" required maxlength="60">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Símbolo de moneda</label>
                            <input type="text" name="cfg_moneda" class="form-control"
                                   value="<?= htmlspecialchars($app['moneda']) ?>" maxlength="4" required>
                            <div class="form-text">Ej: $, USD, €, ARS</div>
                        </div>
                        <div class="col-12"><hr class="my-0"><p class="fw-semibold mb-1 mt-1">Contacto del soporte</p></div>
                        <div class="col-12">
                            <label class="form-label">Email de contacto</label>
                            <input type="email" class="form-control" disabled
                                   value="<?= htmlspecialchars($app['contacto_email'] ?? '') ?>">
                            <div class="form-text"><i class="bi bi-lock-fill text-secondary"></i> Gestionado por el desarrollador.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">WhatsApp</label>
                            <input type="text" class="form-control" disabled
                                   value="<?= htmlspecialchars($app['contacto_whatsapp'] ?? '') ?>">
                            <div class="form-text"><i class="bi bi-lock-fill text-secondary"></i> Gestionado por el desarrollador.</div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Guardar configuración
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Logo + Licencia -->
        <div class="col-12 col-lg-6">

            <!-- Logo -->
            <div class="admin-card mb-4">
                <div class="card-header"><i class="bi bi-image text-success"></i> Logo del restaurante</div>
                <div class="p-3 p-md-4">
                    <div class="mb-3 text-center">
                        <?php if (!empty($app['logo_path']) && file_exists(__DIR__ . '/' . $app['logo_path'])): ?>
                        <img src="<?= htmlspecialchars($app['logo_path']) ?>?v=<?= time() ?>"
                             alt="Logo actual"
                             style="max-height:100px; max-width:240px; border-radius:8px; border:1px solid #eee; padding:8px; background:#f8f9fa;">
                        <?php else: ?>
                        <div class="p-4 bg-light rounded text-muted d-inline-block">
                            <i class="bi bi-image fs-1 d-block mb-1"></i>
                            <small>Sin logo cargado</small>
                        </div>
                        <?php endif; ?>
                    </div>
                    <form method="POST" enctype="multipart/form-data" class="row g-3">
                        <?= csrf_field() ?>
                        <input type="hidden" name="accion" value="subir_logo">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Subir nuevo logo</label>
                            <input type="file" name="logo" class="form-control"
                                   accept="image/jpeg,image/png,image/webp" required>
                            <div class="form-text">JPG, PNG o WebP · máximo 2 MB · se recomienda fondo transparente (PNG)</div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-upload"></i> Subir logo
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Estado de licencia -->
            <div class="admin-card">
                <div class="card-header"><i class="bi bi-key text-warning"></i> Licencia</div>
                <div class="p-3">
                    <?php $lic = $app['licencia']; ?>
                    <?php if ($lic['estado'] === 'activa'): ?>
                    <div class="alert alert-success py-2 small">
                        <i class="bi bi-check-circle-fill"></i> <strong>Licencia activa</strong>
                        <?php if ($lic['hasta']): ?>
                        <br>Válida hasta: <strong><?= htmlspecialchars($lic['hasta']) ?></strong>
                        &nbsp;·&nbsp; <?= htmlspecialchars($lic['email']) ?>
                        <?php if ($lic['dias_restantes'] !== null && $lic['dias_restantes'] <= 30): ?>
                        <br><span class="text-warning"><i class="bi bi-clock"></i> <?= $lic['dias_restantes'] ?> días para vencer</span>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <?php elseif ($lic['estado'] === 'gracia'): ?>
                    <div class="alert alert-danger py-2 small">
                        <i class="bi bi-exclamation-octagon-fill"></i> <strong>Licencia vencida — período de gracia</strong>
                        <br>Renovar antes de que se bloquee el acceso.
                    </div>
                    <?php elseif ($lic['estado'] === 'prueba'): ?>
                    <div class="alert alert-warning py-2 small">
                        <i class="bi bi-clock-fill"></i> <strong>Período de prueba</strong>
                        — <?= $lic['dias_restantes'] ?> día<?= $lic['dias_restantes'] != 1 ? 's' : '' ?> restantes
                    </div>
                    <?php else: ?>
                    <div class="alert alert-danger py-2 small">
                        <i class="bi bi-x-circle-fill"></i> <strong>Sin licencia activa</strong>
                    </div>
                    <?php endif; ?>

                    <form method="POST" class="row g-2 mt-1">
                        <?= csrf_field() ?>
                        <input type="hidden" name="accion" value="activar_licencia_admin">
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Clave de licencia</label>
                            <textarea name="licencia_clave" class="form-control font-monospace"
                                      rows="3" placeholder="Pegá tu clave aquí..."
                                      style="font-size:0.72rem;" required></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-outline-success btn-sm">
                                <i class="bi bi-check-circle"></i> Activar licencia
                            </button>
                            <a href="expirado.php" class="btn btn-outline-secondary btn-sm ms-2">
                                <i class="bi bi-info-circle"></i> Info y compra
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Backup de base de datos -->
            <div class="admin-card mt-4">
                <div class="card-header"><i class="bi bi-database-down text-info"></i> Backup de base de datos</div>
                <div class="p-3">
                    <p class="small text-muted mb-3">
                        Descarga un archivo <code>.sql</code> con todos los datos del sistema.
                        Guardalo en un lugar seguro por si necesitás restaurar.
                    </p>
                    <a href="backup_bd.php" class="btn btn-outline-info btn-sm">
                        <i class="bi bi-download"></i> Descargar backup ahora
                    </a>
                </div>
            </div>

            <!-- Actualizaciones -->
            <div class="admin-card mt-4" id="card-update">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span><i class="bi bi-arrow-repeat text-primary"></i> Actualizaciones del sistema</span>
                    <span class="badge bg-secondary" id="badge-version">v<?= APP_VERSION ?></span>
                </div>
                <div class="p-3">
                    <div id="upd-idle">
                        <p class="small text-muted mb-3">
                            Verificá si hay una nueva versión de Dony Software POS disponible.
                            Las actualizaciones incluyen mejoras, correcciones y cambios en la base de datos —
                            tus datos no se modifican.
                        </p>
                        <button class="btn btn-outline-primary btn-sm" onclick="updCheck()">
                            <i class="bi bi-cloud-check"></i> Verificar actualizaciones
                        </button>
                    </div>

                    <!-- Cargando -->
                    <div id="upd-loading" class="text-center py-3 d-none">
                        <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                        <span class="small text-muted" id="upd-loading-text">Verificando...</span>
                    </div>

                    <!-- Sin actualizaciones -->
                    <div id="upd-ok" class="d-none">
                        <div class="alert alert-success py-2 small mb-2">
                            <i class="bi bi-check-circle-fill"></i>
                            <strong>¡Estás al día!</strong> Versión <span id="upd-current"></span> es la más reciente.
                        </div>
                        <button class="btn btn-outline-secondary btn-sm" onclick="updReset()">
                            <i class="bi bi-arrow-counterclockwise"></i> Verificar de nuevo
                        </button>
                    </div>

                    <!-- Actualización disponible -->
                    <div id="upd-available" class="d-none">
                        <div class="alert alert-primary py-2 small mb-3">
                            <i class="bi bi-arrow-up-circle-fill"></i>
                            <strong>Actualización disponible</strong> —
                            v<span id="upd-current2"></span> → <strong>v<span id="upd-latest"></span></strong>
                        </div>
                        <div id="upd-changelog" class="mb-3"></div>
                        <button class="btn btn-primary btn-sm w-100" onclick="updApply()">
                            <i class="bi bi-cloud-download"></i> Descargar e instalar actualización
                        </button>
                        <p class="text-muted small mt-2 mb-0">
                            <i class="bi bi-shield-check"></i>
                            Se realiza un backup automático antes de actualizar.
                        </p>
                    </div>

                    <!-- Error -->
                    <div id="upd-error" class="d-none">
                        <div class="alert alert-warning py-2 small mb-2">
                            <i class="bi bi-exclamation-triangle"></i>
                            <span id="upd-error-msg"></span>
                        </div>
                        <button class="btn btn-outline-secondary btn-sm" onclick="updReset()">
                            <i class="bi bi-arrow-counterclockwise"></i> Reintentar
                        </button>
                    </div>

                    <!-- Aplicada -->
                    <div id="upd-done" class="d-none">
                        <div class="alert alert-success py-2 small mb-2">
                            <i class="bi bi-check-circle-fill"></i>
                            <strong>¡Actualización instalada!</strong> Recargá la página para ver los cambios.
                        </div>
                        <button class="btn btn-success btn-sm" onclick="location.reload()">
                            <i class="bi bi-arrow-clockwise"></i> Recargar ahora
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function abrirEditar(tipo, id, nombre, desc, precio) {
    document.getElementById('edit_tipo').value    = tipo;
    document.getElementById('edit_id').value      = id;
    document.getElementById('edit_nombre').value  = nombre;
    document.getElementById('edit_desc').value    = desc;
    document.getElementById('edit_precio').value  = precio;
    new bootstrap.Modal(document.getElementById('modalEditar')).show();
}

function abrirCambiarPass(rol, id, nombre) {
    if (rol === 'mozo') {
        document.getElementById('pass_mozo_id').value      = id;
        document.getElementById('pass_mozo_nombre').textContent = nombre;
        new bootstrap.Modal(document.getElementById('modalPassMozo')).show();
    } else {
        document.getElementById('pass_cajero_id').value      = id;
        document.getElementById('pass_cajero_nombre').textContent = nombre;
        new bootstrap.Modal(document.getElementById('modalPassCajero')).show();
    }
}

// ── Mesas: toggle nueva zona ─────────────────────────────────────────
function toggleNuevaZona(sel) {
    const wrap  = document.getElementById('wrap_nueva_zona');
    const input = document.getElementById('input_nueva_zona');
    if (sel.value === '__nueva__') {
        wrap.style.display = 'block';
        input.required = true;
        input.focus();
    } else {
        wrap.style.display = 'none';
        input.required = false;
        input.value = '';
    }
}

document.getElementById('form_agregar_mesa')?.addEventListener('submit', function(e) {
    const sel   = document.getElementById('sel_zona_nueva');
    const input = document.getElementById('input_nueva_zona');
    if (!sel) return; // modo sin zonas: input directo, no hace falta
    if (sel.value === '__nueva__') {
        const nombre = input?.value.trim() ?? '';
        if (!nombre) { e.preventDefault(); input.focus(); return; }
        sel.value = nombre; // el select envía el valor del input como zona
    }
});

// ── Updater ───────────────────────────────────────────────────────────
let _updDownloadUrl = '';

function updShow(id) {
    ['upd-idle','upd-loading','upd-ok','upd-available','upd-error','upd-done']
        .forEach(s => document.getElementById(s).classList.add('d-none'));
    document.getElementById(id).classList.remove('d-none');
}

function updReset() { updShow('upd-idle'); }

async function updCheck() {
    updShow('upd-loading');
    document.getElementById('upd-loading-text').textContent = 'Verificando...';
    try {
        const res  = await fetch('updater.php?accion=check');
        const data = await res.json();

        if (!data.ok) { updError(data.msg); return; }

        if (!data.has_update) {
            document.getElementById('upd-current').textContent = data.current;
            updShow('upd-ok');
            return;
        }

        // Hay actualización
        _updDownloadUrl = data.download_url;
        document.getElementById('upd-current2').textContent = data.current;
        document.getElementById('upd-latest').textContent   = data.latest;

        const cl = document.getElementById('upd-changelog');
        if (data.changelog && data.changelog.length) {
            cl.innerHTML = '<p class="small fw-semibold mb-1">Novedades:</p><ul class="small mb-0">'
                + data.changelog.map(c => `<li>${c}</li>`).join('')
                + '</ul>';
        } else {
            cl.innerHTML = '';
        }
        updShow('upd-available');

    } catch(e) {
        updError('Error de conexión. Verificá tu acceso a internet.');
    }
}

async function updApply() {
    updShow('upd-loading');
    document.getElementById('upd-loading-text').textContent = 'Descargando e instalando... no cerrés esta ventana.';

    const csrf = document.querySelector('input[name="_csrf"]')?.value ?? '';
    const body = new URLSearchParams({ download_url: _updDownloadUrl, _csrf: csrf });

    try {
        const res  = await fetch('updater.php?accion=apply', { method: 'POST', body });
        const data = await res.json();
        if (data.ok) { updShow('upd-done'); }
        else { updError(data.msg); }
    } catch(e) {
        updError('Error al aplicar la actualización. Contactá al soporte.');
    }
}

function updError(msg) {
    document.getElementById('upd-error-msg').textContent = msg;
    updShow('upd-error');
}
</script>
</body>
</html>