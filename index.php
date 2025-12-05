<?php
// Verificar autenticación
require_once 'auth-check.php';

// Conexión a base de datos global
require_once 'conexion.php';
$pdo = conexion();

$page = $_GET['page'] ?? 'mesas';
$userInfo = getUserInfo();

$kioskPages = ['mesa'];
$isKioskMode = in_array($page, $kioskPages);

// Verificar permisos por página
switch ($page) {
    case 'usuarios':
        if (!hasPermission('usuarios', 'ver')) {
            $page = 'error-403';
        }
        break;
    case 'productos':
        if (!hasPermission('productos', 'ver')) {
            $page = 'error-403';
        }
        break;
    case 'ordenes':
        if (!hasPermission('ordenes', 'ver')) {
            $page = 'error-403';
        }
        break;
    case 'cocina':
        if (!hasPermission('cocina', 'ver')) {
            $page = 'error-403';
        }
        break;
    case 'bar':
        if (!hasPermission('bar', 'ver')) {
            $page = 'error-403';
        }
        break;
    case 'mesas':
        if (!hasPermission('mesas', 'ver')) {
            $page = 'error-403';
        }
        break;
    case 'reportes':
        if (!hasPermission('reportes', 'ver')) {
            $page = 'error-403';
        }
        break;
    case 'configuracion':
        if (!hasPermission('configuracion', 'ver')) {
            $page = 'error-403';
        }
        break;
    case 'autorizaciones':
        if (!hasPermission('configuracion', 'ver')) {
            $page = 'error-403';
        }
        break;
}

include 'views/header.php';

switch ($page) {
    case 'mesas':
        if (hasPermission('mesas', 'ver')) {
            include 'views/mesas.php';
        } else {
            include 'views/error-403.php';
        }
        break;
    case 'mesa':
        if (hasPermission('ordenes', 'crear') || hasPermission('ordenes', 'ver')) {
            include 'views/mesa.php';
        } else {
            include 'views/error-403.php';
        }
        break;
    case 'productos':
        if (hasPermission('productos', 'ver')) {
            include 'views/productos.php';
        } else {
            include 'views/error-403.php';
        }
        break;
    case 'cocina':
        if (hasPermission('cocina', 'ver')) {
            include 'views/cocina.php';
        } else {
            include 'views/error-403.php';
        }
        break;
    case 'ordenes':
        if (hasPermission('ordenes', 'ver')) {
            include 'views/ordenes.php';
        } else {
            include 'views/error-403.php';
        }
        break;
    case 'bar':
        if (hasPermission('bar', 'ver')) {
            include 'views/bar.php';
        } else {
            include 'views/error-403.php';
        }
        break;
    case 'detalleOrder':
        if (hasPermission('ordenes', 'ver')) {
            include 'views/orden_detalle.php';
        } else {
            include 'views/error-403.php';
        }
        break;
    case 'usuarios':
        if (hasPermission('usuarios', 'ver')) {
            include 'views/usuarios.php';
        } else {
            include 'views/error-403.php';
        }
        break;
    case 'reportes':
        if (hasPermission('reportes', 'ver')) {
            include 'views/reportes.php';
        } else {
            include 'views/error-403.php';
        }
        break;
    case 'configuracion':
        if (hasPermission('configuracion', 'ver')) {
            include 'views/configuracion.php';
        } else {
            include 'views/error-403.php';
        }
        break;
    case 'autorizaciones':
        if (hasPermission('configuracion', 'ver')) {
            include 'views/autorizaciones.php';
        } else {
            include 'views/error-403.php';
        }
        break;
    case 'error-403':
        include 'views/error-403.php';
        break;
    default:
        include 'views/mesas.php';
}
?>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/auth.js"></script>
</body>

</html>