<?php
// Verificar si ya hay una sesión activa antes de iniciar una nueva
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include_once 'auth-check.php';
include_once 'includes/ConfiguracionSistema.php';
include_once 'conexion.php';

// Verificar que el usuario tenga permisos de administrador para configuración
if (!hasPermission('configuracion', 'editar')) {
    // Si no tiene permisos, mostrar página de error 403
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Acceso Denegado - Kalli Jaguar POS</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    </head>
    <body class="bg-slate-900 text-white min-h-screen">
        <?php include 'views/navbar.php'; ?>
        
        <div class="container mx-auto px-4 py-8">
            <div class="max-w-2xl mx-auto text-center">
                <div class="bg-red-900/20 border border-red-500 rounded-2xl p-8 mb-6">
                    <i class="bi bi-shield-exclamation text-6xl text-red-400 mb-4"></i>
                    <h1 class="text-3xl font-bold text-red-400 mb-4">Acceso Denegado</h1>
                    <p class="text-slate-300 mb-6">No tienes permisos para acceder a la configuración del sistema.</p>
                    <p class="text-slate-400 text-sm mb-6">Solo los administradores pueden modificar la configuración del sistema.</p>
                    <div class="flex justify-center space-x-4">
                        <a href="index.php" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold transition-colors">
                            <i class="bi bi-house mr-2"></i>Volver al Inicio
                        </a>
                        <button onclick="history.back()" class="bg-slate-600 hover:bg-slate-700 text-white px-6 py-3 rounded-xl font-semibold transition-colors">
                            <i class="bi bi-arrow-left mr-2"></i>Regresar
                        </button>
                    </div>
                </div>
                
                <!-- Información del usuario actual -->
                <?php $user = getUserInfo(); ?>
                <div class="bg-slate-800 rounded-xl p-4 text-sm">
                    <p class="text-slate-400">Usuario actual: <span class="text-white"><?= htmlspecialchars($user['username']) ?></span></p>
                    <p class="text-slate-400">Rol: <span class="text-white capitalize"><?= htmlspecialchars($user['rol']) ?></span></p>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Obtener conexión a la base de datos
$pdo = conexion();
$config = new ConfiguracionSistema($pdo);
$config_sms = $config->obtenerTodasConfiguraciones();

// Obtener información del sistema
$info_sistema = [
    'restaurante_nombre' => $config->obtener('empresa_nombre', 'Restaurante POS'),
    'version' => '2.0.0',
    'jwt_expiracion' => '24',
    'php_version' => phpversion(),
    'mysql_version' => $pdo->query('SELECT VERSION()')->fetchColumn()
];

$sesiones_activas = [];
?>

<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-white">Configuración del Sistema</h1>
            <p class="text-slate-400 mt-2">Configura los parámetros de funcionamiento del sistema POS</p>
        </div>
    </div>

    <!-- Pestañas de Configuración -->
    <div class="bg-slate-800 rounded-2xl shadow-2xl border border-slate-600 mb-6">
        <div class="flex border-b border-slate-600">
            <button onclick="cambiarTab('email')" id="tab-email" class="tab-button px-6 py-4 text-white font-semibold border-b-2 border-blue-500 bg-slate-700">
                <i class="bi bi-envelope mr-2"></i>Configuración Email
            </button>
            <button onclick="cambiarTab('empresa')" id="tab-empresa" class="tab-button px-6 py-4 text-slate-400 font-semibold border-b-2 border-transparent hover:text-white hover:bg-slate-700 transition-colors">
                <i class="bi bi-building mr-2"></i>Información Empresa
            </button>
            <button onclick="cambiarTab('impresoras')" id="tab-impresoras" class="tab-button px-6 py-4 text-slate-400 font-semibold border-b-2 border-transparent hover:text-white hover:bg-slate-700 transition-colors">
                <i class="bi bi-printer mr-2"></i>Impresoras Térmicas
            </button>
            <button onclick="cambiarTab('sistema')" id="tab-sistema" class="tab-button px-6 py-4 text-slate-400 font-semibold border-b-2 border-transparent hover:text-white hover:bg-slate-700 transition-colors">
                <i class="bi bi-gear mr-2"></i>Sistema
            </button>
        </div>

        <!-- Contenido de Pestañas -->
        <div class="p-6">

            <!-- Configuración de Email -->
            <div id="content-email" class="tab-content">
                <h3 class="text-xl font-bold text-white mb-4">
                    <i class="bi bi-envelope mr-2"></i>Configuración de Email para Autorizaciones
                </h3>
                <p class="text-slate-300 mb-6">Configura los emails que recibirán los códigos PIN para autorizar cancelaciones de productos.</p>

                <form id="form-email" class="space-y-6">
                    <input type="hidden" name="accion" value="actualizar_email">

                    <!-- Configuración de Email Principal -->
                    <div class="bg-slate-700 rounded-xl p-6 mb-6">
                        <h4 class="text-lg font-semibold text-white mb-4">
                            <i class="bi bi-envelope-fill mr-2"></i>Emails de Administradores
                        </h4>
                        <p class="text-slate-300 mb-4 text-sm">Los códigos PIN se enviarán por email a estos administradores</p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">
                                    <i class="bi bi-envelope mr-1"></i>Email Administrador Principal *
                                </label>
                                <input type="email" name="admin_email_1"
                                    value="<?= htmlspecialchars($config_sms['admin_email_1'] ?? '') ?>"
                                    class="w-full px-4 py-3 bg-slate-900 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="admin@restaurant.com"
                                    required>
                                <small class="text-slate-400">Email principal para recibir códigos PIN</small>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">
                                    <i class="bi bi-envelope mr-1"></i>Email Administrador Secundario
                                </label>
                                <input type="email" name="admin_email_2"
                                    value="<?= htmlspecialchars($config_sms['admin_email_2'] ?? '') ?>"
                                    class="w-full px-4 py-3 bg-slate-900 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="admin2@restaurant.com">
                                <small class="text-slate-400">Email secundario (opcional)</small>
                            </div>
                        </div>

                        <div class="flex items-center space-x-3">
                            <label class="block text-sm font-medium text-slate-300">
                                <i class="bi bi-toggle-on mr-1"></i>Sistema de Email Habilitado
                            </label>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="email_habilitado" class="sr-only peer"
                                    <?= ($config_sms['email_habilitado'] == '1') ? 'checked' : '' ?>>
                                <div class="w-11 h-6 bg-slate-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                    </div>

                    <!-- Configuración del Servidor de Email -->
                    <div class="bg-slate-700 rounded-xl p-6 mb-6">
                        <h4 class="text-lg font-semibold text-white mb-4">
                            <i class="bi bi-server mr-2"></i>Configuración del Servidor de Email
                        </h4>
                        <p class="text-slate-300 mb-4 text-sm">Configura el servidor que enviará los emails</p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">
                                    <i class="bi bi-person mr-1"></i>Email Remitente *
                                </label>
                                <input type="email" name="email_from"
                                    value="<?= htmlspecialchars($config_sms['email_from'] ?? 'no-reply@restaurant.com') ?>"
                                    class="w-full px-4 py-3 bg-slate-900 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="no-reply@turestaurante.com"
                                    required>
                                <small class="text-slate-400">Email que aparecerá como remitente</small>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">
                                    <i class="bi bi-tag mr-1"></i>Nombre del Remitente *
                                </label>
                                <input type="text" name="email_from_name"
                                    value="<?= htmlspecialchars($config_sms['email_from_name'] ?? 'Sistema POS') ?>"
                                    class="w-full px-4 py-3 bg-slate-900 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="Sistema POS Restaurant"
                                    required>
                                <small class="text-slate-400">Nombre que aparecerá como remitente</small>
                            </div>
                        </div>

                        <div class="border-t border-slate-600 pt-4">
                            <div class="flex items-center space-x-3 mb-4">
                                <label class="block text-sm font-medium text-slate-300">
                                    <i class="bi bi-gear mr-1"></i>Usar SMTP Personalizado
                                </label>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="use_smtp" id="use_smtp_toggle" class="sr-only peer"
                                        <?= ($config_sms['use_smtp'] == '1') ? 'checked' : '' ?>>
                                    <div class="w-11 h-6 bg-slate-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                                <small class="text-slate-400">Desactivar para usar el email del hosting</small>
                            </div>

                            <div id="smtp_config" class="grid grid-cols-1 md:grid-cols-2 gap-4" style="display: <?= ($config_sms['use_smtp'] == '1') ? 'grid' : 'none' ?>">
                                <div>
                                    <label class="block text-sm font-medium text-slate-300 mb-2">
                                        <i class="bi bi-server mr-1"></i>Servidor SMTP
                                    </label>
                                    <input type="text" name="smtp_host"
                                        value="<?= htmlspecialchars($config_sms['smtp_host'] ?? 'smtp.gmail.com') ?>"
                                        class="w-full px-4 py-3 bg-slate-900 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="smtp.gmail.com">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-300 mb-2">
                                        <i class="bi bi-plug mr-1"></i>Puerto SMTP
                                    </label>
                                    <input type="number" name="smtp_port"
                                        value="<?= htmlspecialchars($config_sms['smtp_port'] ?? '587') ?>"
                                        class="w-full px-4 py-3 bg-slate-900 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="587">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-300 mb-2">
                                        <i class="bi bi-person-badge mr-1"></i>Usuario SMTP
                                    </label>
                                    <input type="email" name="smtp_username"
                                        value="<?= htmlspecialchars($config_sms['smtp_username'] ?? '') ?>"
                                        class="w-full px-4 py-3 bg-slate-900 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="usuario@gmail.com">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-300 mb-2">
                                        <i class="bi bi-key mr-1"></i>Contraseña SMTP
                                    </label>
                                    <input type="password" name="smtp_password"
                                        value="<?= htmlspecialchars($config_sms['smtp_password'] ?? '') ?>"
                                        class="w-full px-4 py-3 bg-slate-900 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="•••••••••••">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Configuración General -->
                    <div class="bg-slate-700 rounded-xl p-6 mb-6">
                        <h4 class="text-lg font-semibold text-white mb-4">
                            <i class="bi bi-gear-fill mr-2"></i>Configuración General
                        </h4>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">
                                    <i class="bi bi-clock mr-1"></i>Tiempo de Expiración (minutos)
                                </label>
                                <input type="number" name="pin_expiracion" min="1" max="60" step="1"
                                    value="<?= intval($config_sms['email_pin_expiracion'] ?? 300) / 60 ?>"
                                    class="w-full px-4 py-3 bg-slate-900 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <small class="text-slate-400">Tiempo en minutos que será válido el código PIN</small>
                            </div>

                            <div class="flex items-center space-x-3">
                                <label class="block text-sm font-medium text-slate-300">
                                    <i class="bi bi-bug mr-1"></i>Modo de Prueba
                                </label>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="usar_modo_prueba" class="sr-only peer"
                                        <?= ($config_sms['usar_modo_prueba'] == '1') ? 'checked' : '' ?>>
                                    <div class="w-11 h-6 bg-slate-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                                <small class="text-slate-400">Los emails serán simulados en lugar de enviados realmente</small>
                            </div>
                        </div>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="flex justify-between items-center">
                        <button type="button" onclick="probarEmail()" class="bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-xl font-semibold transition-colors flex items-center">
                            <i class="bi bi-envelope-check mr-2"></i>Probar Email
                        </button>

                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-xl font-semibold transition-colors flex items-center">
                            <i class="bi bi-check-circle mr-2"></i>Guardar Configuración
                        </button>
                    </div>
                </form>
            </div>

            <!-- Configuración Empresa -->
            <div id="content-empresa" class="tab-content hidden">
                <h3 class="text-xl font-bold text-white mb-4">
                    <i class="bi bi-building mr-2"></i>Información de la Empresa
                </h3>

                <form id="form-empresa" class="space-y-6">
                    <input type="hidden" name="accion" value="actualizar_empresa">

                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">
                            <i class="bi bi-shop mr-1"></i>Nombre de la Empresa
                        </label>
                        <input type="text" name="empresa_nombre"
                            value="<?= htmlspecialchars($config_sms['empresa_nombre'] ?? 'Restaurante POS') ?>"
                            class="w-full px-4 py-3 bg-slate-900 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">
                            <i class="bi bi-geo-alt mr-1"></i>Dirección
                        </label>
                        <textarea name="empresa_direccion" rows="3"
                            class="w-full px-4 py-3 bg-slate-900 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Dirección completa de la empresa"><?= htmlspecialchars($config_sms['empresa_direccion'] ?? '') ?></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">
                            <i class="bi bi-telephone mr-1"></i>Teléfono
                        </label>
                        <input type="tel" name="empresa_telefono"
                            value="<?= htmlspecialchars($config_sms['empresa_telefono'] ?? '') ?>"
                            class="w-full px-4 py-3 bg-slate-900 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-xl font-semibold transition-colors">
                            <i class="bi bi-check-circle mr-2"></i>Guardar Información
                        </button>
                    </div>
                </form>
            </div>

            <!-- Configuración de Impresoras Térmicas -->
            <div id="content-impresoras" class="tab-content hidden">
                <h3 class="text-xl font-bold text-white mb-4">
                    <i class="bi bi-printer mr-2"></i>Configuración de Impresoras Térmicas
                </h3>
                <p class="text-slate-300 mb-6">Configura las impresoras térmicas para impresión automática de tickets.</p>

                <form id="form-impresoras" class="space-y-6">
                    <input type="hidden" name="accion" value="actualizar_impresoras">

                    <!-- Configuración General de Impresión Térmica -->
                    <div class="bg-slate-700 rounded-xl p-6 mb-6">
                        <h4 class="text-lg font-semibold text-white mb-4">
                            <i class="bi bi-printer-fill mr-2"></i>Configuración General
                        </h4>
                        
                        <div class="space-y-6">
                            <!-- Activar impresión automática -->
                            <div class="flex items-center justify-between p-4 bg-slate-800 rounded-lg">
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-slate-300 mb-1">
                                        <i class="bi bi-lightning mr-1"></i>Impresión Automática Térmica
                                    </label>
                                    <p class="text-slate-400 text-sm">Envía automáticamente los tickets a la impresora térmica configurada</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="impresion_automatica" id="impresion_automatica" class="sr-only peer"
                                        <?= ($config_sms['impresion_automatica'] ?? '0') == '1' ? 'checked' : '' ?>>
                                    <div class="w-11 h-6 bg-slate-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>

                            <!-- Configuración de la impresora -->
                            <div id="config_impresora" class="space-y-4" style="display: <?= ($config_sms['impresion_automatica'] ?? '0') == '1' ? 'block' : 'none' ?>">
                                
                                <!-- Método de impresión -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-300 mb-2">
                                            <i class="bi bi-gear mr-1"></i>Método de Impresión
                                        </label>
                                        <select name="metodo_impresion" id="metodo_impresion" 
                                            class="w-full px-4 py-3 bg-slate-900 border border-slate-600 rounded-xl text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            <option value="local" <?= ($config_sms['metodo_impresion'] ?? 'local') == 'local' ? 'selected' : '' ?>>Impresora Local (USB/LAN)</option>
                                            <option value="compartida" <?= ($config_sms['metodo_impresion'] ?? 'local') == 'compartida' ? 'selected' : '' ?>>Impresora Compartida</option>
                                            <option value="cups" <?= ($config_sms['metodo_impresion'] ?? 'local') == 'cups' ? 'selected' : '' ?>>Sistema CUPS</option>
                                        </select>
                                        <small class="text-slate-400">Selecciona cómo está conectada tu impresora térmica</small>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-slate-300 mb-2">
                                            <i class="bi bi-printer mr-1"></i>Nombre de la Impresora
                                        </label>
                                        <input type="text" name="nombre_impresora"
                                            value="<?= htmlspecialchars($config_sms['nombre_impresora'] ?? '') ?>"
                                            class="w-full px-4 py-3 bg-slate-900 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            placeholder="ThermalPrinter80mm">
                                        <small class="text-slate-400">Nombre de la impresora en el sistema</small>
                                    </div>
                                </div>

                                <!-- Configuración de conexión -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-300 mb-2">
                                            <i class="bi bi-hdd-network mr-1"></i>IP de la Impresora (Si es LAN)
                                        </label>
                                        <input type="text" name="ip_impresora"
                                            value="<?= htmlspecialchars($config_sms['ip_impresora'] ?? '') ?>"
                                            class="w-full px-4 py-3 bg-slate-900 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            placeholder="192.168.1.100">
                                        <small class="text-slate-400">Solo si la impresora está en red</small>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-slate-300 mb-2">
                                            <i class="bi bi-plug mr-1"></i>Puerto (Si es LAN)
                                        </label>
                                        <input type="number" name="puerto_impresora"
                                            value="<?= htmlspecialchars($config_sms['puerto_impresora'] ?? '9100') ?>"
                                            class="w-full px-4 py-3 bg-slate-900 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            placeholder="9100">
                                        <small class="text-slate-400">Puerto para impresoras de red (típicamente 9100)</small>
                                    </div>
                                </div>

                                <!-- Configuración de formato -->
                                <div class="bg-slate-800 rounded-lg p-4">
                                    <h5 class="text-white font-semibold mb-3">
                                        <i class="bi bi-file-earmark-text mr-2"></i>Configuración de Formato
                                    </h5>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-slate-300 mb-2">Ancho de Papel (mm)</label>
                                            <input type="number" name="ancho_papel"
                                                value="<?= htmlspecialchars($config_sms['ancho_papel'] ?? '80') ?>"
                                                class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-lg text-white"
                                                placeholder="80">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-slate-300 mb-2">Copias por Ticket</label>
                                            <input type="number" name="copias_ticket"
                                                value="<?= htmlspecialchars($config_sms['copias_ticket'] ?? '1') ?>"
                                                min="1" max="5"
                                                class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-lg text-white"
                                                placeholder="1">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-slate-300 mb-2">Corte Automático</label>
                                            <select name="corte_automatico" 
                                                class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-lg text-white">
                                                <option value="1" <?= ($config_sms['corte_automatico'] ?? '1') == '1' ? 'selected' : '' ?>>Activado</option>
                                                <option value="0" <?= ($config_sms['corte_automatico'] ?? '1') == '0' ? 'selected' : '' ?>>Desactivado</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- 🔥 NUEVA SECCIÓN: Configuración de Logo -->
                                <div class="bg-slate-800 rounded-lg p-4">
                                    <h5 class="text-white font-semibold mb-3">
                                        <i class="bi bi-image mr-2"></i>Logo del Ticket
                                    </h5>
                                    <div class="space-y-4">
                                        <!-- Activar/Desactivar logo -->
                                        <div class="flex items-center justify-between p-3 bg-slate-900 rounded-lg">
                                            <div class="flex-1">
                                                <label class="block text-sm font-medium text-slate-300 mb-1">
                                                    <i class="bi bi-image mr-1"></i>Mostrar Logo en Tickets
                                                </label>
                                                <p class="text-slate-400 text-xs">Incluir imagen del logo en la parte superior de los tickets</p>
                                            </div>
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" name="logo_activado" id="logo_activado" class="sr-only peer"
                                                    <?= ($config_sms['logo_activado'] ?? '1') == '1' ? 'checked' : '' ?>>
                                                <div class="w-11 h-6 bg-slate-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                            </label>
                                        </div>

                                        <!-- Selector de imagen -->
                                        <div id="config_logo" style="display: <?= ($config_sms['logo_activado'] ?? '1') == '1' ? 'block' : 'none' ?>">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-sm font-medium text-slate-300 mb-2">
                                                        <i class="bi bi-file-image mr-1"></i>Imagen del Logo
                                                    </label>
                                                    <select name="logo_imagen" id="logo_imagen" 
                                                        class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-lg text-white focus:ring-2 focus:ring-blue-500">
                                                        <option value="">Seleccionar imagen...</option>
                                                        <?php
                                                        // Buscar imágenes en la carpeta assets/img/
                                                        $rutaImagenes = './assets/img/';
                                                        $imagenActual = $config_sms['logo_imagen'] ?? 'LogoBlack.png';
                                                        
                                                        if (is_dir($rutaImagenes)) {
                                                            $archivos = scandir($rutaImagenes);
                                                            foreach ($archivos as $archivo) {
                                                                if (in_array(strtolower(pathinfo($archivo, PATHINFO_EXTENSION)), ['png', 'jpg', 'jpeg', 'gif'])) {
                                                                    $selected = ($archivo == $imagenActual) ? 'selected' : '';
                                                                    echo "<option value=\"$archivo\" $selected>$archivo</option>";
                                                                }
                                                            }
                                                        }
                                                        ?>
                                                    </select>
                                                    <small class="text-slate-400">Selecciona una imagen de la carpeta assets/img/</small>
                                                </div>
                                                
                                                <div>
                                                    <label class="block text-sm font-medium text-slate-300 mb-2">
                                                        <i class="bi bi-arrows-angle-expand mr-1"></i>Tamaño del Logo
                                                    </label>
                                                    <select name="logo_tamaño" 
                                                        class="w-full px-3 py-2 bg-slate-900 border border-slate-600 rounded-lg text-white">
                                                        <option value="pequeño" <?= ($config_sms['logo_tamaño'] ?? 'grande') == 'pequeño' ? 'selected' : '' ?>>Pequeño (120px)</option>
                                                        <option value="mediano" <?= ($config_sms['logo_tamaño'] ?? 'grande') == 'mediano' ? 'selected' : '' ?>>Mediano (240px)</option>
                                                        <option value="grande" <?= ($config_sms['logo_tamaño'] ?? 'grande') == 'grande' ? 'selected' : '' ?>>Grande (360px)</option>
                                                    </select>
                                                    <small class="text-slate-400">Tamaño de la imagen en el ticket</small>
                                                </div>
                                            </div>

                                            <!-- Vista previa del logo -->
                                            <div class="mt-4">
                                                <label class="block text-sm font-medium text-slate-300 mb-2">
                                                    <i class="bi bi-eye mr-1"></i>Vista Previa
                                                </label>
                                                <div id="logo_preview" class="bg-slate-900 border border-slate-600 rounded-lg p-4 text-center">
                                                    <?php 
                                                    $imagenPreview = $config_sms['logo_imagen'] ?? 'LogoBlack.png';
                                                    if (file_exists("./assets/img/$imagenPreview")): 
                                                    ?>
                                                        <img src="./assets/img/<?= htmlspecialchars($imagenPreview) ?>" 
                                                             alt="Vista previa del logo" 
                                                             class="max-w-full max-h-32 mx-auto rounded"
                                                             id="img_preview">
                                                        <p class="text-slate-400 text-xs mt-2">Vista previa: <?= htmlspecialchars($imagenPreview) ?></p>
                                                    <?php else: ?>
                                                        <div class="text-slate-400">
                                                            <i class="bi bi-image text-4xl mb-2"></i>
                                                            <p class="text-sm">Selecciona una imagen para ver la vista previa</p>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <!-- Botón de prueba -->
                                            <div class="mt-4">
                                                <button type="button" onclick="probarLogoTicket()" 
                                                    class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-semibold transition-colors text-sm">
                                                    <i class="bi bi-printer mr-2"></i>Probar Logo en Ticket
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detección automática de impresoras -->
                    <div class="bg-slate-700 rounded-xl p-6 mb-6">
                        <h4 class="text-lg font-semibold text-white mb-4">
                            <i class="bi bi-search mr-2"></i>Detección de Impresoras
                        </h4>
                        
                        <div class="space-y-4">
                            <div class="flex flex-col sm:flex-row gap-3">
                                <button type="button" onclick="detectarImpresoras()" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold transition-colors flex items-center justify-center">
                                    <i class="bi bi-search mr-2"></i>Detectar Impresoras
                                </button>
                                <button type="button" onclick="probarImpresion()" 
                                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-xl font-semibold transition-colors flex items-center justify-center">
                                    <i class="bi bi-printer mr-2"></i>Probar Impresión
                                </button>
                            </div>
                            
                            <div id="resultado_deteccion" class="bg-slate-800 rounded-lg p-4 hidden">
                                <p class="text-slate-300 mb-2">Impresoras detectadas:</p>
                                <div id="lista_impresoras"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Botones de acción -->
                    <div class="flex justify-between items-center">
                        <div class="flex space-x-4">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-xl font-semibold transition-colors">
                                <i class="bi bi-check-circle mr-2"></i>Guardar Configuración
                            </button>
                            <button type="button" onclick="resetearConfigImpresoras()" 
                                class="bg-slate-600 hover:bg-slate-700 text-white px-6 py-3 rounded-xl font-semibold transition-colors">
                                <i class="bi bi-arrow-clockwise mr-2"></i>Resetear
                            </button>
                        </div>
                        
                        <div class="text-slate-400 text-sm">
                            <i class="bi bi-info-circle mr-1"></i>La configuración se aplica inmediatamente
                        </div>
                    </div>
                </form>
            </div>

            <!-- Información del Sistema -->
            <div id="content-sistema" class="tab-content hidden">
                <h3 class="text-xl font-bold text-white mb-6">
                    <i class="bi bi-info-circle mr-2"></i>Información del Sistema
                </h3>

                <!-- Información General del Sistema -->
                <div class="bg-slate-700 rounded-xl p-6 mb-6">
                    <h4 class="text-lg font-semibold text-white mb-4">
                        <i class="bi bi-gear-fill mr-2"></i>Información General
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="bg-slate-800 p-4 rounded-lg">
                            <p class="text-slate-300 text-sm">Restaurante</p>
                            <p class="text-white font-semibold"><?= htmlspecialchars($info_sistema['restaurante_nombre']) ?></p>
                        </div>
                        <div class="bg-slate-800 p-4 rounded-lg">
                            <p class="text-slate-300 text-sm">Versión del Sistema</p>
                            <p class="text-white font-semibold">v<?= $info_sistema['version'] ?></p>
                        </div>
                        <div class="bg-slate-800 p-4 rounded-lg">
                            <p class="text-slate-300 text-sm">Expiración JWT</p>
                            <p class="text-white font-semibold"><?= $info_sistema['jwt_expiracion'] ?> horas</p>
                        </div>
                        <div class="bg-slate-800 p-4 rounded-lg">
                            <p class="text-slate-300 text-sm">PHP Version</p>
                            <p class="text-white font-semibold"><?= $info_sistema['php_version'] ?></p>
                        </div>
                    </div>
                </div>

                <!-- Sesiones Activas -->
                <div class="bg-slate-700 rounded-xl p-6 mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-lg font-semibold text-white">
                            <i class="bi bi-person-check mr-2"></i>Sesiones Activas (<?= count($sesiones_activas) ?>)
                        </h4>
                        <div class="flex gap-2">
                            <button onclick="actualizarSesiones()" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm transition-colors">
                                <i class="bi bi-arrow-clockwise mr-1"></i>Actualizar
                            </button>
                            <button onclick="limpiarSesionesExpiradas()" class="bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-1 rounded text-sm transition-colors">
                                <i class="bi bi-trash mr-1"></i>Limpiar Expiradas
                            </button>
                        </div>
                    </div>

                    <?php if (count($sesiones_activas) > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-600">
                                <thead class="bg-slate-800">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Usuario</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">IP</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Dispositivo</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Creada</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Expira</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-slate-700 divide-y divide-slate-600">
                                    <?php foreach ($sesiones_activas as $sesion): ?>
                                        <tr>
                                            <td class="px-4 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-white">
                                                    <?= htmlspecialchars($sesion['nombre'] . ' ' . $sesion['apellidos']) ?>
                                                </div>
                                                <div class="text-sm text-slate-400">
                                                    @<?= htmlspecialchars($sesion['username']) ?>
                                                    <span class="ml-2 px-2 py-1 text-xs rounded-full bg-blue-600 text-white">
                                                        <?= ucfirst($sesion['role']) ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm text-white">
                                                <?= htmlspecialchars($sesion['ip_address']) ?>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm text-white">
                                                Navegador
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm text-white">
                                                <?= date('d/m/Y H:i', strtotime($sesion['creado_en'])) ?>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm text-white">
                                                <?= date('d/m/Y H:i', strtotime($sesion['expires_at'])) ?>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                                                <button onclick="revocarSesion('<?= $sesion['token_jti'] ?>', '<?= htmlspecialchars($sesion['nombre']) ?>')"
                                                    class="text-red-400 hover:text-red-300 transition-colors">
                                                    <i class="bi bi-x-circle mr-1"></i>Revocar
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 text-slate-400">
                            <i class="bi bi-person-x text-4xl mb-3"></i>
                            <p>No hay sesiones activas</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Herramientas de Mantenimiento -->
                <div class="bg-slate-700 rounded-xl p-6">
                    <h4 class="text-lg font-semibold text-white mb-4">
                        <i class="bi bi-tools mr-2"></i>Herramientas de Mantenimiento
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <button onclick="limpiarCache()" class="bg-purple-600 hover:bg-purple-700 text-white p-4 rounded-lg transition-colors">
                            <i class="bi bi-arrow-clockwise text-2xl mb-2"></i>
                            <p class="font-semibold">Limpiar Cache</p>
                            <p class="text-sm text-purple-200">Sistema y configuración</p>
                        </button>

                        <button onclick="optimizarBD()" class="bg-green-600 hover:bg-green-700 text-white p-4 rounded-lg transition-colors">
                            <i class="bi bi-speedometer2 text-2xl mb-2"></i>
                            <p class="font-semibold">Optimizar BD</p>
                            <p class="text-sm text-green-200">Mejorar rendimiento</p>
                        </button>

                        <button onclick="respaldarBD()" class="bg-blue-600 hover:bg-blue-700 text-white p-4 rounded-lg transition-colors">
                            <i class="bi bi-download text-2xl mb-2"></i>
                            <p class="font-semibold">Respaldar BD</p>
                            <p class="text-sm text-blue-200">Crear copia seguridad</p>
                        </button>

                        <button onclick="limpiarTodo()" class="bg-red-600 hover:bg-red-700 text-white p-4 rounded-lg transition-colors">
                            <i class="bi bi-trash text-2xl mb-2"></i>
                            <p class="font-semibold">Limpiar Todo</p>
                            <p class="text-sm text-red-200">Cache y sesiones</p>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Estado del Sistema -->
    <div class="bg-slate-800 rounded-2xl shadow-2xl border border-slate-600">
        <div class="p-6">
            <h3 class="text-xl font-bold text-white mb-4">
                <i class="bi bi-info-circle mr-2"></i>Estado del Sistema
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-slate-700 rounded-xl p-4 text-center">
                    <i class="bi bi-envelope-fill text-3xl text-blue-400 mb-2"></i>
                    <h4 class="text-white font-semibold mb-1">Sistema de Email</h4>
                    <span class="text-sm px-3 py-1 rounded-full <?= ($config_sms['email_habilitado'] == '1') ? 'bg-green-900 text-green-300' : 'bg-red-900 text-red-300' ?>">
                        <?= ($config_sms['email_habilitado'] == '1') ? 'Activo' : 'Inactivo' ?>
                    </span>
                </div>

                <div class="bg-slate-700 rounded-xl p-4 text-center">
                    <i class="bi bi-server text-3xl text-purple-400 mb-2"></i>
                    <h4 class="text-white font-semibold mb-1">Servidor SMTP</h4>
                    <span class="text-sm px-3 py-1 rounded-full <?= ($config_sms['use_smtp'] == '1') ? 'bg-blue-900 text-blue-300' : 'bg-gray-900 text-gray-300' ?>">
                        <?= ($config_sms['use_smtp'] == '1') ? 'SMTP Personalizado' : 'PHP Mail' ?>
                    </span>
                </div>

                <div class="bg-slate-700 rounded-xl p-4 text-center">
                    <i class="bi bi-bug text-3xl text-orange-400 mb-2"></i>
                    <h4 class="text-white font-semibold mb-1">Modo de Prueba</h4>
                    <span class="text-sm px-3 py-1 rounded-full <?= ($config_sms['usar_modo_prueba'] == '1') ? 'bg-yellow-900 text-yellow-300' : 'bg-green-900 text-green-300' ?>">
                        <?= ($config_sms['usar_modo_prueba'] == '1') ? 'Prueba' : 'Producción' ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Cambiar pestañas
    function cambiarTab(tab) {
        // Ocultar todos los contenidos
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
        });

        // Remover clase activa de todos los botones
        document.querySelectorAll('.tab-button').forEach(button => {
            button.classList.remove('text-white', 'border-blue-500', 'bg-slate-700');
            button.classList.add('text-slate-400', 'border-transparent');
        });

        // Mostrar contenido seleccionado
        document.getElementById('content-' + tab).classList.remove('hidden');

        // Activar botón seleccionado
        const activeButton = document.getElementById('tab-' + tab);
        activeButton.classList.remove('text-slate-400', 'border-transparent');
        activeButton.classList.add('text-white', 'border-blue-500', 'bg-slate-700');
    }

    // Toggle SMTP configuration
    document.getElementById('use_smtp_toggle').addEventListener('change', function() {
        const smtpConfig = document.getElementById('smtp_config');
        if (this.checked) {
            smtpConfig.style.display = 'grid';
        } else {
            smtpConfig.style.display = 'none';
        }
    });

    // Submit form email
    document.getElementById('form-email').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        fetch('controllers/actualizar_configuracion.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Configuración guardada!',
                        text: data.message,
                        confirmButtonColor: '#3B82F6'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Error al guardar la configuración',
                        confirmButtonColor: '#3B82F6'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error de conexión al servidor',
                    confirmButtonColor: '#3B82F6'
                });
            });
    });

    // Submit form empresa
    document.getElementById('form-empresa').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        fetch('controllers/actualizar_configuracion.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Información guardada!',
                        text: 'La información de la empresa se ha actualizado correctamente',
                        confirmButtonColor: '#3B82F6'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Error al guardar la información',
                        confirmButtonColor: '#3B82F6'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error de conexión al servidor',
                    confirmButtonColor: '#3B82F6'
                });
            });
    });

    // Probar email
    function probarEmail() {
        const adminEmail1 = document.querySelector('input[name="admin_email_1"]').value;

        if (!adminEmail1) {
            Swal.fire({
                icon: 'warning',
                title: 'Email requerido',
                text: 'Por favor, configura primero el email del administrador principal',
                confirmButtonColor: '#3B82F6'
            });
            return;
        }

        Swal.fire({
            title: 'Enviando email de prueba...',
            text: 'Por favor espera',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        fetch('controllers/probar_email.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    email: adminEmail1
                })
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Email enviado!',
                        text: `Email de prueba enviado correctamente a ${adminEmail1}`,
                        confirmButtonColor: '#3B82F6'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error al enviar email',
                        text: data.message || 'Error desconocido',
                        confirmButtonColor: '#3B82F6'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error de conexión al servidor',
                    confirmButtonColor: '#3B82F6'
                });
            });
    }

    // Funciones de mantenimiento
    function actualizarSesiones() {
        Swal.fire({
            icon: 'info',
            title: 'Actualizando sesiones...',
            text: 'Esta función estará disponible próximamente',
            confirmButtonColor: '#3B82F6'
        });
    }

    function limpiarSesionesExpiradas() {
        Swal.fire({
            icon: 'info',
            title: 'Limpiando sesiones...',
            text: 'Esta función estará disponible próximamente',
            confirmButtonColor: '#3B82F6'
        });
    }

    function limpiarCache() {
        Swal.fire({
            icon: 'info',
            title: 'Limpiando cache...',
            text: 'Esta función estará disponible próximamente',
            confirmButtonColor: '#3B82F6'
        });
    }

    function optimizarBD() {
        Swal.fire({
            icon: 'info',
            title: 'Optimizando base de datos...',
            text: 'Esta función estará disponible próximamente',
            confirmButtonColor: '#3B82F6'
        });
    }

    function respaldarBD() {
        Swal.fire({
            icon: 'info',
            title: 'Creando respaldo...',
            text: 'Esta función estará disponible próximamente',
            confirmButtonColor: '#3B82F6'
        });
    }

    function limpiarTodo() {
        Swal.fire({
            icon: 'info',
            title: 'Limpiando sistema...',
            text: 'Esta función estará disponible próximamente',
            confirmButtonColor: '#3B82F6'
        });
    }

    function revocarSesion(token, nombre) {
        Swal.fire({
            icon: 'info',
            title: 'Revocando sesión...',
            text: 'Esta función estará disponible próximamente',
            confirmButtonColor: '#3B82F6'
        });
    }

    // Funciones para configuración de impresoras térmicas
    document.getElementById('impresion_automatica').addEventListener('change', function() {
        const configImpresora = document.getElementById('config_impresora');
        if (this.checked) {
            configImpresora.style.display = 'block';
        } else {
            configImpresora.style.display = 'none';
        }
    });

    // Submit form impresoras
    document.getElementById('form-impresoras').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        fetch('controllers/actualizar_configuracion.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Configuración guardada!',
                        text: 'La configuración de impresoras térmicas se ha actualizado correctamente',
                        confirmButtonColor: '#3B82F6'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Error al guardar la configuración de impresoras',
                        confirmButtonColor: '#3B82F6'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error de conexión al servidor',
                    confirmButtonColor: '#3B82F6'
                });
            });
    });

    // Detectar impresoras disponibles - MULTIPLATAFORMA
    function detectarImpresoras() {
        Swal.fire({
            title: '🔍 Detectando impresoras...',
            html: 'Buscando impresoras térmicas disponibles<br><small class="text-slate-400">Compatible con Windows y macOS</small>',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        fetch('controllers/detectar_impresoras.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();

                if (data.success && data.impresoras.length > 0) {
                    const resultadoDiv = document.getElementById('resultado_deteccion');
                    const listaDiv = document.getElementById('lista_impresoras');
                    
                    // Mostrar información del sistema operativo
                    const sistemaInfo = document.createElement('div');
                    sistemaInfo.className = 'mb-4 p-3 bg-blue-900/30 border border-blue-500 rounded-lg';
                    sistemaInfo.innerHTML = `
                        <div class="flex items-center">
                            <i class="bi bi-info-circle text-blue-400 mr-2"></i>
                            <div>
                                <p class="text-white font-medium">Sistema Operativo: ${data.sistema_operativo}</p>
                                <p class="text-slate-400 text-sm">${data.total_detectadas} impresora(s) detectada(s)</p>
                            </div>
                        </div>
                    `;
                    
                    listaDiv.innerHTML = '';
                    listaDiv.appendChild(sistemaInfo);
                    
                    data.impresoras.forEach(impresora => {
                        const div = document.createElement('div');
                        div.className = 'flex items-center justify-between p-3 bg-slate-700 rounded-lg mb-2 hover:bg-slate-600 transition-colors';
                        
                        // Icono según tipo de impresora
                        let icono = 'bi-printer';
                        let colorIcono = 'text-green-400';
                        if (impresora.tipo.includes('Térmica')) {
                            icono = 'bi-printer-fill';
                            colorIcono = 'text-blue-400';
                        }
                        
                        // Badge según puerto
                        let badgePuerto = '';
                        switch(impresora.puerto) {
                            case 'USB':
                                badgePuerto = '<span class="text-xs bg-green-900 text-green-300 px-2 py-1 rounded">USB</span>';
                                break;
                            case 'WiFi/Red':
                            case 'Red/IP':
                                badgePuerto = '<span class="text-xs bg-blue-900 text-blue-300 px-2 py-1 rounded">Red</span>';
                                break;
                            case 'Serial':
                                badgePuerto = '<span class="text-xs bg-yellow-900 text-yellow-300 px-2 py-1 rounded">Serial</span>';
                                break;
                            default:
                                badgePuerto = '<span class="text-xs bg-slate-600 text-slate-300 px-2 py-1 rounded">' + impresora.puerto + '</span>';
                        }
                        
                        div.innerHTML = `
                            <div class="flex items-center flex-1">
                                <i class="bi ${icono} ${colorIcono} text-2xl mr-3"></i>
                                <div class="flex-1">
                                    <p class="text-white font-medium">${impresora.nombre}</p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <p class="text-slate-400 text-sm">${impresora.tipo} - ${impresora.estado}</p>
                                        ${badgePuerto}
                                        <span class="text-xs bg-slate-800 text-slate-400 px-2 py-1 rounded">${impresora.sistema || data.sistema_operativo}</span>
                                    </div>
                                </div>
                            </div>
                            <button onclick="seleccionarImpresora('${impresora.nombre.replace(/'/g, "\\'")}')" 
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors flex items-center">
                                <i class="bi bi-check-circle mr-2"></i>
                                Seleccionar
                            </button>
                        `;
                        listaDiv.appendChild(div);
                    });
                    
                    resultadoDiv.classList.remove('hidden');
                    
                    // Mensaje de éxito con info del sistema
                    Swal.fire({
                        icon: 'success',
                        title: '¡Impresoras detectadas!',
                        html: `
                            <p>Se encontraron <strong>${data.total_detectadas} impresora(s)</strong></p>
                            <p class="text-sm text-slate-500 mt-2">Sistema: ${data.sistema_operativo}</p>
                        `,
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No se encontraron impresoras',
                        html: `
                            <p class="mb-3">No se detectaron impresoras térmicas conectadas a tu sistema.</p>
                            <p class="text-sm text-slate-500 mb-3">Sistema operativo: <strong>${data.sistema_operativo || 'Desconocido'}</strong></p>
                            <div class="mt-4 text-left bg-blue-50 p-4 rounded-lg border border-blue-200">
                                <p class="font-semibold mb-3 text-blue-900">📋 Pasos para instalar tu impresora térmica:</p>
                                <ol class="text-sm list-decimal list-inside space-y-2 text-gray-700">
                                    ${data.sistema_operativo === 'Windows' ? `
                                        <li>Conecta la impresora térmica vía USB o red</li>
                                        <li>Ve a <strong>Configuración → Dispositivos → Impresoras y escáneres</strong></li>
                                        <li>Click en <strong>Agregar una impresora o escáner</strong></li>
                                        <li>Selecciona tu impresora térmica de la lista</li>
                                        <li>Instala los drivers (si el sistema los solicita)</li>
                                        <li>Anota el <strong>nombre exacto</strong> de la impresora</li>
                                        <li>Vuelve a hacer click en <strong>Detectar Impresoras</strong></li>
                                    ` : `
                                        <li>Conecta la impresora térmica vía USB o red</li>
                                        <li>Ve a <strong>Preferencias del Sistema → Impresoras y Escáneres</strong></li>
                                        <li>Click en el botón <strong>+</strong> para agregar</li>
                                        <li>Selecciona tu impresora térmica de la lista</li>
                                        <li>macOS detectará automáticamente el tipo (USB/AirPrint/IP)</li>
                                        <li>Anota el <strong>nombre exacto</strong> de la impresora</li>
                                        <li>Vuelve a hacer click en <strong>Detectar Impresoras</strong></li>
                                    `}
                                </ol>
                            </div>
                            <div class="mt-4 text-left bg-yellow-50 p-3 rounded-lg border border-yellow-200">
                                <p class="font-semibold mb-2 text-yellow-900">⚠️ Si ya está instalada:</p>
                                <ul class="text-sm list-disc list-inside space-y-1 text-gray-700">
                                    <li>Verifica que esté <strong>encendida</strong></li>
                                    <li>Prueba imprimir una página de prueba desde el sistema</li>
                                    <li>Verifica que no esté en modo "Sin conexión"</li>
                                    <li>Puedes escribir el nombre manualmente en el campo de configuración</li>
                                </ul>
                            </div>
                        `,
                        width: 600,
                        confirmButtonColor: '#3B82F6',
                        confirmButtonText: 'Entendido'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'Error de detección',
                    html: `
                        <p>Error al detectar impresoras en tu sistema.</p>
                        <p class="text-sm text-red-500 mt-2">${error.message || 'Error desconocido'}</p>
                        <div class="mt-4 text-left bg-red-50 p-3 rounded">
                            <p class="font-semibold mb-2">Posibles causas:</p>
                            <ul class="text-sm list-disc list-inside space-y-1">
                                <li>Permisos insuficientes del servidor web</li>
                                <li>Comandos del sistema bloqueados</li>
                                <li>Firewall o antivirus interfiriendo</li>
                            </ul>
                        </div>
                    `,
                    confirmButtonColor: '#3B82F6'
                });
            });
    }

    // Seleccionar impresora detectada
    function seleccionarImpresora(nombre) {
        document.querySelector('input[name="nombre_impresora"]').value = nombre;
        
        Swal.fire({
            icon: 'success',
            title: 'Impresora seleccionada',
            text: `Se ha seleccionado la impresora: ${nombre}`,
            timer: 1500,
            showConfirmButton: false
        });
    }

    // Probar impresión térmica usando el sistema centralizado
    function probarImpresion() {
        const nombreImpresora = document.querySelector('input[name="nombre_impresora"]').value;
        
        if (!nombreImpresora) {
            Swal.fire({
                icon: 'warning',
                title: 'Impresora requerida',
                text: 'Por favor, configura primero el nombre de la impresora',
                confirmButtonColor: '#3B82F6'
            });
            return;
        }

        // Usar el sistema centralizado de impresión térmica
        imprimirPruebaTermica(nombreImpresora);
    }

    // Resetear configuración de impresoras
    function resetearConfigImpresoras() {
        Swal.fire({
            title: '¿Resetear configuración?',
            text: 'Se restaurarán los valores por defecto de las impresoras térmicas',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3B82F6',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Sí, resetear',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Resetear valores del formulario
                document.querySelector('input[name="impresion_automatica"]').checked = false;
                document.querySelector('select[name="metodo_impresion"]').value = 'local';
                document.querySelector('input[name="nombre_impresora"]').value = '';
                document.querySelector('input[name="ip_impresora"]').value = '';
                document.querySelector('input[name="puerto_impresora"]').value = '9100';
                document.querySelector('input[name="ancho_papel"]').value = '80';
                document.querySelector('input[name="copias_ticket"]').value = '1';
                document.querySelector('select[name="corte_automatico"]').value = '1';
                
                // Ocultar configuración avanzada
                document.getElementById('config_impresora').style.display = 'none';
                document.getElementById('resultado_deteccion').classList.add('hidden');
                
                Swal.fire({
                    icon: 'success',
                    title: 'Configuración reseteada',
                    text: 'Los valores se han restaurado a los valores por defecto',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        });
    }

    // 🔥 FUNCIONES PARA EL LOGO DEL TICKET
    
    // Toggle configuración de logo
    document.getElementById('logo_activado').addEventListener('change', function() {
        const configLogo = document.getElementById('config_logo');
        if (this.checked) {
            configLogo.style.display = 'block';
        } else {
            configLogo.style.display = 'none';
        }
    });

    // Cambiar vista previa cuando se selecciona una imagen
    document.getElementById('logo_imagen').addEventListener('change', function() {
        const logoPreview = document.getElementById('logo_preview');
        const imgPreview = document.getElementById('img_preview');
        
        if (this.value) {
            // Crear nueva imagen de vista previa
            const rutaImagen = './assets/img/' + this.value;
            logoPreview.innerHTML = `
                <img src="${rutaImagen}" 
                     alt="Vista previa del logo" 
                     class="max-w-full max-h-32 mx-auto rounded"
                     id="img_preview"
                     onerror="this.parentElement.innerHTML='<div class=\\'text-red-400\\'>Error: No se pudo cargar la imagen</div>'">
                <p class="text-slate-400 text-xs mt-2">Vista previa: ${this.value}</p>
            `;
        } else {
            // Sin imagen seleccionada
            logoPreview.innerHTML = `
                <div class="text-slate-400">
                    <i class="bi bi-image text-4xl mb-2"></i>
                    <p class="text-sm">Selecciona una imagen para ver la vista previa</p>
                </div>
            `;
        }
    });

    // Probar logo en ticket
    function probarLogoTicket() {
        const logoImagen = document.getElementById('logo_imagen').value;
        const logoTamaño = document.querySelector('select[name="logo_tamaño"]').value;
        const logoActivado = document.getElementById('logo_activado').checked;

        if (!logoActivado) {
            Swal.fire({
                icon: 'warning',
                title: 'Logo desactivado',
                text: 'Primero activa la opción "Mostrar Logo en Tickets"',
                confirmButtonColor: '#3B82F6'
            });
            return;
        }

        if (!logoImagen) {
            Swal.fire({
                icon: 'warning',
                title: 'Sin imagen',
                text: 'Selecciona una imagen para probar',
                confirmButtonColor: '#3B82F6'
            });
            return;
        }

        Swal.fire({
            title: 'Generando ticket de prueba...',
            text: 'Creando ticket con el logo configurado',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Enviar datos al controlador de impresión
        fetch('controllers/imprimir_termica.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                tipo: 'prueba_logo',
                logo_imagen: logoImagen,
                logo_tamaño: logoTamaño,
                logo_activado: logoActivado
            })
        })
        .then(response => response.json())
        .then(data => {
            Swal.close();
            
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Ticket generado!',
                    html: `
                        <p>Ticket de prueba con logo creado exitosamente</p>
                        <p class="text-sm text-gray-600 mt-2">
                            Imagen: ${logoImagen}<br>
                            Tamaño: ${logoTamaño}
                        </p>
                    `,
                    confirmButtonColor: '#3B82F6'
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.error || 'Error al generar el ticket de prueba',
                    confirmButtonColor: '#3B82F6'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.close();
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo conectar con el servidor',
                confirmButtonColor: '#3B82F6'
            });
        });
    }
</script>

<!-- Incluir sistema de impresión térmica -->
<script src="js/impresion-termica.js"></script>