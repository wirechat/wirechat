<?php

use Illuminate\Support\Facades\File;

it('ensure MainServiceWorkerJsScript.stub exists', function () {

    $file_path = dirname(__DIR__, 3).'/stubs/MainServiceWorkerJsScript.stub';

    $expectedContent =
"// In host's sw.js
importScripts('/js/wirechat/sw.js');


// Example: Custom event listener in main SW
self.addEventListener('install', event => {
    console.log('Main Service Worker Installed');
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    console.log('Main Service Worker Activated');
    event.waitUntil(self.clients.claim());
});
";

    expect(File::exists($file_path))->toBeTrue();
    expect(File::get($file_path))->toBe($expectedContent);

});

it('ensure PanelProvider.stub exists', function () {

    $file_path = dirname(__DIR__, 3).'/stubs/PanelProvider.stub';

    $expectedContent =
'<?php

namespace {{ namespace }};

use Wirechat\Wirechat\Panel;
use Wirechat\Wirechat\PanelProvider;

class {{ className }} extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id(\'{{ panelId }}\')
            ->path(\'{{ panelId }}\')
            ->middleware([\'auth\',\'web\'])
            {{ defaultFlag }};
    }
}
';

    expect(File::exists($file_path))->toBeTrue();
    expect(File::get($file_path))->toBe($expectedContent);

});

it('ensure ServiceWorkerJsScript.stub exists', function () {

    $file_path = dirname(__DIR__, 3).'/stubs/ServiceWorkerJsScript.stub';

    $expectedContent =
"const chatClients = new Map();

self.addEventListener('message', event => {
    if (event.data?.type === 'REGISTER_CHAT') {
        chatClients.set(event.source.id, event.data.tag);
    }

    if (event.data?.type === 'SHOW_NOTIFICATION') {
        event.waitUntil(
            self.registration.showNotification(event.data.title, event.data.options)
                .catch(error => console.error('Wirechat Show Notification failed:', error))
        );
    }

    if (event.data?.type === 'CLOSE_NOTIFICATION') {
        event.waitUntil(
            self.registration.getNotifications({ tag: event.data.tag })
                .then(notifications => notifications.forEach(n => n.close()))
                .catch(error => console.error('Wirechat Close notifications failed:', error))
        );
    }
});


self.addEventListener('notificationclick', event => {
    event.notification.close();

    if (event.notification.data?.url && event.notification.data?.tab) {
        const targetUrl = new URL(event.notification.data.url, self.location.origin).href;
        const targetTab = event.notification.data.tab;

        event.waitUntil(
            clients.matchAll({ type: 'window', includeUncontrolled: true }).then(async clientList => {
                for (const client of clientList) {
                    if (chatClients.get(client.id) === targetTab) {
                        // Case 1: Same URL → focus
                        if (client.url === targetUrl && 'focus' in client) {
                            return client.focus();
                        }
                        // Case 2: Different URL → navigate then focus
                        if ('navigate' in client) {
                            return client.navigate(targetUrl).then(() => client.focus());
                        }
                    }
                }

                // Case 3: No matching client → open new window
                if (clients.openWindow) {
                    return clients.openWindow(targetUrl);
                }
            })
        );
    }
});
";

    expect(File::exists($file_path))->toBeTrue();
    expect(File::get($file_path))->toBe($expectedContent);

});
