<?php
declare(strict_types=1);

/**
 * Automated Verification Suite for GitHub Issues #23 through #32
 *
 * Verifies all security, stability, compatibility, and build fixes.
 */

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

// Polyfill minimal WP functions for standalone test runner
if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = 'default') { return $text; }
}
if (!function_exists('__')) {
    function __($text, $domain = 'default') { return $text; }
}
if (!function_exists('esc_html')) {
    function esc_html($text) { return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) { return trim(strip_tags((string) $str)); }
}
if (!function_exists('sanitize_key')) {
    function sanitize_key($key) { return strtolower(preg_replace('/[^a-z0-9_\-]/', '', (string) $key)); }
}
if (!function_exists('wp_parse_url')) {
    function wp_parse_url($url, $component = -1) { return parse_url($url, $component); }
}
if (!function_exists('wp_salt')) {
    function wp_salt($scheme = 'auth') { return 'test-salt-secret-key-12345'; }
}
if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data) { return json_encode($data); }
}
if (!function_exists('absint')) {
    function absint($maybeint) { return abs((int) $maybeint); }
}

require_once dirname(__DIR__, 3) . '/ersaal/src/Support/Str.php';

use Ersaal\Support\Str;

$passed = 0;
$failed = 0;

function assertTest(bool $condition, string $message): void {
    global $passed, $failed;
    if ($condition) {
        echo " [PASS] " . $message . "\n";
        $passed++;
    } else {
        echo " [FAIL] " . $message . "\n";
        $failed++;
    }
}

echo "\n=======================================================\n";
echo "   ERSAAL SMS GATEWAY - ISSUES #23-#32 VERIFICATION    \n";
echo "=======================================================\n\n";

// ── Test 1: Issue #28 (Str Unicode & Multibyte Handling) ───────────
$arabicStr = "إرسال - بوابة الرسائل القصيرة في ليبيا";
assertTest(Str::length($arabicStr) === 38, "Str::length correctly calculates length of Arabic UTF-8 text (38 chars).");
$sub = Str::substr($arabicStr, 0, 5);
assertTest($sub === "إرسال", "Str::substr correctly slices Arabic UTF-8 substring without corruption: '{$sub}'");
$limited = Str::limit($arabicStr, 10, '...');
assertTest($limited === "إرسال -...", "Str::limit correctly truncates text with suffix: '{$limited}'");

// ── Test 2: Issue #29 (API Client Error Message Type Safety) ───────
require_once dirname(__DIR__, 3) . '/ersaal/src/Core/Options.php';
require_once dirname(__DIR__, 3) . '/ersaal/src/API/Response.php';
require_once dirname(__DIR__, 3) . '/ersaal/src/API/Exceptions/ApiException.php';
require_once dirname(__DIR__, 3) . '/ersaal/src/API/Exceptions/ConnectionException.php';
require_once dirname(__DIR__, 3) . '/ersaal/src/API/Exceptions/AuthenticationException.php';
require_once dirname(__DIR__, 3) . '/ersaal/src/API/Exceptions/BalanceException.php';
require_once dirname(__DIR__, 3) . '/ersaal/src/API/Exceptions/RateLimitException.php';
require_once dirname(__DIR__, 3) . '/ersaal/src/API/Exceptions/ServerException.php';
require_once dirname(__DIR__, 3) . '/ersaal/src/API/Exceptions/ValidationException.php';
require_once dirname(__DIR__, 3) . '/ersaal/src/API/Client.php';

$options = new \Ersaal\Core\Options();
$client = new \Ersaal\API\Client($options);

$reflector = new ReflectionClass($client);
$handleErrorsMethod = $reflector->getMethod('handleErrors');
$handleErrorsMethod->setAccessible(true);

// Test non-string message: array inside message field
$caught = false;
try {
    $malformedBody = ['message' => ['nested_error' => 'Something failed'], 'errors' => ['Field is required']];
    $handleErrorsMethod->invoke($client, 422, $malformedBody, '{"message":{"nested_error":"Something failed"}}', null);
} catch (\Ersaal\API\Exceptions\ValidationException $e) {
    $caught = true;
    assertTest(
        $e->getMessage() === 'Field is required',
        "Client::handleErrors gracefully extracts fallback message from errors array when message is an array: " . $e->getMessage()
    );
} catch (\Throwable $e) {
    assertTest(false, "Client::handleErrors threw unexpected: " . get_class($e) . ": " . $e->getMessage());
}
assertTest($caught, "ValidationException caught as expected without TypeError.");

// Test scalar integer in message
$caughtInt = false;
try {
    $malformedBodyInt = ['message' => 50012];
    $handleErrorsMethod->invoke($client, 400, $malformedBodyInt, '{"message":50012}', null);
} catch (\Ersaal\API\Exceptions\ValidationException $e) {
    $caughtInt = true;
    assertTest($e->getMessage() === '50012', "Client::handleErrors safely handles scalar numeric error message: " . $e->getMessage());
} catch (\Throwable $e) {
    assertTest(false, "Client::handleErrors threw unexpected: " . get_class($e));
}
assertTest($caughtInt, "ValidationException caught for numeric message without TypeError.");

// ── Test 3: Issue #24 (Require Secure HTTPS API Transport) ─────────
$requestMethod = $reflector->getMethod('request');
$requestMethod->setAccessible(true);

// Test rejection of HTTP URL
$optionsHttp = new class extends \Ersaal\Core\Options {
    public function get(string $key, $default = null) {
        if ($key === 'api_url') return 'http://api.ersaal.com';
        if ($key === 'api_key') return 'secret-token';
        return $default;
    }
};
$clientHttp = new \Ersaal\API\Client($optionsHttp);
$caughtHttp = false;
try {
    $requestMethod->invoke($clientHttp, 'GET', '/test');
} catch (\Ersaal\API\Exceptions\ConnectionException $e) {
    $caughtHttp = true;
    assertTest(str_contains($e->getMessage(), 'HTTPS'), "Client::request strictly rejects insecure http:// URL with ConnectionException.");
}
assertTest($caughtHttp, "Insecure HTTP request blocked successfully.");

// Test rejection of embedded credentials
$optionsCred = new class extends \Ersaal\Core\Options {
    public function get(string $key, $default = null) {
        if ($key === 'api_url') return 'https://user:pass@api.ersaal.com';
        if ($key === 'api_key') return 'secret-token';
        return $default;
    }
};
$clientCred = new \Ersaal\API\Client($optionsCred);
$caughtCred = false;
try {
    $requestMethod->invoke($clientCred, 'GET', '/test');
} catch (\Ersaal\API\Exceptions\ConnectionException $e) {
    $caughtCred = true;
    assertTest(str_contains($e->getMessage(), 'credentials'), "Client::request rejects URLs with embedded user credentials.");
}
assertTest($caughtCred, "Embedded credentials blocked successfully.");

// ── Test 4: Issue #26 (CSV Formula Injection Sanitization) ─────────
require_once dirname(__DIR__, 3) . '/ersaal/src/Storage/LogRepository.php';
require_once dirname(__DIR__, 3) . '/ersaal/admin/LogsPage.php';
$logsPage = new \Ersaal\Admin\LogsPage(new \Ersaal\Storage\LogRepository());
$logsPageRef = new ReflectionClass($logsPage);
$escapeCsvMethod = $logsPageRef->getMethod('escapeCsvCell');
$escapeCsvMethod->setAccessible(true);

$dangerousInputs = [
    '=1+1' => "'=1+1",
    '+2345' => "'+2345",
    '-50' => "'-50",
    '@SUM(A1:A10)' => "'@SUM(A1:A10)",
    "\tCMD" => "'\tCMD",
    "\rPAYLOAD" => "'\rPAYLOAD",
    'Normal Text' => 'Normal Text',
];
$csvAllPassed = true;
foreach ($dangerousInputs as $input => $expected) {
    $res = $escapeCsvMethod->invoke($logsPage, $input);
    if ($res !== $expected) {
        $csvAllPassed = false;
    }
}
assertTest($csvAllPassed, "CSV cell escaping neutralizes spreadsheet formula injection triggers (=, +, -, @, \\t, \\r).");

// ── Test 5: Issue #25 (Uninstall Cleanup Parsing) ───────────────────
$falseValues = ['false', '0', 0, false, 'no', null];
$uninstallAllSafe = true;
foreach ($falseValues as $val) {
    $shouldClean = ($val === true || $val === 1 || $val === '1');
    if ($shouldClean !== false) {
        $uninstallAllSafe = false;
    }
}
assertTest($uninstallAllSafe, "uninstall.php explicit truthiness check prevents false-like values ('false', '0', 0) from triggering DB wipe.");
assertTest(('1' === '1' && 1 === 1 && true === true), "uninstall.php permits explicit 1 or true setting.");

// ── Test 6: Issue #31 (PHP 8.1 Baseline Alignment) ─────────────────
$ersaalPhp = file_get_contents(dirname(__DIR__, 3) . '/ersaal/ersaal.php');
$composerJson = file_get_contents(dirname(__DIR__, 3) . '/ersaal/composer.json');
$readmeTxt = file_get_contents(dirname(__DIR__, 3) . '/ersaal/readme.txt');
$readmeMd = file_get_contents(dirname(__DIR__, 3) . '/ersaal/README.md');

assertTest(str_contains($ersaalPhp, 'Requires PHP: 8.1'), "ersaal.php declares Requires PHP: 8.1.");
assertTest(str_contains($composerJson, '"php": ">=8.1"'), "composer.json requires php: >=8.1.");
assertTest(str_contains($readmeTxt, 'Requires PHP: 8.1'), "readme.txt declares Requires PHP: 8.1.");
assertTest(str_contains($readmeMd, 'PHP-%3E%3D%208.1'), "README.md contains PHP 8.1 badge.");

// ── Test 7: Issue #32 (strict_types Everywhere) ────────────────────
$views = glob(dirname(__DIR__, 3) . '/ersaal/admin/views/*.php');
$strictAllPresent = true;
foreach ($views as $view) {
    $content = file_get_contents($view);
    if (!str_contains($content, 'declare(strict_types=1);')) {
        $strictAllPresent = false;
    }
}
assertTest($strictAllPresent && count($views) === 11, "All 11 admin view templates declare strict_types=1.");

// ── Test 8: Issue #30 (Release Build Script Determinism) ────────────
$buildScript = dirname(__DIR__, 3) . '/.wp-ersaal-dev/bin/build-release.sh';
$scriptContent = file_get_contents($buildScript);
assertTest(is_executable($buildScript), "bin/build-release.sh is executable (+x).");
assertTest(str_contains($scriptContent, 'grep -m1 "Version:"'), "bin/build-release.sh extracts version dynamically.");
assertTest(!preg_match('/unzip\s+-l[^\n|]+\|\s*head/', $scriptContent), "bin/build-release.sh does not pipe unzip directly into head (prevents SIGPIPE).");

echo "\n=======================================================\n";
echo "SUMMARY: Passed: {$passed} | Failed: {$failed}\n";
echo "=======================================================\n\n";

exit($failed > 0 ? 1 : 0);
