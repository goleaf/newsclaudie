import './bootstrap';
import '../css/app.css';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

const initThemeToggle = (): void => {
    const themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
    const themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');
    const themeToggleBtn = document.getElementById('theme-toggle');

    if (!(themeToggleDarkIcon && themeToggleLightIcon && themeToggleBtn)) {
        return;
    }

    const prefersDark =
        localStorage.getItem('color-theme') === 'dark' ||
        (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches);

    (prefersDark ? themeToggleLightIcon : themeToggleDarkIcon).classList.remove('hidden');

    themeToggleBtn.addEventListener('click', () => {
        themeToggleDarkIcon.classList.toggle('hidden');
        themeToggleLightIcon.classList.toggle('hidden');

        const storedTheme = localStorage.getItem('color-theme');
        if (storedTheme) {
            const nextTheme = storedTheme === 'light' ? 'dark' : 'light';
            document.documentElement.classList.toggle('dark', nextTheme === 'dark');
            localStorage.setItem('color-theme', nextTheme);

            return;
        }

        const willEnableDark = !document.documentElement.classList.contains('dark');
        document.documentElement.classList.toggle('dark', willEnableDark);
        localStorage.setItem('color-theme', willEnableDark ? 'dark' : 'light');
    });
};

const initMobileNav = (): void => {
    const mobileNavToggle = document.querySelector<HTMLButtonElement>('[data-mobile-nav-toggle]');
    const mobileNavPanel = document.getElementById('primary-mobile-nav');

    if (!(mobileNavToggle && mobileNavPanel)) {
        return;
    }

    const mobileNavOpenIcon = mobileNavToggle.querySelector<HTMLElement>('[data-icon="open"]');
    const mobileNavCloseIcon = mobileNavToggle.querySelector<HTMLElement>('[data-icon="close"]');

    mobileNavToggle.addEventListener('click', () => {
        const isExpanded = mobileNavToggle.getAttribute('aria-expanded') === 'true';
        mobileNavToggle.setAttribute('aria-expanded', (!isExpanded).toString());
        mobileNavPanel.classList.toggle('hidden');

        if (mobileNavOpenIcon && mobileNavCloseIcon) {
            mobileNavOpenIcon.classList.toggle('hidden');
            mobileNavCloseIcon.classList.toggle('hidden');
        }
    });
};

const initClearPublishedAtButtons = (): void => {
    document.querySelectorAll<HTMLButtonElement>('[data-clear-published-at]').forEach((button) => {
        const selector = button.getAttribute('data-clear-published-at');
        if (!selector) {
            return;
        }

        const target = document.querySelector<HTMLInputElement | HTMLTextAreaElement>(selector);
        if (!target) {
            return;
        }

        button.addEventListener('click', () => {
            target.value = '';
            target.dispatchEvent(new Event('change'));
        });
    });
};

const slugify = (value: string): string =>
    value
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');

const initSlugInputs = (): void => {
    document.querySelectorAll<HTMLInputElement>('[data-slug-target]').forEach((source) => {
        const targetSelector = source.getAttribute('data-slug-target');
        if (!targetSelector) {
            return;
        }

        const target = document.querySelector<HTMLInputElement | HTMLTextAreaElement>(targetSelector);
        if (!target) {
            return;
        }

        source.addEventListener('input', () => {
            target.value = slugify(source.value);
            target.dispatchEvent(new Event('change'));
        });
    });
};

document.addEventListener('DOMContentLoaded', () => {
    initThemeToggle();
    initMobileNav();
    initClearPublishedAtButtons();
    initSlugInputs();
});