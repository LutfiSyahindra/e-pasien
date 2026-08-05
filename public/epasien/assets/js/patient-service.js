(function () {
    'use strict';

    const root = document.querySelector('[data-patient-service]');
    if (!root) return;

    const currentUserId = Number(root.dataset.userId || 0);
    const activeConversationId = Number(root.dataset.activeConversation || 0);
    const canManage = root.dataset.canManage === '1';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const messageList = root.querySelector('[data-message-list]');
    const conversationList = root.querySelector('[data-conversation-list]');
    const messageForm = root.querySelector('[data-message-form]');
    const messageInput = root.querySelector('[data-message-input]');
    const statusToggle = root.querySelector('[data-status-toggle]');
    const realtimeLabel = root.querySelector('[data-realtime-label]');
    const workspace = root.querySelector('[data-service-workspace]');
    let latestMessageId = Math.max(0, ...Array.from(root.querySelectorAll('[data-message-id]')).map((element) => Number(element.dataset.messageId)));
    let toastTimer;
    let requestBusy = false;
    let readRequest = null;
    let realtimeConnected = false;
    let pollTimer = null;
    const receiptRank = { sent: 1, delivered: 2, read: 3 };
    const receiptLabels = { sent: 'Terkirim', delivered: 'Sudah masuk', read: 'Sudah dibaca' };

    root.querySelector('[data-mobile-inbox-toggle]')?.addEventListener('click', () => {
        workspace?.classList.remove('mobile-chat-open');
        workspace?.classList.add('mobile-inbox-open');
        window.requestAnimationFrame(() => conversationList?.querySelector('.is-active')?.focus());
    });

    const request = async (url, options = {}) => {
        const headers = Object.assign({
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        }, options.headers || {});

        const socketId = window.Echo?.socketId?.();
        if (socketId) headers['X-Socket-ID'] = socketId;

        const response = await fetch(url, Object.assign({
            credentials: 'same-origin',
            headers,
        }, options));
        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            const validationMessage = payload.errors
                ? Object.values(payload.errors).flat()[0]
                : null;
            throw new Error(validationMessage || payload.message || 'Permintaan belum dapat diproses. Silakan coba lagi.');
        }

        return payload;
    };

    const showToast = (message, isError = false) => {
        const toast = root.querySelector('[data-service-toast]');
        if (!toast) return;

        window.clearTimeout(toastTimer);
        toast.hidden = false;
        toast.classList.toggle('is-error', isError);
        toast.querySelector('i').className = isError ? 'bi bi-exclamation-circle-fill' : 'bi bi-check-circle-fill';
        toast.querySelector('span').textContent = message;
        toastTimer = window.setTimeout(() => { toast.hidden = true; }, 3600);
    };

    const scrollMessages = (smooth = false) => {
        if (!messageList) return;
        messageList.scrollTo({ top: messageList.scrollHeight, behavior: smooth ? 'smooth' : 'auto' });
    };

    const updateReceipt = (messageId, status) => {
        if (!receiptRank[status]) return;
        const receipt = messageList?.querySelector(`[data-message-id="${Number(messageId)}"] [data-message-receipt]`);
        if (!receipt || receiptRank[status] < (receiptRank[receipt.dataset.status] || 0)) return;

        receipt.dataset.status = status;
        receipt.className = `ps-message__receipt is-${status}`;
        receipt.querySelector('i').className = status === 'sent' ? 'bi bi-check2' : 'bi bi-check2-all';
        receipt.querySelector('span').textContent = receiptLabels[status];
    };

    const appendMessage = (message, smooth = true) => {
        if (!messageList || Number(message.conversation_id) !== activeConversationId) return;
        if (messageList.querySelector(`[data-message-id="${Number(message.id)}"]`)) return;

        const isMine = Number(message.sender_id) === currentUserId;
        const article = document.createElement('article');
        article.className = `ps-message ${isMine ? 'is-mine' : 'is-theirs'}`;
        article.dataset.messageId = String(message.id);

        const avatar = document.createElement('span');
        avatar.className = 'ps-message__avatar';
        avatar.textContent = message.sender_initials || 'PG';

        const content = document.createElement('div');
        content.className = 'ps-message__content';
        const meta = document.createElement('span');
        meta.className = 'ps-message__meta';
        const sender = document.createElement('strong');
        sender.textContent = isMine ? 'Anda' : (message.sender_name || 'Pengguna');
        const time = document.createElement('time');
        time.dateTime = message.created_at || '';
        time.textContent = message.time_label || 'baru';
        const body = document.createElement('p');
        body.textContent = message.body || '';

        meta.append(sender, time);
        content.append(meta, body);
        if (isMine) {
            const status = message.delivery_status || 'sent';
            const receipt = document.createElement('span');
            receipt.className = `ps-message__receipt is-${status}`;
            receipt.dataset.messageReceipt = '';
            receipt.dataset.status = status;
            const receiptIcon = document.createElement('i');
            receiptIcon.className = status === 'sent' ? 'bi bi-check2' : 'bi bi-check2-all';
            const receiptLabel = document.createElement('span');
            receiptLabel.textContent = message.delivery_status_label || receiptLabels[status] || receiptLabels.sent;
            receipt.append(receiptIcon, receiptLabel);
            content.append(receipt);
        }
        article.append(avatar, content);
        messageList.append(article);
        latestMessageId = Math.max(latestMessageId, Number(message.id));
        scrollMessages(smooth);
    };

    const statusClass = (status) => `is-${status || 'waiting_admin'}`;

    const updateActiveStatus = (conversation) => {
        if (Number(conversation.id) !== activeConversationId) return;

        const badge = root.querySelector('[data-active-status]');
        if (badge) {
            badge.className = `ps-status ${statusClass(conversation.status)}`;
            const label = badge.querySelector('span');
            if (label) label.textContent = conversation.status_label;
        }

        const isClosed = conversation.status === 'closed';
        const composer = root.querySelector('[data-composer]');
        const closedNotice = root.querySelector('[data-closed-notice]');
        if (composer) composer.classList.toggle('is-closed', isClosed);
        if (closedNotice) closedNotice.hidden = !isClosed;
        if (messageForm) messageForm.hidden = isClosed;

        if (statusToggle) {
            statusToggle.dataset.nextStatus = isClosed ? 'open' : 'closed';
            statusToggle.querySelector('i').className = isClosed ? 'bi bi-arrow-counterclockwise' : 'bi bi-check2-circle';
            const actionLabel = isClosed ? 'Buka kembali percakapan' : 'Tandai percakapan selesai';
            statusToggle.setAttribute('aria-label', actionLabel);
            statusToggle.setAttribute('title', actionLabel);
            const label = statusToggle.querySelector('span');
            if (label) label.textContent = isClosed ? 'Buka kembali' : 'Tandai selesai';
        }
    };

    const conversationHref = (conversation) => {
        const url = new URL(root.dataset.listUrl, window.location.origin);
        url.searchParams.set('conversation', conversation.id);
        return `${url.pathname}${url.search}`;
    };

    const createConversationCard = (conversation) => {
        const link = document.createElement('a');
        link.className = 'ps-conversation';
        link.dataset.conversationId = String(conversation.id);
        link.href = conversationHref(conversation);

        const avatar = document.createElement('span');
        avatar.className = 'ps-conversation__avatar';
        avatar.textContent = conversation.patient_initials || 'PS';
        const body = document.createElement('span');
        body.className = 'ps-conversation__body';
        const top = document.createElement('span');
        top.className = 'ps-conversation__top';
        const patient = document.createElement('strong');
        patient.dataset.patientName = '';
        patient.textContent = canManage ? conversation.patient_name : conversation.category_label;
        const time = document.createElement('small');
        time.dataset.conversationTime = '';
        time.textContent = 'baru';
        top.append(patient, time);
        const subject = document.createElement('span');
        subject.className = 'ps-conversation__subject';
        subject.dataset.conversationSubject = '';
        subject.textContent = conversation.subject;
        const preview = document.createElement('span');
        preview.className = 'ps-conversation__preview';
        preview.dataset.conversationPreview = '';
        preview.textContent = conversation.last_message || 'Percakapan baru';
        const footer = document.createElement('span');
        footer.className = 'ps-conversation__footer';
        const status = document.createElement('em');
        status.dataset.conversationStatus = '';
        status.className = statusClass(conversation.status);
        status.textContent = conversation.status_label;
        footer.append(status);
        body.append(top, subject, preview, footer);
        link.append(avatar, body);
        return link;
    };

    const upsertConversation = (conversation, incrementUnread = false) => {
        if (!conversationList || !conversation) return;

        let card = conversationList.querySelector(`[data-conversation-id="${Number(conversation.id)}"]`);
        if (!card) {
            root.querySelector('[data-inbox-empty]')?.remove();
            card = createConversationCard(conversation);
            conversationList.prepend(card);
        }

        const patient = card.querySelector('[data-patient-name]');
        const subject = card.querySelector('[data-conversation-subject]');
        const preview = card.querySelector('[data-conversation-preview]');
        const time = card.querySelector('[data-conversation-time]');
        const status = card.querySelector('[data-conversation-status]');
        if (patient) patient.textContent = canManage ? conversation.patient_name : conversation.category_label;
        if (subject) subject.textContent = conversation.subject;
        if (preview) preview.textContent = conversation.last_message || 'Percakapan diperbarui';
        if (time) time.textContent = 'baru';
        if (status) {
            status.className = statusClass(conversation.status);
            status.textContent = conversation.status_label;
        }

        if (incrementUnread && Number(conversation.id) !== activeConversationId) {
            const footer = card.querySelector('.ps-conversation__footer');
            let badge = card.querySelector('[data-unread-count]');
            if (!badge && footer) {
                badge = document.createElement('b');
                badge.dataset.unreadCount = '';
                footer.append(badge);
            }
            if (badge) {
                const count = Math.min(10, Number.parseInt(badge.textContent, 10) + 1 || 1);
                badge.textContent = count > 9 ? '9+' : String(count);
            }
        }

        if (Number(conversation.id) === activeConversationId) card.querySelector('[data-unread-count]')?.remove();
        conversationList.prepend(card);
        updateActiveStatus(conversation);
    };

    const markRead = () => {
        if (!root.dataset.readUrl || document.visibilityState !== 'visible') return Promise.resolve();
        if (readRequest) return readRequest;

        readRequest = request(root.dataset.readUrl, { method: 'PATCH', body: '{}' })
            .then((result) => {
                conversationList?.querySelector(`[data-conversation-id="${activeConversationId}"] [data-unread-count]`)?.remove();
                try {
                    window.sessionStorage.removeItem(`epasien.notifications.v1.${currentUserId}`);
                } catch (_) {}
                const notificationBadge = document.querySelector('[data-notification-badge]');
                if (notificationBadge && result.notification_unread_count !== undefined) {
                    const unread = Number(result.notification_unread_count) || 0;
                    notificationBadge.textContent = unread > 99 ? '99+' : String(unread);
                    notificationBadge.hidden = unread < 1;
                }
                window.dispatchEvent(new CustomEvent('epasien:patient-service-read', {
                    detail: {
                        conversationId: activeConversationId,
                        notificationUnreadCount: result.notification_unread_count,
                    },
                }));
            })
            .catch(() => {
                // Sinkronisasi baca akan dicoba kembali saat pesan berikutnya masuk.
            })
            .finally(() => { readRequest = null; });

        return readRequest;
    };

    const handleRealtimeMessage = (event) => {
        if (!event?.conversation || !event?.message) return;
        const fromAnotherUser = Number(event.message.sender_id) !== currentUserId;
        upsertConversation(event.conversation, fromAnotherUser);

        if (Number(event.conversation.id) === activeConversationId) {
            appendMessage(event.message);
            if (fromAnotherUser) markRead();
        } else if (fromAnotherUser) {
            showToast(`${event.conversation.patient_name || 'Pasien'}: ${event.conversation.subject}`);
        }
    };

    const pollMessages = async () => {
        if (!root.dataset.messagesUrl || requestBusy || document.hidden) return;
        try {
            const url = new URL(root.dataset.messagesUrl, window.location.origin);
            url.searchParams.set('after_id', latestMessageId);
            const payload = await request(url.toString(), { method: 'GET' });
            (payload.messages || []).forEach((message) => appendMessage(message, false));
            Object.entries(payload.receipt_statuses || {}).forEach(([messageId, status]) => updateReceipt(messageId, status));
            upsertConversation(payload.conversation);
            if ((payload.messages || []).some((message) => Number(message.sender_id) !== currentUserId)) markRead();
        } catch (_) {
            // Realtime atau percobaan sinkronisasi berikutnya tetap menjaga percakapan aktif.
        }
    };

    const initializeRealtime = async () => {
        const echo = window.Echo || await window.initializeEpasienRealtime?.();
        if (!echo) {
            if (realtimeLabel) realtimeLabel.textContent = 'Sinkronisasi otomatis aktif';
            return;
        }

        realtimeConnected = true;
        if (realtimeLabel) realtimeLabel.textContent = 'Terhubung secara realtime';
        const channelName = canManage
            ? 'patient-service.admin'
            : (activeConversationId ? `patient-service.conversation.${activeConversationId}` : null);
        if (!channelName) return;

        echo.private(channelName)
            .listen('.patient-service.message.sent', handleRealtimeMessage)
            .listen('.patient-service.receipt.updated', (event) => {
                if (Number(event?.conversation_id) !== activeConversationId) return;
                (event.message_ids || []).forEach((messageId) => updateReceipt(messageId, event.status));
            })
            .listen('.patient-service.conversation.updated', (event) => {
                if (!event?.conversation) return;
                upsertConversation(event.conversation);
                if (Number(event.conversation.id) === activeConversationId) {
                    showToast(event.conversation.status === 'closed' ? 'Percakapan telah diselesaikan.' : 'Percakapan dibuka kembali.');
                }
            });
    };

    const schedulePolling = () => {
        window.clearTimeout(pollTimer);
        pollTimer = window.setTimeout(async () => {
            await pollMessages();
            schedulePolling();
        }, realtimeConnected ? 45000 : 10000);
    };

    messageForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const body = messageInput.value.trim();
        if (!body || requestBusy) return;

        requestBusy = true;
        const submitButton = messageForm.querySelector('button[type="submit"]');
        if (submitButton) submitButton.disabled = true;

        try {
            const payload = await request(root.dataset.sendUrl, {
                method: 'POST',
                body: JSON.stringify({ message: body }),
            });
            appendMessage(payload.message);
            upsertConversation(payload.conversation);
            messageInput.value = '';
            messageInput.style.height = '';
        } catch (error) {
            showToast(error.message, true);
        } finally {
            requestBusy = false;
            if (submitButton) submitButton.disabled = false;
            messageInput.focus();
        }
    });

    messageInput?.addEventListener('input', () => {
        messageInput.style.height = 'auto';
        messageInput.style.height = `${Math.min(messageInput.scrollHeight, 110)}px`;
    });

    messageInput?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
            event.preventDefault();
            messageForm?.requestSubmit();
        }
    });

    statusToggle?.addEventListener('click', async () => {
        if (requestBusy) return;
        requestBusy = true;
        statusToggle.disabled = true;
        try {
            const payload = await request(root.dataset.statusUrl, {
                method: 'PATCH',
                body: JSON.stringify({ status: statusToggle.dataset.nextStatus }),
            });
            upsertConversation(payload.conversation);
            showToast(payload.message);
        } catch (error) {
            showToast(error.message, true);
        } finally {
            requestBusy = false;
            statusToggle.disabled = false;
        }
    });

    const newConversationForm = document.querySelector('[data-new-conversation-form]');
    newConversationForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (requestBusy) return;
        requestBusy = true;
        const submitButton = newConversationForm.querySelector('button[type="submit"]');
        const errorBox = newConversationForm.querySelector('[data-new-conversation-error]');
        submitButton.disabled = true;
        errorBox.hidden = true;

        try {
            const formData = new FormData(newConversationForm);
            const payload = await request(root.dataset.conversationsUrl, {
                method: 'POST',
                body: JSON.stringify(Object.fromEntries(formData.entries())),
            });
            window.location.assign(payload.redirect_url);
        } catch (error) {
            errorBox.textContent = error.message;
            errorBox.hidden = false;
            submitButton.disabled = false;
            requestBusy = false;
        }
    });

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            pollMessages();
            markRead();
        }
    });

    window.addEventListener('epasien:patient-service-mark-read-requested', (event) => {
        if (Number(event.detail?.conversationId) === activeConversationId) markRead();
    });

    scrollMessages();
    markRead();
    initializeRealtime().finally(schedulePolling);
}());
