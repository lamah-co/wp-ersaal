jQuery(function($) {
    const form = $('#ersaal-manual-send-form');
    const submitButton = $('#ersaal_submit_btn');
    const spinner = $('#ersaal_spinner');
    const responseArea = $('#ersaal_response_area');
    const messageArea = $('#ersaal_message');

    if (!form.length) {
        return;
    }

    function isGsm7(value) {
        const gsm7Characters = "@£$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞ\x1BÆæßÉ !\"#¤%&'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà";
        for (let index = 0; index < value.length; index++) {
            if (gsm7Characters.indexOf(value[index]) === -1) {
                return false;
            }
        }
        return true;
    }

    function calculateMessageParts(value) {
        const length = value.length;
        if (length === 0) {
            return { characters: 0, encoding: 'GSM-7', parts: 0 };
        }

        const gsm = isGsm7(value);
        const singlePartLimit = gsm ? 160 : 70;
        const multipartLimit = gsm ? 153 : 67;

        return {
            characters: length,
            encoding: gsm ? 'GSM-7' : 'Unicode',
            parts: length > singlePartLimit ? Math.ceil(length / multipartLimit) : 1
        };
    }

    messageArea.on('input', function() {
        const stats = calculateMessageParts($(this).val());
        $('#ersaal_char_count').text(stats.characters);
        $('#ersaal_encoding').text(stats.encoding);
        $('#ersaal_parts_count').text(stats.parts);
    });

    function generateUuid() {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') {
            return window.crypto.randomUUID();
        }

        let timestamp = Date.now();
        if (typeof performance !== 'undefined' && typeof performance.now === 'function') {
            timestamp += performance.now();
        }

        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(character) {
            const random = (timestamp + Math.random() * 16) % 16 | 0;
            timestamp = Math.floor(timestamp / 16);
            return (character === 'x' ? random : (random & 0x3 | 0x8)).toString(16);
        });
    }

    let idempotencyKey = generateUuid();

    function setSending(isSending) {
        submitButton.prop('disabled', isSending);
        submitButton.contents().filter(function() {
            return this.nodeType === Node.TEXT_NODE;
        }).last().replaceWith(isSending ? ersaalManualSend.i18n.sending : ersaalManualSend.i18n.send);
        spinner.toggleClass('is-active', isSending);
    }

    function showError(message) {
        responseArea
            .removeClass('ersaal-alert-success')
            .addClass('ersaal-alert-danger')
            .prop('hidden', false);
        $('#ersaal_response_title').text(ersaalManualSend.i18n.error_label);
        $('#ersaal_response_success_icon').prop('hidden', true);
        $('#ersaal_response_error_icon').prop('hidden', false);
        $('#ersaal_response_message').text(message).prop('hidden', false);
        $('#ersaal_response_details, #ersaal_response_logs_link').prop('hidden', true);
    }

    function showSuccess(data) {
        responseArea
            .removeClass('ersaal-alert-danger')
            .addClass('ersaal-alert-success')
            .prop('hidden', false);
        $('#ersaal_response_title').text(ersaalManualSend.i18n.success);
        $('#ersaal_response_success_icon').prop('hidden', false);
        $('#ersaal_response_error_icon').prop('hidden', true);
        $('#ersaal_response_message').prop('hidden', true).empty();
        $('#ersaal_response_details, #ersaal_response_logs_link').prop('hidden', false);
        $('#ersaal_result_message_id_wrap').prop('hidden', !data.message_id);
        $('#ersaal_result_message_id').text(data.message_id || '');
        $('#ersaal_result_log_id').text(data.log_id || '—');
        $('#ersaal_result_parts_wrap').prop('hidden', !data.parts);
        $('#ersaal_result_parts').text(data.parts || '');
        responseArea.get(0).scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    form.on('submit', function(event) {
        event.preventDefault();
        setSending(true);
        responseArea.prop('hidden', true).removeClass('ersaal-alert-success ersaal-alert-danger');

        $.post(ersaalManualSend.ajax_url, {
            action: 'ersaal_manual_send',
            nonce: ersaalManualSend.nonce,
            idempotency_key: idempotencyKey,
            phone: $('#ersaal_phone').val(),
            sender: $('#ersaal_sender').val(),
            payment_type: $('#ersaal_payment_type').val(),
            message: messageArea.val()
        }, function(response) {
            if (response.success) {
                idempotencyKey = generateUuid();
                showSuccess(response.data);
                messageArea.val('').trigger('input');
            } else {
                showError(response.data.message || ersaalManualSend.i18n.error);
            }
        }).fail(function(request) {
            const responseMessage = request.responseJSON && request.responseJSON.data
                ? request.responseJSON.data.message
                : ersaalManualSend.i18n.error;
            showError(responseMessage);
        }).always(function() {
            setSending(false);
        });
    });
});
