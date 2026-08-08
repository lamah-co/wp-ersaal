# Ersaal SMS Gateway

**Requires at least:** 5.8
**Tested up to:** 6.3
**Requires PHP:** 7.4
**Stable tag:** 1.0.0
**License:** GPLv2 or later

Ersaal SMS Gateway integration for WordPress and WooCommerce.

## Overview

Ersaal SMS Gateway plugin allows you to send SMS messages directly from your WordPress admin dashboard and automatically notify your WooCommerce customers about their order statuses using the robust Ersaal API.

## Requirements

* WordPress 5.8 or higher.
* PHP 7.4 or higher.
* An active Ersaal Gateway account.
* WooCommerce (Optional, for automatic order notifications).

## Installation

1. Upload the `wp-ersaal` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to **Ersaal Settings** to configure your API URL and Token.

## Configuration & API Key

1. Go to **Ersaal Settings**.
2. Enter your API Base URL (e.g., `https://api.ersaal.com/`).
3. Enter your Ersaal API Bearer Token.
4. Click "Test API Connection" to ensure your credentials are correct.

*(For developers: You can define `ERSAAL_API_KEY` in your `wp-config.php` file to hardcode the API token securely.)*

## Manual SMS

You can send manual SMS messages at any time:
* Go to **Ersaal SMS -> Manual Send**.
* Enter the recipient's phone number, sender ID, and the message text.
* Select the payment type (Wallet or Subscription).
* The character counter will calculate the GSM-7/Unicode encoding and estimate the number of parts.

## WooCommerce Integration

Send automated SMS notifications to customers based on WooCommerce order events:
1. Go to **Ersaal Settings -> WooCommerce**.
2. Check "Enable WooCommerce SMS".
3. Configure your templates for the supported events:
   * **New Order**
   * **Processing**
   * **Completed**
   * **Cancelled**
4. Supported variables in templates include: `{customer_name}`, `{order_number}`, `{order_total}`, `{order_status}`, `{site_name}`.

## Logs & Retries

* All messages are logged locally. You can view them in **Ersaal Logs**.
* The logs interface provides advanced filtering, searching, and pagination.
* Sensitive data such as the full phone number is masked for privacy.
* The plugin automatically schedules retries for temporary failures (like Rate Limits or server errors) using Action Scheduler (or WP-Cron as a fallback). Permanent errors (like invalid numbers) fail immediately.

## Developer Hooks

Developers can hook into the messaging pipeline:
* `apply_filters('ersaal_message_payload', $payload)`: Filter the payload before sending.
* `do_action('ersaal_message_accepted', $idempotencyKey, $messageId)`: Fired when Ersaal accepts a message.
* `do_action('ersaal_message_failed', $idempotencyKey, $errorCode, $errorMessage)`: Fired on permanent message failure.

## Privacy & Security

* API Keys are securely sanitized and not leaked in UI or logs.
* Phone numbers are masked (`+21892****268`) in the local log database to ensure GDPR/privacy compliance.
* CSRF nonces and proper capabilities (`manage_options`, `manage_woocommerce`) protect all interactions.
