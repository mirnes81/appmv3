const CACHE_NAME = 'mv3pro-mobile-v3.1.1';
const urlsToCache = [
  '/custom/mv3pro_portail/mobile_app/',
  '/custom/mv3pro_portail/mobile_app/css/mobile_app.css',
  '/custom/mv3pro_portail/mobile_app/js/app.js',
  '/custom/mv3pro_portail/mobile_app/notifications/'
];

// Installation du service worker - Force immédiatement
self.addEventListener('install', event => {
  console.log('[SW] Installation v3');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('[SW] Cache ouvert');
        return cache.addAll(urlsToCache);
      })
      .then(() => {
        console.log('[SW] Skip waiting - activation immédiate');
        return self.skipWaiting();
      })
  );
});

// Activation et nettoyage des anciens caches
self.addEventListener('activate', event => {
  console.log('[SW] Activation v3');
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            console.log('[SW] Suppression ancien cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => {
      console.log('[SW] Claim clients');
      return self.clients.claim();
    })
  );
});

// Stratégie Network First avec mise à jour du cache
self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);

  // Network First pour API et pages PHP
  if (url.pathname.includes('/api/') || url.pathname.includes('.php')) {
    event.respondWith(
      fetch(event.request)
        .then(response => {
          // Mettre à jour le cache avec la nouvelle réponse
          if (response.ok && response.status === 200 && response.type === 'basic') {
            try {
              const responseClone = response.clone();
              caches.open(CACHE_NAME).then(cache => {
                cache.put(event.request, responseClone);
              }).catch(err => console.log('[SW] Cache put error:', err));
            } catch(e) {
              console.log('[SW] Clone error:', e);
            }
          }
          return response;
        })
        .catch(() => caches.match(event.request))
    );
  } else {
    // Stale-While-Revalidate pour les assets
    event.respondWith(
      caches.match(event.request)
        .then(cachedResponse => {
          const fetchPromise = fetch(event.request)
            .then(networkResponse => {
              // Mettre à jour le cache en arrière-plan
              if (networkResponse.ok && networkResponse.status === 200 && networkResponse.type === 'basic') {
                try {
                  caches.open(CACHE_NAME).then(cache => {
                    cache.put(event.request, networkResponse.clone());
                  }).catch(err => console.log('[SW] Cache put error:', err));
                } catch(e) {
                  console.log('[SW] Clone error:', e);
                }
              }
              return networkResponse;
            })
            .catch(() => cachedResponse);

          // Retourner le cache immédiatement, puis mettre à jour
          return cachedResponse || fetchPromise;
        })
    );
  }
});

// Gestion des messages
self.addEventListener('message', event => {
  // Forcer l'activation immédiate
  if (event.data && event.data.type === 'SKIP_WAITING') {
    console.log('[SW] SKIP_WAITING reçu - activation immédiate');
    self.skipWaiting();
  }

  // Vérifier les mises à jour
  if (event.data && event.data.type === 'CHECK_UPDATE') {
    console.log('[SW] Vérification des mises à jour...');
    event.waitUntil(
      caches.open(CACHE_NAME).then(cache => {
        return Promise.all(
          urlsToCache.map(url =>
            fetch(url).then(response => {
              if (response.ok) {
                return cache.put(url, response);
              }
            }).catch(err => console.log('[SW] Erreur mise à jour:', url, err))
          )
        );
      })
    );
  }
});

// Réception des notifications push
self.addEventListener('push', event => {
  console.log('Push notification reçue:', event);

  let data = {};
  if (event.data) {
    try {
      data = event.data.json();
    } catch (e) {
      data = {
        title: 'MV3 PRO',
        body: event.data.text(),
        url: '/custom/mv3pro_portail/mobile_app/'
      };
    }
  }

  const title = data.title || 'MV3 PRO';
  const options = {
    body: data.body || 'Nouvelle notification',
    icon: '/custom/mv3pro_portail/mobile_app/icon-192.png',
    badge: '/custom/mv3pro_portail/mobile_app/icon-192.png',
    vibrate: [200, 100, 200, 100, 200],
    tag: data.tag || 'mv3pro-notification',
    requireInteraction: false,
    data: {
      url: data.url || '/custom/mv3pro_portail/mobile_app/',
      notificationId: data.id
    },
    actions: [
      {
        action: 'open',
        title: 'Ouvrir',
        icon: '/custom/mv3pro_portail/mobile_app/icon-192.png'
      },
      {
        action: 'close',
        title: 'Fermer'
      }
    ]
  };

  event.waitUntil(
    self.registration.showNotification(title, options)
  );
});

// Clic sur notification
self.addEventListener('notificationclick', event => {
  event.notification.close();

  if (event.action === 'close') {
    return;
  }

  const urlToOpen = event.notification.data.url || '/custom/mv3pro_portail/mobile_app/';

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true })
      .then(windowClients => {
        // Chercher si une fenêtre est déjà ouverte
        for (let client of windowClients) {
          if (client.url.includes('mv3pro_portail') && 'focus' in client) {
            return client.focus().then(client => {
              // Envoyer un message à la page pour naviguer
              if ('postMessage' in client) {
                client.postMessage({
                  type: 'NOTIFICATION_CLICK',
                  url: urlToOpen,
                  notificationId: event.notification.data.notificationId
                });
              }
              return client;
            });
          }
        }
        // Sinon ouvrir une nouvelle fenêtre
        if (clients.openWindow) {
          return clients.openWindow(urlToOpen);
        }
      })
  );
});

// Fermeture de notification
self.addEventListener('notificationclose', event => {
  console.log('Notification fermée:', event.notification.tag);
});
