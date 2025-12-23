const CACHE_NAME = 'mv3-rapports-v1';
const OFFLINE_URL = 'offline.html';

const ASSETS_TO_CACHE = [
  '../css/mobile_app.css',
  '../js/app.js',
  'new.php',
  'list.php'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(ASSETS_TO_CACHE);
    })
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') {
    return;
  }

  event.respondWith(
    caches.match(event.request).then((response) => {
      return response || fetch(event.request).then((response) => {
        if (!response || response.status !== 200 || response.type !== 'basic') {
          return response;
        }

        const responseToCache = response.clone();
        caches.open(CACHE_NAME).then((cache) => {
          cache.put(event.request, responseToCache);
        });

        return response;
      });
    }).catch(() => {
      return caches.match(OFFLINE_URL);
    })
  );
});

self.addEventListener('sync', (event) => {
  if (event.tag === 'sync-rapports') {
    event.waitUntil(syncOfflineRapports());
  }
});

async function syncOfflineRapports() {
  const db = await openDB();
  const tx = db.transaction('pending_rapports', 'readonly');
  const store = tx.objectStore('pending_rapports');
  const rapports = await store.getAll();

  for (const rapport of rapports) {
    try {
      const formData = new FormData();
      for (const [key, value] of Object.entries(rapport.data)) {
        formData.append(key, value);
      }

      const response = await fetch('new.php', {
        method: 'POST',
        body: formData
      });

      if (response.ok) {
        const txDelete = db.transaction('pending_rapports', 'readwrite');
        const storeDelete = txDelete.objectStore('pending_rapports');
        await storeDelete.delete(rapport.id);
      }
    } catch (error) {
      console.error('Erreur sync rapport:', error);
    }
  }
}

function openDB() {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open('mv3_rapports_db', 1);
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });
}
