// In host's sw.js
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
