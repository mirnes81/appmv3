const CACHE_NAME = 'mv3-pro-v1';
const RUNTIME_CACHE = 'mv3-runtime';
const API_CACHE = 'mv3-api';

const PRECACHE_URLS = [
  '/',
  '/index.html',
  '/manifest.json'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(PRECACHE_URLS))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  const currentCaches = [CACHE_NAME, RUNTIME_CACHE, API_CACHE];
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return cacheNames.filter(cacheName => !currentCaches.includes(cacheName));
    }).then(cachesToDelete => {
      return Promise.all(cachesToDelete.map(cacheToDelete => {
        return caches.delete(cacheToDelete);
      }));
    }).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  if (event.request.url.startsWith(self.location.origin)) {
    if (event.request.url.includes('/api/')) {
      event.respondWith(
        caches.open(API_CACHE).then(cache => {
          return fetch(event.request)
            .then(response => {
              if (response.status === 200) {
                cache.put(event.request, response.clone());
              }
              return response;
            })
            .catch(() => {
              return cache.match(event.request);
            });
        })
      );
    } else {
      event.respondWith(
        caches.match(event.request).then(cachedResponse => {
          if (cachedResponse) {
            return cachedResponse;
          }

          return caches.open(RUNTIME_CACHE).then(cache => {
            return fetch(event.request).then(response => {
              if (response.status === 200) {
                return cache.put(event.request, response.clone()).then(() => {
                  return response;
                });
              }
              return response;
            });
          });
        })
      );
    }
  }
});

self.addEventListener('sync', (event) => {
  if (event.tag === 'sync-reports') {
    event.waitUntil(syncReports());
  }
});

async function syncReports() {
  const db = await openDB();
  const tx = db.transaction('sync_queue', 'readonly');
  const store = tx.objectStore('sync_queue');
  const items = await store.getAll();

  for (const item of items) {
    try {
      const response = await fetch(item.url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(item.payload)
      });

      if (response.ok) {
        const txDelete = db.transaction('sync_queue', 'readwrite');
        await txDelete.objectStore('sync_queue').delete(item.id);
      }
    } catch (error) {
      console.error('Sync failed for item:', item.id, error);
    }
  }
}

function openDB() {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open('MV3ProDB', 1);
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });
}

self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});

self.addEventListener('push', (event) => {
  const data = event.data ? event.data.json() : {};
  const options = {
    body: data.body || 'Nouvelle notification',
    icon: '/icon-192.png',
    badge: '/icon-192.png',
    vibrate: [200, 100, 200],
    data: data,
    actions: data.actions || []
  };

  event.waitUntil(
    self.registration.showNotification(data.title || 'MV3 Pro', options)
  );
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();

  event.waitUntil(
    clients.openWindow(event.notification.data.url || '/')
  );
});
