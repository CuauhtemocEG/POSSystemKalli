<?php
// Carga variables desde .env (si existe) sin sobrescribir las ya definidas en el entorno
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim(trim($value), "\"'");
        if (getenv($key) === false) {
            putenv("$key=$value");
        }
    }
}

function env($key, $default = null) {
    $value = getenv($key);
    return $value === false ? $default : $value;
}

// Configuración JWT
define('JWT_SECRET_KEY', env('JWT_SECRET_KEY', 'kalli_jaguar_pos_2025_secret_key_super_secure')); // Definir en .env en producción
define('JWT_ALGORITHM', 'HS256');
define('JWT_EXPIRATION_TIME', 8 * 60 * 60); // 8 horas
define('JWT_REFRESH_TIME', 7 * 24 * 60 * 60); // 7 días para refresh

// Valores por defecto según el sistema operativo (MAMP en macOS usa puertos no estándar;
// Windows/XAMPP y Linux usualmente usan los puertos estándar 3306/80)
$esMac = PHP_OS_FAMILY === 'Darwin';
$dbHostDefault = $esMac ? 'localhost:8889' : 'localhost:3306';
$appUrlDefault = $esMac ? 'http://localhost:8888/POSSystemKalli' : 'http://localhost/POSSystemKalli';

// Configuración de la Base de Datos
define('DB_HOST', env('DB_HOST', $dbHostDefault));
define('DB_NAME', env('DB_NAME', 'kallijaguarpos'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', 'root'));

// Configuración de la Aplicación
define('APP_NAME', 'Kalli Jaguar POS');
define('APP_URL', env('APP_URL', $appUrlDefault));
define('APP_TIMEZONE', env('APP_TIMEZONE', 'America/Mexico_City'));

// Configuración de Sesiones
define('SESSION_TIMEOUT', 8 * 60 * 60); // 8 horas
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 15 * 60); // 15 minutos

// Configuración de Logs
define('LOG_AUTH_ATTEMPTS', true);
define('LOG_USER_ACTIONS', true);

// Rutas que no requieren autenticación
define('PUBLIC_ROUTES', [
    '/login.php',
    '/auth/login.php',
    '/auth/logout.php',
    '/auth/verify-token.php',
    '/assets/',
    '/vendor/'
]);

// Configuración de CORS (lista separada por comas en CORS_ALLOWED_ORIGINS)
define('CORS_ALLOWED_ORIGINS', array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:8888,http://localhost,http://192.168.100.191:8888,https://localhost:8890,https://192.168.100.191:8890'))));

date_default_timezone_set(APP_TIMEZONE);
