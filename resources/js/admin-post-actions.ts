type PostAction = 'publish' | 'unpublish';

type PostStates = Record<number, boolean>;

type QueueItem = {
    id: number;
    action: PostAction;
    targetState: boolean;
    previousState: boolean;
};

type AdminPostActionsConfig = {
    initialStates?: PostStates;
    defaultError?: string;
};

const fallbackError = 'Unable to update the post right now. Please try again.';
const lagThresholdMs = 550;

export const adminPostActions = (config: AdminPostActionsConfig = {}) => ({
    states: { ...(config.initialStates ?? {}) } as PostStates,
    confirmed: { ...(config.initialStates ?? {}) } as PostStates,
    defaultError: config.defaultError ?? fallbackError,
    queue: [] as Array<QueueItem>,
    active: null as QueueItem | null,
    lagging: false,
    errorMessage: '',
    $wire: undefined as unknown as {
        publish(id: number): Promise<void>;
        unpublish(id: number): Promise<void>;
    },

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

    isLagging(id: number): boolean {
        return Boolean(this.lagging && this.active && this.active.id === id);
    },

    queuedPosition(id: number): number | null {
        const position = this.queue.findIndex((item) => item.id === id);

        return position >= 0 ? position + 1 : null;
    },

    queueSize(): number {
        return this.queue.length + (this.active ? 1 : 0);
    },

    clearError(): void {
        this.errorMessage = '';
    },

    queueAction(action: PostAction, id: number, fallback = false): void {
        const nextState = this.actionToState(action);
        const previousState = this.confirmed[id] ?? this.stateFor(id, fallback);

        this.stateFor(id, fallback);

        this.states[id] = nextState;
        this.errorMessage = '';
        this.queue.push({
            id,
            action,
            targetState: nextState,
            previousState,
        });

        if (!this.active) {
            this.processNext();
        }
    },

    mergeServerState(snapshot?: PostStates | null): void {
        if (!snapshot) {
            return;
        }

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

        this.lagging = false;
        this.states[this.active.id] = this.active.targetState;

        const currentId = this.active.id;
        const lagTimer = window.setTimeout(() => {
            if (this.active && this.active.id === currentId) {
                this.lagging = true;
            }
        }, lagThresholdMs);

        try {
            const handler = this.$wire?.[this.active.action];

            if (typeof handler !== 'function') {
                throw new Error(this.defaultError ?? fallbackError);
            }

            await handler.call(this.$wire, this.active.id);

            this.confirmed[this.active.id] = this.active.targetState;
            this.states[this.active.id] = this.active.targetState;
        } catch (error) {
            const fallbackState =
                this.confirmed[this.active.id] ??
                this.active.previousState ??
                false;

            this.states[this.active.id] = fallbackState;
            this.errorMessage = this.extractError(error);
        } finally {
            window.clearTimeout(lagTimer);
            this.lagging = false;
            this.active = null;

            queueMicrotask(() => this.processNext());
        }
    },

    actionToState(action: PostAction): boolean {
        return action === 'publish';
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

if (typeof window !== 'undefined') {
    (window as Window & { adminPostActions: typeof adminPostActions }).adminPostActions = adminPostActions;
}
