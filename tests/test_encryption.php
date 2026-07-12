<?php
/**
 * tests/test_encryption.php — Test AES-256-CBC encrypt/decrypt
 * Run: php tests/test_encryption.php
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/encryption.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/interfaces/Encryptable.php';

// Concrete stub to access Database protected methods
class EncryptionTester extends Database {
    public function __construct() { $this->connect(); }
    public function getConnection() { return $this->conn; }
    public function testLogAudit($t, $r, $f, $op, $c) {
        return $this->logAudit($t, $r, $f, $op, $c);
    }
}

$tester = new EncryptionTester();
$passed = 0; $failed = 0;

function runTest($name, $result, $expected = true) {
    global $passed, $failed;
    $ok = ($result === $expected);
    echo ($ok ? '✅ PASS' : '❌ FAIL') . " — {$name}\n";
    $ok ? $passed++ : $failed++;
}

echo "=== ENCRYPTION TEST SUITE ===\n\n";

// Test 1: Basic encrypt/decrypt roundtrip
$plain = "Shamba la Juma - Kilosa, Morogoro";
$cipher = $tester->encrypt($plain);
runTest('Encrypt returns non-empty string', !empty($cipher));
runTest('Ciphertext differs from plaintext', $cipher !== $plain);

$decrypted = $tester->decrypt($cipher);
runTest('Decrypt returns original value', $decrypted === $plain);

// Test 2: Different IVs each time (same input → different ciphertext)
$cipher2 = $tester->encrypt($plain);
runTest('Two encryptions of same value produce different ciphertext', $cipher !== $cipher2);
runTest('Both ciphertexts decrypt to same plaintext', $tester->decrypt($cipher2) === $plain);

// Test 3: Numeric values
$numPlain = '50000';
$numCipher = $tester->encrypt($numPlain);
runTest('Numeric encryption/decryption', $tester->decrypt($numCipher) === $numPlain);

// Test 4: Special characters
$special = 'Pamba & Korosho — "Biashara" <Tanzania>';
$specCipher = $tester->encrypt($special);
runTest('Special character roundtrip', $tester->decrypt($specCipher) === $special);

// Test 5: Empty string
$emptyCipher = $tester->encrypt('');
runTest('Empty string returns empty', $emptyCipher === '');

// Test 6: Corrupted ciphertext returns fallback
$corrupted = 'not-valid-base64!!!';
$fallback = $tester->decrypt($corrupted);
runTest('Corrupted ciphertext does not crash', $fallback !== null);

// Test 7: Audit log
try {
    $tester->testLogAudit('test_table', 1, 'test_field', 'ENCRYPT', $cipher);
    runTest('Audit log insert succeeds', true);
} catch (Exception $e) {
    runTest('Audit log insert succeeds', false);
}

// Test 8: Verify encryption key length
runTest('Encryption key is 32 bytes', strlen(ENCRYPTION_KEY) === 32);

echo "\n=== RESULTS: {$passed} passed, {$failed} failed ===\n";
if ($failed === 0) echo "🎉 All encryption tests passed!\n";
