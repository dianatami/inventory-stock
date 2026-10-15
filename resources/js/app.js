
document.addEventListener('DOMContentLoaded', () => {
    const menuButton = document.querySelector('[data-menu-toggle]');
    const sidebar = document.querySelector('[data-sidebar]');

    if (menuButton && sidebar) {
        menuButton.addEventListener('click', () => {
            sidebar.classList.toggle('sidebar-open');
        });
    }

    document.querySelectorAll('[data-confirm]').forEach((element) => {
        element.addEventListener('click', (event) => {
            const message =
                element.getAttribute('data-confirm') ||
                'Apakah kamu yakin ingin melanjutkan?';

            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const menuToggle = document.querySelector('[data-menu-toggle]');
    const sidebar = document.querySelector('#sidebar');

    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('sidebar-open');
        });
    }
});
