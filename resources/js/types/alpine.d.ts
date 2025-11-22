declare module 'alpinejs' {
    interface AlpineInstance {
        start(): void;
        plugin?(callback: unknown): void;
    }

    const Alpine: AlpineInstance;

    export default Alpine;
}

