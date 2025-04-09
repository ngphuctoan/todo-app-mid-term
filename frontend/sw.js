const VERSION = "v1";
const CACHE_NAME = `todo-app-${VERSION}`;

const APP_ASSETS = [
    "/",
    "/index.html",
    "/favicon.ico",
    "/manifest.json",
    "/app.js",
    "/sw.js",
    "/assets/bg.jpg",
    "/assets/empty.jpg",
    "/assets/icons/192.png",
    "/assets/icons/512.png",
    "/assets/css/fonts.css",
    "/assets/css/styles.css",
    "/assets/fonts/quicksand.ttf",
    "/assets/fonts/material_icons.ttf"
];

const UNPKG_CDNS = [
    "dayjs@1",
    "flatpickr",
    "flatpickr/dist/flatpickr.min.css",
    "axios",
    "alpinejs@3.x.x",
    "@tailwindcss/browser@4"
].map(package => `https://unpkg.com/${package}`);

const persistentCacheNames = [ CACHE_NAME, "sync-data" ];
const appCaches = [...APP_ASSETS, ...UNPKG_CDNS];

self.addEventListener("install", event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(
            cache => cache.addAll(appCaches)
        )
    );
});

self.addEventListener("activate", event => {
    event.waitUntil((async () => {
        const cacheNames = await caches.keys();
        await Promise.all(cacheNames
            .filter(name => !persistentCacheNames.includes(name))
            .map(name => caches.delete(name))
        );

        await self.clients.claim();
    })());
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

self.addEventListener("sync", event => {
    if (event.tag === "sync-todos") {
        event.waitUntil(syncTodos());
    }
});

async function syncTodos() {
    console.log("Syncing todos...");

    const db = await caches.open("sync-data");
    const response = await db.match("/sync-queue");
    const queue = response ? await response.json() : [];

    for (const { action, id, todoJson } of queue) {
        try {
            switch (action) {
                case "create":
                    await fetch("/api/todos", {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify(todoJson)
                    });
                    break;
                case "update":
                    await fetch(`/api/todos/${id}`, {
                        method: "PATCH",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify(todoJson)
                    });
                    break;
                case "delete":
                    await fetch(`/api/todos/${id}`, {
                        method: "DELETE"
                    });
                    break;
            }

            console.log("Sync complete!");
        } catch (error) {
            console.error("Sync failed:", error);
        }
    }

    await db.put("/sync-queue", new Response(JSON.stringify([])));

    const matchedClients = await self.clients.matchAll();
    for (const client of matchedClients) {
        client.postMessage({ type: "sync-complete" });
    }
}