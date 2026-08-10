# OTP Developer API

Use the shared service so integrations inherit the Ersaal client, validation, settings, privacy rules, safe logs, and error mapping.

## Obtain the service

Call the helper after `plugins_loaded`:

```php
add_action('init', function (): void {
    $otp = ersaal_otp_service();
    if (!$otp || !$otp->isEnabled()) {
        return;
    }

    $sent = $otp->initiate('+2189XXXXXXXX', 'member_confirmation', [
        'user_id' => get_current_user_id(),
    ]);

    if ($sent->isSuccess()) {
        $reference = $sent->getReference();
        // Bind the reference to your user/transaction for a short period.
    }
});
```

The helper can return `null` while Ersaal is still booting.

## Verify

```php
$result = ersaal_otp_service()->verify(
    $reference,
    $submittedCode,
    'member_confirmation',
    ['user_id' => $userId, 'phone' => $phone]
);

unset($submittedCode);

if ($result->isSuccess()) {
    // Complete the bound one-time workflow.
}
```

The submitted code goes directly to Ersaal. The service never passes it to hooks, logs, transients, or user metadata.

## Result object

`OTPResult` provides `isSuccess()`, `getStatus()`, `getReference()`, `getCost()`, `getExpiresIn()`, `getErrorCode()`, `getErrorMessage()`, `getHttpStatus()`, `getRetryAfter()`, and `toArray()`.

Stable local statuses include `sent`, `verified`, `invalid`, `expired`, `rate_limited`, `unavailable`, `failed`, and `disabled`. Drive logic from status/error code, not translated messages.

## Hooks

```php
do_action('ersaal_otp_before_initiate', $context, $safeMetadata);
do_action('ersaal_otp_initiated', $resultArray, $context, $safeMetadata);
do_action('ersaal_otp_verified', $resultArray, $context, $safeMetadata);
do_action('ersaal_otp_failed', $resultArray, $context, $safeMetadata);
```

Safe metadata contains only `phone_masked` and `user_id`; results never contain the code or full phone. Hook listeners must keep these guarantees.

## Integration responsibilities

- Bind each reference to its intended user or transaction.
- Use an unpredictable public token when state crosses requests.
- Add a nonce and suitable capability/ownership check.
- Apply short expiration, resend cooldown, and local abuse controls.
- Delete local workflow state before granting access to prevent replay.
- Never log request bodies or submitted codes.
- Validate user-controlled redirects with `wp_safe_redirect()` or `wp_validate_redirect()`.

The admin test, profile enrollment, and WordPress Login OTP are reference implementations. See [OTP Architecture](OTP-ARCHITECTURE.md) and the [real API contract](OTP-API-CONTRACT.md).
