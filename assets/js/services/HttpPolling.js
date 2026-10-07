// @ts-check

export class HttpPolling {
    /** @type {ReturnType<typeof setTimeout> | null} */
    timer = null;

    /** @type {AbortController | null} */
    abortController = null;

    running = false;

    /**
     * @param {(signal: AbortSignal) => Promise<void>} handler
     */
    constructor(handler) {
        this.handler = handler;
    }

    start() {
        if (this.running) {
            return;
        }

        this.running = true;
        this.poll();
    }

    async poll() {
        if (!this.running) {
            return;
        }

        this.abortController = new AbortController();

        try {
            await this.handler(this.abortController.signal);
        } catch (error) {
            if (error instanceof Error && error.name !== "AbortError") {
                console.error(error);
            }
        }

        if (!this.running) {
            return;
        }

        this.timer = setTimeout(() => this.poll(), 1000);
    }

    stop() {
        this.running = false;

        this.abortController?.abort();

        if (this.timer !== null) {
            clearTimeout(this.timer);
            this.timer = null;
        }
    }
}
