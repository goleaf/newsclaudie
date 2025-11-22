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

type ValidatableElement = HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement;

const resolveInvalidClasses = (element: ValidatableElement): string[] => {
    const styles = element.getAttribute('data-invalid-styles');

    if (styles) {
        return styles.split(' ').filter(Boolean);
    }

    return [
        'border-rose-400',
        'ring-2',
        'ring-rose-100',
        'focus:border-rose-500',
        'focus:ring-rose-200',
        'dark:border-rose-500',
        'dark:ring-rose-500/30',
        'dark:focus:border-rose-400',
        'dark:focus:ring-rose-500/40',
    ];
};

const initValidationFeedback = (): void => {
    document.querySelectorAll<HTMLElement>('[data-validation-field]').forEach((field) => {
        const control = field.querySelector<ValidatableElement>('input, textarea, select');
        const errorMessage = field.querySelector<HTMLElement>('[data-field-error]');

        if (!control) {
            return;
        }

        const invalidClasses = resolveInvalidClasses(control);
        const validationType = control.dataset.validationType;

        const setInvalidState = (isInvalid: boolean, message?: string): void => {
            if (isInvalid) {
                control.classList.add(...invalidClasses);
                control.setAttribute('aria-invalid', 'true');

                if (errorMessage) {
                    if (message) {
                        errorMessage.textContent = message;
                    }

                    errorMessage.classList.remove('hidden');
                }

                return;
            }

            control.classList.remove(...invalidClasses);
            control.removeAttribute('aria-invalid');

            if (errorMessage) {
                errorMessage.classList.add('hidden');
            }
        };

        const validateTags = (): boolean => {
            const maxLength = Number(control.dataset.tagMaxLength ?? '50');
            const raw = control.value ?? '';
            const tags = raw
                .split(',')
                .map((tag) => tag.trim())
                .filter(Boolean);

            if (tags.length === 0) {
                return true;
            }

            const tooLong = tags.find((tag) => tag.length > maxLength);

            if (tooLong) {
                const message = control.dataset.tagLengthError;
                setInvalidState(true, message);

                return false;
            }

            return true;
        };

        const runValidation = (): void => {
            if (validationType === 'tags') {
                const valid = validateTags();

                if (valid) {
                    setInvalidState(false);
                }

                return;
            }

            setInvalidState(!control.checkValidity());
        };

        if (errorMessage && !errorMessage.classList.contains('hidden')) {
            control.setAttribute('aria-invalid', 'true');
            control.classList.add(...invalidClasses);
        }

        ['input', 'change', 'blur'].forEach((eventName) => {
            control.addEventListener(eventName, runValidation);
        });
    });
};

document.addEventListener('DOMContentLoaded', () => {
    initThemeToggle();
    initMobileNav();
    initClearPublishedAtButtons();
    initValidationFeedback();
});
