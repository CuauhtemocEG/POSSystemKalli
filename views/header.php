<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>POS Kalli Jaguar</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" href="../assets/Logo.jpg" type="image/jpg">
  
  <!-- PWA Manifest -->
  <link rel="manifest" href="manifest.json">
  
  <!-- PWA Meta Tags -->
  <meta name="theme-color" content="#1e293b">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="Kalli POS">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="msapplication-TileColor" content="#1e293b">
  <meta name="msapplication-tap-highlight" content="no">
  
  <!-- Apple Touch Icons -->
  <link rel="apple-touch-icon" href="assets/icons/icon-152x152.png">
  <link rel="apple-touch-icon" sizes="152x152" href="assets/icons/icon-152x152.png">
  <link rel="apple-touch-icon" sizes="180x180" href="assets/icons/icon-180x180.png">
  
  <!-- Splash Screens -->
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <link rel="apple-touch-startup-image" href="../assets/splash/splash-640x1136.png" media="(device-width: 320px) and (device-height: 568px) and (-webkit-device-pixel-ratio: 2)">
  <link rel="apple-touch-startup-image" href="../assets/splash/splash-750x1334.png" media="(device-width: 375px) and (device-height: 667px) and (-webkit-device-pixel-ratio: 2)">
  <link rel="apple-touch-startup-image" href="../assets/splash/splash-1242x2208.png" media="(device-width: 414px) and (device-height: 736px) and (-webkit-device-pixel-ratio: 3)">
  
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          fontFamily: {
            'montserrat': ['Montserrat', 'system-ui', 'sans-serif'],
            'inter': ['Inter', 'system-ui', 'sans-serif'],
            'space': ['Space Grotesk', 'system-ui', 'sans-serif']
          },
          colors: {
            primary: {
              50: '#f0f9ff',
              500: '#3b82f6',
              600: '#2563eb',
              700: '#1d4ed8',
              900: '#1e3a8a'
            },
            dark: {
              50: '#f8fafc',
              100: '#f1f5f9',
              200: '#e2e8f0',
              300: '#cbd5e1',
              400: '#94a3b8',
              500: '#64748b',
              600: '#475569',
              700: '#334155',
              800: '#1e293b',
              900: '#0f172a'
            }
          },
          animation: {
            'fade-in': 'fadeIn 0.5s ease-in-out',
            'slide-up': 'slideUp 0.3s ease-out',
            'pulse-slow': 'pulse 3s infinite'
          }
        }
      }
    }
  </script>
  
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <!-- PWA Script -->
  <script src="js/pwa.js"></script>
  
  <!-- Custom Styles -->
  <style>
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    @keyframes slideUp {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .glass-effect {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .card-hover {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .card-hover:hover {
      transform: translateY(-4px);
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
    
    .gradient-text {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    
    .dark .gradient-text {
      background: linear-gradient(135deg, #60a5fa 0%, #a78bfa 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    
    body {
      font-family: 'Montserrat', system-ui, sans-serif;
      font-weight: 400;
    }
    
    .font-display {
      font-family: 'Montserrat', system-ui, sans-serif;
      font-weight: 700;
    }
    
    .font-inter {
      font-family: 'Inter', system-ui, sans-serif;
    }
    
    .font-space {
      font-family: 'Space Grotesk', system-ui, sans-serif;
    }
    
    /* Montserrat weight utilities */
    .font-montserrat-light { font-family: 'Montserrat', system-ui, sans-serif; font-weight: 300; }
    .font-montserrat-regular { font-family: 'Montserrat', system-ui, sans-serif; font-weight: 400; }
    .font-montserrat-medium { font-family: 'Montserrat', system-ui, sans-serif; font-weight: 500; }
    .font-montserrat-semibold { font-family: 'Montserrat', system-ui, sans-serif; font-weight: 600; }
    .font-montserrat-bold { font-family: 'Montserrat', system-ui, sans-serif; font-weight: 700; }
    .font-montserrat-extrabold { font-family: 'Montserrat', system-ui, sans-serif; font-weight: 800; }
    .font-montserrat-black { font-family: 'Montserrat', system-ui, sans-serif; font-weight: 900; }
    
    /* 🎨 Layout System - Tipos de vistas estandarizadas */
    
    /* Layout por defecto - Vistas estándar con contenido centrado */
    .view-standard {
      max-width: 1536px;
      margin: 0 auto;
      padding: 1.5rem 1rem;
      min-height: calc(100vh - 4rem);
    }
    
    @media (min-width: 640px) {
      .view-standard {
        padding: 2rem 1.5rem;
      }
    }
    
    @media (min-width: 1024px) {
      .view-standard {
        padding: 2.5rem 2rem;
      }
    }
    
    @media (min-width: 1280px) {
      .view-standard {
        padding: 3rem 2.5rem;
      }
    }
    
    /* Layout wide - Para dashboards y vistas que necesitan más espacio */
    .view-wide {
      max-width: 1920px;
      margin: 0 auto;
      padding: 1.5rem 1rem;
      min-height: calc(100vh - 4rem);
    }
    
    @media (min-width: 640px) {
      .view-wide {
        padding: 2rem 1.5rem;
      }
    }
    
    @media (min-width: 1024px) {
      .view-wide {
        padding: 2.5rem 2rem;
      }
    }
    
    /* Layout fullscreen - Para vistas kiosk sin márgenes */
    .view-fullscreen {
      width: 100vw;
      height: calc(100vh - 4rem);
      margin: 0;
      padding: 0;
      overflow: hidden;
      position: relative;
      left: 50%;
      right: 50%;
      margin-left: -50vw;
      margin-right: -50vw;
    }
    
    /* Layout compact - Para vistas con menos espaciado */
    .view-compact {
      max-width: 1280px;
      margin: 0 auto;
      padding: 1rem;
      min-height: calc(100vh - 4rem);
    }
    
    @media (min-width: 640px) {
      .view-compact {
        padding: 1.5rem;
      }
    }
    
    /* Layout minimal - Sin padding para control total */
    .view-minimal {
      padding: 0;
      margin: 0;
      min-height: calc(100vh - 4rem);
    }
    
    /* 📐 Helpers para ajustar el main container según la vista */
    .main-standard {
      padding: 0 1rem;
    }
    
    @media (min-width: 640px) {
      .main-standard {
        padding: 0 1.5rem;
      }
    }
    
    @media (min-width: 1024px) {
      .main-standard {
        padding: 0 2rem;
      }
    }
    
    .main-fullscreen {
      padding: 0;
      margin: 0;
      max-width: 100%;
    }
    
    .main-wide {
      padding: 0 1rem;
    }
    
    @media (min-width: 640px) {
      .main-wide {
        padding: 0 1.5rem;
      }
    }
    
    /* 🎯 Override para vistas específicas */
    body.kiosk-mode {
      overflow: hidden;
    }
    
    body.kiosk-mode main {
      padding: 0;
      overflow: hidden;
    }
    
    /* PWA Styles */
    .pwa-installed .navbar {
      padding-top: env(safe-area-inset-top);
    }
    
    .offline {
      filter: grayscale(0.3);
    }
    
    .offline .connection-indicator {
      background-color: #ef4444 !important;
      color: white !important;
      border-color: #dc2626 !important;
    }
    
    .connection-indicator {
      background-color: #10b981;
      color: white;
      border-color: #059669;
    }
    
    .connection-indicator.offline {
      background-color: #ef4444;
      border-color: #dc2626;
    }
    
    .connection-indicator.offline span:first-child {
      background-color: #fca5a5;
    }
    
    .connection-indicator span:first-child {
      background-color: #86efac;
    }
    
    /* Install button animation */
    #pwa-install-btn {
      animation: slideInUp 0.3s ease-out;
    }
    
    @keyframes slideInUp {
      from {
        transform: translateY(100px);
        opacity: 0;
      }
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }
    
    /* PWA display mode adjustments */
    @media (display-mode: standalone) {
      body {
        padding-top: env(safe-area-inset-top);
      }
      
      .navbar {
        top: env(safe-area-inset-top);
      }
    }
  </style>
</head>

<body class="dark bg-gradient-to-br from-dark-900 via-dark-800 to-dark-900 min-h-screen text-white font-montserrat <?= isset($isKioskMode) && $isKioskMode ? 'kiosk-mode' : '' ?>">
  <?php include 'navbar.php'; ?>
  
  <!-- 
    Main Content Container - Sistema de Layout Flexible 
    
    Clases disponibles para las vistas:
    - view-standard: Layout estándar con max-width 1536px (productos, usuarios, etc)
    - view-wide: Layout amplio con max-width 1920px (reportes, dashboards)
    - view-fullscreen: Sin márgenes, fullscreen (mesa, cocina, bar)
    - view-compact: Layout compacto con max-width 1280px
    - view-minimal: Sin padding para control total
  -->
  <main class="pt-16 min-h-screen <?= isset($isKioskMode) && $isKioskMode ? '' : 'px-4 sm:px-6 lg:px-8' ?>" id="main-content">
    <!-- El contenido de las vistas se insertará aquí -->