const VERSION = "v1";
const CACHE_NAME = `todo-app-${VERSION}`;

const APP_ASSETS = [
    "/",
    "/index.html",
    "/app.js",
    "/sw.js",
    "/assets/bg.jpg",
    "/assets/empty.jpg",
    "/assets/icons/192.png",
    "/assets/icons/512.png"
];

self.addEventListener("install", event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(
            cache => cache.addAll(APP_ASSETS)
        )
    );
});

self.addEventListener("fetch", event => {
    const url = new URL(event.request.url);

    // Blocks API requests from the PHP-FPM server.
    if (url.pathname.startsWith("/api") && !self.navigator.onLine) {
        event.respondWith(new Response(
            JSON.stringify({ error: "You are offline!" }),
            {
                status: 503,
                headers: { "Content-Type": "application/json" }
            }
        ));
        return;
    }

    event.respondWith(
        caches.match(event.request).then(
            cachedResponse => cachedResponse || fetch(event.request)
        )
    );
});