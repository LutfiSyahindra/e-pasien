const mobileViewport = window.matchMedia('(max-width: 767px)');
const sheetSelector = '.ep-message-menu, .ep-notification-menu';
const backdrop = document.querySelector('[data-navbar-sheet-backdrop]');

const activeSheet = () => document.querySelector('.ep-message-menu.show, .ep-notification-menu.show');

const closeSheet = (sheet = activeSheet()) => {
    const trigger = sheet?.closest('.dropdown')?.querySelector('[data-bs-toggle="dropdown"]');
    if (!trigger) return;

    if (window.bootstrap?.Dropdown) {
        window.bootstrap.Dropdown.getOrCreateInstance(trigger).hide();
        return;
    }

    trigger.click();
};

const syncSheetState = () => {
    const sheet = mobileViewport.matches ? activeSheet() : null;
    const isOpen = Boolean(sheet);

    document.body.classList.toggle('ep-navbar-sheet-open', isOpen);
    if (backdrop) backdrop.hidden = !isOpen;

    document.querySelectorAll(sheetSelector).forEach((panel) => {
        if (isOpen && panel === sheet) panel.setAttribute('aria-modal', 'true');
        else panel.removeAttribute('aria-modal');
    });
};

document.querySelectorAll('[data-navbar-sheet-close]').forEach((button) => {
    button.addEventListener('click', () => closeSheet(button.closest(sheetSelector)));
});

backdrop?.addEventListener('click', () => closeSheet());

document.querySelectorAll('.ep-message-nav, .ep-notification-nav').forEach((dropdown) => {
    dropdown.addEventListener('shown.bs.dropdown', () => {
        syncSheetState();
        dropdown.querySelector('.ep-message-list, .ep-notification-list')?.scrollTo({ top: 0 });
    });
    dropdown.addEventListener('hidden.bs.dropdown', () => window.queueMicrotask(syncSheetState));
});

mobileViewport.addEventListener('change', syncSheetState);
syncSheetState();
