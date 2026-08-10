document.addEventListener('DOMContentLoaded', function() {
    const settingsToggle = document.getElementById('ersaal_otp_enabled');
    const settingsPanel = document.getElementById('ersaal-otp-configuration');
    const senderField = document.getElementById('ersaal_otp_sender');

    function updateSettingsState() {
        if (!settingsToggle || !settingsPanel) {
            return;
        }
        settingsPanel.classList.toggle('is-disabled', !settingsToggle.checked);
        if (senderField) {
            senderField.required = settingsToggle.checked;
        }
    }

    if (settingsToggle) {
        settingsToggle.addEventListener('change', updateSettingsState);
        updateSettingsState();
    }

    const sendForm = document.getElementById('ersaal-otp-send-form');
    if (!sendForm || typeof ersaalOtpAdmin === 'undefined') {
        return;
    }

    const verifyForm = document.getElementById('ersaal-otp-verify-form');
    const completePanel = document.getElementById('ersaal-otp-complete');
    const phoneInput = document.getElementById('ersaal-otp-test-phone');
    const codeInput = document.getElementById('ersaal-otp-test-code');
    const sendButton = document.getElementById('ersaal-otp-send-button');
    const verifyButton = document.getElementById('ersaal-otp-verify-button');
    const resendButton = document.getElementById('ersaal-otp-resend-button');
    const startOverButton = document.getElementById('ersaal-otp-start-over');
    const sendSpinner = document.getElementById('ersaal-otp-send-spinner');
    const verifySpinner = document.getElementById('ersaal-otp-verify-spinner');
    const feedback = document.getElementById('ersaal-otp-feedback');
    const stateBadge = document.getElementById('ersaal-otp-state');
    const countdown = document.getElementById('ersaal-otp-countdown');
    let countdownTimer = null;
    let secondsRemaining = 0;

    function post(action, fields) {
        const body = new URLSearchParams(Object.assign({
            action: action,
            nonce: ersaalOtpAdmin.nonce
        }, fields));
        return fetch(ersaalOtpAdmin.ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: body.toString()
        }).then(function(response) {
            return response.json().catch(function() {
                return { success: false, data: { message: ersaalOtpAdmin.i18n.unexpected } };
            });
        });
    }

    function setPanel(name) {
        sendForm.hidden = name !== 'send';
        verifyForm.hidden = name !== 'verify';
        completePanel.hidden = name !== 'done';
        document.querySelectorAll('[data-otp-step-indicator]').forEach(function(item) {
            const step = item.dataset.otpStepIndicator;
            item.classList.toggle('is-active', step === name);
            item.classList.toggle('is-complete', (name === 'verify' && step === 'send') || (name === 'done' && step !== 'done'));
        });
    }

    function showFeedback(message, type) {
        feedback.textContent = message;
        feedback.className = 'ersaal-alert ersaal-alert-' + type;
        feedback.hidden = false;
    }

    function clearFeedback() {
        feedback.hidden = true;
        feedback.textContent = '';
        feedback.className = 'ersaal-alert';
    }

    function setBusy(button, spinner, busy, busyLabel, readyLabel) {
        button.disabled = busy;
        button.textContent = busy ? busyLabel : readyLabel;
        spinner.classList.toggle('is-active', busy);
    }

    function updateCountdown() {
        if (secondsRemaining <= 0) {
            countdown.textContent = '';
            resendButton.disabled = false;
            if (countdownTimer) {
                window.clearInterval(countdownTimer);
                countdownTimer = null;
            }
            return;
        }
        countdown.textContent = ersaalOtpAdmin.i18n.wait.replace('%s', String(secondsRemaining));
        resendButton.disabled = true;
        secondsRemaining -= 1;
    }

    function startCountdown(seconds) {
        secondsRemaining = Math.max(0, Number(seconds) || 0);
        if (countdownTimer) {
            window.clearInterval(countdownTimer);
        }
        updateCountdown();
        countdownTimer = window.setInterval(updateCountdown, 1000);
    }

    function sendCode() {
        clearFeedback();
        setBusy(sendButton, sendSpinner, true, ersaalOtpAdmin.i18n.sending, ersaalOtpAdmin.i18n.send);
        resendButton.disabled = true;
        return post('ersaal_otp_test_send', { phone: phoneInput.value }).then(function(response) {
            if (!response.success) {
                const retryAfter = response.data && response.data.retry_after;
                if (retryAfter && response.data.can_verify) {
                    setPanel('verify');
                    startCountdown(retryAfter);
                }
                showFeedback(response.data && response.data.message ? response.data.message : ersaalOtpAdmin.i18n.unexpected, 'danger');
                return;
            }
            setPanel('verify');
            stateBadge.textContent = ersaalOtpAdmin.i18n.verify;
            stateBadge.className = 'ersaal-badge ersaal-badge-warning';
            showFeedback(response.data.message, 'success');
            startCountdown(response.data.expires_in);
            codeInput.value = '';
            codeInput.focus();
        }).catch(function() {
            showFeedback(ersaalOtpAdmin.i18n.unexpected, 'danger');
        }).finally(function() {
            setBusy(sendButton, sendSpinner, false, ersaalOtpAdmin.i18n.sending, ersaalOtpAdmin.i18n.send);
        });
    }

    sendForm.addEventListener('submit', function(event) {
        event.preventDefault();
        sendCode();
    });

    resendButton.addEventListener('click', function() {
        setPanel('send');
        sendCode();
    });

    verifyForm.addEventListener('submit', function(event) {
        event.preventDefault();
        clearFeedback();
        setBusy(verifyButton, verifySpinner, true, ersaalOtpAdmin.i18n.verifying, ersaalOtpAdmin.i18n.verify);
        post('ersaal_otp_test_verify', { code: codeInput.value }).then(function(response) {
            if (!response.success) {
                showFeedback(response.data && response.data.message ? response.data.message : ersaalOtpAdmin.i18n.unexpected, 'danger');
                if (response.data && response.data.status === 'expired') {
                    startCountdown(0);
                }
                codeInput.select();
                return;
            }
            if (countdownTimer) {
                window.clearInterval(countdownTimer);
                countdownTimer = null;
            }
            clearFeedback();
            setPanel('done');
            stateBadge.textContent = response.data.message;
            stateBadge.className = 'ersaal-badge ersaal-badge-success';
        }).catch(function() {
            showFeedback(ersaalOtpAdmin.i18n.unexpected, 'danger');
        }).finally(function() {
            setBusy(verifyButton, verifySpinner, false, ersaalOtpAdmin.i18n.verifying, ersaalOtpAdmin.i18n.verify);
        });
    });

    startOverButton.addEventListener('click', function() {
        clearFeedback();
        codeInput.value = '';
        stateBadge.textContent = ersaalOtpAdmin.i18n.send;
        stateBadge.className = 'ersaal-badge ersaal-badge-muted';
        setPanel('send');
        phoneInput.focus();
    });
});
