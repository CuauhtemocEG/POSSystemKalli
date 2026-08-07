const CACHE_NAME = 'kalli-pos-v1.0.0';
const STATIC_CACHE = 'static-v1.0.0';
const DYNAMIC_CACHE = 'dynamic-v1.0.0';

// Archivos estáticos que se cachean inmediatamente
const STATIC_ASSETS = [
  '/POSSystemKalli/',
  '/POSSystemKalli/index.php',
  '/POSSystemKalli/login.php',
  '/POSSystemKalli/views/header.php',
  '/POSSystemKalli/views/navbar.php',
  '/POSSystemKalli/js/auth.js',
  '/POSSystemKalli/js/pos.js',
  '/POSSystemKalli/assets/css/bar.css',
  // CDN assets importantes
  'https://cdn.tailwindcss.com',
  'https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap',
  'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
  'https://cdn.jsdelivr.net/npm/sweetalert2@11'
];

// Archivos que se cachean dinámicamente
const DYNAMIC_ASSETS = [
  '/POSSystemKalli/views/',
  '/POSSystemKalli/api/',
  '/POSSystemKalli/controllers/',
  '/POSSystemKalli/assets/img/'
];

// Install event - Cachear archivos estáticos
self.addEventListener('install', event => {
  console.log('🚀 Service Worker: Installing...');
  
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then(cache => {
        console.log('📦 Service Worker: Caching static assets');
        return cache.addAll(STATIC_ASSETS);
      })
      .then(() => {
        console.log('✅ Service Worker: Static assets cached');
        return self.skipWaiting();
      })
      .catch(error => {
        console.error('❌ Service Worker: Error caching static assets:', error);
      })
  );
});

// Activate event - Limpiar cachés antiguos
self.addEventListener('activate', event => {
  console.log('🔄 Service Worker: Activating...');
  
  event.waitUntil(
    caches.keys()
      .then(keys => {
        return Promise.all(
          keys.map(key => {
            if (key !== STATIC_CACHE && key !== DYNAMIC_CACHE) {
              console.log('🗑️ Service Worker: Deleting old cache:', key);
              return caches.delete(key);
            }
          })
        );
      })
      .then(() => {
        console.log('✅ Service Worker: Activated');
        return self.clients.claim();
      })
  );
});

// Fetch event - Estrategia de caché
self.addEventListener('fetch', event => {
  const { request } = event;
  const url = new URL(request.url);
  
  // Skip non-GET requests
  if (request.method !== 'GET') {
    return;
  }
  
  // Skip Chrome extensions
  if (url.protocol === 'chrome-extension:') {
    return;
  }
  
  event.respondWith(
    cacheFirst(request)
  );
});

// Estrategia Cache First con Network Fallback
async function cacheFirst(request) {
  const url = new URL(request.url);
  
  try {
    // 1. Buscar en caché primero
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
      console.log('📦 Cache hit:', url.pathname);
      
      // Si es un archivo estático, actualizar en segundo plano
      if (isStaticAsset(request)) {
        updateCache(request);
      }
      
      return cachedResponse;
    }
    
    // 2. Si no está en caché, buscar en red
    console.log('🌐 Network request:', url.pathname);
    const networkResponse = await fetch(request);
    
    // 3. Cachear la respuesta si es exitosa
    if (networkResponse.ok) {
      await updateCache(request, networkResponse.clone());
    }
    
    return networkResponse;
    
  } catch (error) {
    console.error('❌ Fetch error:', error);
    
    // 4. Fallback para páginas offline
    if (request.headers.get('accept').includes('text/html')) {
      return caches.match('/POSSystemKalli/offline.html') || 
             new Response('App offline. Revisa tu conexión.', {
               status: 503,
               statusText: 'Service Unavailable'
             });
    }
    
    // Fallback para imágenes
    if (request.headers.get('accept').includes('image')) {
      return new Response(
        '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200"><rect width="200" height="200" fill="#f3f4f6"/><text x="100" y="100" text-anchor="middle" fill="#6b7280" font-family="Arial" font-size="14">Imagen no disponible</text></svg>',
        { headers: { 'Content-Type': 'image/svg+xml' } }
      );
    }
    
    throw error;
  }
}

// Verificar si es un archivo estático
function isStaticAsset(request) {
  const url = new URL(request.url);
  return STATIC_ASSETS.some(asset => url.pathname.includes(asset)) ||
         url.hostname !== location.hostname; // CDN assets
}

// Actualizar caché
async function updateCache(request, response = null) {
  const url = new URL(request.url);
  const cacheName = isStaticAsset(request) ? STATIC_CACHE : DYNAMIC_CACHE;
  
  try {
    const cache = await caches.open(cacheName);
    
    if (response) {
      await cache.put(request, response);
      console.log('💾 Cached:', url.pathname);
    } else {
      const networkResponse = await fetch(request);
      if (networkResponse.ok) {
        await cache.put(request, networkResponse.clone());
        console.log('💾 Updated cache:', url.pathname);
      }
      return networkResponse;
    }
  } catch (error) {
    console.error('❌ Cache update error:', error);
  }
}

// Manejar mensajes del cliente
self.addEventListener('message', event => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
  
  if (event.data && event.data.type === 'GET_VERSION') {
    event.ports[0].postMessage({ version: CACHE_NAME });
  }
  
  if (event.data && event.data.type === 'CLEAR_CACHE') {
    caches.keys().then(keys => {
      return Promise.all(keys.map(key => caches.delete(key)));
    }).then(() => {
      event.ports[0].postMessage({ success: true });
    });
  }
});

// Sincronización en segundo plano
self.addEventListener('sync', event => {
  if (event.tag === 'background-sync') {
    event.waitUntil(doBackgroundSync());
  }
});

async function doBackgroundSync() {
  console.log('🔄 Background sync triggered');
  // Aquí puedes implementar sincronización de datos pendientes
}

// Push notifications (opcional)
self.addEventListener('push', event => {
  const options = {
    body: event.data ? event.data.text() : 'Nueva notificación de POS',
    icon: '/POSSystemKalli/assets/icons/icon-192x192.png',
    badge: '/POSSystemKalli/assets/icons/badge-72x72.png',
    vibrate: [100, 50, 100],
    data: {
      dateOfArrival: Date.now(),
      primaryKey: 1
    },
    actions: [
      {
        action: 'explore',
        title: 'Ver',
        icon: '/POSSystemKalli/assets/icons/checkmark.png'
      },
      {
        action: 'close',
        title: 'Cerrar',
        icon: '/POSSystemKalli/assets/icons/xmark.png'
      }
    ]
  };
  
  event.waitUntil(
    self.registration.showNotification('Kalli Jaguar POS', options)
  );
});

// Manejar clics en notificaciones
self.addEventListener('notificationclick', event => {
  event.notification.close();
  
  if (event.action === 'explore') {
    event.waitUntil(
      clients.openWindow('/POSSystemKalli/')
    );
  }
});

console.log('🎯 Service Worker: Loaded successfully');
