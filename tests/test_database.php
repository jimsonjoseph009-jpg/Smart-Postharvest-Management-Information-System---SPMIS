<?php
/**
 * tests/test_database.php — Test PDO connection, prepared statements & SQL injection prevention
 * Run: php tests/test_database.php
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/encryption.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/interfaces/Encryptable.php';

class DBTester extends Database {
    public function __construct() { $this->connect(); }
    public function getConnection() { return $this->conn; }
    public function testLogAudit($t, $r, $f, $op, $c) {
        return $this->logAudit($t, $r, $f, $op, $c);
    }
    public function testLogSystemAction($u, $act) {
        return $this->logSystemAction($u, $act);
    }
}

$t = new DBTester();
$db = $t->getConnection();
$passed = 0; $failed = 0;

function runTest($name, $result, $expected = true) {
    global $passed, $failed;
    $ok = ($result === $expected);
    echo ($ok ? '✅ PASS' : '❌ FAIL') . " — {$name}\n";
    $ok ? $passed++ : $failed++;
}

echo "=== DATABASE TEST SUITE ===\n\n";

// Test 1: PDO connection active
runTest('PDO connection is active', $db instanceof PDO);

// Test 2: Expected tables exist
$expected_tables = ['users','farmers','crops','harvests','storage_facilities',
    'storage_requests','transport_vehicles','transport_requests',
    'processing_facilities','processing_requests','processed_products',
    'market_listings','orders','payments','reviews','notifications',
    'system_logs','encrypted_data_audit'];

$stmt = $db->query("SHOW TABLES");
$tables = array_column($stmt->fetchAll(PDO::FETCH_NUM), 0);
foreach ($expected_tables as $table) {
    runTest("Table '{$table}' exists", in_array($table, $tables));
}

// Test 3: Prepared statements (SQL injection prevention)
$malicious = "' OR '1'='1"; // Classic SQL injection attempt
$hash = hash('sha256', strtolower($malicious));
$stmt = $db->prepare("SELECT id FROM users WHERE username_hash = :h LIMIT 1");
$stmt->execute([':h' => $hash]);
$result = $stmt->fetch();
runTest('SQL injection via prepared statement returns no data', $result === false);

// Test 4: Prepared statement with valid data works
$stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE 1=1");
$stmt->execute();
runTest('Basic prepared query executes', $stmt->fetchColumn() >= 0);

// Test 5: 3NF — no column stores multi-valued non-key data without foreign key
// Just verify foreign key constraints are in information_schema
$stmt = $db->prepare("
    SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
    WHERE CONSTRAINT_SCHEMA = ? AND REFERENCED_TABLE_NAME IS NOT NULL
");
$stmt->execute([DB_NAME]);
$fkCount = (int)$stmt->fetchColumn();
runTest('Foreign key constraints defined (>= 5)', $fkCount >= 5);

// Test 6: Encryption audit table records insertions
$t->testLogAudit('test_table_db', null, 'test_col', 'ENCRYPT', 'dummy_cipher');
$stmt = $db->query("SELECT COUNT(*) FROM encrypted_data_audit WHERE table_name = 'test_table_db'");
runTest('Audit log writes to encrypted_data_audit', (int)$stmt->fetchColumn() > 0);

// Test 7: System log works
$t->testLogSystemAction(null, 'Test system log entry from test_database.php');
$stmt = $db->query("SELECT COUNT(*) FROM system_logs");
runTest('System logs table is writable', (int)$stmt->fetchColumn() > 0);

echo "\n=== RESULTS: {$passed} passed, {$failed} failed ===\n";
