<?php
// Configuración JWT
define('JWT_SECRET_KEY', 'kalli_jaguar_pos_2025_secret_key_super_secure'); // CAMBIAR EN PRODUCCIÓN
define('JWT_ALGORITHM', 'HS256');
define('JWT_EXPIRATION_TIME', 8 * 60 * 60); // 8 horas
define('JWT_REFRESH_TIME', 7 * 24 * 60 * 60); // 7 días para refresh

// Configuración de la Base de Datos
define('DB_HOST', 'localhost:3306');
define('DB_NAME', 'KalliJaguarPOS');
define('DB_USER', 'root');
define('DB_PASS', 'root');

// Configuración de la Aplicación
define('APP_NAME', 'Kalli Jaguar POS');
define('APP_URL', 'http://localhost/POS');
define('APP_TIMEZONE', 'America/Mexico_City');

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

// Configuración de CORS
define('CORS_ALLOWED_ORIGINS', [
    'http://localhost:8888',
    'http://localhost'
]);

date_default_timezone_set(APP_TIMEZONE);
