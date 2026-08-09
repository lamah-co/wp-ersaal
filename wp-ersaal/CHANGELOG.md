# Changelog

All notable changes to this project will be documented in this file.

## Unreleased

### Added
- Complete Arabic translation catalog for the WordPress admin interface, AJAX feedback, logs, settings, and WooCommerce screens.
- Explicit WordPress text-domain loading and Arabic plural rules.

### Changed
- Improved RTL behavior and Arabic wording throughout the built-in help guide.

## [1.0.1] - 2026-08-09

### Changed
- Redesigned the Ersaal WordPress admin interface.
- Improved dashboard layout and information hierarchy.
- Improved settings and manual SMS user experience.
- Improved WooCommerce notification configuration UI.
- Redesigned logs layout, filters, status badges, and details.
- Improved in-plugin Help experience.
- Unified buttons, cards, inputs, badges, alerts, and spacing using the shared design system.
- Improved RTL and LTR consistency.
- Improved tablet and responsive admin layouts.

### Fixed
- Fixed log status badge rendering warnings and missing statuses.
- Fixed an issue where the database schema wasn't updating automatically upon pulling new changes, which caused log insertions to fail.
- Fixed UI inconsistencies introduced during the admin redesign.

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
