/**
 * Sistema de Notificaciones de Audio para Cocina y Bar
 * Reproduce sonidos cuando llegan nuevos productos a preparar
 */

class NotificationSound {
    constructor() {
        this.audioContext = null;
        this.lastNotificationIds = new Set(); // IDs de productos ya notificados
        this.isEnabled = true;
        this.volume = 0.7;
        
        // Inicializar Audio Context (se activa con interacción del usuario)
        this.initAudioContext();
    }
    
    initAudioContext() {
        try {
            window.AudioContext = window.AudioContext || window.webkitAudioContext;
            this.audioContext = new AudioContext();
        } catch (e) {
            console.warn('Web Audio API no soportada', e);
        }
    }
    
    /**
     * Reproduce un sonido de notificación agradable
     */
    playNotification() {
        if (!this.isEnabled || !this.audioContext) return;
        
        // Reanudar contexto si está suspendido
        if (this.audioContext.state === 'suspended') {
            this.audioContext.resume();
        }
        
        const currentTime = this.audioContext.currentTime;
        
        // Crear osciladores para un sonido agradable (campana)
        // Primera nota (E5 - 659 Hz)
        const osc1 = this.audioContext.createOscillator();
        const gain1 = this.audioContext.createGain();
        
        osc1.connect(gain1);
        gain1.connect(this.audioContext.destination);
        
        osc1.frequency.value = 659.25; // E5
        osc1.type = 'sine';
        
        gain1.gain.setValueAtTime(0, currentTime);
        gain1.gain.linearRampToValueAtTime(this.volume * 0.3, currentTime + 0.01);
        gain1.gain.exponentialRampToValueAtTime(0.01, currentTime + 0.4);
        
        osc1.start(currentTime);
        osc1.stop(currentTime + 0.4);
        
        // Segunda nota (G5 - 784 Hz) - retrasada
        const osc2 = this.audioContext.createOscillator();
        const gain2 = this.audioContext.createGain();
        
        osc2.connect(gain2);
        gain2.connect(this.audioContext.destination);
        
        osc2.frequency.value = 783.99; // G5
        osc2.type = 'sine';
        
        gain2.gain.setValueAtTime(0, currentTime + 0.1);
        gain2.gain.linearRampToValueAtTime(this.volume * 0.3, currentTime + 0.11);
        gain2.gain.exponentialRampToValueAtTime(0.01, currentTime + 0.5);
        
        osc2.start(currentTime + 0.1);
        osc2.stop(currentTime + 0.5);
        
        // Tercera nota (C6 - 1047 Hz) - retrasada
        const osc3 = this.audioContext.createOscillator();
        const gain3 = this.audioContext.createGain();
        
        osc3.connect(gain3);
        gain3.connect(this.audioContext.destination);
        
        osc3.frequency.value = 1046.50; // C6
        osc3.type = 'sine';
        
        gain3.gain.setValueAtTime(0, currentTime + 0.2);
        gain3.gain.linearRampToValueAtTime(this.volume * 0.3, currentTime + 0.21);
        gain3.gain.exponentialRampToValueAtTime(0.01, currentTime + 0.7);
        
        osc3.start(currentTime + 0.2);
        osc3.stop(currentTime + 0.7);
    }
    
    /**
     * Verifica si hay nuevos productos y reproduce sonido
     * @param {Array} productos - Array de productos actuales
     */
    checkAndNotify(productos) {
        if (!this.isEnabled || !productos || productos.length === 0) return;
        
        const currentIds = new Set();
        let hasNewProducts = false;
        
        // Recopilar IDs actuales y detectar nuevos
        productos.forEach(producto => {
            const uniqueId = `${producto.op_id}_${producto.mesa_id}`;
            currentIds.add(uniqueId);
            
            // Si es un ID nuevo, hay que notificar
            if (!this.lastNotificationIds.has(uniqueId)) {
                hasNewProducts = true;
            }
        });
        
        // Si hay productos nuevos, reproducir sonido
        if (hasNewProducts && this.lastNotificationIds.size > 0) {
            // Solo notificar si ya había productos antes (no en la carga inicial)
            this.playNotification();
            console.log('🔔 Nueva orden detectada - Reproduciendo notificación');
        }
        
        // Actualizar lista de IDs conocidos
        this.lastNotificationIds = currentIds;
    }
    
    /**
     * Habilita o deshabilita las notificaciones
     */
    toggleEnabled() {
        this.isEnabled = !this.isEnabled;
        return this.isEnabled;
    }
    
    /**
     * Establece el volumen (0.0 - 1.0)
     */
    setVolume(volume) {
        this.volume = Math.max(0, Math.min(1, volume));
    }
    
    /**
     * Resetea el estado (útil al cambiar de página)
     */
    reset() {
        this.lastNotificationIds.clear();
    }
}

// Crear instancia global
if (typeof window !== 'undefined') {
    window.notificationSound = new NotificationSound();
    
    // Activar el contexto de audio con la primera interacción del usuario
    const enableAudio = () => {
        if (window.notificationSound.audioContext) {
            window.notificationSound.audioContext.resume();
        }
        document.removeEventListener('click', enableAudio);
        document.removeEventListener('touchstart', enableAudio);
    };
    
    document.addEventListener('click', enableAudio);
    document.addEventListener('touchstart', enableAudio);
}
