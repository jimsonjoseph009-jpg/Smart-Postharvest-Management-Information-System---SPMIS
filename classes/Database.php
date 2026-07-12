<?php
/**
 * Database.php — Abstract Database class implementing Encryptable
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/encryption.php';
require_once __DIR__ . '/interfaces/Encryptable.php';

abstract class Database implements Encryptable {
    protected ?PDO $conn = null;

    abstract public function __construct();
    abstract public function getConnection();

    /**
     * Connects to the database and initializes the connection
     */
    protected function connect() {
        if ($this->conn === null) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                $this->conn = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                die("Kosa la Muunganisho wa Database (Database Connection Error): " . $e->getMessage());
            }
        }
        return $this->conn;
    }

    /**
     * Encrypts plaintext data using AES-256-CBC
     */
    public function encrypt($plaintext) {
        if ($plaintext === null || $plaintext === '') {
            return '';
        }
        
        $cipher = ENCRYPTION_CIPHER;
        $key = ENCRYPTION_KEY;
        $iv_length = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($iv_length);
        
        $encrypted = openssl_encrypt((string)$plaintext, $cipher, $key, 0, $iv);
        $combined = base64_encode($iv . $encrypted);
        
        return $combined;
    }

    /**
     * Decrypts ciphertext data using AES-256-CBC
     */
    public function decrypt($ciphertext) {
        if ($ciphertext === null || $ciphertext === '') {
            return '';
        }
        
        $cipher = ENCRYPTION_CIPHER;
        $key = ENCRYPTION_KEY;
        $combined = base64_decode($ciphertext);
        
        if ($combined === false) {
            return $ciphertext; // Return original if not valid base64
        }
        
        $iv_length = openssl_cipher_iv_length($cipher);
        
        if (strlen($combined) < $iv_length) {
            return $ciphertext; // Invalid combined length
        }
        
        $iv = substr($combined, 0, $iv_length);
        $encrypted = substr($combined, $iv_length);
        
        $decrypted = openssl_decrypt($encrypted, $cipher, $key, 0, $iv);
        return $decrypted === false ? $ciphertext : $decrypted;
    }

    /**
     * Logs encryption or decryption operations to the audit table.
     * Prevents recursion by using a direct, non-audited insertion.
     */
    public function logAudit($tableName, $recordId, $fieldName, $operation, $ciphertext) {
        $db = $this->getConnection();
        if ($db === null) {
            return;
        }
        
        $hash = hash('sha256', (string)$ciphertext);
        $keyRef = KEY_REFERENCE;
        
        try {
            $stmt = $db->prepare("
                INSERT INTO `encrypted_data_audit` 
                (`table_name`, `record_id`, `field_name`, `operation`, `encrypted_value_hash`, `key_reference`)
                VALUES (:table_name, :record_id, :field_name, :operation, :hash, :key_ref)
            ");
            $stmt->execute([
                ':table_name' => $tableName,
                ':record_id' => $recordId ? (int)$recordId : null,
                ':field_name' => $fieldName,
                ':operation' => strtoupper($operation),
                ':hash' => $hash,
                ':key_ref' => $keyRef
            ]);
        } catch (Exception $e) {
            // Silently fail or write to PHP error log to prevent crashing application
            error_log("Failed to log encryption audit: " . $e->getMessage());
        }
    }

    /**
     * Logs system events to system_logs table
     */
    public function logSystemAction($userId, $action) {
        $db = $this->getConnection();
        if ($db === null) {
            return;
        }
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        
        // Encrypt sensitive logs actions/ips
        $encAction = $this->encrypt($action);
        $encIp = $this->encrypt($ip);
        
        try {
            $stmt = $db->prepare("
                INSERT INTO `system_logs` (`user_id`, `action`, `ip_address`)
                VALUES (:user_id, :action, :ip)
            ");
            $stmt->execute([
                ':user_id' => $userId ? (int)$userId : null,
                ':action' => $encAction,
                ':ip' => $encIp
            ]);
        } catch (Exception $e) {
            error_log("Failed to write system log: " . $e->getMessage());
        }
    }
}
