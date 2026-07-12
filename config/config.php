<?php
/**
 * config.php — Global configuration, session startup, and CSRF protection
 */

// Set default timezone
date_default_timezone_set('Africa/Dar_es_Salaam');

// Load environment variables from .env file
function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $val = trim($parts[1]);
            
            // Remove surrounding quotes if present
            if (preg_match('/^"(.+)"$/', $val, $matches)) {
                $val = $matches[1];
            } elseif (preg_match("/^'(.+)'$/", $val, $matches)) {
                $val = $matches[1];
            }
            
            $_ENV[$key] = $val;
            putenv("{$key}={$val}");
        }
    }
}

// Load .env from the project root directory
loadEnv(dirname(__DIR__) . '/.env');

// Configure error reporting based on environment
$environment = $_ENV['ENVIRONMENT'] ?? 'development';
if ($environment === 'production') {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
} else {
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    error_reporting(E_ALL);
}

// Start secure session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 0,
        'cookie_path' => '/',
        'cookie_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax'
    ]);
}

// Generate CSRF token
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Security: Regenerate session ID periodically to prevent fixation
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 1800) { // 30 minutes
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}
