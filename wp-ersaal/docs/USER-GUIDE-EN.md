# Ersaal WordPress Plugin - User Guide

**Version 1.0.2**

## Table of Contents
1. [Introduction](#1-introduction)
2. [Requirements](#2-requirements)
3. [Quick Start](#3-quick-start)
4. [Installation](#4-installation)
5. [Getting Started (Plugin Menus)](#5-getting-started)
6. [Connection Settings](#6-connection-settings)
7. [API Key Security](#7-api-key-security)
8. [Testing Connection & Status](#8-testing-connection--status)
9. [Subscription & Wallet](#9-subscription--wallet)
10. [Sender ID](#10-sender-id)
11. [Manual SMS Sending](#11-manual-sms-sending)
12. [Message Characters & Estimated Parts](#12-message-characters--estimated-parts)
13. [Accepted vs Delivered](#13-accepted-vs-delivered)
14. [WooCommerce Integration (Automatic)](#14-woocommerce-integration-automatic)
15. [WooCommerce Template Variables](#15-woocommerce-template-variables)
16. [WooCommerce Order Notes & Manual Send](#16-woocommerce-order-notes--manual-send)
17. [Ersaal Logs](#17-ersaal-logs)
18. [Retries & Action Scheduler](#18-retries--action-scheduler)
19. [Privacy & Security](#19-privacy--security)
20. [Troubleshooting](#20-troubleshooting)
21. [Uninstall & Data Removal](#21-uninstall--data-removal)
22. [FAQ](#22-faq)
23. [Glossary](#23-glossary)

---

## 1. Introduction
The **Ersaal SMS Plugin** for WordPress allows you to seamlessly send SMS messages from your WordPress dashboard using the powerful Ersaal platform. Whether you want to send manual notifications to specific numbers or automatically update your WooCommerce customers about their order status, this plugin handles it securely and efficiently.

**Key Features:**
* Direct manual SMS sending.
* Deep integration with WooCommerce for automatic order notifications.
* Extensive localized logs with advanced search and filtering.
* Privacy-first design (masking phone numbers).
* Automatic retries for temporary connection issues.

---

## 2. Requirements
Before you begin, ensure you have:
* **WordPress** (version 5.8 or higher recommended).
* An active **Ersaal Gateway** account and project.
* A valid **Ersaal API Key** (Bearer Token).
* An **Approved Sender ID** from the Ersaal platform.
* **WooCommerce** (Optional, only required if you want automatic order notifications).
* Internet connection allowing external API requests from your server.

---

## 3. Quick Start
1. Install and activate the plugin.
2. Go to **Ersaal Settings -> General**.
3. Enter your **API Base URL** and **API Key**.
4. Click **Test API Connection**.
5. Save your settings.
6. Navigate to **Send SMS** and test sending a manual message using your approved **Sender ID**.
7. (Optional) Go to **Ersaal Settings -> WooCommerce** to configure automatic order notifications.

---

## 4. Installation

### From WordPress Admin
1. Go to **Plugins -> Add New Plugin**.
2. Click **Upload Plugin** at the top.
3. Choose the `wp-ersaal.zip` file.
4. Click **Install Now**.
5. Click **Activate Plugin**.

### Manual Installation
1. Extract the `wp-ersaal` folder.
2. Upload the folder to your `/wp-content/plugins/` directory.
3. Go to the **Plugins** page in WordPress and activate it.

---

## 5. Getting Started
After activation, a new menu called **Ersaal** will appear in your WordPress sidebar.
It contains three main sections:
* **Send SMS**: For manually composing and sending messages.
* **Logs**: For viewing the status and history of all sent messages.
* **Settings**: For configuring API keys, testing connections, and enabling WooCommerce integration.

---

## 6. Connection Settings
To connect your website to Ersaal:
1. Go to **Ersaal -> Settings**.
2. Under the **General** tab, you will find:
   * **API Base URL**: The official production URL provided by Ersaal (e.g., `https://api.ersaal.com/`). *(Note: `http://localhost` should only be used in local development).*
   * **API Key**: Your secure Bearer Token provided by Ersaal.

---

## 7. API Key Security
Your API Key is treated with strict security measures:
* Once saved, it will be masked as `********`. **You do not need to re-enter it when changing other settings.**
* Leaving the field blank or keeping `********` preserves your existing key.
* The API Key is never logged in error logs, never displayed in WooCommerce order notes, and is strictly hidden from regular users.
* **wp-config.php Override**: Developers can define `ERSAAL_API_KEY` in `wp-config.php`. If this is done, the settings page will disable the field and display a message indicating the key is managed securely via configuration.

---

## 8. Testing Connection & Status
At the bottom of the **General Settings** page, click the **Test API Connection** button.
If successful, you will see your **System Status** in a new tab, displaying:
* **Project**: The name of your active Ersaal project.
* **Status**: E.g., `Active`.
* **Wallet Balance**: Your available financial balance.
* **Subscription**: Your active SMS or OTP plans.

---

## 9. Subscription & Wallet
Ersaal supports two payment types:
* **Wallet**: The cost of the SMS is deducted directly from your project's balance. *(Note: If the Wallet Balance displays as `Unknown`, it does not mean it is zero; it simply means the API does not currently expose this value).*
* **Subscription**: The SMS cost is deducted from an active package.

> **Important**: Subscriptions are strictly categorized. An `OTP Enterprise` subscription (Type: OTP) cannot be used to send regular SMS messages. If you attempt to use it for normal SMS, the system will reject it indicating no valid SMS subscription was found.

---

## 10. Sender ID
The **Sender ID** is the name or number that appears on the recipient's phone (e.g., `Lamah`).
* You must type the name exactly as it is approved in your Ersaal account.
* **Project Name ≠ Sender ID**: Having a project named "My Store" does not automatically mean you can send messages using the Sender ID "My Store" unless it has been explicitly approved by Ersaal.

---

## 11. Manual SMS Sending
Go to **Ersaal -> Send SMS** to send quick manual messages.
1. **Phone Number**: Enter the recipient's full number including the country code (e.g., `+21892xxxxxxx`).
2. **Sender ID**: Type your approved Sender ID.
3. **Payment Type**: Choose `Wallet` or `Subscription`.
4. **Message**: Type the content of your message. Empty messages are not permitted.

---

## 12. Message Characters & Estimated Parts
As you type your message, the interface will automatically calculate:
* **Encoding**: Either `GSM-7` (mostly standard English/Latin characters) or `Unicode` (used for Arabic, emojis, and special symbols). Unicode reduces the maximum number of characters per SMS part.
* **Estimated Parts**: If your message is long, it will be split into multiple parts. A message with 2 parts will be charged as two separate SMS messages.

---

## 13. Accepted vs Delivered
> **WARNING**: `Accepted ≠ Delivered`

When the plugin shows `Message accepted by Ersaal` (or `Status: accepted` in the Logs), it means the Ersaal platform successfully received your request and validated it. It does **not** guarantee that the physical phone has received the message yet. Final delivery depends on telecom operators.

---

## 14. WooCommerce Integration (Automatic)
*(If WooCommerce is not installed, the plugin continues to function perfectly for Manual SMS).*

To enable automatic order updates:
1. Go to **Ersaal -> Settings -> WooCommerce**.
2. Check **Enable WooCommerce SMS**.
3. Choose your default **Sender ID** and **Payment Type**.

You can enable notifications for four specific events:
* **New Order** (Sent when a customer places an order).
* **Processing** (Sent when payment is verified and order is processing).
* **Completed** (Sent when the order is fulfilled).
* **Cancelled** (Sent when an order is cancelled).

*Note: All events are disabled by default. You must check the box next to the event you wish to activate.*

---

## 15. WooCommerce Template Variables
You can customize the SMS text for each event using variables.

| Variable | Description | Example |
|---|---|---|
| `{customer_name}` | Customer's full billing name | John Doe |
| `{billing_first_name}` | Customer's first name | John |
| `{billing_last_name}` | Customer's last name | Doe |
| `{order_number}` | The WooCommerce order ID | 1045 |
| `{order_total}` | The total order amount | $150.00 |
| `{order_status}` | Current order status | processing |
| `{site_name}` | Your website's name | My Awesome Store |

**Example Template:**
`Hello {customer_name}, your order #{order_number} is now {order_status}. Thank you for choosing {site_name}!`

---

## 16. WooCommerce Order Notes & Manual Send

### Manual Order SMS
Inside any WooCommerce Order edit screen, you will find an **Ersaal SMS** box on the sidebar. This allows you to quickly type a custom message to that specific customer.
* It automatically retrieves the customer's billing phone.
* You can send multiple manual messages for the same order.

### Order Notes
Whenever an automatic or manual SMS is triggered for an order, the plugin will add a secure **Order Note** to WooCommerce.
* Success: `Ersaal SMS accepted. Message ID: xxxxx`
* Failure: `Ersaal SMS failed: [Safe Error Reason]`

*(Note: The plugin is fully compatible with WooCommerce High-Performance Order Storage (HPOS)).*

---

## 17. Ersaal Logs
Go to **Ersaal -> Logs** to track all messaging activity.

**Summary Cards:**
* **Total**: All logs.
* **Accepted**: Successfully sent to Ersaal.
* **Failed/Error**: Permanent failures.
* **Retry**: Scheduled to be retried later.
* **Processing**: Currently being sent.

**Filters & Search:**
You can filter logs by **Status** or **Source** (Manual vs WooCommerce). You can also search using the Log ID, Message ID, Order ID, or the Masked Phone number.

**View Details:**
Click **Details** on any row to open a pop-up modal containing exact error codes, attempt counts, and timestamps.

---

## 18. Retries & Action Scheduler
The plugin features a robust, background retry system using WordPress Action Scheduler (or WP-Cron).
* **Temporary Errors** (e.g., connection drops, API timeouts, or Rate Limits) will automatically trigger a retry schedule.
* **Permanent Errors** (e.g., Invalid Phone, Insufficient Balance, Authentication Failure) will fail immediately without retrying, saving system resources.

**Idempotency (Duplicate Protection):**
Automatic WooCommerce events are protected against duplication. If WordPress triggers a `processing` event twice for the same order, Ersaal will only send the SMS once.

---

## 19. Privacy & Security
The plugin was built with strict privacy compliance (GDPR/Local laws):
* **Masked Phones**: Phone numbers are stored in the database with masking (e.g., `+21892****268`). The full number only exists in memory momentarily during the API call.
* **No Message Storage**: The content of the SMS messages is **not** stored in the local WordPress database logs.
* **No Token Leaks**: API Keys are never printed in error logs or order notes.

---

## 20. Troubleshooting

### Connection Failed
* Ensure the API Base URL is correct.
* Double-check your API Key.
* Ensure your Ersaal Project is active.

### Sender ID Rejected
* Ensure the Sender ID is exactly as approved in Ersaal.
* Ensure the Sender ID belongs to the project associated with your API Key.

### Wallet shows "Unknown"
* This simply means the API does not currently return the wallet balance. It does not mean your balance is zero.

### WooCommerce SMS Not Sent
* Check if WooCommerce SMS is enabled in settings.
* Ensure the specific event (e.g., New Order) is checked.
* Check if the customer provided a valid Billing Phone.
* Check the Ersaal Logs page for specific error codes.

### Duplicate Message Didn't Send Again
* This is intentional (Idempotency) for automatic events to prevent spamming customers. If you must resend a notification, use the Manual Send box inside the Order page.

---

## 21. Uninstall & Data Removal
By default, deactivating or deleting the plugin will **not** delete your settings or your SMS logs.
If you wish to completely wipe all Ersaal data upon deletion:
1. Go to **Ersaal Settings -> Advanced**.
2. Check **Delete all data (Logs and Settings) when deleting the plugin.**
3. Save changes.
When you delete the plugin from the WordPress plugins page, all tables and options will be permanently destroyed.

---

## 22. FAQ

**Q: Do I need WooCommerce to use this plugin?**
A: No, the plugin works perfectly for sending manual SMS messages without WooCommerce.

**Q: Why does my subscription say "OTP" but my SMS fails?**
A: OTP subscriptions are separate from standard SMS subscriptions. You cannot use an OTP package to send general promotional or order update messages.

**Q: Does "Accepted" mean the customer read the message?**
A: No. "Accepted" only means Ersaal's API validated and accepted the request. Final delivery tracking is not managed within WordPress.

**Q: How do I copy a Message ID for support?**
A: In the Logs page, there is a small copy icon next to the shortened Message ID. Click it to copy the full ID to your clipboard.

---

## 23. Glossary
* **API Key**: The secret password (Bearer Token) connecting your site to Ersaal.
* **Sender ID**: The approved name/number displayed on the recipient's phone.
* **OTP**: One-Time Password (specialized subscription type).
* **GSM-7 / Unicode**: Character encoding methods that determine how many characters fit in a single SMS part.
* **HPOS**: High-Performance Order Storage (WooCommerce's modern database structure).
* **Idempotency**: A safety mechanism ensuring a specific automated event is never triggered twice.

---

## 24. OTP and WordPress Login 2FA

OTP is a short-lived verification code, separate from ordinary notification SMS. It is disabled after upgrade until an administrator enables it.

1. Configure the approved sender, payment source, code length, lifetime, and language in **Ersaal → Settings → OTP**.
2. Run **Ersaal → OTP Test** and complete both Send and Verify.
3. Review masked results in **Ersaal → OTP Activity**. Codes and full phones never appear there.
4. To enroll a user, open their profile, enter an international mobile phone, send a code, verify it, then enable Login OTP and save.

Login OTP requires global OTP, global Login OTP, a verified phone, and that user's explicit opt-in. A wrong password never sends a code. A correct password opens the OTP screen, and WordPress creates its session only after verification succeeds. Resend is available after the active code expires.

Changing or removing the phone clears verification and disables 2FA. If Login OTP becomes unavailable, add `define('ERSAAL_DISABLE_LOGIN_OTP', true);` to `wp-config.php`; this bypasses only the login second step. Remove it after recovery.

See [OTP Setup](OTP-SETUP-EN.md), [Login OTP](OTP-LOGIN-2FA-EN.md), and [Developer API](OTP-DEVELOPER-API.md) for full details and common errors.
