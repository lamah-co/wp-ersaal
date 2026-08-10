# OTP Architecture

## Dependency direction

```text
Ersaal API
    ↓
API Client transport
    ↓
OTPClient
    ↓
OTPService
    ├── Admin OTP Test
    ├── User phone enrollment
    ├── WordPress Login 2FA
    └── Developer API and hooks
            ↓
      Dedicated OTP activity logs
            ↓
          Dashboard
```

WordPress Login is a consumer of `OTPService`; it does not own HTTP communication, OTP validation, or OTP logging.

## Storage decision

OTP activity uses a dedicated `wp_ersaal_otp_logs` table rather than extending the SMS log table.

The SMS table is tightly coupled to message content, sender, payment, parts, costs, delivery message IDs, retries, and WooCommerce sources. Adding nullable OTP-only fields to it would weaken both models and would expose irrelevant SMS columns in OTP views. The dedicated table stores only:

- action (`initiate` or `verify`)
- safe status
- masked phone and HMAC fingerprint
- Ersaal request reference
- context and optional WordPress user ID
- safe HTTP/error metadata
- timestamps

The table has no OTP-code or full-phone column.

## Schema migration

`Database::VERSION` is independent from the plugin version. During normal plugin boot, `Database::upgrade()` compares the installed schema option with the current schema version. `dbDelta()` runs only when the installed version is older, so an active v1.0.x installation upgrades without deactivate/reactivate and current installations do not run schema work on every request.

## Public service contract

After `plugins_loaded`, integrations can obtain the shared service with:

```php
$otp = ersaal_otp_service();
$send = $otp?->initiate($phone, 'custom');
$verify = $otp?->verify($reference, $code, 'custom', ['phone' => $phone]);
```

`OTPResult::toArray()` contains only safe result metadata. Verification hooks never receive the OTP code.
