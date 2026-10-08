<?php
declare(strict_types=1);

namespace Ersaal\API;

use Ersaal\API\Exceptions\ApiException;
use Ersaal\API\Exceptions\AuthenticationException;
use Ersaal\API\Exceptions\BalanceException;
use Ersaal\API\Exceptions\ConnectionException;
use Ersaal\API\Exceptions\RateLimitException;
use Ersaal\API\Exceptions\ServerException;
use Ersaal\API\Exceptions\ValidationException;
use Ersaal\Core\Options;
use Ersaal\Support\Str;

class Client
{
    private Options $options;

    public function __construct(Options $options)
    {
        $this->options = $options;
    }

    public function getProjectDetails(): Response
    {
        return $this->request('GET', '/api/project/details');
    }

    public function getBalance(): Response
    {
        return $this->request('GET', '/api/project/balance');
    }

    public function sendMessage(array $payload, string $idempotencyKey): Response
    {
        return $this->request('POST', '/api/sms/messages', $payload, ['Idempotency-Key' => $idempotencyKey]);
    }

    public function getMessage(string $messageId): Response
    {
        return $this->request('GET', "/api/sms/messages/{$messageId}");
    }

    public function initiateOtp(array $payload, string $idempotencyKey): Response
    {
        return $this->request('POST', '/api/otp/initiate', $payload, ['Idempotency-Key' => $idempotencyKey]);
    }

    public function verifyOtp(string $requestId, string $code): Response
    {
        return $this->request('POST', '/api/otp/verify', [
            'request_id' => $requestId,
            'code' => $code,
        ]);
    }

    private function request(string $method, string $endpoint, array $body = [], array $headers = []): Response
    {
        $baseUrl = rtrim((string) $this->options->get('api_url', 'https://api.ersaal.com'), '/');
        if (empty($baseUrl) || !filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            throw new ConnectionException(esc_html__('Invalid Base URL configured.', 'ersaal'));
        }

        $scheme = wp_parse_url($baseUrl, PHP_URL_SCHEME);
        if (strtolower((string) $scheme) !== 'https') {
            throw new ConnectionException(esc_html__('Ersaal API requires a secure HTTPS connection.', 'ersaal'));
        }

        $user = wp_parse_url($baseUrl, PHP_URL_USER);
        $pass = wp_parse_url($baseUrl, PHP_URL_PASS);
        if (!empty($user) || !empty($pass)) {
            throw new ConnectionException(esc_html__('API Base URL must not contain embedded user credentials.', 'ersaal'));
        }

        $apiKey = trim((string) $this->options->get('api_key', ''));
        if (empty($apiKey)) {
            throw new AuthenticationException(esc_html__('API Key is missing.', 'ersaal'));
        }

        // Clean token if user prefixed with Bearer
        if (str_starts_with(strtolower($apiKey), 'bearer ')) {
            $apiKey = trim(substr($apiKey, 7));
        }

        $url = $baseUrl . $endpoint;
        
        $defaultHeaders = [
            'Authorization' => 'Bearer ' . $apiKey,
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ];

        $args = [
            'method'      => $method,
            'headers'     => array_merge($defaultHeaders, $headers),
            'timeout'     => 10,
            'redirection' => 0,
        ];

        if (!empty($body) && $method !== 'GET') {
            $args['body'] = wp_json_encode($body);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            $errMsg = $response->get_error_message();
            if (str_contains($errMsg, 'timed out') || str_contains($errMsg, 'Could not resolve host') || str_contains($errMsg, 'Connection refused')) {
                $errMsg = esc_html__('Could not connect to Ersaal. Check the API URL and service status.', 'ersaal');
            }
            throw new ConnectionException(esc_html($this->sanitizeErrorMessage((string) $errMsg)));
        }

        $statusCode = (int) wp_remote_retrieve_response_code($response);
        $responseBody = (string) wp_remote_retrieve_body($response);
        
        $decoded = [];
        if (!empty($responseBody)) {
            $decoded = json_decode($responseBody, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $decoded = [];
            }
        }

        $this->handleErrors($statusCode, is_array($decoded) ? $decoded : [], $responseBody, $response);

        return new Response($statusCode, is_array($decoded) ? $decoded : []);
    }

    private function handleErrors(int $statusCode, array $body, string $rawBody, $response): void
    {
        if ($statusCode >= 200 && $statusCode < 300) {
            return;
        }

        if ($statusCode >= 300 && $statusCode < 400) {
            throw new ConnectionException(esc_html__('Redirects are disabled for security reasons.', 'ersaal'), absint($statusCode));
        }

        $rawMsg = $body['message'] ?? '';
        $message = is_string($rawMsg) ? $rawMsg : (is_scalar($rawMsg) ? (string) $rawMsg : '');

        if (empty($message) && isset($body['errors']) && is_array($body['errors'])) {
            $firstError = reset($body['errors']);
            if (is_array($firstError)) {
                $firstError = reset($firstError);
            }
            if (is_string($firstError)) {
                $message = $firstError;
            }
        }

        // Handle raw HTML error pages or sensitive stack traces / logs
        if (empty($message) || str_contains($rawBody, '<html') || str_contains($rawBody, 'Permission denied') || str_contains($rawBody, 'Stack trace')) {
            if ($statusCode >= 500) {
                $message = esc_html__('Ersaal server returned an internal error. Check the Ersaal service logs.', 'ersaal');
            } else {
                $message = esc_html__('Unexpected API response received.', 'ersaal');
            }
        }

        $message = $this->sanitizeErrorMessage($message);

        if ($statusCode === 401) {
            if (empty($message) || $message === 'Unauthenticated.') {
                $message = esc_html__('Authentication failed. Check the API Key.', 'ersaal');
            }
            throw new AuthenticationException(esc_html($message), absint($statusCode));
        }

        if ($statusCode === 403) {
            if (stripos($rawBody, 'ip') !== false || stripos($rawBody, 'whitelist') !== false || stripos($rawBody, 'not allowed') !== false) {
                $message = esc_html__('This server IP is not allowed by the Ersaal project.', 'ersaal');
            } elseif (stripos($rawBody, 'inactive') !== false) {
                $message = esc_html__('The Ersaal project is inactive.', 'ersaal');
            } else {
                $message = esc_html__('Authentication failed or access forbidden.', 'ersaal');
            }
            throw new AuthenticationException(esc_html($message), absint($statusCode));
        }

        if ($statusCode === 429) {
            $retryAfter = $this->parseRetryAfter(wp_remote_retrieve_header($response, 'retry-after'));
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new RateLimitException(esc_html($message), absint($statusCode), null, $retryAfter);
        }

        if ($statusCode === 400 || $statusCode === 422) {
            if (stripos($message, 'balance') !== false) {
                throw new BalanceException(esc_html($message), absint($statusCode));
            }
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new ValidationException(esc_html($message), absint($statusCode), null, is_array($body['errors'] ?? null) ? $body['errors'] : []);
        }

        if ($statusCode >= 500) {
            throw new ServerException(esc_html($message), absint($statusCode));
        }

        throw new ApiException(esc_html($message), absint($statusCode));
    }

    private function sanitizeErrorMessage(string $message): string
    {
        $clean = sanitize_text_field($message);
        return Str::limit($clean, 255);
    }

    private function parseRetryAfter($header): int
    {
        $headerStr = is_string($header) ? trim($header) : '';
        if (empty($headerStr)) {
            return 60; // default 1 min
        }
        
        if (is_numeric($headerStr)) {
            $seconds = (int) $headerStr;
        } else {
            $time = strtotime($headerStr);
            $seconds = $time ? ($time - time()) : 60;
        }
        
        return max(5, min(21600, $seconds)); // Min 5s, Max 6h
    }
}
