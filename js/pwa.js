/**
 * PWA Management Script
 * Handles service worker registration, installation prompts, and offline functionality
 */

class PWAManager {
    constructor() {
        this.deferredPrompt = null;
        this.isInstalled = false;
        this.isOnline = navigator.onLine;
        this.swRegistration = null;
        
        this.init();
    }
    
    async init() {
        console.log('🚀 PWA Manager: Initializing...');
        
        // Check if PWA is already installed
        this.checkInstallation();
        
        // Register service worker
        await this.registerServiceWorker();
        
        // Setup event listeners
        this.setupEventListeners();
        
        // Show install button if available
        this.setupInstallButton();
        
        // Check for updates
        this.checkForUpdates();
        
        console.log('✅ PWA Manager: Initialized successfully');
    }
    
    checkInstallation() {
        // Check if running as PWA
        if (window.matchMedia('(display-mode: standalone)').matches || 
            window.navigator.standalone === true) {
            this.isInstalled = true;
            document.body.classList.add('pwa-installed');
            console.log('📱 PWA: Running as installed app');
        }
    }
    
    async registerServiceWorker() {
        if ('serviceWorker' in navigator) {
            try {
                this.swRegistration = await navigator.serviceWorker.register('/POS/sw.js', {
                    scope: '/POS/'
                });
                
                console.log('✅ Service Worker: Registered successfully', this.swRegistration);
                
                // Listen for SW updates
                this.swRegistration.addEventListener('updatefound', () => {
                    console.log('🔄 Service Worker: New version found');
                    this.handleSWUpdate();
                });
                
            } catch (error) {
                console.error('❌ Service Worker: Registration failed', error);
            }
        }
    }
    
    setupEventListeners() {
        // Install prompt
        window.addEventListener('beforeinstallprompt', (e) => {
            console.log('📥 PWA: Install prompt available');
            e.preventDefault();
            this.deferredPrompt = e;
            this.showInstallButton();
        });
        
        // App installed
        window.addEventListener('appinstalled', () => {
            console.log('🎉 PWA: App installed successfully');
            this.isInstalled = true;
            this.hideInstallButton();
            this.showNotification('¡App instalada correctamente!', 'success');
        });
        
        // Online/Offline events
        window.addEventListener('online', () => {
            console.log('🌐 PWA: Back online');
            this.isOnline = true;
            this.updateOnlineStatus();
            this.showNotification('Conexión restaurada', 'success');
        });
        
        window.addEventListener('offline', () => {
            console.log('📴 PWA: Gone offline');
            this.isOnline = false;
            this.updateOnlineStatus();
            this.showNotification('Modo offline activado', 'warning');
        });
        
        // Visibility change
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden && this.swRegistration) {
                this.checkForUpdates();
            }
        });
    }
    
    setupInstallButton() {
        // Create install button if it doesn't exist
        if (!document.getElementById('pwa-install-btn') && !this.isInstalled) {
            this.createInstallButton();
        }
    }
    
    createInstallButton() {
        const installBtn = document.createElement('button');
        installBtn.id = 'pwa-install-btn';
        installBtn.className = 'fixed bottom-4 right-4 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow-lg transition-all duration-300 z-50 hidden';
        installBtn.innerHTML = `
            <i class="bi bi-download mr-2"></i>
            Instalar App
        `;
        
        installBtn.addEventListener('click', () => this.installApp());
        document.body.appendChild(installBtn);
    }
    
    showInstallButton() {
        const installBtn = document.getElementById('pwa-install-btn');
        if (installBtn && !this.isInstalled) {
            installBtn.classList.remove('hidden');
            
            // Auto-hide after 10 seconds
            setTimeout(() => {
                if (installBtn && !installBtn.matches(':hover')) {
                    installBtn.classList.add('hidden');
                }
            }, 10000);
        }
    }
    
    hideInstallButton() {
        const installBtn = document.getElementById('pwa-install-btn');
        if (installBtn) {
            installBtn.classList.add('hidden');
        }
    }
    
    async installApp() {
        if (!this.deferredPrompt) {
            console.log('❌ PWA: No install prompt available');
            return;
        }
        
        try {
            // Show install prompt
            this.deferredPrompt.prompt();
            
            // Wait for user response
            const { outcome } = await this.deferredPrompt.userChoice;
            
            if (outcome === 'accepted') {
                console.log('✅ PWA: User accepted install');
            } else {
                console.log('❌ PWA: User dismissed install');
            }
            
            this.deferredPrompt = null;
            this.hideInstallButton();
            
        } catch (error) {
            console.error('❌ PWA: Install error', error);
        }
    }
    
    handleSWUpdate() {
        const newWorker = this.swRegistration.installing;
        
        newWorker.addEventListener('statechange', () => {
            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                // New version available
                this.showUpdateNotification();
            }
        });
    }
    
    showUpdateNotification() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '🔄 Actualización disponible',
                text: 'Hay una nueva versión de la app disponible',
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'Actualizar',
                cancelButtonText: 'Después',
                confirmButtonColor: '#3b82f6'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.updateApp();
                }
            });
        } else {
            const update = confirm('Nueva versión disponible. ¿Actualizar ahora?');
            if (update) {
                this.updateApp();
            }
        }
    }
    
    updateApp() {
        if (this.swRegistration && this.swRegistration.waiting) {
            this.swRegistration.waiting.postMessage({ type: 'SKIP_WAITING' });
            window.location.reload();
        }
    }
    
    updateOnlineStatus() {
        const statusIndicator = document.getElementById('online-status');
        if (statusIndicator) {
            statusIndicator.textContent = this.isOnline ? 'En línea' : 'Sin conexión';
            statusIndicator.className = this.isOnline ? 'online' : 'offline';
        }
        
        // Update body class
        document.body.classList.toggle('offline', !this.isOnline);
    }
    
    async checkForUpdates() {
        if (this.swRegistration) {
            try {
                await this.swRegistration.update();
            } catch (error) {
                console.error('❌ PWA: Update check failed', error);
            }
        }
    }
    
    showNotification(message, type = 'info') {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                text: message,
                icon: type,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        }
    }
    
    // Public methods
    async clearCache() {
        if (this.swRegistration) {
            const messageChannel = new MessageChannel();
            
            return new Promise((resolve) => {
                messageChannel.port1.onmessage = (event) => {
                    resolve(event.data.success);
                };
                
                this.swRegistration.active.postMessage(
                    { type: 'CLEAR_CACHE' }, 
                    [messageChannel.port2]
                );
            });
        }
    }
    
    async getVersion() {
        if (this.swRegistration) {
            const messageChannel = new MessageChannel();
            
            return new Promise((resolve) => {
                messageChannel.port1.onmessage = (event) => {
                    resolve(event.data.version);
                };
                
                this.swRegistration.active.postMessage(
                    { type: 'GET_VERSION' }, 
                    [messageChannel.port2]
                );
            });
        }
    }
    
    getInstallStatus() {
        return {
            isInstalled: this.isInstalled,
            canInstall: !!this.deferredPrompt,
            isOnline: this.isOnline
        };
    }
}

// Initialize PWA Manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.pwaManager = new PWAManager();
});

// Export for global access
window.PWAManager = PWAManager;
