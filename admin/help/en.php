<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

return [
    [
        'id' => 'quick-start',
        'title' => 'Quick Start',
        'content' => '
            <div class="ersaal-help-quick-start">
                <ol>
                    <li><a href="' . esc_url(admin_url('admin.php?page=ersaal-settings')) . '">Open Settings</a>.</li>
                    <li>Enter API URL and API Key.</li>
                    <li>Test Connection.</li>
                    <li>Enter approved Sender ID.</li>
                    <li><a href="' . esc_url(admin_url('admin.php?page=ersaal-send-message')) . '">Open Send SMS</a>.</li>
                    <li>Send a test message.</li>
                    <li>Configure WooCommerce if needed.</li>
                </ol>
            </div>
        '
    ],
    [
        'id' => 'getting-started',
        'title' => 'Getting Started',
        'content' => '
            <p><strong>Ersaal SMS Plugin</strong> integrates your WordPress site and WooCommerce store with the Ersaal Gateway.</p>
            <h3>Requirements</h3>
            <ul>
                <li>Active account on Ersaal Gateway.</li>
                <li>API URL and API Key.</li>
                <li>Approved Sender ID.</li>
            </ul>
        '
    ],
    [
        'id' => 'connection-account',
        'title' => 'Connection & Account',
        'content' => '
            <p>From the <a href="' . esc_url(admin_url('admin.php?page=ersaal-settings')) . '">Settings page</a>, you can manage your connection and monitor account limits.</p>
            <ul>
                <li><strong>Project Status:</strong> Must be Active to send messages.</li>
                <li><strong>Wallet Balance:</strong> The total funds available for SMS. Unknown does not mean zero balance.</li>
                <li><strong>Subscription:</strong> Shows your limits for OTP and standard SMS.</li>
                <li><strong>Sender ID:</strong> All messages must be sent from an approved Sender ID.</li>
            </ul>
        '
    ],
    [
        'id' => 'send-sms',
        'title' => 'Send SMS',
        'content' => '
            <p>You can send manual SMS messages from <a href="' . esc_url(admin_url('admin.php?page=ersaal-send-message')) . '">Send SMS</a>.</p>
            <ul>
                <li><strong>Phone Number:</strong> Formatted automatically based on the gateway\'s requirements.</li>
                <li><strong>Message Characters:</strong> Supports standard GSM-7 and Unicode (Arabic). Arabic messages use Unicode (70 chars per part).</li>
            </ul>
            <div class="notice notice-warning inline"><p><strong>Important:</strong> Accepted does not mean Delivered. It means Ersaal has queued the message for processing.</p></div>
        '
    ],
    [
        'id' => 'woocommerce',
        'title' => 'WooCommerce Integration',
        'content' => '
            <p>To automate SMS for orders, go to <a href="' . esc_url(admin_url('admin.php?page=ersaal-settings&tab=woocommerce')) . '">Settings > WooCommerce</a>.</p>
            <h3>Supported Events</h3>
            <ul>
                <li>New Order</li>
                <li>Processing</li>
                <li>Completed</li>
                <li>Cancelled</li>
            </ul>
            <h3>Variables</h3>
            <p>You can use these placeholders in your templates:</p>
            <code>{customer_name}</code>, <code>{order_number}</code>, <code>{order_total}</code>, <code>{order_status}</code>, <code>{site_name}</code>, <code>{billing_first_name}</code>, <code>{billing_last_name}</code>
            <p><strong>Example:</strong> Hello {billing_first_name}, your order #{order_number} is now {order_status}.</p>
            <h3>Manual SMS from Order</h3>
            <p>Inside any WooCommerce Order page, find the <strong>Ersaal SMS</strong> metabox to send a direct message to the customer.</p>
        '
    ],
    [
        'id' => 'logs',
        'title' => 'Logs & Status',
        'content' => '
            <p>Track all messages in the <a href="' . esc_url(admin_url('admin.php?page=ersaal-logs')) . '">Logs page</a>.</p>
            <ul>
                <li><strong>Filters:</strong> Search by Status, Source, Event, or Date Range.</li>
                <li><strong>Details:</strong> Click "Details" to view the full message content and API responses.</li>
                <li><strong>Delete:</strong> Single and bulk delete options are available.</li>
                <li><strong>Export CSV:</strong> Export the current view to a CSV file.</li>
            </ul>
            <h3>Status Explanations</h3>
            <table class="widefat striped">
                <tr><th>Processing</th><td>Sending request to the API.</td></tr>
                <tr><th>Accepted</th><td>Ersaal received it and will deliver it.</td></tr>
                <tr><th>Retry Scheduled</th><td>Temporary API error; will retry automatically.</td></tr>
                <tr><th>Error / Failed</th><td>Permanent failure (e.g. invalid number or no balance).</td></tr>
            </table>
        '
    ],
    [
        'id' => 'otp-setup',
        'title' => 'OTP Setup & Testing',
        'content' => '
            <p>OTP is optional and remains disabled after upgrade. Open <a href="' . esc_url(admin_url('admin.php?page=ersaal-settings&tab=otp')) . '">Settings &gt; OTP</a> to enable it.</p>
            <ol>
                <li>Select an approved sender, payment source, code length, lifetime, and language.</li>
                <li>Save the settings.</li>
                <li>Open <a href="' . esc_url(admin_url('admin.php?page=ersaal-otp-test')) . '">OTP Test</a> and enter a phone with its country code.</li>
                <li>Send the code, enter it once, and confirm the successful state.</li>
                <li>Review the safe record in <a href="' . esc_url(admin_url('admin.php?page=ersaal-otp-logs')) . '">OTP Activity</a>.</li>
            </ol>
            <div class="notice notice-info inline"><p>OTP Activity never stores verification codes or full phone numbers. A real test may consume wallet balance or one OTP subscription unit.</p></div>
            <h3>What OTP requires</h3>
            <p>OTP is a short-lived identity check, not an ordinary notification SMS. It requires an active project, valid API key, approved sender, and wallet credit or an OTP subscription with available units.</p>
            <h3>Common errors</h3>
            <ul>
                <li><strong>Active code:</strong> use the current code or wait until it expires.</li>
                <li><strong>Invalid or expired:</strong> check the code, then request a new one after expiry.</li>
                <li><strong>Sender or subscription:</strong> verify the approved sender and selected payment source.</li>
                <li><strong>Rate limited or unavailable:</strong> wait and retry; local controls do not replace Ersaal limits.</li>
            </ul>
        '
    ],
    [
        'id' => 'otp-login',
        'title' => 'WordPress Login OTP',
        'content' => '
            <p>Login OTP is an optional second step. Existing users are not enrolled automatically.</p>
            <ol>
                <li>Enable OTP and WordPress login verification in <a href="' . esc_url(admin_url('admin.php?page=ersaal-settings&tab=otp')) . '">OTP Settings</a>.</li>
                <li>Open a user profile and enter the user\'s mobile phone.</li>
                <li>Send and verify the profile code.</li>
                <li>Enable <strong>Require a verification code after my WordPress password</strong> and save the profile.</li>
            </ol>
            <p>The code is requested only after WordPress accepts the username and password. A user session is created only after the OTP succeeds.</p>
            <p>Changing the user phone clears its verified state and disables Login OTP. Resend stays disabled while the current code is active. The challenge is one-time, bound to the user, and fails closed when Ersaal is unavailable.</p>
            <h3>Emergency recovery</h3>
            <p>Add <code>define(\'ERSAAL_DISABLE_LOGIN_OTP\', true);</code> to <code>wp-config.php</code> to bypass only login OTP while retaining the rest of the plugin. Remove it after the issue is resolved.</p>
        '
    ],
    [
        'id' => 'troubleshooting',
        'title' => 'Troubleshooting',
        'content' => '
            <h3>Connection failed</h3>
            <p>Check your API URL, API Key, Project Status, and internet connection.</p>
            <h3>Wallet = Unknown</h3>
            <p>Unknown does not mean zero balance. It means the API didn\'t return a specific balance number, usually because you have a subscription rather than prepaid credit.</p>
            <h3>Subscription Error</h3>
            <p>Ensure you have an active <strong>SMS subscription</strong>. OTP subscriptions cannot send general SMS.</p>
            <h3>Sender ID Error</h3>
            <p>Ensure your Sender ID is exactly as approved in your Ersaal account.</p>
            <h3>Message Accepted But Not Received</h3>
            <p>Accepted ≠ Delivered. The operator may delay the message or the phone might be out of reach.</p>
            <h3>WooCommerce message not sent</h3>
            <p>Ensure integration is enabled, the event is checked, a valid Billing phone is present, and Logs show no errors.</p>
        '
    ],
    [
        'id' => 'faq',
        'title' => 'FAQ',
        'content' => '
            <div class="ersaal-help-faq-list">
                <details><summary>Do I need WooCommerce?</summary><div class="ersaal-help-faq-answer"><p>No, the plugin works as a standalone SMS sender. WooCommerce is optional.</p></div></details>
                <details><summary>Can I send manual SMS?</summary><div class="ersaal-help-faq-answer"><p>Yes, from the Send SMS page.</p></div></details>
                <details><summary>What does Wallet Unknown mean?</summary><div class="ersaal-help-faq-answer"><p>Your account might be using a quota subscription instead of a wallet balance.</p></div></details>
                <details><summary>What is the difference between OTP and SMS?</summary><div class="ersaal-help-faq-answer"><p>OTP is strictly for verification codes. SMS is for marketing and notifications. Make sure you use the right subscription type.</p></div></details>
                <details><summary>Why are phone numbers masked?</summary><div class="ersaal-help-faq-answer"><p>For privacy, full numbers are not stored in logs.</p></div></details>
                <details><summary>Can I send multiple manual SMS for the same order?</summary><div class="ersaal-help-faq-answer"><p>Yes, manual sending allows multiple messages to the same order.</p></div></details>
            </div>
        '
    ]
];
