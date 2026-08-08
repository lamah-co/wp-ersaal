# Changelog

All notable changes to the Ersaal SMS plugin will be documented in this file.

## [1.0.0] - 2026-08-08
### Added
- Core API connection module with robust error handling and token masking.
- Manual SMS sending interface with real-time character counting (GSM-7/Unicode).
- Automatic retry system for rate limits (HTTP 429) and temporary server errors (HTTP 5xx) using Action Scheduler.
- Strict Idempotency guarantees to prevent duplicate messaging.
- WooCommerce integration for automated order status notifications (New Order, Processing, Completed, Cancelled).
- Manual order SMS Meta Box with full HPOS (High-Performance Order Storage) support.
- Comprehensive Logs UI in WP Admin with Server-side Pagination, Advanced Filters, Search, and Status Badges.
- WooCommerce Order Notes integration for successful and failed message tracking.
- Privacy compliance by masking full phone numbers in the local database.
- Database auto-installation and schema updates upon plugin activation.
