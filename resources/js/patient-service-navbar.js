const endpoint = document.querySelector('meta[name="epasien-patient-service-navbar-url"]')?.content || '';
const deliveryEndpoint = document.querySelector('meta[name="epasien-patient-service-delivery-url"]')?.content || '';
const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
const nav = document.querySelector('.ep-message-nav');
const sidebarBadge = document.querySelector('[data-patient-service-sidebar-badge]');
const sidebarLink = document.querySelector('[data-patient-service-sidebar-link]');

if (endpoint && (nav || sidebarBadge)) {
    const list = nav?.querySelector('[data-navbar-message-list]');
    const badge = nav?.querySelector('[data-message-badge]');
    const search = nav?.querySelector('[data-message-search]');
    let loaded = false;
    let loading;
    const deliveryRequests = new Set();

    const acknowledgeDeliveries = (messageIds = []) => {
        if (!deliveryEndpoint) return;
        const ids = [...new Set(messageIds.map(Number).filter((id) => id > 0 && !deliveryRequests.has(id)))];
        if (!ids.length) return;
        ids.forEach((id) => deliveryRequests.add(id));

        fetch(deliveryEndpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({ message_ids: ids }),
        }).catch(() => {}).finally(() => ids.forEach((id) => deliveryRequests.delete(id)));
    };

    const setBadge = (count) => {
        const value = Number(count) || 0;
        const label = value > 0
            ? `Buka ${value} pesan Pasien Service yang belum dibaca`
            : 'Buka Pasien Service';

        if (badge) {
            badge.textContent = value > 99 ? '99+' : String(value);
            badge.hidden = value < 1;
        }
        if (sidebarBadge) {
            sidebarBadge.textContent = value > 99 ? '99+' : String(value);
            sidebarBadge.hidden = value < 1;
        }
        nav?.querySelector('a[role="button"]')?.setAttribute(
            'aria-label',
            value > 0 ? `Buka ${value} pesan Pasien Service yang belum dibaca` : 'Buka pesan Pasien Service',
        );
        sidebarLink?.setAttribute('aria-label', label);
    };

    const emptyState = (title = 'Belum ada percakapan', detail = 'Pesan Pasien Service akan muncul di sini.') => {
        const empty = document.createElement('div');
        empty.className = 'ep-message-empty';
        const icon = document.createElement('i'); icon.className = 'bi bi-chat-square-heart';
        const strong = document.createElement('strong'); strong.textContent = title;
        const small = document.createElement('small'); small.textContent = detail;
        empty.append(icon, strong, small);
        return empty;
    };

    const messageItem = (conversation) => {
        const link = document.createElement('a');
        link.className = `ep-message-item${conversation.unread_count > 0 ? ' is-unread' : ''}`;
        link.href = conversation.url;
        link.dataset.searchValue = `${conversation.name} ${conversation.subject} ${conversation.preview}`.toLocaleLowerCase('id');

        const avatar = document.createElement('span');
        avatar.className = 'ep-message-avatar';
        avatar.textContent = conversation.initials || 'PS';

        const copy = document.createElement('span');
        copy.className = 'ep-message-copy';
        const top = document.createElement('span');
        top.className = 'ep-message-copy__top';
        const name = document.createElement('strong'); name.textContent = conversation.name;
        const time = document.createElement('time'); time.textContent = conversation.time_label || 'baru';
        top.append(name, time);
        const subject = document.createElement('b'); subject.textContent = conversation.subject;
        const preview = document.createElement('p'); preview.textContent = conversation.preview;
        const bottom = document.createElement('span');
        bottom.className = 'ep-message-copy__bottom';
        const status = document.createElement('em');
        status.className = conversation.status === 'closed' ? 'is-closed' : '';
        status.textContent = conversation.status_label;
        bottom.append(status);
        if (conversation.unread_count > 0) {
            const count = document.createElement('b');
            count.textContent = conversation.unread_count > 9 ? '9+' : String(conversation.unread_count);
            bottom.append(count);
        }
        copy.append(top, subject, preview, bottom);
        link.append(avatar, copy);
        return link;
    };

    const applySearch = () => {
        if (!list) return;
        const query = (search?.value || '').trim().toLocaleLowerCase('id');
        let visible = 0;
        list.querySelectorAll('.ep-message-item').forEach((item) => {
            item.hidden = query !== '' && !item.dataset.searchValue.includes(query);
            if (!item.hidden) visible += 1;
        });
        list.querySelector('[data-message-search-empty]')?.remove();
        if (query && visible === 0) {
            const empty = emptyState('Percakapan tidak ditemukan', 'Coba gunakan kata kunci yang berbeda.');
            empty.dataset.messageSearchEmpty = '';
            list.append(empty);
        }
    };

    const render = (payload) => {
        setBadge(payload.unread_count);
        if (!list) return;
        list.replaceChildren();
        if (!payload.conversations?.length) {
            list.append(emptyState());
            return;
        }
        payload.conversations.forEach((conversation) => list.append(messageItem(conversation)));
        applySearch();
    };

    const load = ({ force = false } = {}) => {
        if (loaded && !force) return Promise.resolve();
        if (loading) return loading;

        loading = fetch(endpoint, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        }).then((response) => {
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            return response.json();
        }).then((payload) => {
            loaded = true;
            render(payload);
            acknowledgeDeliveries(payload.pending_delivery_message_ids || []);
        }).catch(() => {
            list?.replaceChildren(emptyState('Pesan belum dapat dimuat', 'Periksa koneksi lalu buka kembali menu pesan.'));
        }).finally(() => { loading = null; });

        return loading;
    };

    nav?.addEventListener('shown.bs.dropdown', () => load({ force: true }));
    search?.addEventListener('input', applySearch);
    window.addEventListener('epasien:patient-service-notification', (event) => {
        acknowledgeDeliveries([event.detail?.message_id]);
        load({ force: true });
    });
    window.addEventListener('epasien:patient-service-read', () => load({ force: true }));
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') load({ force: true });
    });

    load();
    window.setInterval(() => {
        if (document.visibilityState === 'visible') load({ force: true });
    }, 60000);
}
