import type Alpine from 'alpinejs';
import type { AxiosInstance } from 'axios';

declare global {
    interface Window {
        Alpine: typeof Alpine;
        axios: AxiosInstance;
    }
}

export {};

