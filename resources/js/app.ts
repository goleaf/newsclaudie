import './bootstrap';
import '../css/app.css';
import Alpine from 'alpinejs';
import { adminPostActions } from './admin-post-actions';
import { optimisticUI, optimisticComponent } from './admin-optimistic-ui';

window.Alpine = Alpine;
window.adminPostActions = adminPostActions;
window.optimisticUI = optimisticUI;
window.optimisticComponent = optimisticComponent;
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
        const controls = Array.from(field.querySelectorAll<ValidatableElement>('input, textarea, select'));
        const errorMessages = field.querySelectorAll<HTMLElement>('[data-field-error]');
        const hints = field.querySelectorAll<HTMLElement>('[data-field-hint]');

        if (controls.length === 0) {
            return;
        }

        const primaryControl = controls[0];
        const invalidClasses = resolveInvalidClasses(primaryControl);
        const validationType = primaryControl.dataset.validationType;

        const setInvalidState = (isInvalid: boolean, message?: string): void => {
            if (isInvalid) {
                primaryControl.classList.add(...invalidClasses);
                primaryControl.setAttribute('aria-invalid', 'true');

                errorMessages.forEach((errorMessage, index) => {
                    if (message && index === 0) {
                        errorMessage.textContent = message;
                    }

                    errorMessage.classList.remove('hidden');
                });

                hints.forEach((hint) => hint.classList.add('hidden'));

                return;
            }

            primaryControl.classList.remove(...invalidClasses);
            primaryControl.removeAttribute('aria-invalid');

            errorMessages.forEach((errorMessage) => {
                errorMessage.classList.add('hidden');
            });

            hints.forEach((hint) => hint.classList.remove('hidden'));
        };

        const validateTags = (): boolean => {
            const maxLength = Number(primaryControl.dataset.tagMaxLength ?? '50');
            const raw = primaryControl.value ?? '';
            const tags = raw
                .split(',')
                .map((tag) => tag.trim())
                .filter(Boolean);

            if (tags.length === 0) {
                return true;
            }

            const tooLong = tags.find((tag) => tag.length > maxLength);

            if (tooLong) {
                const message = primaryControl.dataset.tagLengthError;
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

            const hasInvalidControl = controls.some((control) => !control.checkValidity());
            setInvalidState(hasInvalidControl);
        };

        errorMessages.forEach((errorMessage) => {
            if (!errorMessage.classList.contains('hidden')) {
                primaryControl.setAttribute('aria-invalid', 'true');
                primaryControl.classList.add(...invalidClasses);
            }
        });

        controls.forEach((control) => {
            ['input', 'change', 'blur'].forEach((eventName) => {
                control.addEventListener(eventName, runValidation);
            });
        });
    });
};

const isTextEntry = (target: EventTarget | null): target is HTMLElement => {
    if (!(target instanceof HTMLElement)) {
        return false;
    }

    const tag = target.tagName.toLowerCase();

    return target.isContentEditable || tag === 'input' || tag === 'textarea' || tag === 'select';
};

const findOpenFluxDialog = (): HTMLDialogElement | null =>
    document.querySelector<HTMLDialogElement>('[data-flux-modal] dialog[open]');

class AdminTableNavigator {
    element: HTMLElement;
    rows: HTMLElement[] = [];
    selectedId: string | null = null;

    constructor(element: HTMLElement) {
        this.element = element;
        this.refreshRows();
    }

    private rowId(row: HTMLElement): string {
        return row.dataset.rowId ?? String(this.rows.indexOf(row));
    }

    refreshRows(): void {
        this.rows = Array.from(this.element.querySelectorAll<HTMLElement>('[data-admin-row]'));

        if (this.rows.length === 0) {
            this.selectedId = null;
            return;
        }

        if (!this.selectedId) {
            this.clearSelection();
            return;
        }

        const current = this.rows.find((row) => this.rowId(row) === this.selectedId);

        if (current) {
            this.applySelection(current, false, false);
        } else {
            this.clearSelection();
        }
    }

    applySelection(row: HTMLElement, scroll = true, focus = true): void {
        this.rows.forEach((item) => {
            const isSelected = item === row;
            item.classList.toggle('admin-row-selected', isSelected);
            item.setAttribute('aria-selected', isSelected ? 'true' : 'false');
        });

        this.selectedId = this.rowId(row);

        if (focus) {
            try {
                row.focus({ preventScroll: true });
            } catch {
                row.focus();
            }
        }

        if (scroll) {
            row.scrollIntoView({ block: 'nearest' });
        }
    }

    move(delta: number): void {
        if (this.rows.length === 0) {
            return;
        }

        const currentIndex = this.rows.findIndex((row) => this.rowId(row) === this.selectedId);
        const safeIndex = currentIndex >= 0 ? currentIndex : -1;
        const targetIndex = Math.min(Math.max(safeIndex + delta, 0), this.rows.length - 1);

        this.applySelection(this.rows[targetIndex]);
    }

    currentRow(): HTMLElement | null {
        if (this.rows.length === 0 || !this.selectedId) {
            return null;
        }

        return this.rows.find((row) => this.rowId(row) === this.selectedId) ?? null;
    }

    deleteSelected(): boolean {
        const row = this.currentRow();

        if (!row) {
            return false;
        }

        const trigger = row.querySelector<HTMLElement>('[data-row-delete]');

        if (!trigger) {
            return false;
        }

        try {
            trigger.focus({ preventScroll: true });
        } catch {
            trigger.focus();
        }

        trigger.click();

        return true;
    }

    clearSelection(): void {
        this.rows.forEach((row) => {
            row.classList.remove('admin-row-selected');
            row.setAttribute('aria-selected', 'false');
        });

        this.selectedId = null;
    }
}

const initAdminKeyboardShortcuts = (): void => {
    const adminRoot = document.querySelector<HTMLElement>('[data-admin-keyboard]');

    if (!adminRoot) {
        return;
    }

    const tables = new Map<HTMLElement, AdminTableNavigator>();
    let activeTable: AdminTableNavigator | null = null;
    let refreshTimer: number | null = null;

    const refreshTables = (): void => {
        const tableElements = Array.from(adminRoot.querySelectorAll<HTMLElement>('[data-admin-table]'));
        const nextTables = new Map<HTMLElement, AdminTableNavigator>();

        tableElements.forEach((element) => {
            const navigator = tables.get(element) ?? new AdminTableNavigator(element);
            navigator.refreshRows();
            nextTables.set(element, navigator);
        });

        tables.clear();
        nextTables.forEach((navigator, element) => {
            tables.set(element, navigator);
        });

        if (!activeTable || !tables.has(activeTable.element)) {
            activeTable = tableElements.length ? tables.get(tableElements[0]) ?? null : null;
        }
    };

    refreshTables();

    const observer = new MutationObserver(() => {
        if (refreshTimer !== null) {
            window.clearTimeout(refreshTimer);
        }

        refreshTimer = window.setTimeout(() => refreshTables(), 80);
    });

    observer.observe(adminRoot, { childList: true, subtree: true });

    adminRoot.addEventListener('click', (event) => {
        const row = (event.target as HTMLElement | null)?.closest<HTMLElement>('[data-admin-row]');

        if (!row) {
            return;
        }

        const tableElement = row.closest<HTMLElement>('[data-admin-table]');

        if (!tableElement) {
            return;
        }

        const navigator = tables.get(tableElement);

        if (!navigator) {
            return;
        }

        navigator.refreshRows();
        navigator.applySelection(row, false);
        activeTable = navigator;
    });

    const openCreateModal = (): void => {
        const trigger = adminRoot.querySelector<HTMLElement>('[data-admin-create-trigger]');

        if (trigger) {
            trigger.focus();
            trigger.click();
        }
    };

    const closeActiveModal = (): boolean => {
        const fluxDialog = findOpenFluxDialog();

        if (fluxDialog) {
            const modalName = fluxDialog.getAttribute('data-modal');
            document.dispatchEvent(
                new CustomEvent('modal-close', {
                    detail: modalName ? { name: modalName } : {},
                }),
            );
            fluxDialog.close();

            return true;
        }

        const overlay = document.querySelector<HTMLElement>('[data-admin-modal]');

        if (overlay) {
            const closer = overlay.querySelector<HTMLElement>('[data-admin-modal-close]');
            closer?.click();

            return true;
        }

        return false;
    };

    const handleKeyDown = (event: KeyboardEvent): void => {
        if (event.defaultPrevented) {
            return;
        }

        const target = event.target as HTMLElement | null;
        const fluxDialog = findOpenFluxDialog();
        const overlay = document.querySelector<HTMLElement>('[data-admin-modal]');

        if (fluxDialog || overlay) {
            if (event.key === 'Escape') {
                event.preventDefault();
                closeActiveModal();
            }

            return;
        }

        if (event.key.toLowerCase() === 'c' && event.shiftKey && (event.metaKey || event.ctrlKey)) {
            event.preventDefault();
            openCreateModal();

            return;
        }

        if (isTextEntry(target)) {
            return;
        }

        if (event.key === 'ArrowDown') {
            if (activeTable) {
                event.preventDefault();
                activeTable.refreshRows();
                activeTable.move(1);
            }

            return;
        }

        if (event.key === 'ArrowUp') {
            if (activeTable) {
                event.preventDefault();
                activeTable.refreshRows();
                activeTable.move(-1);
            }

            return;
        }

        if (event.key === 'Delete') {
            if (activeTable) {
                event.preventDefault();
                activeTable.refreshRows();
                activeTable.deleteSelected();
            }
        }
    };

    document.addEventListener('keydown', handleKeyDown);
};

document.addEventListener('DOMContentLoaded', () => {
    initThemeToggle();
    initMobileNav();
    initClearPublishedAtButtons();
    initValidationFeedback();
    initAdminKeyboardShortcuts();
});
