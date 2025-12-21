importScripts('https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.7.1/firebase-messaging-compat.js');

firebase.initializeApp({
    apiKey: "AIzaSyAKDn17J0jzjYrQFCGF7WRN6Lt4AW4n7PA",
    authDomain: "go-mezzix.firebaseapp.com",
    projectId: "go-mezzix",
    storageBucket: "go-mezzix.firebasestorage.app",
    messagingSenderId: "234995051320",
    appId: "1:234995051320:web:f32c705f863362b936afcd",
    measurementId: "G-2VHQ757K08"
});

const messaging = firebase.messaging();

// Variable para almacenar la ruta de iconos
let iconBasePath = '/images/icons';

// Obtener configuración de iconos dinámicamente
fetch('/pwa-config.json')
    .then(response => response.json())
    .then(config => {
        iconBasePath = config.iconPath || '/images/icons';
    })
    .catch(() => {
        console.log('Usando ruta de iconos por defecto');
    });

// Notificaciones cuando la PWA está en segundo plano
messaging.onBackgroundMessage((payload) => {
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(clients => {
        // Si la app está abierta, no mostrar notificación (evita duplicados)
        if (clients && clients.length > 0) {
            return;
        }

        const title = payload.data?.title ?? payload.notification?.title;
        const body = payload.data?.body ?? payload.notification?.body;

        self.registration.showNotification(title, {
            body: body,
            icon: iconBasePath + '/icon-192x192.png',
            badge: iconBasePath + '/icon-72x72.png',
            data: payload.data
        });
    });

    self.addEventListener('push', (event) => {
        const payload = event.data.json();

        const title = payload.data?.title;
        const body = payload.data?.body;

        event.waitUntil(
            self.registration.showNotification(title, {
                body: body,
                icon: iconBasePath + '/icon-192x192.png',
                badge: iconBasePath + '/icon-72x72.png',
                data: payload.data
            })
        );
    });

});


// Manejo del clic en la notificación
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    let urlToOpen = '/';

    // Si es notificación de evento, ir al detalle
    if (event.notification.data?.tipo === 'evento' && event.notification.data?.evento_id) {
        urlToOpen = '/eventos-publicos/' + event.notification.data.evento_id;
    } else if (event.notification.data?.url) {
        urlToOpen = event.notification.data.url;
    }

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(clientList => {
            for (const client of clientList) {
                if (client.url.includes(urlToOpen) && 'focus' in client) {
                    return client.focus();
                }
            }
            return clients.openWindow(urlToOpen);
        })
    );
});
