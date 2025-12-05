/**
 * Funciones de autenticación del lado cliente
 */

// Verificar token al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    checkAuthStatus();
});

/**
 * Verificar estado de autenticación
 */
async function checkAuthStatus() {
    try {
        const response = await fetch('auth/verify-token.php');
        const result = await response.json();
        
        if (!result.valid) {
            // Token inválido, redirigir al login
            window.location.href = 'login.php';
        }
    } catch (error) {
        console.error('Error verificando autenticación:', error);
        // En caso de error, también redirigir al login
        window.location.href = 'login.php';
    }
}

/**
 * Interceptor para todas las peticiones AJAX
 */
function setupAjaxInterceptors() {
    // Configurar headers por defecto para fetch
    const originalFetch = window.fetch;
    window.fetch = function(...args) {
        const [url, config = {}] = args;
        
        // Agregar token a los headers si existe
        const token = getCookie('jwt_token');
        if (token && !config.headers) {
            config.headers = {};
        }
        if (token) {
            config.headers['Authorization'] = `Bearer ${token}`;
        }
        
        return originalFetch.apply(this, [url, config])
            .then(response => {
                // Verificar si la respuesta indica que el token expiró
                if (response.status === 401) {
                    handleUnauthorized();
                }
                return response;
            })
            .catch(error => {
                console.error('Error en petición:', error);
                throw error;
            });
    };
}

/**
 * Manejar respuestas no autorizadas
 */
function handleUnauthorized() {
    // Limpiar cookies y redirigir
    deleteCookie('jwt_token');
    deleteCookie('jwt_refresh_token');
    
    Swal.fire({
        icon: 'warning',
        title: 'Sesión Expirada',
        text: 'Tu sesión ha expirado. Por favor, inicia sesión nuevamente.',
        confirmButtonText: 'Ir al Login'
    }).then(() => {
        window.location.href = 'login.php';
    });
}

/**
 * Renovar token automáticamente
 */
async function refreshTokenIfNeeded() {
    try {
        const response = await fetch('auth/verify-token.php');
        const result = await response.json();
        
        if (result.valid && result.expires_at) {
            const expiresAt = result.expires_at * 1000; // Convertir a milisegundos
            const now = Date.now();
            const timeUntilExpiry = expiresAt - now;
            
            // Si expira en menos de 5 minutos, intentar renovar
            if (timeUntilExpiry < 5 * 60 * 1000) {
                const refreshToken = getCookie('jwt_refresh_token');
                if (refreshToken) {
                    const refreshResponse = await fetch('auth/refresh-token.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ refresh_token: refreshToken })
                    });
                    
                    const refreshResult = await refreshResponse.json();
                    if (refreshResult.success) {
                        setCookie('jwt_token', refreshResult.access_token);
                    }
                }
            }
        }
    } catch (error) {
        console.error('Error renovando token:', error);
    }
}

/**
 * Utilidades para cookies
 */
function getCookie(name) {
    let cookieValue = null;
    if (document.cookie && document.cookie !== '') {
        const cookies = document.cookie.split(';');
        for (let i = 0; i < cookies.length; i++) {
            const cookie = cookies[i].trim();
            if (cookie.substring(0, name.length + 1) === (name + '=')) {
                cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
                break;
            }
        }
    }
    return cookieValue;
}

function setCookie(name, value, days = 0) {
    let expires = '';
    if (days) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = '; expires=' + date.toUTCString();
    }
    document.cookie = name + '=' + (value || '') + expires + '; path=/';
}

function deleteCookie(name) {
    document.cookie = name + '=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
}

/**
 * Mostrar información del usuario logueado
 */
function showUserInfo() {
    fetch('auth/verify-token.php')
        .then(response => response.json())
        .then(result => {
            if (result.valid && result.user) {
                console.log('Usuario actual:', result.user);
            }
        });
}

// Configurar interceptores al cargar
setupAjaxInterceptors();

// Verificar renovación de token cada 5 minutos
setInterval(refreshTokenIfNeeded, 5 * 60 * 1000);

// Verificar estado de autenticación cada 30 segundos
setInterval(checkAuthStatus, 30 * 1000);
