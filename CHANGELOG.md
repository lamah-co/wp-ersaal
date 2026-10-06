# Changelog

All notable changes to this project will be documented in this file.

## [1.1.0] - 2026-08-10

### Added
- Real Ersaal OTP initiate and verify integration with a reusable internal service and documented developer hooks.
- OTP settings, service-readiness status, administration test workflow, privacy-safe activity logs, and dashboard metrics.
- Verified OTP phone enrollment in WordPress user profiles.
- Optional per-user WordPress Login 2FA with secure temporary challenges, resend handling, Remember Me support, and safe redirects.
- Runtime database migration for the dedicated OTP activity table without plugin reactivation.
- Complete Arabic and English OTP interfaces, help content, user guides, API contract, developer guide, and manual test checklist.
- Libyan phone validation strictly enforcing Almadar (091, 093) and Libyana (092, 094) mobile networks.
- Central phone normalization converting inputs automatically to the standard 002189XXXXXXXX API format.

### Changed
- Extended the shared Ersaal administration design system for OTP screens in RTL and LTR layouts.
- Expanded regression coverage for messaging, WooCommerce notifications, logs, active upgrades, localization, and login security.

### Security
- OTP codes are never persisted, logged, exposed in URLs, or passed to developer hooks.
- Login OTP starts only after WordPress validates the password and creates no authentication cookie before successful verification.
- Added replay-safe, user-bound, expiring challenges with local attempt limits and fail-closed API behavior.
- Changing a stored phone clears verification and disables that user's login 2FA.
- Added the `ERSAAL_DISABLE_LOGIN_OTP` wp-config.php recovery switch for the login OTP layer only.

## [1.0.2] - 2026-08-09

### Added
- Full Arabic localization across the WordPress admin interface and AJAX feedback.
- Added Arabic PO and MO files and the source POT translation template.
- Automatic locale loading based on the WordPress user or site language, including Arabic plural rules.

### Changed
- Improved RTL behavior across the Dashboard, Settings, Send SMS, WooCommerce, Logs, and Help screens.
- Localized WooCommerce notification settings, default messages, log statuses, sources, events, and CSV exports.
- Reworked the Help interface with complete Arabic wording and localized language controls.

### Fixed
- Corrected RTL alignment and directional icons in expandable controls and navigation actions.
- Improved Arabic layout consistency while keeping technical values such as phone numbers, IDs, and API URLs isolated as LTR.
- Corrected validation and API error fallbacks so English and Arabic locales each receive language-appropriate messages.
- Fixed WooCommerce invalid-phone logging so automatic notifications record a valid recipient type, status, and error message.

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
