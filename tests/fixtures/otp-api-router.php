<?php
declare(strict_types=1);

// Local-only HTTP fixture for tests/manual/test_otp_login_e2e.php.
// phpcs:ignoreFile
if (in_array(php_sapi_name(), ['cli', 'cli-server'], true) && !defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}
defined('ABSPATH') || exit;

$statePath = sys_get_temp_dir() . '/ersaal-otp-mock-state.json';
$state = is_file($statePath) ? json_decode((string) file_get_contents($statePath), true) : [];
$state = is_array($state) ? $state : [];
$state += ['mode' => 'ok', 'initiate' => 0, 'verify' => 0, 'sms' => 0, 'used' => []];
header('Content-Type: application/json; charset=utf-8');

if ($state['mode'] === 'down') {
    http_response_code(500);
    echo json_encode(['message' => 'Service unavailable']);
    exit;
}

$path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if ($path === '/api/sms/messages') {
    $state['sms']++;
    file_put_contents($statePath, json_encode($state));
    echo json_encode([
        'data' => [
            'message_id' => 'mock-sms-' . $state['sms'],
            'parts' => 1,
            'cost' => 0.01,
        ],
    ]);
    exit;
}

if ($path === '/api/otp/initiate') {
    $state['initiate']++;
    $reference = 'mock-reference-' . $state['initiate'];
    file_put_contents($statePath, json_encode($state));
    echo json_encode(['request_id' => $reference, 'cost' => 0.01]);
    exit;
}

if ($path === '/api/otp/verify') {
    $payload = json_decode((string) file_get_contents('php://input'), true);
    $reference = (string) ($payload['request_id'] ?? '');
    $code = (string) ($payload['code'] ?? '');
    $state['verify']++;
    if (!empty($state['used'][$reference])) {
        file_put_contents($statePath, json_encode($state));
        http_response_code(404);
        echo json_encode(['message' => 'OTP expired or not found']);
        exit;
    }
    if ($code !== '123456') {
        file_put_contents($statePath, json_encode($state));
        http_response_code(400);
        echo json_encode(['message' => 'Invalid OTP code']);
        exit;
    }
    $state['used'][$reference] = true;
    file_put_contents($statePath, json_encode($state));
    echo json_encode(['message' => 'OTP verified successfully']);
    exit;
}

http_response_code(404);
echo json_encode(['message' => 'Not found']);
