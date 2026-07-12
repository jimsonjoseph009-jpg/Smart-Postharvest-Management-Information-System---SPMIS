<?php
/**
 * session_check.php — RBAC session guard middleware
 *
 * Usage: require_role(['farmer', 'admin']);
 *        require_login();
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

/**
 * Ensure user is logged in
 */
function requireLogin() {
    if (empty($_SESSION['user_id'])) {
        setFlash('danger', 'Tafadhali ingia kwanza (Please log in first).');
        redirect(BASE_URL . '/auth/login.php');
    }
}

/**
 * Ensure user has one of the allowed roles
 */
function requireRole(array $allowedRoles) {
    requireLogin();
    $role = $_SESSION['role'] ?? '';
    if (!in_array($role, $allowedRoles, true)) {
        setFlash('danger', 'Huna ruhusa ya kufikia ukurasa huu (Access Denied).');
        redirect(BASE_URL . '/index.php');
    }
}

/**
 * Redirect logged-in users away from guest pages (login, register)
 */
function redirectIfLoggedIn() {
    if (!empty($_SESSION['user_id'])) {
        $role = $_SESSION['role'] ?? '';
        redirect(dashboardUrl($role));
    }
}

/**
 * Return dashboard URL for a role
 */
function dashboardUrl($role) {
    $map = [
        'farmer'             => BASE_URL . '/farmer/dashboard.php',
        'storage_provider'   => BASE_URL . '/storage/dashboard.php',
        'transport_provider' => BASE_URL . '/transport/dashboard.php',
        'processor'          => BASE_URL . '/processing/dashboard.php',
        'buyer'              => BASE_URL . '/market/browse.php',
        'admin'              => BASE_URL . '/admin/dashboard.php',
    ];
    return $map[$role] ?? BASE_URL . '/index.php';
}

// Define BASE_URL constant once
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base     = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
    define('BASE_URL', $protocol . '://' . $host . $base);
}
