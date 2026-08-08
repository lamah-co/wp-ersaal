# Changelog

All notable changes to this project will be documented in this file.

## [1.0.0] - 2026-08-08
### Added
- Native WordPress Admin UI for Ersaal configuration.
- Manual SMS sending with preview and cost estimation.
- WooCommerce integration for automated order status notifications (New Order, Processing, Completed, Cancelled).
- Support for dynamic WooCommerce custom order statuses.
- WooCommerce Admin Notifications (New Order SMS for Store Admin).
- Interactive Template UX with dynamic variables click-to-insert and real-time previews.
- Advanced Logging System (Search, Status filters, Source filters, Event filters, Date range filters).
- Bulk and Single log deletion with strict capability and nonce checks.
- Secure CSV Export for logs (respecting applied filters and UTF-8 Arabic support).
- Built-in Help & User Guide inside the WordPress dashboard (Bilingual: Arabic & English).
- SMS Activity Dashboard showing real-time metrics (Messages Today, Accepted, Failed, Processing) and recent messages.

### Security
- Strict nonce validation and capability checks (`manage_options`) across all administrative actions.
- Prepared SQL statements for all database interactions.
- Prevention of CSV injection vulnerabilities during export.
- Idempotency keys used for automated events to prevent duplicate SMS billing.
