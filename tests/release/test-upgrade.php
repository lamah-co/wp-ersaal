<?php
declare(strict_types=1);

// Require through wp eval after defining ERSAAL_UPGRADE_STAGE as seed or verify.
// Use only on an isolated test installation.
defined('ABSPATH') || exit;

global $wpdb;
if (ERSAAL_UPGRADE_STAGE === 'seed') {
    if (ERSAAL_VERSION !== '1.3.1') {
        throw new RuntimeException('Expected v1.3.1 upgrade baseline.');
    }
    update_option('ersaal_sender', 'ReleaseTest');
    $reference = 'release-132-' . wp_generate_uuid4();
    $inserted = $wpdb->insert($wpdb->prefix . 'ersaal_otp_logs', [
        'action' => 'initiate',
        'status' => 'accepted',
        'phone_hash' => hash('sha256', 'release-test'),
        'phone_masked' => '00218*****678',
        'reference' => $reference,
        'context' => 'custom',
        'created_at' => current_time('mysql'),
        'updated_at' => current_time('mysql'),
    ]);
    if ($inserted !== 1) {
        throw new RuntimeException('Could not create upgrade fixture.');
    }
    update_option('ersaal_release_test_reference', $reference);
    echo "PASS: v1.3.1 settings and OTP log fixture created.\n";
} elseif (ERSAAL_UPGRADE_STAGE === 'verify') {
    $reference = get_option('ersaal_release_test_reference');
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}ersaal_otp_logs WHERE reference = %s",
        $reference
    ));
    if (ERSAAL_VERSION !== '1.3.2' || get_option('ersaal_sender') !== 'ReleaseTest' || !$reference || (int) $count !== 1) {
        throw new RuntimeException('Upgrade did not preserve settings and OTP log.');
    }
    echo "PASS: v1.3.1 to v1.3.2 upgrade preserves settings and OTP log.\n";
} else {
    throw new RuntimeException('Invalid upgrade test stage.');
}
