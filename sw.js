// Audit4me Service Worker
// Scope: /
// Cache version is injected at registration time via postMessage from the page.
// Falls back to a build stamp so cold installs still work.

var CACHE_VERSION  = 'audit4me-v6';   // overwritten by postMessage on first load
var CACHE_DYNAMIC  = 'audit4me-dyn-v1';
var OFFLINE_URL    = '/?wpi=1';
var MAX_DYN_ITEMS  = 60;

var STATIC_ASSETS = [
  '/?wpi=1',
  '/wp-content/plugins/wp-inspector/assets/js/app.js',
  '/wp-content/plugins/wp-inspector/assets/css/app.css',
  '/wp-content/plugins/wp-inspector/assets/icons/icon-192x192.png',
  '/wp-content/plugins/wp-inspector/assets/icons/icon-512x512.png',
  '/wp-content/plugins/wp-inspector/assets/icons/app-icon-180.png',
];

// ── Version message from page ─────────────────────────────────────
// wp-inspector.php calls: sw.postMessage({ type:'SET_VERSION', version: ver })
self.addEventListener('message', function(event) {
  if (event.data && event.data.type === 'SET_VERSION') {
    var newCache = 'audit4me-' + event.data.version;
    if (newCache === CACHE_VERSION) return;
    CACHE_VERSION = newCache;
    // Evict all old caches now that we know the new version
    caches.keys().then(function(keys) {
      return Promise.all(
        keys.filter(function(k) {
          return k !== CACHE_VERSION && k !== CACHE_DYNAMIC;
        }).map(function(k) { return caches.delete(k); })
      );
    }).then(function() {
      // Re-cache static assets under the new key
      return caches.open(CACHE_VERSION).then(function(cache) {
        return Promise.allSettled(
          STATIC_ASSETS.map(function(url) {
            return fetch(url, {cache:'reload'}).then(function(res) {
              if (res && res.status === 200) return cache.put(url, res);
            }).catch(function(){});
          })
        );
      });
    });
  }
});

// ── Install — cache static assets ────────────────────────────────
self.addEventListener('install', function(event) {
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_VERSION).then(function(cache) {
      return Promise.allSettled(
        STATIC_ASSETS.map(function(url) {
          return cache.add(url).catch(function(){});
        })
      );
    })
  );
});

// ── Activate — clear stale caches ────────────────────────────────
self.addEventListener('activate', function(event) {
  event.waitUntil(
    caches.keys().then(function(keys) {
      return Promise.all(
        keys.filter(function(k) {
          return k !== CACHE_VERSION && k !== CACHE_DYNAMIC;
        }).map(function(k) { return caches.delete(k); })
      );
    }).then(function() { return self.clients.claim(); })
  );
});

// ── Fetch ─────────────────────────────────────────────────────────
self.addEventListener('fetch', function(event) {
  var req = event.request;
  if (req.method !== 'GET') return;

  var url = new URL(req.url);

  // Never intercept: AJAX, WP admin, non-same-origin, wpi_manifest
  if (
    url.origin !== self.location.origin ||
    url.pathname.includes('admin-ajax.php') ||
    url.pathname.includes('/wp-admin/') ||
    url.pathname.includes('wp-json') ||
    url.search.includes('wpi_manifest')
  ) return;

  // ── Static plugin assets — stale-while-revalidate ──────────────
  // Serve from cache immediately, then fetch fresh in background.
  // On version bump the background fetch updates the cache so next
  // load gets new code without any user-visible delay.
  if (url.pathname.includes('/wp-content/plugins/wp-inspector/assets/')) {
    event.respondWith(
      caches.open(CACHE_VERSION).then(function(cache) {
        return cache.match(req).then(function(cached) {
          var fetchPromise = fetch(req).then(function(res) {
            if (res && res.status === 200) {
              cache.put(req, res.clone());
            }
            return res;
          }).catch(function() { return cached; });

          // Return cached immediately if available, else wait for network
          return cached || fetchPromise;
        });
      })
    );
    return;
  }

  // ── App shell (/?wpi=1) — network first, cache fallback ────────
  if (url.search.includes('wpi=1') || url.pathname === '/') {
    event.respondWith(
      fetch(req).then(function(res) {
        if (res && res.status === 200) {
          var clone = res.clone();
          caches.open(CACHE_VERSION).then(function(c) { c.put(req, clone); });
        }
        return res;
      }).catch(function() {
        return caches.match(req)
          .then(function(c) { return c || caches.match(OFFLINE_URL); })
          .then(function(c) { return c || offlinePage(); });
      })
    );
    return;
  }

  // ── Everything else — network first, dynamic cache fallback ────
  event.respondWith(
    fetch(req).then(function(res) {
      if (res && res.status === 200) {
        var clone = res.clone();
        caches.open(CACHE_DYNAMIC).then(function(cache) {
          cache.put(req, clone);
          // Trim dynamic cache to MAX_DYN_ITEMS
          cache.keys().then(function(keys) {
            if (keys.length > MAX_DYN_ITEMS) {
              cache.delete(keys[0]);
            }
          });
        });
      }
      return res;
    }).catch(function() {
      return caches.match(req).then(function(c) { return c || offlinePage(); });
    })
  );
});

// ── Offline page ──────────────────────────────────────────────────
function offlinePage() {
  var html = '<!DOCTYPE html><html><head>' +
    '<meta charset="UTF-8">' +
    '<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">' +
    '<title>Audit4me \u2014 Offline</title>' +
    '<style>' +
    '*{box-sizing:border-box;margin:0;padding:0;}' +
    'body{min-height:100vh;background:linear-gradient(160deg,#0f2440,#1a3a5c);' +
    'display:flex;align-items:center;justify-content:center;' +
    'padding:calc(24px + env(safe-area-inset-top)) 24px calc(24px + env(safe-area-inset-bottom));' +
    'font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;}' +
    '.card{background:#fff;border-radius:20px;padding:40px 28px;max-width:360px;' +
    'width:100%;text-align:center;box-shadow:0 24px 80px rgba(0,0,0,.35);}' +
    '.icon{font-size:52px;margin-bottom:16px;}' +
    'h1{font-size:22px;font-weight:800;color:#0f172a;margin-bottom:8px;}' +
    'p{font-size:14px;color:#64748b;line-height:1.6;margin-bottom:24px;}' +
    'button{width:100%;padding:14px;border-radius:10px;border:none;' +
    'background:#1a3a5c;color:#fff;font-size:15px;font-weight:700;cursor:pointer;}' +
    '</style></head><body>' +
    '<div class="card">' +
    '<div class="icon">\uD83D\uDCE1</div>' +
    '<h1>You\'re Offline</h1>' +
    '<p>Audit4me needs an internet connection.<br>Please check your network and try again.</p>' +
    '<button onclick="window.location.reload()">Try Again</button>' +
    '</div></body></html>';
  return new Response(html, {
    status: 200,
    headers: { 'Content-Type': 'text/html; charset=utf-8' }
  });
}

self.addEventListener('push', function(event) {
  var data = {};
  if (event.data) {
    try { data = event.data.json(); } catch(e) {
      try {
        var text = event.data.text();
        // Try parsing as JSON if it looks like JSON
        if (text && text.trim().charAt(0) === '{') {
          data = JSON.parse(text);
        } else {
          data = { body: text };
        }
      } catch(e2) {}
    }
  }
  var title  = data.title  || 'Audit4me';
  var body   = data.body   || 'You have a new notification';
  var icon   = data.icon   || '/wp-content/plugins/wp-inspector/assets/icons/icon-192x192.png';
  var badge  = data.badge  || '/wp-content/plugins/wp-inspector/assets/icons/icon-192x192.png';
  var tag    = data.tag    || 'audit4me-action';
  var url    = data.url    || '/?wpi=1';
  event.waitUntil(
    self.registration.showNotification(title, {
      body: body,
      icon: icon,
      badge: badge,
      tag: tag,
      data: { url: url },
      requireInteraction: false
    }).catch(function(err) {
      // showNotification failed — notify open clients
      self.clients.matchAll().then(function(clients) {
        clients.forEach(function(c) {
          c.postMessage({ type: 'PUSH_ERROR', error: err ? err.toString() : 'unknown' });
        });
      });
    })
  );
});

// ── Notification click ────────────────────────────────────────────
self.addEventListener('notificationclick', function(event) {
  event.notification.close();
  if (event.action === 'dismiss') return;
  var targetUrl = (event.notification.data && event.notification.data.url) || '/?wpi=1';
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function(list) {
      for (var i = 0; i < list.length; i++) {
        var c = list[i];
        if (c.url.indexOf('wpi=1') >= 0 && 'focus' in c) {
          c.focus();
          if ('navigate' in c) c.navigate(targetUrl);
          return;
        }
      }
      if (clients.openWindow) return clients.openWindow(targetUrl);
    })
  );
});

// ── Background Sync ───────────────────────────────────────────────
// Retries failed inspection saves when connectivity is restored.
self.addEventListener('sync', function(event) {
  if (event.tag === 'wpi-sync-inspections') {
    event.waitUntil(syncPendingInspections());
  }
});

function syncPendingInspections() {
  // Notify all open clients to flush any queued saves
  return self.clients.matchAll({ type: 'window' }).then(function(clients) {
    clients.forEach(function(client) {
      client.postMessage({ type: 'WPI_BG_SYNC', tag: 'wpi-sync-inspections' });
    });
  });
}

// ── Periodic Background Sync ──────────────────────────────────────
// Wakes the SW periodically to refresh cached content and notify
// users of pending actions (requires site permission grant by user).
self.addEventListener('periodicsync', function(event) {
  if (event.tag === 'wpi-refresh') {
    event.waitUntil(periodicRefresh());
  }
});

function periodicRefresh() {
  // Re-cache the app shell silently so the next open is always fresh
  return caches.open(CACHE_VERSION).then(function(cache) {
    return fetch('/?wpi=1', { cache: 'no-store' }).then(function(res) {
      if (res && res.status === 200) cache.put('/?wpi=1', res);
    }).catch(function() {});
  });
}
