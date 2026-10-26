
document.addEventListener('DOMContentLoaded', () => {
    const menuButton = document.querySelector('[data-menu-toggle]');
    const sidebar = document.querySelector('[data-sidebar]');
    const layout = sidebar?.closest('.app-layout');

    if (menuButton && sidebar && layout) {
        const isMobile = () => window.matchMedia('(max-width: 900px)').matches;
        const setCollapsedLinks = (collapsed) => {
            sidebar.querySelectorAll('.nav-link').forEach((link) => {
                if (collapsed) {
                    const label = link.textContent.trim();
                    link.title = label;
                    link.setAttribute('aria-label', label);
                } else {
                    link.removeAttribute('title');
                    link.removeAttribute('aria-label');
                }
            });
        };
        const closeMobileSidebar = () => {
            sidebar.classList.remove('sidebar-open');
            layout.classList.remove('sidebar-overlay');
            menuButton.setAttribute('aria-expanded', 'false');
            menuButton.setAttribute('aria-label', 'Buka menu');
        };

        menuButton.addEventListener('click', () => {
            if (isMobile()) {
                const isOpen = sidebar.classList.toggle('sidebar-open');
                layout.classList.toggle('sidebar-overlay', isOpen);
                menuButton.setAttribute('aria-expanded', String(isOpen));
                menuButton.setAttribute('aria-label', isOpen ? 'Tutup menu' : 'Buka menu');
                return;
            }

            const isCollapsed = layout.classList.toggle('sidebar-collapsed');
            setCollapsedLinks(isCollapsed);
            menuButton.setAttribute('aria-expanded', String(!isCollapsed));
            menuButton.setAttribute('aria-label', isCollapsed ? 'Perluas sidebar' : 'Ciutkan sidebar');
        });

        document.addEventListener('click', (event) => {
            if (isMobile()
                && sidebar.classList.contains('sidebar-open')
                && !sidebar.contains(event.target)
                && !menuButton.contains(event.target)) {
                closeMobileSidebar();
            }
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && sidebar.classList.contains('sidebar-open')) {
                closeMobileSidebar();
                menuButton.focus();
            }
        });
        window.addEventListener('resize', () => {
            if (!isMobile()) {
                sidebar.classList.remove('sidebar-open');
                layout.classList.remove('sidebar-overlay');
                menuButton.setAttribute('aria-expanded', String(!layout.classList.contains('sidebar-collapsed')));
            } else {
                layout.classList.remove('sidebar-collapsed');
                setCollapsedLinks(false);
            }
        });
    }

    document.addEventListener('click', (event) => {
        document.querySelectorAll('[data-profile-menu][open]').forEach((profileMenu) => {
            if (!profileMenu.contains(event.target)) {
                profileMenu.removeAttribute('open');
            }
        });
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            document.querySelectorAll('[data-profile-menu][open]').forEach((profileMenu) => {
                profileMenu.removeAttribute('open');
            });
        }
    });

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

    {
        const root = document.querySelector('[data-notifications]');
        if (root) {

        const toggle = root.querySelector('[data-notification-toggle]');
        const panel = root.querySelector('[data-notification-panel]');
        const badge = root.querySelector('[data-notification-count]');
        const items = root.querySelector('[data-notification-items]');
        const readAll = root.querySelector('[data-notification-read-all]');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        const render = (payload) => {
            badge.textContent = payload.unread_count > 99 ? '99+' : String(payload.unread_count);
            badge.hidden = payload.unread_count === 0;
            items.replaceChildren();

            if (!payload.items.length) {
                const empty = document.createElement('div');
                empty.className = 'notification-panel-empty';
                empty.textContent = 'Belum ada notifikasi.';
                items.append(empty);
                return;
            }

            payload.items.forEach((notification) => {
                const card = document.createElement('article');
                card.className = `notification-item${notification.read ? '' : ' unread'}`;

                const icon = document.createElement('span');
                icon.className = `notification-item-icon ${notification.tone}`;
                icon.textContent = notification.icon;
                icon.setAttribute('aria-hidden', 'true');

                const content = document.createElement('div');
                const title = document.createElement('strong');
                title.textContent = notification.title;
                const summary = document.createElement('p');
                summary.textContent = notification.message;
                const time = document.createElement('small');
                time.textContent = `${notification.created_at} · ${notification.read ? 'Dibaca' : 'Belum dibaca'}`;
                content.append(title, summary, time);

                let markRead;
                if (!notification.read) {
                    markRead = document.createElement('button');
                    markRead.type = 'button';
                    markRead.className = 'notification-read-action';
                    markRead.textContent = 'Tandai dibaca';
                    markRead.addEventListener('click', async () => {
                        await updateReadStatus(notification.read_url, notification.read_method);
                    });
                }

                if (notification.action_url) {
                    const link = document.createElement('a');
                    link.className = 'notification-item-link';
                    link.href = notification.action_url;
                    link.setAttribute('aria-label', `Buka: ${notification.title}`);
                    link.append(icon, content);
                    card.append(link);
                } else {
                    card.append(icon, content);
                }
                if (markRead) card.append(markRead);
                items.append(card);
            });
        };

        const loadNotifications = async () => {
            try {
                const response = await fetch(root.dataset.feed, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                if (!response.ok) throw new Error('Notifikasi gagal dimuat.');
                render(await response.json());
            } catch (error) {
                items.textContent = error.message || 'Notifikasi tidak dapat dimuat.';
            }
        };

        const updateReadStatus = async (url, method) => {
            try {
                const response = await fetch(url, {
                    method,
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
                });
                if (!response.ok) throw new Error('Status baca notifikasi gagal diperbarui.');
                await loadNotifications();
            } catch (error) {
                items.textContent = error.message || 'Status notifikasi tidak dapat diperbarui.';
            }
        };

        toggle.addEventListener('click', (event) => {
            event.preventDefault();
            panel.hidden = !panel.hidden;
            toggle.setAttribute('aria-expanded', String(!panel.hidden));
            if (!panel.hidden) loadNotifications();
        });
        if (readAll) {
            readAll.addEventListener('click', () => updateReadStatus(root.dataset.readAll, 'POST'));
        }
        document.addEventListener('click', (event) => {
            if (!root.contains(event.target)) {
                panel.hidden = true;
                toggle.setAttribute('aria-expanded', 'false');
            }
        });

        loadNotifications();
        window.setInterval(loadNotifications, 60000);
        }
    }

    document.addEventListener('input', (event) => {
        const receivedInput = event.target.closest('[data-received-quantity]');
        if (!receivedInput) return;

        const row = receivedInput.closest('tr');
        const shortageInput = row?.querySelector('[data-short-quantity]');
        if (!shortageInput) return;

        const dispatched = Number(receivedInput.dataset.dispatched);
        const received = Number(receivedInput.value);
        shortageInput.value = String(Math.max(0, dispatched - received));
        shortageInput.max = String(dispatched);
    });

    {
        document.querySelectorAll('[data-password-toggle]').forEach((button) => {
            const password = document.getElementById(button.getAttribute('aria-controls'));
            if (!password) return;

            button.addEventListener('click', () => {
                const visible = password.type === 'password';
                password.type = visible ? 'text' : 'password';
                button.textContent = visible ? 'Sembunyi' : 'Lihat';
                button.setAttribute('aria-pressed', String(visible));
            });
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
