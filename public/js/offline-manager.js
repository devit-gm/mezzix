/**
 * Sistema de gestión de estado offline y sincronización
 * Mezzix - Offline Manager
 */

class OfflineManager {
    constructor() {
        this.isOnline = navigator.onLine;
        this.pendingCount = 0;
        this.syncInProgress = false;
        this.statusIndicator = null;
        this.wasOffline = false; // Nuevo: rastrear si estuvimos offline

        this.init();
    }

    init() {
        // Escuchar cambios de conexión
        window.addEventListener('online', () => this.handleOnline());
        window.addEventListener('offline', () => this.handleOffline());

        // Escuchar mensajes del Service Worker
        if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
            navigator.serviceWorker.addEventListener('message', (event) => {
                this.handleServiceWorkerMessage(event);
            });
        }

        // Crear indicador de estado
        this.createStatusIndicator();

        // Actualizar estado inicial
        this.updateStatus();

        // Verificar peticiones pendientes cada 30 segundos
        setInterval(() => this.checkPendingRequests(), 30000);

        // Verificar al cargar
        this.checkPendingRequests();
    }

    createStatusIndicator() {
        // Crear div para indicador de conexión
        const indicator = document.createElement('div');
        indicator.id = 'offline-indicator';
        indicator.style.cssText = `
            position: fixed;
            top: 10px;
            right: 10px;
            padding: 10px 15px;
            border-radius: 5px;
            font-size: 14px;
            font-weight: bold;
            z-index: 9999;
            display: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        `;

        document.body.appendChild(indicator);
        this.statusIndicator = indicator;
    }

    updateStatus() {
        if (!this.statusIndicator) return;

        if (!this.isOnline) {
            // Offline: mostrar siempre
            this.statusIndicator.style.backgroundColor = '#dc3545';
            this.statusIndicator.style.color = 'white';
            this.statusIndicator.innerHTML = '📡 Sin conexión - Modo Offline';
            this.statusIndicator.style.display = 'block';
        } else if (this.pendingCount > 0) {
            // Sincronizando: mostrar siempre
            this.statusIndicator.style.backgroundColor = '#ffc107';
            this.statusIndicator.style.color = '#000';
            this.statusIndicator.innerHTML = `⏳ Sincronizando... (${this.pendingCount} pendientes)`;
            this.statusIndicator.style.display = 'block';
        } else if (this.wasOffline) {
            // Solo mostrar "Conectado" si acabamos de recuperar conexión
            this.statusIndicator.style.backgroundColor = '#28a745';
            this.statusIndicator.style.color = 'white';
            this.statusIndicator.innerHTML = '✅ Conectado';
            this.statusIndicator.style.display = 'block';

            // Ocultar después de 3 segundos
            setTimeout(() => {
                if (this.isOnline && this.pendingCount === 0) {
                    this.statusIndicator.style.display = 'none';
                }
            }, 3000);
        } else {
            // Conexión normal: no mostrar nada
            this.statusIndicator.style.display = 'none';
        }
    }

    handleOnline() {
        console.log('[OfflineManager] Conexión restaurada');
        this.isOnline = true;
        this.updateStatus();

        // Solo mostrar notificación si realmente estuvimos offline
        if (this.wasOffline) {
            this.showNotification('Conexión restaurada', 'Se sincronizarán los datos pendientes', 'success');
            this.wasOffline = false;
        }

        // Intentar sincronizar
        this.syncNow();
    }

    handleOffline() {
        console.log('[OfflineManager] Conexión perdida');
        this.isOnline = false;
        this.wasOffline = true; // Marcar que estamos offline
        this.updateStatus();

        // Mostrar notificación
        this.showNotification('Sin conexión', 'Los cambios se guardarán y sincronizarán al reconectar', 'warning');
    }

    handleServiceWorkerMessage(event) {
        const { data } = event;

        if (data.type === 'SYNC_COMPLETE') {
            console.log('[OfflineManager] Sincronización completada');
            this.pendingCount = data.pendingCount || 0;
            this.syncInProgress = false;
            this.updateStatus();

            if (this.pendingCount === 0) {
                this.showNotification('Sincronización completa', 'Todos los datos están actualizados', 'success');
            }
        }

        if (data.type === 'PENDING_COUNT') {
            this.pendingCount = data.count;
            this.updateStatus();
        }
    }

    async checkPendingRequests() {
        if (!navigator.serviceWorker.controller) return;

        try {
            const messageChannel = new MessageChannel();

            messageChannel.port1.onmessage = (event) => {
                if (event.data.type === 'PENDING_COUNT') {
                    this.pendingCount = event.data.count;
                    this.updateStatus();
                }
            };

            navigator.serviceWorker.controller.postMessage({
                type: 'GET_PENDING_COUNT'
            }, [messageChannel.port2]);
        } catch (error) {
            console.error('[OfflineManager] Error verificando peticiones pendientes:', error);
        }
    }

    async syncNow() {
        if (this.syncInProgress || !this.isOnline) {
            return;
        }

        this.syncInProgress = true;
        this.updateStatus();

        try {
            if (navigator.serviceWorker.controller) {
                navigator.serviceWorker.controller.postMessage({
                    type: 'SYNC_NOW'
                });
            }

            // Si el navegador soporta Background Sync
            if ('sync' in navigator.serviceWorker.registration) {
                await navigator.serviceWorker.ready;
                await navigator.serviceWorker.registration.sync.register('sync-requests');
                console.log('[OfflineManager] Background sync registrado');
            }
        } catch (error) {
            console.error('[OfflineManager] Error en sincronización:', error);
            this.syncInProgress = false;
            this.updateStatus();
        }
    }

    showNotification(title, message, type = 'info') {
        // Crear notificación toast personalizada
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            max-width: 300px;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10000;
            animation: slideInRight 0.3s ease;
        `;

        const colors = {
            success: { bg: '#28a745', color: 'white' },
            warning: { bg: '#ffc107', color: '#000' },
            error: { bg: '#dc3545', color: 'white' },
            info: { bg: '#17a2b8', color: 'white' }
        };

        const color = colors[type] || colors.info;
        toast.style.backgroundColor = color.bg;
        toast.style.color = color.color;

        toast.innerHTML = `
            <strong>${title}</strong><br>
            <small>${message}</small>
        `;

        document.body.appendChild(toast);

        // Auto eliminar después de 5 segundos
        setTimeout(() => {
            toast.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    }

    // Método público para forzar sincronización desde otros scripts
    forceSyncNow() {
        return this.syncNow();
    }

    // Obtener estado actual
    getStatus() {
        return {
            isOnline: this.isOnline,
            pendingCount: this.pendingCount,
            syncInProgress: this.syncInProgress
        };
    }
}

// Añadir estilos de animación
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Inicializar el gestor offline cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.offlineManager = new OfflineManager();
    });
} else {
    window.offlineManager = new OfflineManager();
}

// Exportar para uso en módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = OfflineManager;
}
