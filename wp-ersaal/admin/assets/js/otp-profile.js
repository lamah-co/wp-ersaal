document.addEventListener('DOMContentLoaded', function() {
    const root = document.querySelector('.ersaal-profile-otp');
    if (!root || typeof ersaalOtpProfile === 'undefined') {
        return;
    }

    const userId = root.dataset.userId;
    const phone = document.getElementById('ersaal_otp_phone');
    const sendButton = document.getElementById('ersaal-otp-profile-send');
    const verifyButton = document.getElementById('ersaal-otp-profile-verify');
    const codeWrap = document.getElementById('ersaal-otp-profile-code-wrap');
    const code = document.getElementById('ersaal-otp-profile-code');
    const spinner = document.getElementById('ersaal-otp-profile-spinner');
    const feedback = document.getElementById('ersaal-otp-profile-feedback');
    const status = document.getElementById('ersaal-otp-profile-status');
    const loginToggle = document.getElementById('ersaal_otp_login_2fa');

    function post(action, fields) {
        const body = new URLSearchParams(Object.assign({
            action: action,
            nonce: ersaalOtpProfile.nonce,
            user_id: userId
        }, fields));
        return fetch(ersaalOtpProfile.ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: body.toString()
        }).then(function(response) {
            return response.json().catch(function() {
                return { success: false, data: { message: ersaalOtpProfile.i18n.unexpected } };
            });
        });
    }

    function setBusy(button, busy, busyLabel, readyLabel) {
        button.disabled = busy;
        button.textContent = busy ? busyLabel : readyLabel;
        spinner.classList.toggle('is-active', busy);
    }

    function showFeedback(message, type) {
        feedback.textContent = message;
        feedback.className = 'ersaal-alert ersaal-alert-' + type;
        feedback.hidden = false;
    }

    sendButton.addEventListener('click', function() {
        setBusy(sendButton, true, ersaalOtpProfile.i18n.sending, ersaalOtpProfile.i18n.send);
        post('ersaal_otp_profile_send', { phone: phone.value }).then(function(response) {
            if (!response.success) {
                showFeedback(response.data && response.data.message ? response.data.message : ersaalOtpProfile.i18n.unexpected, 'danger');
                return;
            }
            showFeedback(response.data.message, 'success');
            codeWrap.hidden = false;
            code.value = '';
            code.focus();
        }).catch(function() {
            showFeedback(ersaalOtpProfile.i18n.unexpected, 'danger');
        }).finally(function() {
            setBusy(sendButton, false, ersaalOtpProfile.i18n.sending, ersaalOtpProfile.i18n.send);
        });
    });

    verifyButton.addEventListener('click', function() {
        setBusy(verifyButton, true, ersaalOtpProfile.i18n.verifying, ersaalOtpProfile.i18n.verify);
        post('ersaal_otp_profile_verify', { code: code.value }).then(function(response) {
            if (!response.success) {
                showFeedback(response.data && response.data.message ? response.data.message : ersaalOtpProfile.i18n.unexpected, 'danger');
                code.select();
                return;
            }
            phone.value = response.data.phone;
            code.value = '';
            codeWrap.hidden = true;
            status.textContent = ersaalOtpProfile.i18n.verified;
            status.className = 'ersaal-badge ersaal-badge-success';
            loginToggle.disabled = false;
            if (response.data.login_reset) {
                loginToggle.checked = false;
            }
            showFeedback(response.data.message, 'success');
        }).catch(function() {
            showFeedback(ersaalOtpProfile.i18n.unexpected, 'danger');
        }).finally(function() {
            setBusy(verifyButton, false, ersaalOtpProfile.i18n.verifying, ersaalOtpProfile.i18n.verify);
        });
    });

    phone.addEventListener('input', function() {
        if (loginToggle) {
            loginToggle.checked = false;
        }
    });
});
