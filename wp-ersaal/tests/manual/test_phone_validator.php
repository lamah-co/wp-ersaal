<?php
declare(strict_types=1);

/**
 * Test script for Ersaal Phone Validator
 *
 * Run with: php -f wp-content/plugins/wp-ersaal/tests/manual/test_phone_validator.php
 */

// Mock translation function for isolated testing
if (!function_exists('__')) {
    function __(string $text, string $domain = 'default'): string {
        return $text;
    }
}
if (!function_exists('wp_salt')) {
    function wp_salt(string $scheme = 'auth'): string {
        return 'mocked_salt_12345';
    }
}

require_once __DIR__ . '/../../src/Services/PhoneValidator.php';

use Ersaal\Services\PhoneValidator;
if (php_sapi_name() !== 'cli') {
    die("CLI only.\n");
}

$validator = new PhoneValidator();
$successes = 0;
$failures = 0;

function assertNormalization(PhoneValidator $validator, string $input, string $expected) {
    global $successes, $failures;
    try {
        $result = $validator->normalize($input);
        if ($result === $expected) {
            echo "✅ PASS: '$input' -> '$expected'\n";
            $successes++;
        } else {
            echo "❌ FAIL: '$input' -> expected '$expected', got '$result'\n";
            $failures++;
        }
    } catch (\Exception $e) {
        echo "❌ FAIL: '$input' threw exception: " . $e->getMessage() . "\n";
        $failures++;
    }
}

function assertRejection(PhoneValidator $validator, string $input) {
    global $successes, $failures;
    try {
        $result = $validator->normalize($input);
        echo "❌ FAIL: '$input' should have been rejected, but returned '$result'\n";
        $failures++;
    } catch (\InvalidArgumentException $e) {
        echo "✅ PASS: '$input' correctly rejected.\n";
        $successes++;
    } catch (\Exception $e) {
        echo "❌ FAIL: '$input' threw wrong exception: " . get_class($e) . "\n";
        $failures++;
    }
}

function assertMask(PhoneValidator $validator, string $input, string $expected) {
    global $successes, $failures;
    $result = $validator->mask($input);
    if ($result === $expected) {
        echo "✅ PASS: '$input' masked as '$expected'\n";
        $successes++;
        return;
    }
    echo "❌ FAIL: '$input' -> expected mask '$expected', got '$result'\n";
    $failures++;
}

echo "=== Testing Valid Formats ===\n";
assertNormalization($validator, "0912345678", "00218912345678");
assertNormalization($validator, "0923553268", "00218923553268");
assertNormalization($validator, "0931234567", "00218931234567");
assertNormalization($validator, "0947654321", "00218947654321");

echo "\n=== Testing International Formats ===\n";
assertNormalization($validator, "+218923553268", "00218923553268");
assertNormalization($validator, "218923553268", "00218923553268");
assertNormalization($validator, "00218923553268", "00218923553268");
assertMask($validator, "00218923553268", "+21892 *** 3268");

echo "\n=== Testing Spacing and Hyphens ===\n";
assertNormalization($validator, "092 355 3268", "00218923553268");
assertNormalization($validator, "092-355-3268", "00218923553268");
assertNormalization($validator, "(092) 355 3268", "00218923553268");
assertNormalization($validator, "+218 92 355 3268", "00218923553268");

echo "\n=== Testing Invalid Formats ===\n";
assertRejection($validator, "0951234567"); // unsupported prefix
assertRejection($validator, "0901234567"); // unsupported prefix
assertRejection($validator, "092123456"); // too short
assertRejection($validator, "09212345678"); // too long
assertRejection($validator, "123456789"); // arbitrary
assertRejection($validator, "+218951234567"); // unsupported prefix international
assertRejection($validator, "abc0923553268"); // alphabetic prefix

echo "\n=== Results ===\n";
echo "Total Passed: $successes\n";
echo "Total Failed: $failures\n";

if ($failures > 0) {
    exit(1);
}
exit(0);
