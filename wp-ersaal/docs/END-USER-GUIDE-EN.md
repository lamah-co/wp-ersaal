# Ersaal WordPress Plugin
**Ersaal Help & User Guide**
**Version 1.0.1**

## Table of Contents
1. [What is the Ersaal Plugin?](#1-what-is-the-ersaal-plugin)
2. [Before You Begin](#2-before-you-begin)
3. [Accessing the Plugin](#3-accessing-the-plugin)
4. [Setting Up the Connection](#4-setting-up-the-connection)
5. [How Do I Know the Connection Works?](#5-how-do-i-know-the-connection-works)
6. [Wallet Balance: Unknown](#6-wallet-balance-unknown)
7. [Understanding Subscriptions](#7-understanding-subscriptions)
8. [Sender ID](#8-sender-id)
9. [Sending a Manual SMS](#9-sending-a-manual-sms)
10. [Message Length and Parts](#10-message-length-and-parts)
11. [GSM-7 vs Unicode](#11-gsm-7-vs-unicode)
12. [Successful Sending (Accepted)](#12-successful-sending-accepted)
13. [WooCommerce Integration](#13-woocommerce-integration)
14. [Enabling WooCommerce SMS](#14-enabling-woocommerce-sms)
15. [Order Notifications](#15-order-notifications)
16. [Editing WooCommerce Messages](#16-editing-woocommerce-messages)
17. [What Will the Customer See?](#17-what-will-the-customer-see)
18. [Why Was a WooCommerce SMS Not Sent?](#18-why-was-a-woocommerce-sms-not-sent)
19. [Sending a Manual SMS from an Order](#19-sending-a-manual-sms-from-an-order)
20. [Order Notes](#20-order-notes)
21. [Logs (Message History)](#21-logs-message-history)
22. [Summary Cards](#22-summary-cards)
23. [Filters](#23-filters)
24. [Reading the Log Table](#24-reading-the-log-table)
25. [Log Statuses](#25-log-statuses)
26. [Why is the Phone Number Hidden?](#26-why-is-the-phone-number-hidden)
27. [View Details](#27-view-details)
28. [Common Errors](#28-common-errors)
29. [Changing Your API Key](#29-changing-your-api-key)
30. [Advanced Settings](#30-advanced-settings)
31. [Deactivating the Plugin](#31-deactivating-the-plugin)
32. [Deleting the Plugin](#32-deleting-the-plugin)
33. [Quick Troubleshooting Checklist](#33-quick-troubleshooting-checklist)
34. [FAQ](#34-faq)

---

## 1. What is the Ersaal Plugin?
The Ersaal plugin allows you to easily send SMS messages directly from your WordPress website and link your WooCommerce store notifications to the Ersaal platform.

**Main Uses:**
* Sending manual messages to specific numbers.
* Sending automatic order updates to your customers.
* Tracking the history of all sent messages.

---

## 2. Before You Begin
To use the plugin, you need:
* An active **Ersaal account**.
* An active **Project** in Ersaal.
* An **API Key**.
* An approved **Sender ID**.
* Sufficient **Wallet balance** or a suitable **SMS Subscription**.
* (Optional) **WooCommerce** must be installed and active if you want automatic order notifications.

---

## 3. Accessing the Plugin
Once the plugin is activated, you will find a new menu in your WordPress Dashboard sidebar named **Ersaal**.
It contains the following sections:
* **Settings**: To configure your connection.
* **Send SMS**: To write and send messages manually.
* **Logs**: To view your messaging history.

---

## 4. Setting Up the Connection
Follow these simple steps to link your website to Ersaal:

1. Open **Ersaal → Settings**.
2. Enter the **API Base URL** and your **API Key**.
3. Click the **Save Changes** button.
4. Click the **Test API Connection** button at the bottom of the page.

> Screenshot: Connection Settings

---

## 5. How Do I Know the Connection Works?
When you click **Test API Connection**, a new box will appear. If it says `Connected`, the setup is successful!
You will see basic information about your account:
* **Project**: The name of your project in Ersaal.
* **Status**: E.g., `Active`.
* **Wallet Balance**: Your financial balance.
* **Subscription**: Any active packages you own.

---

## 6. Wallet Balance: Unknown
If your connection test shows `Wallet Balance: Unknown`, **this does not mean your balance is zero**. 
It simply means that the current connection does not support displaying the exact wallet amount inside WordPress. You can safely ignore this and proceed.

---

## 7. Understanding Subscriptions
If you have an active package, it will be displayed here. 
For example: `OTP Enterprise (Type: OTP)`
> **Note**: An OTP subscription means you can only send verification codes. It does not automatically allow you to send regular SMS messages. If you want to send standard order updates, you must use your Wallet or purchase an SMS subscription.

---

## 8. Sender ID
The **Sender ID** is the name that appears on the customer's phone as the sender (e.g., `Lamah`).
* Your Sender ID must be officially approved inside your Ersaal account.
* The name of your project is not necessarily your Sender ID.

---

## 9. Sending a Manual SMS
To send a message quickly:
1. Open **Ersaal → Send SMS**.
2. **Phone Number**: Enter the number including the country code (e.g., `+218...`).
3. **Sender ID**: Type your approved sender name (e.g., `Lamah`).
4. **Payment Type**: Choose either `Wallet` or `Subscription`.
5. **Message**: Type the content of your message.
6. Click **Send Message**.

> Screenshot: Send SMS Page

---

## 10. Message Length and Parts
As you type your message, a counter will show:
* **Characters**: How many letters you've typed.
* **Encoding**: The text format.
* **Estimated Parts**: If the message is very long, it will split into multiple SMS parts. 

> This helps you estimate the cost, as a message with 2 parts is charged as two separate SMS messages.

---

## 11. GSM-7 vs Unicode
* English text is usually **GSM-7**.
* Arabic text (or text with emojis) is usually **Unicode**.

> Arabic messages reach the maximum character limit faster. Therefore, a long Arabic message might be divided into multiple parts sooner than an English one.

---

## 12. Successful Sending (Accepted)
When you send a message successfully, you will see a green notice:
`Message accepted by Ersaal. Message ID: ... Log ID: ... Cost: ...`

> **Important**: "Accepted" means that the Ersaal platform received and approved your request. It does **not** necessarily mean the message has reached the customer's phone yet.

---

## 13. WooCommerce Integration
You can set up the plugin to automatically send SMS notifications whenever an order's status changes.
To do this, go to **Ersaal → Settings → WooCommerce**.

> Screenshot: WooCommerce Settings

---

## 14. Enabling WooCommerce SMS
1. Check the box **Enable WooCommerce SMS**.
2. Choose a default **Sender ID** and **Payment Type**.

---

## 15. Order Notifications
You can activate notifications for the following events:
* **New Order**: When the order is first placed.
* **Processing**: When the order is being prepared.
* **Completed**: When the order is finished and shipped.
* **Cancelled**: When the order is cancelled.

You can **Enable or Disable** each notification individually by checking the box next to it.

---

## 16. Editing WooCommerce Messages
You can customize the text sent to the customer using variables.
For example: `Hello {customer_name}, we received your order #{order_number}.`

| Variable | Meaning |
|---|---|
| `{customer_name}` | Customer's full name |
| `{order_number}` | Order number |
| `{order_total}` | Total price of the order |
| `{order_status}` | Current status of the order |
| `{site_name}` | Your website's name |
| `{billing_first_name}` | Customer's first name |
| `{billing_last_name}` | Customer's last name |

---

## 17. What Will the Customer See?
If your template is:
`Hello {customer_name}, your order #{order_number} is being processed.`

After the system replaces the variables, the customer receives:
`Hello Ahmed, your order #1052 is being processed.`
*(This is just an example with dummy data).*

---

## 18. Why Was a WooCommerce SMS Not Sent?
If a customer did not receive a message, check this list:
* [ ] Is "Enable WooCommerce SMS" checked?
* [ ] Is the specific event (like "Completed") checked?
* [ ] Is the Sender ID correct?
* [ ] Is the Payment Type appropriate?
* [ ] Did the customer provide a phone number at checkout?
* [ ] Is the connection to Ersaal working?

If everything is correct, check the **Ersaal Logs** to see the exact reason.

---

## 19. Sending a Manual SMS from an Order
You can send a specific message directly to a customer from their order page:
1. Open any WooCommerce Order.
2. Look for the **Ersaal SMS** box on the side.
3. Type your message and click **Send SMS**.

> You can send as many manual messages as you need for the same order.

> Screenshot: Ersaal SMS Box inside Order

---

## 20. Order Notes
When an SMS is sent for an order (automatically or manually), a note will be added to the order history, such as:
`Ersaal SMS accepted. Message ID: ...`
This helps you track which notifications the customer has been sent.

---

## 21. Logs (Message History)
Go to **Ersaal → Logs**.
This is the main page for reviewing all the messages your website has attempted to send.

> Screenshot: Logs Page

---

## 22. Summary Cards
At the top of the logs page, you will see quick statistics:
* **Total**: Total number of messages.
* **Accepted**: Messages successfully sent to Ersaal.
* **Failed/Error**: Messages that completely failed.
* **Retry Scheduled**: Messages that the system will try sending again later.
* **Processing**: Messages currently being sent.

---

## 23. Filters
You can filter the history using:
* **Status**: Show only accepted, failed, etc.
* **Source**: Filter by `Manual` sends or `WooCommerce` automatic sends.
* **Search**: Search using an order number or message ID.

---

## 24. Reading the Log Table
The table displays:
* **ID**: Local reference number.
* **Source**: Where the message came from.
* **Phone**: The customer's number.
* **Status**: The outcome of the message.
* **Message ID**: The unique ID from Ersaal.
* **Created**: Date and time of the attempt.

---

## 25. Log Statuses

| Status | Meaning |
|---|---|
| Processing | The message is currently being sent. |
| Accepted | Ersaal accepted the message request. |
| Retry Scheduled | The plugin will automatically try sending it again. |
| Failed | Sending failed completely (e.g., wrong phone number). |
| Error | A system error occurred during sending. |

---

## 26. Why is the Phone Number Hidden?
In the logs, you might see phone numbers like `+21892****268`.
> The plugin hides the middle digits of the phone number to protect your customers' privacy.

---

## 27. View Details
You can click the **Details** button on any log entry to open a window showing more information, such as:
* The exact error message (if it failed).
* The number of retry attempts.
* The WooCommerce Order it belongs to.

---

## 28. Common Errors

### API Connection Failed
* Check if the API Base URL is typed correctly.
* Verify your API Key.
* Ensure your Ersaal Project is Active.
* Make sure your website is connected to the internet.

### Invalid Sender ID
* Double-check that your Sender ID is officially approved in Ersaal.
* Type the name exactly as it appears in your account.

### Insufficient Balance
* Verify your Wallet balance inside your Ersaal account.

### Subscription Error
> You might have an active subscription, but it is for OTPs, not standard SMS.

### Invalid Phone Number
* Ensure the customer's phone number includes the correct country code.

### Empty Message
* You must write a message; you cannot send blank text.

### Accepted But Customer Received Nothing
> "Accepted" means Ersaal received the request. It does not mean "Delivered". You should check the delivery status directly in your Ersaal account or contact your provider.

---

## 29. Changing Your API Key
If you need to update your key:
1. Open **Settings**.
2. Type the new key in the API Key box.
3. Click **Save Changes**.
4. Test the connection.

> If you don't want to change the key, you do not need to retype it every time you save other settings.

---

## 30. Advanced Settings
The **Advanced Settings** page contains specialized options.
Specifically, **Delete all data on uninstall**.
> **Warning**: Enabling this option will permanently delete all your SMS history and settings if you completely remove the plugin from WordPress.

---

## 31. Deactivating the Plugin
When you **Deactivate** the plugin from the Plugins page, automatic messages will stop sending. However, your settings and log history will remain safe on your website.

---

## 32. Deleting the Plugin
If you fully **Delete** the plugin, your data will only be deleted if you enabled the "Delete all data on uninstall" option in the Advanced Settings. Otherwise, the data stays in your database.

---

## 33. Quick Troubleshooting Checklist
If something isn't working, verify these points:
* [ ] Connection Status = Connected
* [ ] Project Status = Active
* [ ] Sender ID is correct and approved
* [ ] Payment Type is appropriate
* [ ] You have sufficient Wallet balance or an SMS Subscription
* [ ] Phone number is correct and includes country code
* [ ] Message is not empty
* [ ] The WooCommerce Event is enabled (for automatic messages)
* [ ] You have checked the Logs for specific error messages

---

## 34. FAQ

### Do I need WooCommerce to use this plugin?
No, you can use the plugin just to send manual SMS messages.

### Can I send manual SMS messages?
Yes, using the "Send SMS" page.

### Why does my Wallet say "Unknown"?
Because the exact balance amount is currently unavailable to display, not because it is zero.

### I have a Subscription, but it doesn't work?
Your subscription might be for OTP (passwords) instead of normal SMS messages.

### Does "Accepted" mean the phone received the message?
No. It means Ersaal accepted your request to send it.

### Why is the customer's phone number hidden in the logs?
To protect customer privacy.

### Where can I find the Message ID?
In the success message after sending, or in the Logs page.

### Can I send more than one message for the same order?
Yes, using the Manual Send box inside the WooCommerce order page.

### Will the plugin send the same automatic order notification twice?
No. The plugin prevents sending duplicate automatic messages for the same order status.

*(Note: This plugin is fully compatible with WooCommerce High-Performance Order Storage (HPOS)).*
