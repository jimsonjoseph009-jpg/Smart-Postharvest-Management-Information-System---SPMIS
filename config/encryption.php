<?php
/**
 * encryption.php — Encryption configurations
 */

require_once __DIR__ . '/config.php';

define('ENCRYPTION_CIPHER', 'AES-256-CBC');

// Retrieve key from env
$rawKey = $_ENV['ENCRYPTION_KEY'] ?? '';

if (empty($rawKey)) {
    // Fail-safe default key for development (should trigger warnings in production)
    $key = 'this-is-a-secure-32-byte-key-123456';
} else {
    // Decode base64 key
    $key = base64_decode($rawKey);
    
    // Check if key length is exactly 32 bytes for AES-256
    if (strlen($key) !== 32) {
        $key = hash('sha256', $rawKey, true); // derive a 32-byte key deterministically if length mismatch
    }
}

define('ENCRYPTION_KEY', $key);
define('KEY_REFERENCE', substr(md5($rawKey), 0, 8)); // Short MD5 hash to log key references for audits
