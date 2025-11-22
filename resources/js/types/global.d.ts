import type Alpine from 'alpinejs';
import type { AxiosInstance } from 'axios';
import type { OptimisticUIManager } from '../admin-optimistic-ui';

declare global {
    interface Window {
        Alpine: typeof Alpine;
        axios: AxiosInstance;
        optimisticUI: OptimisticUIManager;
        optimisticComponent: () => any;
        adminPostActions: () => any;
    }
}

export {};

