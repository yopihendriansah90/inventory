import { notify } from "../../notifications";

const debounceDelay = 300;

export default (initialQuery, resultsHeader = "X-Stock-Results", syncUrl = true) => ({
    hasQuery: initialQuery !== "",
    loading: false,
    timeout: null,
    request: null,

    schedule() {
        this.hasQuery = this.$refs.input.value !== "";
        this.cancel();
        this.timeout = setTimeout(() => this.search(), debounceDelay);
    },

    clear() {
        this.$refs.input.value = "";
        this.hasQuery = false;
        this.$refs.input.focus();
        this.search();
    },

    cancel() {
        clearTimeout(this.timeout);
        this.timeout = null;
        this.request?.abort();
        this.request = null;
        this.loading = false;
    },

    destroy() {
        this.cancel();
    },

    async search() {
        this.cancel();
        const form = this.$refs.form;
        const query = this.$refs.input.value.trim();
        const url = new URL(form.action);
        url.search = new URLSearchParams(new FormData(form));
        if (query) url.searchParams.set("q", query);
        else url.searchParams.delete("q");

        const request = new AbortController();
        this.request = request;
        this.loading = true;

        try {
            const response = await fetch(url, {
                headers: { "X-Requested-With": "XMLHttpRequest" },
                signal: request.signal,
            });

            if (request.signal.aborted) return;
            if (response.redirected || response.status === 401) {
                window.location.assign(url);
                return;
            }
            if (!response.ok || response.headers.get(resultsHeader) !== "1") {
                throw new Error("Search results unavailable");
            }

            const html = await response.text();
            if (request.signal.aborted) return;
            this.$refs.results.innerHTML = html;
            if (syncUrl) window.history.replaceState(window.history.state, "", url);
        } catch {
            if (!request.signal.aborted) notify("error", form.dataset.searchError);
        } finally {
            if (this.request === request) {
                this.request = null;
                this.loading = false;
            }
        }
    },
});
