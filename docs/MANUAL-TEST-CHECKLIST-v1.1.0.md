# wp-ersaal v1.1.0 — Final Manual Test Checklist

Run this checklist on a staging site before creating the `v1.1.0` tag or GitHub Release. Use a test Ersaal project and phone number. Keep `ERSAAL_DISABLE_LOGIN_OTP` available in `wp-config.php` while testing login security.

## Preparation

- [ ] Confirm the plugin reports version `1.1.0` and remains active after updating from `1.0.2`.
- [ ] Confirm the OTP module and WordPress Login OTP are disabled immediately after the update.
- [ ] Confirm Ersaal Core, the landing page, and WordPress Core have no changes.
- [ ] Configure a valid API URL, bearer token, approved OTP sender, and payment source.
- [ ] Open OTP Settings and confirm the live service/balance status is accurate and contains no hardcoded plan name.

## OTP administration test

- [ ] Enable the OTP module and save settings.
- [ ] OTP Test → send a code to a valid test phone.
- [ ] Confirm the interface shows only the current workflow step and masks the phone.
- [ ] OTP Test → verify the correct code.
- [ ] Confirm the verified state is clear and the same code cannot be reused.
- [ ] Send again and submit a wrong code; confirm verification fails without exposing raw API data.
- [ ] Let a code expire; confirm the expired state and new-code action are clear.
- [ ] Test resend/cooldown and confirm repeated clicks do not create request spam.
- [ ] Test unavailable subscription/balance, rate-limit, and API-down messages if the test project can safely simulate them.

## OTP activity and privacy

- [ ] Confirm initiate and verify events appear in OTP Activity with action, status, context, reference, and time.
- [ ] Confirm phone numbers are masked and no full phone appears in the database or UI log.
- [ ] Confirm no OTP code, bearer token, API key, Authorization header, or raw sensitive response appears in logs, options, user meta, transients, page source, URL, or debug log.
- [ ] Confirm OTP Activity filters and pagination work.
- [ ] Confirm dashboard OTP totals, verification result counts, rate, and recent activity match the stored events.

## User phone enrollment

- [ ] Open a test user's profile and enter a normalized international phone number.
- [ ] Send and verify the user's phone.
- [ ] Confirm the profile shows Verified and records the verification time.
- [ ] Enable Login OTP for that user and save the profile.
- [ ] Change the phone; confirm verification is cleared and per-user Login OTP is disabled.
- [ ] Confirm a newly entered phone cannot enable Login OTP until it is verified.
- [ ] Confirm a user without permission cannot modify or verify another user's phone.

## WordPress Login 2FA

- [ ] Enable global WordPress Login OTP and keep it enabled for the verified test user only.
- [ ] User without 2FA → username/password completes the normal WordPress login.
- [ ] 2FA user + wrong password → normal error and no OTP request is sent.
- [ ] 2FA user + correct password → OTP challenge appears and no auth cookie exists yet.
- [ ] Confirm the challenge URL contains no OTP, phone, user ID, or public challenge token.
- [ ] Confirm the challenge masks the phone and supports keyboard focus and one-time-code autocomplete.
- [ ] Correct OTP → login completes as the intended user.
- [ ] Wrong OTP → login remains denied.
- [ ] Expired OTP → login remains denied and a new code can be requested.
- [ ] Resend → a new OTP reference is created and the old workflow cannot grant access.
- [ ] Reuse the successful challenge/code → replay is denied.
- [ ] Confirm five local failed attempts end the pending challenge.
- [ ] Confirm `Remember Me` is preserved after successful OTP.
- [ ] Confirm a valid `redirect_to` is preserved and an external redirect is rejected safely.
- [ ] Stop/block the Ersaal API → login fails closed for the 2FA user.
- [ ] Add `define('ERSAAL_DISABLE_LOGIN_OTP', true);` to `wp-config.php` → password login works without OTP and Settings shows the recovery notice.
- [ ] Remove the constant → OTP enforcement resumes; confirm Manual SMS and OTP Test were not disabled by the constant.

## Arabic, English, responsive, and accessibility

- [ ] Review Dashboard, Settings, OTP Test, Profile, SMS Logs, OTP Activity, Help, and Login OTP in Arabic/RTL.
- [ ] Review the same pages in English/LTR.
- [ ] Review representative pages at 1440, 1280, 1024, and 768 px with no horizontal overflow or clipped actions.
- [ ] Confirm labels, status messages, errors, buttons, focus indicators, tab order, and contrast are clear.
- [ ] Confirm all OTP text is translated in Arabic and technical values retain a readable LTR direction.

## SMS and WooCommerce regression

- [ ] Manual SMS succeeds and writes a log.
- [ ] WooCommerce new-order customer notification succeeds.
- [ ] WooCommerce processing, completed, and cancelled notifications succeed.
- [ ] A configured custom order-status notification succeeds.
- [ ] Admin new-order notification succeeds.
- [ ] Manual order SMS succeeds.
- [ ] Temporary failure schedules retry; permanent failure does not.
- [ ] Idempotency prevents duplicate automatic sends.
- [ ] SMS Logs filters, details, single delete, bulk delete, and CSV export work.
- [ ] Delete all SMS logs while the plugin stays active, then send again; confirm a new log is created without a “Log not found after execution” error.
- [ ] Dashboard, Settings, Send SMS, Logs, and Help remain functional.

## Final gate

- [ ] PHP syntax, JavaScript syntax, translation audit, debug scan, secret scan, and local-runtime-path scan pass.
- [ ] `feature/v1.1-otp` is clean and synchronized with its remote branch.
- [ ] Do not create a tag, GitHub Release, or release ZIP until every manual item above is accepted.
