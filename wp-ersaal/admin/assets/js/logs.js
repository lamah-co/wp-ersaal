document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('ersaal-log-modal');
    const panel = modal ? modal.querySelector('.ersaal-modal-panel') : null;
    const closeButton = document.getElementById('ersaal-close-modal');
    const detailList = document.getElementById('ersaal-log-detail-list');
    const messageElement = document.getElementById('ersaal-log-message');
    const messageSection = document.getElementById('ersaal-log-message-section');
    const copyMessageButton = document.getElementById('ersaal-copy-message');
    const deleteForm = document.getElementById('ersaal-single-delete-form');
    let lastTrigger = null;
    let currentMessage = '';

    function copyText(value, button, originalLabel) {
        if (!navigator.clipboard || !value) {
            return;
        }

        navigator.clipboard.writeText(value).then(function() {
            button.setAttribute('aria-label', ersaalLogs.i18n.copied);
            setTimeout(function() {
                button.setAttribute('aria-label', originalLabel);
            }, 1500);
        });
    }

    document.querySelectorAll('.ersaal-copy-id').forEach(function(button) {
        button.addEventListener('click', function() {
            copyText(button.dataset.clipboard, button, ersaalLogs.i18n.copy_id);
        });
    });

    document.querySelectorAll('.ersaal-delete-log').forEach(function(button) {
        button.addEventListener('click', function() {
            if (!window.confirm(ersaalLogs.i18n.delete_confirm)) {
                return;
            }
            document.getElementById('ersaal-single-delete-id').value = button.dataset.logId;
            deleteForm.submit();
        });
    });

    const bulkButton = document.querySelector('[name="ersaal_bulk_action"]');
    if (bulkButton) {
        bulkButton.addEventListener('click', function(event) {
            const action = document.getElementById('ersaal-bulk-action').value;
            if (action === 'delete' && !window.confirm(bulkButton.dataset.confirm)) {
                event.preventDefault();
            }
        });
    }

    function addDetail(label, value) {
        const term = document.createElement('dt');
        const description = document.createElement('dd');
        term.textContent = label;
        description.textContent = value === null || value === undefined || value === '' ? '—' : String(value);
        detailList.append(term, description);
    }

    function openModal(button) {
        const log = JSON.parse(button.dataset.log);
        const fields = ['id', 'status', 'source', 'source_id', 'source_event', 'phone_masked', 'message_id', 'parts_final', 'cost_final', 'attempts', 'api_http_code', 'api_error', 'created_at', 'updated_at'];
        lastTrigger = button;
        detailList.replaceChildren();

        fields.forEach(function(field) {
            addDetail(ersaalLogs.i18n.fields[field], log[field]);
        });

        currentMessage = log.message_text || log.message_excerpt || '';
        messageElement.textContent = currentMessage || '—';
        messageSection.hidden = !currentMessage;
        modal.setAttribute('aria-hidden', 'false');
        panel.focus();
    }

    function closeModal() {
        if (!modal || modal.getAttribute('aria-hidden') === 'true') {
            return;
        }
        modal.setAttribute('aria-hidden', 'true');
        if (lastTrigger) {
            lastTrigger.focus();
        }
    }

    document.querySelectorAll('.ersaal-view-log').forEach(function(button) {
        button.addEventListener('click', function() { openModal(button); });
    });

    if (closeButton) {
        closeButton.addEventListener('click', closeModal);
    }

    if (copyMessageButton) {
        copyMessageButton.addEventListener('click', function() {
            copyText(currentMessage, copyMessageButton, ersaalLogs.i18n.copy_message);
        });
    }

    if (modal) {
        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeModal();
            }
        });
    }

    document.addEventListener('keydown', function(event) {
        if (!modal || modal.getAttribute('aria-hidden') === 'true') {
            return;
        }

        if (event.key === 'Escape') {
            closeModal();
            return;
        }

        if (event.key === 'Tab') {
            const focusable = panel.querySelectorAll('button:not([disabled]), a[href], input:not([disabled]), [tabindex]:not([tabindex="-1"])');
            if (!focusable.length) {
                event.preventDefault();
                return;
            }
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    });
});
