<?php
// Load WordPress from LocalWP path
require_once __DIR__ . '/../../../../../wp-load.php';

use Ersaal\Storage\LogRepository;
use Ersaal\API\Client;
use Ersaal\Core\Options;
use Ersaal\Services\MessageService;
use Ersaal\Jobs\MessageJob;

echo "=== ERSAAL PHASE 5 MESSAGING CORE TESTS ===\n\n";

$options = new Options();
update_option('ersaal_api_url', 'http://localhost');
update_option('ersaal_api_key', 'test_secret_token');

$client = new Client($options);
$repository = new LogRepository();
$service = new MessageService($repository);

$mock_responses = [];
$current_test = '';

add_filter('pre_http_request', function ($false, $args, $url) use (&$mock_responses, &$current_test) {
    if (isset($mock_responses[$current_test])) {
        return $mock_responses[$current_test];
    }
    return $false;
}, 10, 3);

function run_message_test($name, $payload, $mockResponse, $assertCallback) {
    global $current_test, $mock_responses, $service, $repository, $client;
    
    $current_test = $name;
    $mock_responses[$name] = $mockResponse;
    
    // Add formatting so column spacing is aligned like PASS
    echo str_pad($name, 31, " ", STR_PAD_RIGHT);
    
    // 1. Send (Enqueues job)
    $key = wp_generate_uuid4();
    $payload['idempotency_key'] = $key;
    
    $returnedKey = $service->send($payload);
    
    // 2. Simulate Background Job Execution
    $job = new MessageJob($repository, $client);
    
    try {
        $job->handle($key, $payload);
    } catch (\Throwable $e) {
        echo "FAIL (Exception: " . $e->getMessage() . ")\n";
        return;
    }

    // 3. Verify final DB state
    $finalLog = $repository->getLogByKey($key);
    if (!$finalLog) {
        echo "FAIL (Log not found)\n";
    } else {
        $assertCallback($finalLog);
    }
}

$mock_success = [
    'response' => ['code' => 202, 'message' => 'Accepted'],
    'body' => json_encode(['status' => 'pending', 'data' => ['id' => 'msg-123', 'parts' => 1, 'cost' => 0.05]])
];

// 1. Success Message
run_message_test('success_message', ['receiver' => '0911234567', 'message' => 'Hello', 'source' => 'api'], $mock_success, function($log) {
    echo $log->status === 'accepted' ? "PASS\n" : "FAIL (Expected accepted, got {$log->status})\n";
});

// 2. Validation Error -> failed
run_message_test('validation_failure', ['receiver' => '0911234567', 'message' => 'Hello', 'source' => 'api'], 
    ['response' => ['code' => 422, 'message' => 'Unprocessable Entity'], 'body' => '{"message": "Invalid"}'], 
function($log) {
    echo $log->status === 'failed' ? "PASS\n" : "FAIL (Expected failed, got {$log->status})\n";
});

// 3. Authentication Error -> failed
run_message_test('authentication_failure', ['receiver' => '0911234567', 'message' => 'Hello', 'source' => 'api'], 
    ['response' => ['code' => 401, 'message' => 'Unauthorized'], 'body' => '{"message": "Unauth"}'], 
function($log) {
    echo $log->status === 'failed' ? "PASS\n" : "FAIL (Expected failed, got {$log->status})\n";
});

// 4. Balance Error -> failed
run_message_test('balance_failure', ['receiver' => '0911234567', 'message' => 'Hello', 'source' => 'api'], 
    ['response' => ['code' => 422, 'message' => 'Unprocessable Entity'], 'body' => '{"message": "balance"}'], 
function($log) {
    echo $log->status === 'failed' ? "PASS\n" : "FAIL (Expected failed, got {$log->status})\n";
});

// 5. Manual Rate Limit -> error (No Retry)
run_message_test('manual_rate_limit_no_retry', ['receiver' => '0911234567', 'message' => 'Hello', 'source' => 'manual'], 
    ['response' => ['code' => 429, 'message' => 'Too Many Requests'], 'headers' => ['retry-after' => '120'], 'body' => '{"message": "Rate"}'], 
function($log) {
    echo $log->status === 'error' ? "PASS\n" : "FAIL (Expected error, got {$log->status})\n";
});

// 6. WooCommerce Rate Limit -> retry_scheduled
run_message_test('woocommerce_rate_limit_retry', ['receiver' => '0911234567', 'message' => 'Hello', 'source' => 'woocommerce'], 
    ['response' => ['code' => 429, 'message' => 'Too Many Requests'], 'headers' => ['retry-after' => '120'], 'body' => '{"message": "Rate"}'], 
function($log) {
    echo $log->status === 'retry_scheduled' ? "PASS\n" : "FAIL (Expected retry_scheduled, got {$log->status})\n";
});

// 7. Manual Server Error -> error
run_message_test('server_error_manual', ['receiver' => '0911234567', 'message' => 'Hello', 'source' => 'manual'], 
    ['response' => ['code' => 500, 'message' => 'Internal Server Error'], 'body' => ''], 
function($log) {
    echo $log->status === 'error' ? "PASS\n" : "FAIL (Expected error, got {$log->status})\n";
});

// 8. WooCommerce Server Error -> retry_scheduled
run_message_test('server_error_woocommerce', ['receiver' => '0911234567', 'message' => 'Hello', 'source' => 'woocommerce'], 
    ['response' => ['code' => 500, 'message' => 'Internal Server Error'], 'body' => ''], 
function($log) {
    echo $log->status === 'retry_scheduled' ? "PASS\n" : "FAIL (Expected retry_scheduled, got {$log->status})\n";
});
