<?php
declare(strict_types=1);
if (php_sapi_name() === 'cli' && !defined('ABSPATH')) {
    require_once dirname(__DIR__, 5) . '/wp-load.php';
}
defined('ABSPATH') || exit;

require_once ABSPATH . 'wp-admin/includes/user.php';

use Ersaal\Modules\OTP\OTPUserProfile;

if (!function_exists('curl_init')) {
    echo "cURL extension is required for this end-to-end test.\n";
    exit(1);
}

echo "=== ERSAAL v1.1 OTP LOGIN END-TO-END TESTS ===\n\n";
$failures = 0;
function e2eAssert(string $name, bool $condition, string $details = ''): void
{
    global $failures;
    echo str_pad($name, 42) . ($condition ? 'PASS' : 'FAIL' . ($details ? " ({$details})" : '')) . "\n";
    if (!$condition) { $failures++; }
}

function e2eRequest(string $url, string $cookieFile, ?array $fields = null): array
{
    $handle = curl_init($url);
    curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_HTTPHEADER => ['Expect:'],
    ]);
    if ($fields !== null) {
        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($fields));
    }
    $raw = (string) curl_exec($handle);
    $error = curl_error($handle);
    $headerSize = (int) curl_getinfo($handle, CURLINFO_HEADER_SIZE);
    $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    curl_close($handle);
    return ['status' => $status, 'headers' => substr($raw, 0, $headerSize), 'body' => substr($raw, $headerSize), 'error' => $error];
}

function e2eNonce(string $html): string
{
    return preg_match('/name="ersaal_otp_login_nonce" value="([^"]+)"/', $html, $match) ? html_entity_decode($match[1], ENT_QUOTES) : '';
}

function e2eLocation(string $headers): string
{
    return preg_match('/(?:^|\r\n)Location:\s*([^\r\n]+)/i', $headers, $match) ? trim($match[1]) : '';
}

function e2eCookies(string $path): array
{
    $cookies = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        if (strpos($line, '#HttpOnly_') === 0) {
            $line = substr($line, strlen('#HttpOnly_'));
        } elseif ($line[0] === '#') {
            continue;
        }
        $parts = explode("\t", $line);
        if (count($parts) >= 7) {
            $cookies[$parts[5]] = ['value' => $parts[6], 'expires' => (int) $parts[4]];
        }
    }
    return $cookies;
}

function e2ePrime(string $loginUrl, string $cookieFile): void
{
    e2eRequest($loginUrl, $cookieFile);
}

function e2eSetMockMode(string $mode): void
{
    $path = sys_get_temp_dir() . '/ersaal-otp-mock-state.json';
    $state = is_file($path) ? json_decode((string) file_get_contents($path), true) : [];
    $state = is_array($state) ? $state : [];
    $state += ['initiate' => 0, 'verify' => 0, 'used' => []];
    $state['mode'] = $mode;
    file_put_contents($path, wp_json_encode($state));
}

$optionNames = ['ersaal_api_url', 'ersaal_api_key', 'ersaal_otp_enabled', 'ersaal_otp_login_enabled', 'ersaal_otp_sender', 'ersaal_otp_expiration'];
$missing = new stdClass();
$backups = [];
foreach ($optionNames as $name) { $backups[$name] = get_option($name, $missing); }
$userId = 0;
$cookieFiles = [];

try {
    $mockStatePath = sys_get_temp_dir() . '/ersaal-otp-mock-state.json';
    file_put_contents($mockStatePath, wp_json_encode(['mode' => 'ok', 'initiate' => 0, 'verify' => 0, 'used' => []]));
    update_option('ersaal_api_url', 'http://127.0.0.1:18080');
    update_option('ersaal_api_key', 'e2e-local-placeholder');
    update_option('ersaal_otp_enabled', true);
    update_option('ersaal_otp_login_enabled', true);
    update_option('ersaal_otp_sender', 'Lamah');
    update_option('ersaal_otp_expiration', 1);

    $username = 'ersaal_otp_e2e_' . wp_generate_password(8, false, false);
    $password = wp_generate_password(28, true, true);
    $userId = wp_create_user($username, $password, $username . '@example.invalid');
    if (is_wp_error($userId)) { throw new RuntimeException($userId->get_error_message()); }
    update_user_meta($userId, OTPUserProfile::META_PHONE, '+218911234567');
    update_user_meta($userId, OTPUserProfile::META_VERIFIED, 1);
    update_user_meta($userId, OTPUserProfile::META_LOGIN_ENABLED, 1);

    $loginUrl = site_url('wp-login.php', 'login_post');
    $redirect = admin_url('profile.php');
    $loginFields = ['log' => $username, 'pwd' => $password, 'wp-submit' => 'Log In', 'redirect_to' => $redirect, 'testcookie' => '1', 'rememberme' => 'forever'];

    $wrongJar = tempnam(sys_get_temp_dir(), 'ersaal-otp-wrong-');
    $cookieFiles[] = $wrongJar;
    e2ePrime($loginUrl, $wrongJar);
    $wrong = e2eRequest($loginUrl, $wrongJar, array_merge($loginFields, ['pwd' => 'definitely-wrong']));
    $mockState = json_decode((string) file_get_contents($mockStatePath), true);
    e2eAssert('wrong_password_sends_no_otp', (int) $mockState['initiate'] === 0 && strpos(e2eLocation($wrong['headers']), 'ersaal_otp') === false);

    $jar = tempnam(sys_get_temp_dir(), 'ersaal-otp-login-');
    $cookieFiles[] = $jar;
    e2ePrime($loginUrl, $jar);
    $passwordStep = e2eRequest($loginUrl, $jar, $loginFields);
    $challengeUrl = e2eLocation($passwordStep['headers']);
    $mockState = json_decode((string) file_get_contents($mockStatePath), true);
    e2eAssert('correct_password_triggers_otp', $passwordStep['status'] === 302 && strpos($challengeUrl, 'action=ersaal_otp') !== false && (int) $mockState['initiate'] === 1);
    e2eAssert('challenge_token_not_exposed_in_url', strpos($challengeUrl, 'challenge=') === false);

    $screen = e2eRequest($challengeUrl, $jar);
    $nonce = e2eNonce($screen['body']);
    e2eAssert('challenge_screen_is_masked', $nonce !== '' && strpos($screen['body'], '+218911234567') === false && strpos($screen['body'], 'ersaal_otp_code') !== false);

    $wrongCode = e2eRequest($challengeUrl, $jar, ['ersaal_otp_login_nonce' => $nonce, 'ersaal_otp_code' => '000000', 'otp_action' => 'verify']);
    $wrongCookies = e2eCookies($jar);
    e2eAssert('wrong_otp_denies_login', $wrongCode['status'] === 200 && !isset($wrongCookies[AUTH_COOKIE]));

    $nonce = e2eNonce($wrongCode['body']);
    $success = e2eRequest($challengeUrl, $jar, ['ersaal_otp_login_nonce' => $nonce, 'ersaal_otp_code' => '123456', 'otp_action' => 'verify']);
    $cookies = e2eCookies($jar);
    $authCookie = $cookies[AUTH_COOKIE] ?? null;
    clean_user_cache($userId);
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $authenticatedUser = $authCookie ? wp_validate_auth_cookie(rawurldecode($authCookie['value']), 'auth') : 0;
    e2eAssert('correct_otp_completes_login', $success['status'] === 302 && $authenticatedUser === $userId);
    e2eAssert('redirect_to_is_preserved_safely', e2eLocation($success['headers']) === $redirect);
    e2eAssert('remember_me_is_preserved', $authCookie && $authCookie['expires'] > time() + (10 * DAY_IN_SECONDS));

    $replay = e2eRequest($challengeUrl, $jar, ['ersaal_otp_login_nonce' => $nonce, 'ersaal_otp_code' => '123456', 'otp_action' => 'verify']);
    e2eAssert('challenge_replay_is_blocked', $replay['status'] === 200 && strpos(e2eLocation($replay['headers']), 'profile.php') === false);

    $expiryJar = tempnam(sys_get_temp_dir(), 'ersaal-otp-expiry-');
    $cookieFiles[] = $expiryJar;
    e2ePrime($loginUrl, $expiryJar);
    $expiryStart = e2eRequest($loginUrl, $expiryJar, array_merge($loginFields, ['rememberme' => '']));
    $expiryUrl = e2eLocation($expiryStart['headers']);
    $expiryCookies = e2eCookies($expiryJar);
    $challengeCookie = $expiryCookies['ersaal_otp_challenge']['value'] ?? '';
    $transientKey = 'ersaal_otp_login_' . hash('sha256', $challengeCookie);
    $state = get_transient($transientKey);
    e2eAssert('challenge_is_bound_to_user', is_array($state) && (int) $state['user_id'] === $userId);
    $state['expires_at'] = time() - 1;
    $state['resend_after'] = time() - 1;
    set_transient($transientKey, $state, 5 * MINUTE_IN_SECONDS);
    $expiryScreen = e2eRequest($expiryUrl, $expiryJar);
    $expiryNonce = e2eNonce($expiryScreen['body']);
    $expired = e2eRequest($expiryUrl, $expiryJar, ['ersaal_otp_login_nonce' => $expiryNonce, 'ersaal_otp_code' => '123456', 'otp_action' => 'verify']);
    e2eAssert('expired_challenge_denies_login', $expired['status'] === 200 && !isset(e2eCookies($expiryJar)[AUTH_COOKIE]));
    $resendNonce = e2eNonce($expired['body']);
    $beforeResend = json_decode((string) file_get_contents($mockStatePath), true);
    $resent = e2eRequest($expiryUrl, $expiryJar, ['ersaal_otp_login_nonce' => $resendNonce, 'otp_action' => 'resend']);
    $afterResend = json_decode((string) file_get_contents($mockStatePath), true);
    e2eAssert('resend_creates_new_otp', $resent['status'] === 200 && (int) $afterResend['initiate'] === (int) $beforeResend['initiate'] + 1);

    e2eSetMockMode('down');
    $downJar = tempnam(sys_get_temp_dir(), 'ersaal-otp-down-');
    $cookieFiles[] = $downJar;
    e2ePrime($loginUrl, $downJar);
    $down = e2eRequest($loginUrl, $downJar, $loginFields);
    e2eAssert('api_down_fails_closed', $down['status'] === 200 && strpos(e2eLocation($down['headers']), 'ersaal_otp') === false && !isset(e2eCookies($downJar)[AUTH_COOKIE]));
} finally {
    if ($userId) {
        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'ersaal_otp_logs', ['user_id' => $userId], ['%d']);
        wp_delete_user($userId);
    }
    foreach ($backups as $name => $value) {
        $value === $missing ? delete_option($name) : update_option($name, $value);
    }
    global $wpdb;
    $transientPrefix = $wpdb->esc_like('_transient_ersaal_otp_login_') . '%';
    $timeoutPrefix = $wpdb->esc_like('_transient_timeout_ersaal_otp_login_') . '%';
    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $transientPrefix, $timeoutPrefix));
    foreach ($cookieFiles as $path) { if (is_file($path)) { unlink($path); } }
    if (isset($mockStatePath) && is_file($mockStatePath)) { unlink($mockStatePath); }
}

echo "\n" . ($failures === 0 ? 'ALL OTP LOGIN END-TO-END TESTS PASSED' : "{$failures} OTP LOGIN END-TO-END TEST(S) FAILED") . "\n";
exit($failures === 0 ? 0 : 1);
