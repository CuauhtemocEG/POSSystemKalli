<?php
// Asegurar que $userInfo esté disponible
if (!isset($userInfo) || !$userInfo) {
  if (function_exists('getUserInfo')) {
    $userInfo = getUserInfo();
  }
  
  if (!$userInfo) {
    $userInfo = [
      'username' => 'Usuario',
      'rol' => 'Sin rol',
      'permisos' => []
    ];
  }
}
?>

<!-- Professional Fixed Navbar -->
<nav class="fixed top-0 left-0 right-0 z-50 bg-dark-900/98 backdrop-blur-lg border-b border-dark-700/30 shadow-xl">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between h-16">

      <!-- Logo Section -->
      <div class="flex items-center">
        <img src="assets/img/Kalliblanco.png" alt="Kalli Jaguar POS" class="h-10 w-auto object-contain">
      </div>

      <!-- Desktop Navigation -->
      <div class="hidden md:flex items-center space-x-1">

        <!-- 🍽️ Mesas -->
        <?php if (hasPermission('mesas', 'ver')): ?>
          <a href="index.php?page=mesas"
            class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 hover:text-white hover:bg-dark-700/50 rounded-lg transition-all duration-200">
            <i class="bi bi-grid-3x3 text-blue-400 mr-2"></i>
            Mesas
          </a>
        <?php endif; ?>

        <!-- 📋 Ordenes -->
        <?php if (hasPermission('ordenes', 'ver')): ?>
          <a href="index.php?page=ordenes"
            class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 hover:text-white hover:bg-dark-700/50 rounded-lg transition-all duration-200">
            <i class="bi bi-receipt text-green-400 mr-2"></i>
            Órdenes
          </a>
        <?php endif; ?>

        <!-- 🛍️ Catálogo -->
        <?php if (hasPermission('productos', 'ver')): ?>
          <a href="index.php?page=productos"
            class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 hover:text-white hover:bg-dark-700/50 rounded-lg transition-all duration-200">
            <i class="bi bi-bag text-purple-400 mr-2"></i>
            Catálogo
          </a>
        <?php endif; ?>

        <!-- 🔥 Cocina -->
        <?php if (hasPermission('cocina', 'ver')): ?>
          <a href="index.php?page=cocina"
            class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 hover:text-white hover:bg-dark-700/50 rounded-lg transition-all duration-200">
            <i class="bi bi-fire text-orange-400 mr-2"></i>
            Cocina
          </a>
        <?php endif; ?>

        <!-- 🍹 Bar -->
        <?php if (hasPermission('bar', 'ver')): ?>
          <a href="index.php?page=bar"
            class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 hover:text-white hover:bg-dark-700/50 rounded-lg transition-all duration-200">
            <i class="bi bi-cup-straw text-cyan-400 mr-2"></i>
            Bar
          </a>
        <?php endif; ?>

        <!-- 📊 Reportes -->
        <?php if (hasPermission('reportes', 'ver')): ?>
          <a href="index.php?page=reportes"
            class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 hover:text-white hover:bg-dark-700/50 rounded-lg transition-all duration-200">
            <i class="bi bi-graph-up text-pink-400 mr-2"></i>
            Reportes
          </a>
        <?php endif; ?>

        <!-- 🔐 Autorizaciones -->
        <?php if (hasPermission('configuracion', 'ver')): ?>
          <a href="index.php?page=autorizaciones"
            class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 hover:text-white hover:bg-dark-700/50 rounded-lg transition-all duration-200">
            <i class="bi bi-shield-exclamation text-red-400 mr-2"></i>
            Autorizaciones
          </a>
        <?php endif; ?>

      </div>

      <!-- User Menu & Mobile Button -->
      <div class="flex items-center space-x-3">

        <!-- User Dropdown -->
        <div class="relative">
          <button id="user-menu-button"
            class="flex items-center space-x-2 px-3 py-2 bg-dark-700/50 hover:bg-dark-600/50 rounded-lg transition-all duration-200 border border-dark-600/30">
            <div class="w-7 h-7 bg-gradient-to-br from-blue-500 to-purple-600 rounded-md flex items-center justify-center">
              <i class="bi bi-person text-white text-sm"></i>
            </div>
            <div class="hidden sm:block text-left">
              <p class="text-sm font-montserrat-medium text-white leading-tight"><?= htmlspecialchars($userInfo['username']) ?></p>
              <p class="text-xs text-gray-400 leading-tight"><?= htmlspecialchars($userInfo['rol']) ?></p>
            </div>
            <i class="bi bi-chevron-down text-gray-400 text-xs"></i>
          </button>

          <!-- Dropdown Menu -->
          <div id="user-dropdown"
            class="absolute right-0 mt-2 w-48 bg-dark-800/95 backdrop-blur-xl rounded-xl shadow-2xl border border-dark-700/50 py-2 opacity-0 invisible transform scale-95 transition-all duration-200 origin-top-right">
            <a href="index.php?page=configuracion"
              class="flex items-center px-4 py-2 text-sm text-gray-300 hover:bg-dark-700/50 hover:text-white transition-colors">
              <i class="bi bi-gear mr-3 text-gray-400 w-4"></i>
              Configuración
            </a>
            <a href="index.php?page=usuarios"
              class="flex items-center px-4 py-2 text-sm text-gray-300 hover:bg-dark-700/50 hover:text-white transition-colors">
              <i class="bi bi-people mr-3 text-gray-400 w-4"></i>
              Usuarios
            </a>
            <hr class="my-2 border-dark-700/50">
            <a href="#" onclick="logout(); return false;"
              class="flex items-center px-4 py-2 text-sm text-red-400 hover:bg-red-500/10 hover:text-red-300 transition-colors">
              <i class="bi bi-box-arrow-right mr-3 w-4"></i>
              Cerrar Sesión
            </a>
          </div>
        </div>

        <!-- Mobile Menu Button -->
        <button id="mobile-menu-button"
          class="md:hidden p-2 text-gray-400 hover:text-white hover:bg-dark-700/50 rounded-lg transition-all duration-200">
          <i class="bi bi-list text-xl"></i>
        </button>
      </div>
    </div>
  </div>

  <!-- Mobile Navigation Menu -->
  <div id="mobile-menu"
    class="md:hidden bg-dark-800/98 backdrop-blur-lg border-t border-dark-700/30 hidden">
    <div class="px-4 py-3 space-y-1">

      <?php if (hasPermission('mesas', 'ver')): ?>
        <a href="index.php?page=mesas"
          class="flex items-center px-3 py-2 text-base font-medium text-gray-300 hover:text-white hover:bg-dark-700/50 rounded-lg transition-colors">
          <i class="bi bi-grid-3x3 text-blue-400 mr-3 w-5"></i>
          Mesas
        </a>
      <?php endif; ?>

      <?php if (hasPermission('ordenes', 'ver')): ?>
        <a href="index.php?page=ordenes"
          class="flex items-center px-3 py-2 text-base font-medium text-gray-300 hover:text-white hover:bg-dark-700/50 rounded-lg transition-colors">
          <i class="bi bi-receipt text-green-400 mr-3 w-5"></i>
          Órdenes
        </a>
      <?php endif; ?>

      <?php if (hasPermission('productos', 'ver')): ?>
        <a href="index.php?page=productos"
          class="flex items-center px-3 py-2 text-base font-medium text-gray-300 hover:text-white hover:bg-dark-700/50 rounded-lg transition-colors">
          <i class="bi bi-bag text-purple-400 mr-3 w-5"></i>
          Catálogo
        </a>
      <?php endif; ?>

      <?php if (hasPermission('cocina', 'ver')): ?>
        <a href="index.php?page=cocina"
          class="flex items-center px-3 py-2 text-base font-medium text-gray-300 hover:text-white hover:bg-dark-700/50 rounded-lg transition-colors">
          <i class="bi bi-fire text-orange-400 mr-3 w-5"></i>
          Cocina
        </a>
      <?php endif; ?>

      <?php if (hasPermission('bar', 'ver')): ?>
        <a href="index.php?page=bar"
          class="flex items-center px-3 py-2 text-base font-medium text-gray-300 hover:text-white hover:bg-dark-700/50 rounded-lg transition-colors">
          <i class="bi bi-cup-straw text-cyan-400 mr-3 w-5"></i>
          Bar
        </a>
      <?php endif; ?>

      <?php if (hasPermission('reportes', 'ver')): ?>
        <a href="index.php?page=reportes"
          class="flex items-center px-3 py-2 text-base font-medium text-gray-300 hover:text-white hover:bg-dark-700/50 rounded-lg transition-colors">
          <i class="bi bi-graph-up text-pink-400 mr-3 w-5"></i>
          Reportes
        </a>
      <?php endif; ?>

      <?php if (hasPermission('configuracion', 'ver')): ?>
        <a href="index.php?page=autorizaciones"
          class="flex items-center px-3 py-2 text-base font-medium text-gray-300 hover:text-white hover:bg-dark-700/50 rounded-lg transition-colors">
          <i class="bi bi-shield-exclamation text-red-400 mr-3 w-5"></i>
          Autorizaciones
        </a>
      <?php endif; ?>

    </div>
  </div>

</nav>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');

    if (mobileMenuButton && mobileMenu) {
      mobileMenuButton.addEventListener('click', function() {
        mobileMenu.classList.toggle('hidden');
      });
    }

    // User dropdown toggle
    const userMenuButton = document.getElementById('user-menu-button');
    const userDropdown = document.getElementById('user-dropdown');

    if (userMenuButton && userDropdown) {
      userMenuButton.addEventListener('click', function(e) {
        e.stopPropagation();

        if (userDropdown.classList.contains('opacity-0')) {
          userDropdown.classList.remove('opacity-0', 'invisible', 'scale-95');
          userDropdown.classList.add('opacity-100', 'visible', 'scale-100');
        } else {
          userDropdown.classList.add('opacity-0', 'invisible', 'scale-95');
          userDropdown.classList.remove('opacity-100', 'visible', 'scale-100');
        }
      });

      // Close dropdown when clicking outside
      document.addEventListener('click', function() {
        userDropdown.classList.add('opacity-0', 'invisible', 'scale-95');
        userDropdown.classList.remove('opacity-100', 'visible', 'scale-100');
      });
    }
  });

  document.addEventListener('DOMContentLoaded', function() {
    // Navigation toggle for mobile
    const toggle = document.getElementById('navbar-toggle');
    const menu = document.getElementById('navbar-menu');

    if (toggle && menu) {
      toggle.addEventListener('click', function() {
        const expanded = this.getAttribute('aria-expanded') === 'true';
        this.setAttribute('aria-expanded', !expanded);
        menu.classList.toggle('hidden');
        menu.classList.toggle('block');
      });
    }

    // User dropdown toggle
    const userButton = document.getElementById('user-menu-button');
    const userDropdown = document.getElementById('user-dropdown');

    if (userButton && userDropdown) {
      userButton.addEventListener('click', function(e) {
        e.stopPropagation();
        userDropdown.classList.toggle('hidden');
      });

      // Close dropdown when clicking outside
      document.addEventListener('click', function() {
        userDropdown.classList.add('hidden');
      });

      userDropdown.addEventListener('click', function(e) {
        e.stopPropagation();
      });
    }
  });

  async function logout() {
    try {
      // Mostrar confirmación
      const confirmResult = await Swal.fire({
        title: '¿Cerrar sesión?',
        text: 'Se cerrará tu sesión actual',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3b82f6',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, cerrar sesión',
        cancelButtonText: 'Cancelar',
        background: '#1f2937',
        color: '#ffffff'
      });

      if (!confirmResult.isConfirmed) return;

      // Mostrar loading
      Swal.fire({
        title: 'Cerrando sesión...',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
          Swal.showLoading();
        },
        background: '#1f2937',
        color: '#ffffff'
      });

      const response = await fetch('auth/logout.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'same-origin'
      });

      const result = await response.json();

      if (result.success) {
        // Limpiar todas las cookies manualmente
        document.cookie.split(";").forEach(function(c) { 
          document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/"); 
        });

        // Limpiar localStorage
        localStorage.clear();
        
        // Limpiar sessionStorage
        sessionStorage.clear();

        // Forzar limpieza de caché del navegador
        if ('caches' in window) {
          caches.keys().then(function(names) {
            for (let name of names) caches.delete(name);
          });
        }

        // Redirigir con parámetro de tiempo para evitar caché
        const timestamp = new Date().getTime();
        window.location.href = 'index.php';
        
        // Forzar recarga sin caché
        setTimeout(() => {
          window.location.reload(true);
        }, 100);
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: result.message || 'Error al cerrar sesión',
          background: '#1f2937',
          color: '#ffffff',
          confirmButtonColor: '#3b82f6'
        });
      }
    } catch (error) {
      console.error('Error:', error);
      
      // En caso de error, forzar limpieza y redirección
      document.cookie.split(";").forEach(function(c) { 
        document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/"); 
      });
      localStorage.clear();
      sessionStorage.clear();
      
      window.location.href = 'login.php?t=' + new Date().getTime();
    }
  }

  function changePassword() {
    // Implementar modal de cambio de contraseña
    alert('Funcionalidad de cambio de contraseña próximamente...');
  }
</script>