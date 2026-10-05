=== Ersaal SMS Gateway ===
Contributors: lamah
Tags: sms, woocommerce, ersaal, otp, notifications
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Send SMS notifications, verify phone numbers with OTP, and automate WooCommerce order updates via the official Ersaal SMS Gateway API.

== Description ==

Ersaal SMS Gateway allows WordPress administrators to send SMS messages directly from the WordPress admin dashboard, verify Libyan mobile phone numbers with secure One-Time Passwords (OTP), and automatically notify WooCommerce customers about their order statuses using the robust Ersaal API.

= Key Features =

* **Native Admin Dashboard:** Monitor live delivery statuses, cost estimations, and daily messaging volume.
* **Manual SMS:** Send individual or targeted SMS messages with real-time GSM-7 and Unicode part estimations.
* **WooCommerce Automated Notifications:** Automatically send customized SMS alerts on Order Pending, Processing, Completed, and Cancelled events.
* **One-Time Password (OTP) Verification:** Optional phone verification for user profiles and Two-Factor Authentication (2FA) for WordPress logins.
* **Libyan Telecom Network Validation:** Native normalization and validation for Almadar (091, 093) and Libyana (092, 094) mobile networks.
* **Comprehensive Logs & Privacy:** Detailed local logging with sensitive number masking, carrier telemetry, and CSV export.
* **Fully Localized:** Complete Arabic and English native interfaces with full RTL and LTR support.

== Installation ==

1. Upload the `ersaal` folder to the `/wp-content/plugins/` directory, or install directly through the WordPress Plugins screen.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Navigate to **Ersaal SMS -> Settings** to configure your API URL and Token.
4. Test the API connection to confirm project readiness.

== Frequently Asked Questions ==

= Do I need an Ersaal account? =
Yes. You need an active account and API Bearer Token from [Ersaal](https://ersaal.com/).

= Which Libyan telecom networks are supported? =
The plugin natively validates and normalizes Libyan mobile numbers for Almadar Aljaded (091, 093) and Libyana Mobile Phone (092, 094).

= How does WooCommerce order notification work? =
Under **Ersaal SMS -> WooCommerce**, enable notifications and customize templates using dynamic tags like `{customer_name}`, `{order_number}`, and `{order_total}`.

= Is OTP verification mandatory? =
No. OTP features and Login Two-Factor Authentication (2FA) are opt-in and disabled by default.

== Screenshots ==

1. Ersaal SMS Dashboard overview.
2. WooCommerce automated SMS notification settings.
3. Manual SMS composition with live character counter.
4. Detailed SMS activity logs and filters.

== Changelog ==

= 1.2.0 =
* Full WordPress.org plugin directory compliance.
* Hardened database queries with prepared statements.
* Enhanced RTL styling and responsive design.
* Added official GPLv2 licensing and clean distribution packaging.

= 1.1.0 =
* Added OTP initiate and verify service.
* Added optional WordPress user profile phone verification and Login 2FA.
* Added runtime migration for dedicated OTP activity logs.

= 1.0.0 =
* Initial release with Manual Send, WooCommerce notifications, and local activity logging.
