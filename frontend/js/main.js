document.addEventListener("alpine:init", () => {
    Alpine.data("app", () => ({
        todos: [],

        async init() {
            this.todos = await this.fetchTodos();
        },

        async fetchTodos() {
            try {
                const response = await axios.get("/api/todos");
                return response.data;
            } catch (error) {
                this.redirectToLogin();
            }
        },

        async addTodo(title) {
            try {
                const response = await axios.post("/api/todos", { title });
                this.todos = await this.fetchTodos();
            } catch (error) {
                console.log(error);
            }
        },

        async changeState(id, is_completed) {
            try {
                const response = await axios.patch(`/api/todos/${id}`, { is_completed });
                this.todos = await this.fetchTodos();
            } catch (error) {
                console.log(error);
            }
        },

        redirectToLogin() {
            window.location.pathname = "/login";
        }
    }));
});