
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

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('select:not([data-searchable="false"])').forEach((select, index) => {
        const label = select.labels?.[0];
        const wrapper = document.createElement('div');
        const search = document.createElement('input');
        const list = document.createElement('div');
        const listId = `select-search-options-${index}`;
        let matches = [];
        let activeIndex = -1;

        wrapper.className = 'select-search';
        search.type = 'search';
        search.className = 'select-search-input';
        search.id = select.id ? `${select.id}-search` : listId;
        search.autocomplete = 'off';
        search.placeholder = label
            ? `Cari ${label.textContent.trim().replace(/\*$/, '').toLowerCase()}...`
            : 'Ketik untuk mencari pilihan...';
        search.setAttribute('role', 'combobox');
        search.setAttribute('aria-autocomplete', 'list');
        search.setAttribute('aria-controls', listId);
        search.setAttribute('aria-expanded', 'false');
        search.setAttribute('aria-required', String(select.required));
        search.required = select.required;

        list.id = listId;
        list.className = 'select-search-options';
        list.setAttribute('role', 'listbox');
        list.hidden = true;

        if (label) {
            label.htmlFor = search.id;
        }

        select.required = false;
        select.classList.add('select-search-native');
        select.parentNode.insertBefore(wrapper, select);
        wrapper.append(search, list, select);

        const selectedOption = () => select.options[select.selectedIndex];
        const closeList = (restoreSelection = false) => {
            list.hidden = true;
            search.setAttribute('aria-expanded', 'false');
            search.removeAttribute('aria-activedescendant');
            activeIndex = -1;

            if (restoreSelection) {
                search.value = selectedOption()?.textContent.trim() ?? '';
                search.setCustomValidity('');
            }
        };

        const chooseOption = (option) => {
            select.value = option.value;
            search.value = option.textContent.trim();
            search.setCustomValidity('');
            closeList();
            select.dispatchEvent(new Event('change', { bubbles: true }));
        };

        const renderOptions = () => {
            const query = search.value.trim().toLocaleLowerCase();
            matches = Array.from(select.options).filter((option) =>
                !option.disabled
                && option.textContent.trim().toLocaleLowerCase().includes(query)
            );
            list.replaceChildren();
            activeIndex = -1;

            matches.forEach((option, optionIndex) => {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'select-search-option';
                item.id = `${listId}-option-${optionIndex}`;
                item.setAttribute('role', 'option');
                item.setAttribute('aria-selected', String(option.value === select.value));
                item.textContent = option.textContent.trim();
                item.addEventListener('mousedown', (event) => event.preventDefault());
                item.addEventListener('click', () => chooseOption(option));
                item.addEventListener('mousemove', () => setActiveOption(optionIndex));
                list.append(item);
            });

            if (matches.length === 0) {
                const empty = document.createElement('div');
                empty.className = 'select-search-empty';
                empty.textContent = 'Pilihan tidak ditemukan.';
                list.append(empty);
            }

            list.hidden = false;
            search.setAttribute('aria-expanded', 'true');
        };

        const setActiveOption = (index) => {
            const options = list.querySelectorAll('[role="option"]');
            if (options.length === 0) return;

            activeIndex = ((index % options.length) + options.length) % options.length;
            options.forEach((option, optionIndex) => {
                option.classList.toggle('active', optionIndex === activeIndex);
            });
            search.setAttribute('aria-activedescendant', options[activeIndex].id);
            options[activeIndex].scrollIntoView({ block: 'nearest' });
        };

        search.addEventListener('focus', () => {
            search.value = '';
            renderOptions();
        });
        search.addEventListener('input', () => {
            select.value = '';
            search.setCustomValidity('Pilih salah satu pilihan yang muncul.');
            select.dispatchEvent(new Event('change', { bubbles: true }));
            renderOptions();
        });
        search.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                if (list.hidden) renderOptions();
                setActiveOption(activeIndex + (event.key === 'ArrowDown' ? 1 : -1));
            } else if (event.key === 'Enter' && !list.hidden && activeIndex >= 0) {
                event.preventDefault();
                chooseOption(matches[activeIndex]);
            } else if (event.key === 'Escape') {
                closeList(true);
            }
        });
        search.addEventListener('blur', () => {
            window.setTimeout(() => closeList(true), 120);
        });
        select.addEventListener('change', () => {
            if (document.activeElement !== search) {
                search.value = selectedOption()?.textContent.trim() ?? '';
                search.setCustomValidity('');
            }
        });
        document.addEventListener('click', (event) => {
            if (!wrapper.contains(event.target)) {
                closeList(true);
            }
        });

        search.value = selectedOption()?.textContent.trim() ?? '';
    });
});
