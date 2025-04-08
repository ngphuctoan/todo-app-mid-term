flatpickrOptions = {
    enableTime: true,
    time_24hr: true,
    altInput: true,
    altFormat: "F j, Y H:i",
    dateFormat: "Y-m-d H:i:S",
    minDate: "today",
    disableMobile: true
}

document.addEventListener("alpine:init", () => {
    Alpine.data("app", () => ({
        todos: [],

        init() {
            this.fetchTodos().catch(async () => await this.logOut());
        },

        parseTodo(todoData) {
            return {
                id: todoData.id,
                title: todoData.title,
                description: todoData.description,
                isCompleted: todoData.is_completed === 1,
                reminder: todoData.reminder ? new Date(todoData.reminder) : undefined
            }
        },

        async fetchTodos() {
            const { data: todosData } = await axios.get("/api/todos");
            this.todos = todosData.map(this.parseTodo);
        },

        async addTodo({ title, description, reminder }) {
            const { data: todoData } = await axios.post("/api/todos", { title, description, reminder });
            this.todos.push(this.parseTodo(todoData));
        },

        async updateTodo(id, { title, description, isCompleted, reminder }) {
            const payload = Object.fromEntries(
                Object.entries({
                    title,
                    description,
                    is_completed: +isCompleted,
                    reminder
                }).filter(([, value]) => value !== undefined)
            );

            const { data: todoData } = await axios.patch(`/api/todos/${id}`, payload);
            const index = this.todos.findIndex(todo => todo.id === id);
            this.todos[index] = this.parseTodo(todoData);
        },

        async deleteTodo(id) {
            await axios.delete(`/api/todos/${id}`);
            this.todos = this.todos.filter(todo => todo.id !== id);
        },

        async logOut() {
            await axios.post("/api/logout");
            localStorage.removeItem("user");
            this.redirectToLogin();
        },

        redirectToLogin() {
            window.location.pathname = "/login";
        }
    }));
});