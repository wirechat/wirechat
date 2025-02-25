
self.addEventListener('message', event => {
    if (event.data.type === 'SHOW_NOTIFICATION') {
        event.waitUntil(
            self.registration.showNotification(event.data.title, event.data.options)
                .catch(error => {
                    console.error('Wirechat Show Notification failed:', error);
                })
        );
    }
    if (event.data.type === 'CLOSE_NOTIFICATION') {
        event.waitUntil(
            self.registration.getNotifications({ tag: event.data.tag })
                .then(notifications => {
                    notifications.forEach(notification => notification.close());
                })
                .catch(error => {
                    console.error('Wirechat Close notifications failed:', error);
                })
        );
    }
});

self.addEventListener('notificationclick', event => {
    event.notification.close();
    if (event.notification.data && event.notification.data.url) {
        const windowName='wirechat-conversation';
        event.waitUntil(clients.openWindow(event.notification.data.url,windowName));
    }
});
