# Ersaal OTP API Contract

This document records the OTP contract implemented by the Ersaal API gateway. It was derived from the read-only Ersaal Core sources, primarily:

- `go/internal/gateway/server.go`
- `go/internal/gateway/handler_otp.go`
- `go/internal/gateway/validate.go`
- `go/internal/gateway/middleware.go`
- `go/internal/gateway/response.go`
- `go/internal/gateway/handler_common.go`
- `infra/nginx/api-gateway.conf`

No credentials or production tokens are included here.

## Transport and authentication

- Base URL: the configured Ersaal API base URL.
- Content type: `application/json`.
- Authentication: `Authorization: Bearer <project-token>`.
- Both OTP endpoints also enforce the project's IP whitelist when configured.
- An inactive project/company, an invalid token, or a disallowed IP is rejected before OTP handling.
- OTP initiation supports an optional `Idempotency-Key` header. Successful responses are cached by project and key for 24 hours.

Errors use this shape:

```json
{
  "message": "Safe error description"
}
```

## Initiate OTP

### Endpoint

```text
POST /api/otp/initiate
```

### Request

```json
{
  "receiver": "00218911234567",
  "sender": "ApprovedSender",
  "length": 6,
  "expiration": 5,
  "lang": "en",
  "payment_type": "subscription"
}
```

| Field | Required | Contract |
|---|---:|---|
| `receiver` | Yes | Normalized Libyan mobile number in `002189XXXXXXXX` format. The gateway detects the provider from it. |
| `sender` | Yes | Must be an active sender approved for the company and the receiver's provider. |
| `length` | Yes | Integer `4` or `6`. |
| `expiration` | Yes | Integer from `1` through `10`, in minutes. |
| `lang` | Yes | `ar` or `en`; controls the OTP message language. |
| `payment_type` | Yes | `wallet` or `subscription`. |

If the company is not verified, the gateway sends to the company's registered phone instead of the supplied receiver.

### Success response

HTTP `200`:

```json
{
  "request_id": "uuid",
  "cost": 1
}
```

- `request_id` is the reference required by verification.
- `cost` is a monetary numeric value for wallet payment and the consumed OTP/SMS parts count for subscription payment.
- The response does not return the OTP code, expiry timestamp, remaining attempts, or delivery status.

### Validation and operational errors

| HTTP | Meaning |
|---:|---|
| `400` | Invalid JSON, active OTP already exists for the phone, unsupported sender/provider combination, or insufficient balance. |
| `401` | Missing/invalid Bearer token, inactive project/company, missing company data, or IP not whitelisted. |
| `404` | Sender is missing or inactive. |
| `422` | Required field validation failed or `lang`, `length`, `expiration`, or `payment_type` is outside the accepted values. |
| `500` | OTP storage/queueing or another gateway operation failed. |

## Verify OTP

### Endpoint

```text
POST /api/otp/verify
```

### Request

```json
{
  "request_id": "uuid-from-initiate",
  "code": "123456"
}
```

| Field | Required | Contract |
|---|---:|---|
| `request_id` | Yes | The `request_id` returned by initiation. |
| `code` | Yes | Non-empty OTP code supplied by the user. |

### Success response

HTTP `200`:

```json
{
  "message": "OTP verified successfully"
}
```

Successful verification deletes both the reference record and the phone duplicate-check record, making the OTP one-time and preventing replay.

### Errors

| HTTP | Meaning |
|---:|---|
| `400` | Invalid JSON or incorrect OTP code. |
| `401` | Authentication, project/company state, or IP whitelist failure. |
| `404` | Reference does not exist, has expired, or was already used. |
| `422` | Missing `request_id` or `code`. |
| `500` | Stored OTP record is corrupt or another gateway failure occurred. |

## Expiration

- Expiration is selected by the initiating client within the API-supported range of 1–10 minutes.
- The reference and phone duplicate-check keys receive the same Redis TTL.
- The API does not return `expires_at` or `expires_in`; clients must calculate a local display deadline from the submitted expiration and must still treat the API as the source of truth.
- An expired reference returns HTTP `404` with `OTP not found or expired`.

## Attempts

- The current Go gateway does not return `attempts_remaining`.
- An incorrect code returns HTTP `400` and does not delete the challenge.
- The gateway source does not currently decrement a separate verification-attempt counter; the OTP remains bounded by its TTL.
- Consumers must not invent or display an API attempt count. A local consumer may invalidate its own pending workflow to limit abuse without representing that policy as an Ersaal API limit.

## Rate limiting and resend

- The production Nginx configuration routes exact OTP POST endpoints directly to the Go gateway.
- The Go gateway prevents another OTP for the same project and phone while the phone key is active, returning HTTP `400` with `You have already sent an OTP to this number`.
- Laravel retains an `OTPRateLimitMiddleware` definition of 10 requests per project/phone per hour with HTTP `429` and `Retry-After`, but that middleware is not on the direct Nginx-to-Go route. Clients must still handle `429` defensively if deployment routing changes.
- Resend must respect the active-OTP response and any `Retry-After` header. Local UI cooldown is only an additional anti-spam control.

## Subscription and payment behavior

- `payment_type=subscription` reserves OTP subscription units based on encoded SMS parts.
- `payment_type=wallet` reserves wallet balance using the configured SMS cost per part.
- Insufficient wallet or OTP subscription balance returns HTTP `400` with `Insufficient balance`.
- The contract exposes no plan names and no OTP-subscription metadata on initiate/verify.
- Project balance/details endpoints can be used separately for a best-effort service-status display, but initiate remains authoritative.

## WordPress integration rules

- Persist only the `request_id` and safe workflow metadata; never persist the OTP code.
- Never log the full receiver, OTP code, Bearer token, raw headers, or sensitive response bodies.
- Send `length`, `expiration`, `lang`, sender, and payment type only from validated settings supported by this contract.
- Normalize errors into stable local codes while retaining only a safe, translated message for users.
