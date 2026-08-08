jQuery(document).ready(function($) {
    var $form = $('#ersaal-manual-send-form');
    var $submitBtn = $('#ersaal_submit_btn');
    var $spinner = $('#ersaal_spinner');
    var $responseArea = $('#ersaal_response_area');
    var $messageArea = $('#ersaal_message');
    
    // SMS Parts Calculator
    function isGSM7(str) {
        var gsm7Chars = "@£$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞ\x1BÆæßÉ !\"#¤%&'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà";
        for (var i = 0; i < str.length; i++) {
            if (gsm7Chars.indexOf(str[i]) === -1) {
                return false;
            }
        }
        return true;
    }

    function calculateMessageParts(text) {
        var len = text.length;
        if (len === 0) return { chars: 0, encoding: 'GSM-7', parts: 0 };
        
        var isGsm = isGSM7(text);
        var encoding = isGsm ? 'GSM-7' : 'Unicode';
        var parts = 1;
        
        if (isGsm) {
            if (len > 160) {
                parts = Math.ceil(len / 153);
            }
        } else {
            if (len > 70) {
                parts = Math.ceil(len / 67);
            }
        }
        
        return { chars: len, encoding: encoding, parts: parts };
    }

    $messageArea.on('input', function() {
        var text = $(this).val();
        var stats = calculateMessageParts(text);
        
        $('#ersaal_char_count').text(stats.chars);
        $('#ersaal_encoding').text(stats.encoding);
        $('#ersaal_parts_count').text(stats.parts);
    });

    function generateUUID() {
        var d = new Date().getTime();
        if (typeof performance !== 'undefined' && typeof performance.now === 'function'){
            d += performance.now(); //use high-precision timer if available
        }
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = (d + Math.random() * 16) % 16 | 0;
            d = Math.floor(d / 16);
            return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
        });
    }

    var currentIdempotencyKey = generateUUID();

    $form.on('submit', function(e) {
        e.preventDefault();
        
        $submitBtn.prop('disabled', true).text(ersaalManualSend.i18n.sending);
        $spinner.addClass('is-active');
        $responseArea.hide().removeClass('success error').empty();
        
        var data = {
            action: 'ersaal_manual_send',
            nonce: ersaalManualSend.nonce,
            idempotency_key: currentIdempotencyKey,
            phone: $('#ersaal_phone').val(),
            sender: $('#ersaal_sender').val(),
            payment_type: $('#ersaal_payment_type').val(),
            message: $messageArea.val()
        };
        
        $.post(ersaalManualSend.ajax_url, data, function(response) {
            $submitBtn.prop('disabled', false).text(ersaalManualSend.i18n.send);
            $spinner.removeClass('is-active');
            
            if (response.success) {
                currentIdempotencyKey = generateUUID();
                $responseArea.addClass('success');
                var html = '<strong>' + ersaalManualSend.i18n.success + '</strong><br/>';
                if (response.data.message_id) {
                    html += 'Message ID: ' + response.data.message_id + '<br/>';
                }
                html += 'Log ID: ' + response.data.log_id;
                if (response.data.parts) {
                    html += '<br/>Parts: ' + response.data.parts;
                }
                if (response.data.cost) {
                    html += '<br/>Cost: ' + response.data.cost;
                }
                $responseArea.html(html).show();
                $messageArea.val('').trigger('input');
            } else {
                $responseArea.addClass('error');
                var msg = response.data.message || ersaalManualSend.i18n.error;
                $responseArea.html('<strong>Error:</strong> ' + msg).show();
            }
        }).fail(function(jqXHR) {
            $submitBtn.prop('disabled', false).text(ersaalManualSend.i18n.send);
            $spinner.removeClass('is-active');
            var msg = ersaalManualSend.i18n.error;
            if (jqXHR && jqXHR.responseJSON && jqXHR.responseJSON.data && jqXHR.responseJSON.data.message) {
                msg = jqXHR.responseJSON.data.message;
            }
            $responseArea.addClass('error').html('<strong>Error:</strong> ' + msg).show();
        });
    });
});
