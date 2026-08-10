# OTP Setup Guide

OTP support is optional and disabled by default. Upgrading does not enroll users or change WordPress login.

## Requirements

- A working Ersaal API connection.
- An active approved sender compatible with the destination provider.
- Wallet balance or an OTP subscription with available units.
- An international phone number such as `+2189XXXXXXXX`.

## Configure and test

1. Open **Ersaal → Settings → OTP** and enable OTP services.
2. Choose the sender, wallet or subscription, 4 or 6 digits, a 1–10 minute lifetime, and Arabic, English, or Automatic language.
3. Save, then open **Ersaal → OTP Test**.
4. Enter a phone with its country code and send the code.
5. Enter the received code before expiry and verify it.
6. Review the safe record in **Ersaal → OTP Activity**.

Automatic language follows the WordPress locale. A real send can consume wallet balance or an OTP subscription unit. The temporary workflow keeps the phone and reference only for its short verification window; the code is never stored.

## Activity and privacy

OTP Activity contains the action, outcome, workflow, masked phone, one-way phone fingerprint, safe reference, HTTP status, and sanitized error summary. It never contains the OTP code, full phone, API credentials, password, or authentication cookie.

## Common failures

- **Authentication:** verify the API key, project/company status, and IP allowlist.
- **Sender:** select an active approved sender compatible with the phone provider.
- **Balance/subscription:** fund the wallet or select an OTP plan with available units.
- **Active code:** use it or wait for the configured lifetime.
- **Expired/used:** send a new code; successful codes cannot be replayed.
- **Rate limited:** wait for the retry period.
- **Unavailable:** retry later and review the connection and Ersaal status.

Next: [WordPress Login OTP](OTP-LOGIN-2FA-EN.md) or [OTP Developer API](OTP-DEVELOPER-API.md).
