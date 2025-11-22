type PostAction = 'publish' | 'unpublish';

type PostStates = Record<number, boolean>;

type AdminPostActionsConfig = {
    initialStates?: PostStates;
    defaultError?: string;
};

const fallbackError = 'Unable to update the post right now. Please try again.';

export const adminPostActions = (config: AdminPostActionsConfig = {}) => ({
    states: { ...(config.initialStates ?? {}) } as PostStates,
    confirmed: { ...(config.initialStates ?? {}) } as PostStates,
    defaultError: config.defaultError ?? fallbackError,
    queue: [] as Array<{ id: number; action: PostAction }>,
    active: null as { id: number; action: PostAction } | null,
    lagging: false,
    errorMessage: '',

    stateFor(id: number, fallback = false): boolean {
        if (this.states[id] === undefined) {
            this.states[id] = fallback;
            this.confirmed[id] = fallback;
        }

        return this.states[id];
    },

    isPublished(id: number, fallback = false): boolean {
        return this.stateFor(id, fallback);
    },

    isPending(id: number): boolean {
        return Boolean(
            (this.active && this.active.id === id) ||
                this.queue.find((item) => item.id === id),
        );
    },

    isInFlight(id: number): boolean {
        return Boolean(this.active && this.active.id === id);
    },

    queueSize(): number {
        return this.queue.length + (this.active ? 1 : 0);
    },

    clearError(): void {
        this.errorMessage = '';
    },

    queueAction(action: PostAction, id: number, fallback = false): void {
        const nextState = action === 'publish';

        this.stateFor(id, fallback);

        this.states[id] = nextState;
        this.errorMessage = '';
        this.queue.push({ id, action });

        if (!this.active) {
            this.processNext();
        }
    },

    mergeServerState(snapshot: PostStates): void {
        Object.entries(snapshot).forEach(([rawId, state]) => {
            const id = Number(rawId);

            if (!Number.isFinite(id)) {
                return;
            }

            if (this.isPending(id)) {
                return;
            }

            this.states[id] = !!state;
            this.confirmed[id] = !!state;
        });
    },

    async processNext(): Promise<void> {
        if (this.active || this.queue.length === 0) {
            return;
        }

        this.active = this.queue.shift() ?? null;

        if (!this.active) {
            return;
        }

        const lagTimer = window.setTimeout(() => {
            this.lagging = true;
        }, 450);

        try {
            if (this.active.action === 'publish') {
                await this.$wire.publish(this.active.id);
            } else {
                await this.$wire.unpublish(this.active.id);
            }

            this.confirmed[this.active.id] = this.states[this.active.id];
        } catch (error) {
            this.states[this.active.id] = this.confirmed[this.active.id] ?? false;
            this.errorMessage = this.extractError(error);
        } finally {
            window.clearTimeout(lagTimer);
            this.lagging = false;
            this.active = null;

            queueMicrotask(() => this.processNext());
        }
    },

    extractError(error: unknown): string {
        if (typeof error === 'string') {
            return error;
        }

        if (error instanceof Error && error.message) {
            return error.message;
        }

        if (error && typeof (error as { message?: string }).message === 'string') {
            return (error as { message: string }).message;
        }

        return this.defaultError ?? fallbackError;
    },
});

declare global {
    interface Window {
        adminPostActions: typeof adminPostActions;
    }
}

window.adminPostActions = adminPostActions;
