const VERSION = "v1";

const APP_CACHE = `todo-app-${VERSION}`;
const UNPKG_CACHE = `todo-unpkg-${VERSION}`;

const APP_ASSETS = [
    "/",
    "/index.html",
    "/favicon.ico",
    "/manifest.json",
    "/app.js",
    "/sw.js",
    "/assets/imgs/app_bg.jpg",
    "/assets/imgs/empty.jpg",
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
    "@alpinejs/persist@3.x.x",
    "@tailwindcss/browser@4"
].map(package => `https://unpkg.com/${package}`);

const persistentCacheNames = [APP_CACHE, UNPKG_CACHE, "sync-data"];

self.addEventListener("install", event => {
    event.waitUntil(Promise.all([
        caches.open(APP_CACHE).then(cache => cache.addAll(APP_ASSETS)),
        caches.open(UNPKG_CACHE).then(cache => cache.addAll(UNPKG_CDNS))
    ]));
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

    if (url.origin.includes("unpkg.com")) {
        event.respondWith(
            caches.open(UNPKG_CACHE).then(
                cache => cache.match(event.request).then(
                    cachedResponse => cachedResponse || fetch(event.request)
                )
            )
        );
        return;
    }

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
        caches.open(APP_CACHE).then(cache =>
            cache.match(event.request).then(
                cachedResponse => cachedResponse || fetch(event.request)
            )
        )
    );
});

self.addEventListener("push", event => {
    const data = event.data?.json() || {};

    event.waitUntil(
        self.registration.showNotification(
            data.title || "Reminder",
            {
                body: data.body || "You have a todo to finish!",
                icon: "/assets/icons/192.png"
            }
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