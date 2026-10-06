# Public SMS Developer API

`wp-ersaal` exposes a stable facade for other WordPress plugins. Integrations should use this facade instead of constructing `MessageService`, `Client`, `PhoneValidator`, or repositories directly.

Call the API after `plugins_loaded`:

```php
if (!function_exists('ersaal_send_sms') || !ersaal_sms_available()) {
    return;
}

$result = ersaal_send_sms([
    'receiver'        => '0912345678',
    'message'         => 'Your booking is confirmed.',
    'idempotency_key' => 'booking:1:125:paid:customer:v1',
    'source'          => 'booking',
    'source_id'       => '125',
    'source_event'    => 'booking_paid',
    'recipient_type'  => 'customer',
]);
```

## Availability

```php
ersaal_sms_available(): bool
```

The helper returns `true` only after the plugin has initialized and its API URL and API key are configured. It does not make a remote connection request.

## Send Contract

```php
ersaal_send_sms(array $request): \Ersaal\PublicApi\SmsResult
```

Required fields:

| Field | Description |
|---|---|
| `receiver` | A supported Libyan mobile number. Accepted local and international formats are normalized by `PhoneValidator`. |
| `message` | Plain SMS text. |

Recommended integration fields:

| Field | Description |
|---|---|
| `idempotency_key` | Stable key, at most 100 characters. A UUID is generated if omitted. |
| `source` | Integration identifier, at most 30 characters. |
| `source_id` | External record ID, at most 50 characters. |
| `source_event` | External event, at most 50 characters. |
| `recipient_type` | Recipient role, such as `customer` or `admin`. |

The facade owns sender and payment defaults. Integrations must not read Ersaal credentials or issue raw Ersaal HTTP requests. Defaults can be customized inside `wp-ersaal` with:

```php
add_filter('ersaal_public_sms_defaults', function (array $defaults, array $request): array {
    $defaults['sender'] = 'Lamah';
    $defaults['payment_type'] = 'wallet';
    return $defaults;
}, 10, 2);
```

## Result

`SmsResult` provides:

```text
isSuccess()
getStatus()
getLogId()
getMessageId()
getIdempotencyKey()
getErrorCode()
getErrorMessage()
toArray()
```

A successful facade call means the request is safely logged and queued. It does not mean the asynchronous Ersaal API request has already completed. The initial status is normally `processing`, and `message_id` is normally `null` until the queued job is accepted by Ersaal.

Expected validation and configuration failures return a failed `SmsResult`; they do not throw into the calling plugin. Unexpected transport failures are handled by the existing log and retry pipeline.

## Idempotency

Calling the facade repeatedly with the same `idempotency_key` reuses the existing log and does not enqueue another message. Integrations should derive a stable key from their domain event.

## Phone Handling and Privacy

The facade routes every number through the shared `PhoneValidator`. Supported Libyan prefixes are `091`, `092`, `093`, and `094`; successful normalization produces `002189XXXXXXXX`.

Logs store a masked phone and a fingerprint/hash, not the full phone. Invalid phones produce a privacy-safe failed log and no Ersaal API request.

## Log Extensions

External integrations can extend log labels and references without adding integration-specific code to `wp-ersaal`:

```php
add_filter('ersaal_log_sources', function (array $sources): array {
    $sources['booking'] = 'Booking';
    return $sources;
});

add_filter('ersaal_log_event_label', function (string $label, string $event): string {
    return $event === 'booking_paid' ? 'Booking paid' : $label;
}, 10, 2);

add_filter('ersaal_log_reference_label', function (string $label, object $log): string {
    return $log->source === 'booking' ? 'Booking #' . $log->source_id : $label;
}, 10, 2);

add_filter('ersaal_log_reference_url', function (string $url, object $log): string {
    return $log->source === 'booking'
        ? admin_url('admin.php?page=bookings&id=' . absint($log->source_id))
        : $url;
}, 10, 2);
```

The integration that supplies an admin URL must enforce its own capability rules.

## Delivery Hooks

```php
do_action('ersaal_message_accepted', $idempotencyKey, $messageId);
do_action('ersaal_message_failed', $idempotencyKey, $errorCode, $errorMessage);
```

The failed hook is emitted only for a permanent failed delivery attempt. A queued retry is not a permanent failure.
