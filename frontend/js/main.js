document.addEventListener("alpine:init", () => {
    Alpine.data("app", () => ({
        todos: [],

        async init() {
            this.todos = await this.fetch();
        },

        async fetch() {
            try {
                const response = await axios.get("/api/todos");
                return response.data;
            } catch (error) {
                console.error("Error fetching todos:", error);
                return [];
            }
        }
    }));
});