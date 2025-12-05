<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Kalli Jaguar POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        body {
            background: #0f172a;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Professional geometric background pattern */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                linear-gradient(90deg, rgba(59, 130, 246, 0.03) 1px, transparent 1px),
                linear-gradient(rgba(59, 130, 246, 0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            pointer-events: none;
        }
        
        /* Subtle gradient overlay */
        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 50% 0%, rgba(59, 130, 246, 0.08) 0%, transparent 50%);
            pointer-events: none;
        }
        
        .login-container {
            backdrop-filter: blur(20px);
            background: rgba(15, 23, 42, 0.95);
            border: 1px solid rgba(71, 85, 105, 0.3);
            box-shadow: 
                0 25px 60px rgba(0, 0, 0, 0.6),
                0 0 0 1px rgba(255, 255, 255, 0.03) inset,
                0 1px 3px rgba(255, 255, 255, 0.05) inset;
        }
        
        .logo-container {
            position: relative;
        }
        
        .logo-glow {
            filter: drop-shadow(0 4px 12px rgba(59, 130, 246, 0.2));
        }
        
        .input-field {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(71, 85, 105, 0.5);
            color: white;
            transition: all 0.3s ease;
        }
        
        .input-field:focus {
            background: rgba(15, 23, 42, 0.8);
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            outline: none;
        }
        
        .input-field::placeholder {
            color: rgba(148, 163, 184, 0.5);
        }
        
        .btn-gradient {
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-gradient::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-gradient:hover::before {
            left: 100%;
        }
        
        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(59, 130, 246, 0.4);
        }
        
        .btn-gradient:active {
            transform: translateY(0);
        }
        
        .demo-card {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(71, 85, 105, 0.3);
            transition: all 0.3s ease;
        }
        
        .demo-card:hover {
            border-color: rgba(59, 130, 246, 0.5);
            background: rgba(15, 23, 42, 0.8);
            transform: translateX(4px);
        }
        
        .checkbox-custom:checked {
            background-color: #3b82f6;
            border-color: #3b82f6;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center px-4 py-8 relative">
    
    <div class="login-container w-full max-w-md p-8 md:p-10 rounded-3xl relative z-10">
        
        <!-- Logo y Título -->
        <div class="text-center mb-8">
            <div class="logo-container mb-6">
                <img src="assets/img/Kalliblanco.png" alt="Kalli Jaguar POS" class="h-20 mx-auto logo-glow">
            </div>
            <h1 class="text-3xl font-bold text-white mb-2 tracking-tight">Bienvenido</h1>
            <p class="text-slate-400 text-sm">Inicia sesión para continuar</p>
        </div>

        <!-- Mensaje de logout exitoso -->
        <?php if (isset($_GET['message']) && $_GET['message'] === 'logout_success'): ?>
        <div class="mb-6 p-4 bg-green-500/10 border border-green-500/30 text-green-400 rounded-xl backdrop-blur-sm">
            <div class="flex items-center">
                <i class="bi bi-check-circle-fill mr-2 text-lg"></i>
                <span class="text-sm font-medium">Sesión cerrada exitosamente</span>
            </div>
        </div>
        <?php endif; ?>


        <!-- Formulario de Login -->
        <form id="loginForm" class="space-y-5">
            
            <!-- Campo Usuario -->
            <div class="group">
                <label class="block text-sm font-semibold text-slate-300 mb-2 flex items-center">
                    <i class="bi bi-person-circle mr-2 text-blue-400"></i>
                    Usuario o Email
                </label>
                <div class="relative">
                    <input 
                        type="text" 
                        id="login" 
                        name="login" 
                        required
                        autocomplete="username"
                        class="input-field w-full px-4 py-3.5 rounded-xl transition-all"
                        placeholder="Ingresa tu usuario o email"
                    >
                    <div class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500">
                        <i class="bi bi-person"></i>
                    </div>
                </div>
            </div>

            <!-- Campo Contraseña -->
            <div class="group">
                <label class="block text-sm font-semibold text-slate-300 mb-2 flex items-center">
                    <i class="bi bi-shield-lock mr-2 text-purple-400"></i>
                    Contraseña
                </label>
                <div class="relative">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                        autocomplete="current-password"
                        class="input-field w-full px-4 py-3.5 rounded-xl pr-12 transition-all"
                        placeholder="Ingresa tu contraseña"
                    >
                    <button 
                        type="button" 
                        onclick="togglePassword()" 
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white transition-colors p-1"
                        tabindex="-1"
                    >
                        <i id="passwordToggleIcon" class="bi bi-eye text-lg"></i>
                    </button>
                </div>
            </div>

            <!-- Recordarme y Recuperar -->
            <div class="flex items-center justify-between">
                <label class="flex items-center cursor-pointer group">
                    <input 
                        type="checkbox" 
                        id="remember" 
                        name="remember"
                        class="checkbox-custom w-4 h-4 text-blue-600 border-slate-600 rounded focus:ring-2 focus:ring-blue-500 focus:ring-offset-0 bg-slate-700 transition-all"
                    >
                    <span class="ml-2 text-sm text-slate-400 group-hover:text-slate-300 transition-colors">Recordarme</span>
                </label>
                <a href="#" class="text-sm text-blue-400 hover:text-blue-300 transition-colors font-medium">
                    ¿Olvidaste tu contraseña?
                </a>
            </div>

            <!-- Botón de Login -->
            <button 
                type="submit" 
                id="loginBtn"
                class="btn-gradient w-full py-3.5 px-4 rounded-xl font-semibold text-white shadow-lg flex items-center justify-center text-base mt-6"
            >
                <span id="loginBtnText" class="flex items-center">
                    <i class="bi bi-box-arrow-in-right mr-2 text-lg"></i>
                    Iniciar Sesión
                </span>
                <span id="loginBtnLoading" class="hidden flex items-center">
                    <i class="bi bi-arrow-clockwise animate-spin mr-2 text-lg"></i>
                    Iniciando sesión...
                </span>
            </button>
        </form>


        <!-- Información de usuarios demo -->
        
        <!-- Footer -->
        <div class="mt-6 text-center">
            <p class="text-xs text-slate-500">
                © 2024 Kalli Jaguar POS • Todos los derechos reservados
            </p>
        </div>
    </div>

    <script>
        // Limpiar caché y cookies al cargar la página de login
        window.addEventListener('load', function() {
            // Limpiar localStorage y sessionStorage
            localStorage.clear();
            sessionStorage.clear();
            
            // Limpiar caché si está disponible
            if ('caches' in window) {
                caches.keys().then(function(names) {
                    for (let name of names) caches.delete(name);
                });
            }
        });

        // Verificar si ya está logueado
        if (getCookie('jwt_token')) {
            // Verificar si el token es válido antes de redirigir
            fetch('auth/verify-token.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin'
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'index.php';
                } else {
                    // Token inválido, limpiar
                    document.cookie = 'jwt_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
                }
            })
            .catch(() => {
                // Error al verificar, limpiar token
                document.cookie = 'jwt_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
            });
        }

        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const loginBtn = document.getElementById('loginBtn');
            const loginBtnText = document.getElementById('loginBtnText');
            const loginBtnLoading = document.getElementById('loginBtnLoading');
            
            // Mostrar loading
            loginBtnText.classList.add('hidden');
            loginBtnLoading.classList.remove('hidden');
            loginBtn.disabled = true;
            
            try {
                // IMPORTANTE: Limpiar todo antes de iniciar nueva sesión
                localStorage.clear();
                sessionStorage.clear();
                document.cookie.split(";").forEach(function(c) { 
                    document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/"); 
                });
                
                const formData = new FormData(this);
                const data = {
                    login: formData.get('login'),
                    password: formData.get('password'),
                    remember: formData.get('remember') === 'on'
                };
                
                const response = await fetch('auth/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Bienvenido!',
                        text: `Hola ${result.user.nombre_completo}`,
                        timer: 1500,
                        showConfirmButton: false,
                        background: '#1e293b',
                        color: '#ffffff',
                        iconColor: '#22c55e'
                    }).then(() => {
                        // Redirigir con timestamp para evitar caché
                        const timestamp = new Date().getTime();
                        window.location.href = 'index.php?t=' + timestamp;
                        
                        // Forzar recarga sin caché
                        setTimeout(() => {
                            window.location.reload(true);
                        }, 100);
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de inicio de sesión',
                        text: result.message || 'Credenciales inválidas',
                        background: '#1e293b',
                        color: '#ffffff',
                        confirmButtonColor: '#3b82f6'
                    });
                }
                
            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error de conexión. Inténtalo de nuevo.',
                    background: '#1e293b',
                    color: '#ffffff',
                    confirmButtonColor: '#3b82f6'
                });
            } finally {
                // Ocultar loading
                loginBtnText.classList.remove('hidden');
                loginBtnLoading.classList.add('hidden');
                loginBtn.disabled = false;
            }
        });
        
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('passwordToggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.className = 'bi bi-eye-slash';
            } else {
                passwordInput.type = 'password';
                toggleIcon.className = 'bi bi-eye';
            }
        }
        
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
    </script>
</body>
</html>
