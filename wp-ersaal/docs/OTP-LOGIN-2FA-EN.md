# WordPress Login OTP

Login OTP adds a second step after a correct username and password. It is opt-in at both site and user levels.

## Safety model

- Disabled by default; existing users are not enrolled.
- Wrong passwords never send OTP.
- No WordPress authentication cookie exists before OTP succeeds.
- The challenge is bound to one user and a 256-bit token held in an `HttpOnly`, `SameSite=Lax` cookie.
- The challenge token never appears in a URL, page, or browser history.
- A successful or ended challenge is deleted before login completes.
- Five failed local checks end the challenge.
- Redirects use WordPress safe-redirect validation.

## Enable and enroll

1. Complete a successful OTP Test.
2. Enable **Allow OTP for WordPress login** in **Ersaal → Settings → OTP**.
3. Open the user's WordPress profile and enter an international mobile phone.
4. Send and verify the profile code.
5. Enable **Require a verification code after my WordPress password** and save.

Global enablement alone changes no user. Changing/removing a phone clears verification and turns login OTP off. A replacement number must be verified and explicitly opted in again.

## Login experience

After WordPress accepts the password, Ersaal sends a code and shows a separate verification screen. WordPress creates its normal session only after the code succeeds. Resend stays unavailable until the active code expires, and used codes cannot be replayed.

## Emergency recovery

Add this to `wp-config.php`:

```php
define('ERSAAL_OTP_DISABLE_LOGIN_2FA', true);
```

This bypasses only Login OTP; tests, logs, developer OTP, SMS, WooCommerce, and settings remain available. Remove it or set it to `false` after resolving the issue.

For opted-in users, password authentication outside interactive `wp-login.php` fails closed so XML-RPC, REST, AJAX, or another non-interactive path cannot bypass the second step.
