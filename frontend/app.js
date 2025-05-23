document.addEventListener("alpine:init", () => {
    Alpine.data("app", function () {
        return {
            status: navigator.onLine ? "online" : "offline",

            todos: this.$persist([]).as("cached-todo"),

            init() {
                window.ononline = () => this.status = "online";
                window.onoffline = () => this.status = "offline";

                navigator.serviceWorker?.addEventListener("message", event => {
                    const status = event.data?.type;

                    if (status && status.includes("sync-")) {
                        this.status = status;

                        if (status === "sync-complete") {
                            this.fetchTodos();
                        }
                    }
                });

                this.fetchTodos();

                registerPush();
            },

            parseTodo({ id, title, description, is_completed, reminder }) {
                return {
                    id, title, description,
                    isCompleted: is_completed === 1,
                    reminder: reminder ? new Date(reminder) : undefined
                }
            },

            jsonifyTodo({ title, description, isCompleted, reminder }) {
                return {
                    title, description,
                    is_completed: +isCompleted,
                    reminder: reminder ? dayjs(reminder).format("YYYY-MM-DD HH:mm:ss") : undefined
                }
            },

            async fetchTodos() {
                const { data: todosData } = await axios.get("/api/todos");
                this.todos = todosData.map(this.parseTodo);
            },

            async addTodo({ title, description, reminder }) {
                const tempId = Date.now();
                const newTodo = {
                    id: tempId,
                    title, description,
                    isCompleted: false,
                    reminder
                }

                this.todos.push(newTodo);

                const payload = this.jsonifyTodo(newTodo);

                try {
                    const { data: todoJson } = await axios.post("/api/todos", payload);
                    this.todos[this._findTodoIndex(tempId)] = this.parseTodo(todoJson);
                } catch {
                    await this.queueSync({
                        action: "create",
                        id: tempId, todoJson: payload
                    });

                    console.log(...syncLog(`Queued CREATE "${title}" (Temporary ID: ${tempId})"`, "white", "blue", "SYNC"));
                }
            },

            async updateTodo(id, { title, description, isCompleted, reminder }) {
                const index = this._findTodoIndex(id);
                const oldTodo = { ...this.todos[index] };

                const newTodo = {
                    ...oldTodo,
                    title, description, isCompleted, reminder
                }

                this.todos[index] = newTodo;

                const payload = this.jsonifyTodo(newTodo);

                try {
                    const { data: todoJson } = await axios.patch(`/api/todos/${id}`, payload);
                    this.todos[index] = this.parseTodo(todoJson);
                } catch {
                    await this.queueSync({
                        action: "update",
                        id, todoJson: payload
                    });

                    console.log(...syncLog(`Queued UPDATE "${title}" (ID: ${id})`, "white", "blue", "SYNC"));
                }
            },

            async deleteTodo(id) {
                const index = this._findTodoIndex(id);
                const deletedTodo = this.todos[index];

                this.todos.splice(index, 1);

                try {
                    await axios.delete(`/api/todos/${id}`);
                } catch {
                    await this.queueSync({
                        action: "delete",
                        id
                    });

                    console.log(...syncLog(`Queued DELETE "${deletedTodo.title}" (ID: ${id})`, "white", "blue", "SYNC"));
                }
            },

            _findTodoIndex(id) {
                return this.todos.findIndex(todo => todo.id === id);
            },

            async queueSync({ action, id, todoJson }) {
                const db = await caches.open("sync-data");
                const response = await db.match("/sync-queue");
                const queue = response ? await response.json() : [];
            
                queue.push({ action, id, todoJson });
            
                await db.put("/sync-queue", new Response(JSON.stringify(queue)));
                await this.requestSync();
            },
            
            async requestSync() {
                const registration = await navigator.serviceWorker.ready;
                await registration.sync.register("sync-todos");
            }
        }
    });

    Alpine.store("status", {
        
    });
});

async function logOut() {
    await axios.post("/api/logout");
    localStorage.removeItem("user");
    redirectToLogin();
}

function redirectToLogin() {
    window.location.pathname = "/login";
}

function base64ToUint8Array(message) {
    const binary = atob(message.replace(/-/g, "+").replace(/_/g, "/"));
    return Uint8Array.from(binary, char => char.charCodeAt(0));
}

async function registerPush() {
    const permission = await Notification.requestPermission();
    
    if (permission === "granted") {
        const { public_key: vapidPublicKey } = await axios
            .get("/api/push/publickey")
            .then(response => response.data);

        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: base64ToUint8Array(vapidPublicKey)
        });

        await axios.post("/api/push/subscribe", subscription);
    }
}

flatpickrOptions = {
    enableTime: true,
    time_24hr: true,
    altInput: true,
    altFormat: "F j, Y H:i",
    dateFormat: "Y-m-d H:i:S",
    minDate: "today",
    disableMobile: true
};

axios.interceptors.response.use(
    null,
    async error => {
        if (error.response?.status === 401) {
            await logOut();
        }
        return Promise.reject(error);
    }
);

if ("serviceWorker" in navigator) {
    navigator.serviceWorker.register("/sw.js");
}