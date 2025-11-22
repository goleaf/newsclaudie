/**
 * Optimistic UI Helper for Admin Interface
 * 
 * Provides utilities for managing optimistic updates with automatic reversion on failure.
 * Integrates with Livewire for seamless state management.
 */

interface OptimisticAction {
    id: string;
    type: string;
    originalState: any;
    timestamp: number;
}

interface OptimisticUIOptions {
    onSuccess?: () => void;
    onFailure?: (error: any) => void;
    revertDelay?: number;
}

export class OptimisticUIManager {
    private pendingActions: Map<string, OptimisticAction> = new Map();
    private actionQueue: Array<() => Promise<void>> = [];
    private isProcessing: boolean = false;

    /**
     * Register an optimistic action
     */
    registerAction(id: string, type: string, originalState: any): void {
        this.pendingActions.set(id, {
            id,
            type,
            originalState,
            timestamp: Date.now()
        });
    }

    /**
     * Confirm an action succeeded
     */
    confirmAction(id: string): void {
        this.pendingActions.delete(id);
    }

    /**
     * Revert an action that failed
     */
    revertAction(id: string, revertCallback: (originalState: any) => void): void {
        const action = this.pendingActions.get(id);
        if (action) {
            revertCallback(action.originalState);
            this.pendingActions.delete(id);
        }
    }

    /**
     * Queue an action for sequential processing
     */
    queueAction(action: () => Promise<void>): void {
        this.actionQueue.push(action);
        if (!this.isProcessing) {
            this.processQueue();
        }
    }

    /**
     * Process queued actions sequentially
     */
    private async processQueue(): Promise<void> {
        if (this.actionQueue.length === 0) {
            this.isProcessing = false;
            return;
        }

        this.isProcessing = true;
        const action = this.actionQueue.shift();
        
        if (action) {
            try {
                await action();
            } catch (error) {
                console.error('Action failed:', error);
            }
        }

        // Process next action
        await this.processQueue();
    }

    /**
     * Clear all pending actions
     */
    clearPending(): void {
        this.pendingActions.clear();
    }

    /**
     * Get count of pending actions
     */
    getPendingCount(): number {
        return this.pendingActions.size;
    }
}

// Global instance
export const optimisticUI = new OptimisticUIManager();

/**
 * Alpine.js data component for optimistic UI
 */
export function optimisticComponent() {
    return {
        pendingActions: new Map(),
        
        /**
         * Perform an optimistic update
         */
        optimisticUpdate(
            actionId: string,
            optimisticCallback: () => void,
            serverCallback: () => Promise<void>,
            options: OptimisticUIOptions = {}
        ) {
            const originalState = this.captureState();
            
            // Store original state
            this.pendingActions.set(actionId, originalState);
            
            // Apply optimistic update immediately
            optimisticCallback();
            
            // Execute server action
            serverCallback()
                .then(() => {
                    // Success - remove from pending
                    this.pendingActions.delete(actionId);
                    options.onSuccess?.();
                })
                .catch((error) => {
                    // Failure - revert after delay
                    setTimeout(() => {
                        this.revertState(actionId, originalState);
                        options.onFailure?.(error);
                    }, options.revertDelay || 300);
                });
        },
        
        /**
         * Capture current state (override in component)
         */
        captureState(): any {
            return {};
        },
        
        /**
         * Revert to previous state
         */
        revertState(actionId: string, state: any): void {
            this.pendingActions.delete(actionId);
            // Override in component to restore state
        },
        
        /**
         * Check if action is pending
         */
        isPending(actionId: string): boolean {
            return this.pendingActions.has(actionId);
        }
    };
}

// Make available globally for Alpine
if (typeof window !== 'undefined') {
    (window as any).optimisticUI = optimisticUI;
    (window as any).optimisticComponent = optimisticComponent;
}
