import './bootstrap';
import '../css/app.css';
import Alpine from 'alpinejs';
import { adminPostActions } from './admin-post-actions';

window.Alpine = Alpine;
window.adminPostActions = adminPostActions;
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

    const setTheme = (isDark: boolean): void => {
        document.documentElement.classList.toggle('dark', isDark);
        themeToggleDarkIcon.classList.toggle('hidden', isDark);
        themeToggleLightIcon.classList.toggle('hidden', !isDark);

        const lightLabel = themeToggleBtn.dataset.themeLabelLight;
        const darkLabel = themeToggleBtn.dataset.themeLabelDark;

        if (isDark && lightLabel) {
            themeToggleBtn.title = lightLabel;
            themeToggleBtn.setAttribute('aria-label', lightLabel);
        } else if (!isDark && darkLabel) {
            themeToggleBtn.title = darkLabel;
            themeToggleBtn.setAttribute('aria-label', darkLabel);
        }

        themeToggleBtn.setAttribute('aria-pressed', isDark.toString());
    };

    setTheme(prefersDark);

    themeToggleBtn.addEventListener('click', () => {
        const storedTheme = localStorage.getItem('color-theme');
        const nextTheme =
            storedTheme === 'dark'
                ? 'light'
                : storedTheme === 'light'
                    ? 'dark'
                    : document.documentElement.classList.contains('dark')
                        ? 'light'
                        : 'dark';

        setTheme(nextTheme === 'dark');
        localStorage.setItem('color-theme', nextTheme);
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

document.addEventListener('DOMContentLoaded', () => {
    initThemeToggle();
    initMobileNav();
    initClearPublishedAtButtons();
});
