<?php
/**
 * functions.php — Security helpers, XSS protection, validation, notifications
 */

/**
 * Escape output to prevent XSS
 */
function escape($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect helper
 */
function redirect($path) {
    header("Location: " . $path);
    exit;
}

/**
 * Validate required fields
 */
function validateRequired(array $fields, array $post) {
    $errors = [];
    foreach ($fields as $field => $label) {
        if (empty(trim($post[$field] ?? ''))) {
            $errors[] = "{$label} inahitajika.";
        }
    }
    return $errors;
}

/**
 * Validate email format
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (Tanzania format)
 */
function validatePhone($phone) {
    return preg_match('/^(\+?255|0)[6-9][0-9]{8}$/', preg_replace('/\s+/', '', $phone));
}

/**
 * Validate password strength
 */
function validatePassword($password) {
    return strlen($password) >= 8;
}

/**
 * Flash message helper — set
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Flash message helper — get and clear
 */
function getFlash() {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Render flash alert HTML
 */
function renderFlash() {
    $flash = getFlash();
    if (!$flash) return '';
    $type = $flash['type'] === 'success' ? 'success' : ($flash['type'] === 'warning' ? 'warning' : 'danger');
    $icon = $flash['type'] === 'success' ? '&#10003;' : '&#9888;';
    return "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
                {$icon} " . escape($flash['message']) . "
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>";
}

/**
 * CSRF field HTML helper
 */
function csrfField() {
    $token = generateCSRFToken();
    return "<input type='hidden' name='csrf_token' value='" . escape($token) . "'>";
}

/**
 * Format Tanzanian Shilling
 */
function formatTshs($amount) {
    return 'Tshs ' . number_format((float)$amount, 2);
}

/**
 * Format date to Swahili-friendly format
 */
function formatDate($date) {
    if (empty($date)) return '—';
    $ts = strtotime($date);
    return $ts ? date('d/m/Y', $ts) : $date;
}

/**
 * Role label in Swahili
 */
function roleLabel($role) {
    $map = [
        'farmer'             => 'Mkulima',
        'storage_provider'   => 'Mtoa Huduma ya Uhifadhi',
        'transport_provider' => 'Msafirishaji',
        'processor'          => 'Msindikaji',
        'buyer'              => 'Mnunuzi',
        'admin'              => 'Msimamizi',
    ];
    return $map[$role] ?? ucfirst($role);
}

/**
 * Status plain-text label in Swahili
 */
function statusBadgeLabel($status) {
    $swahili = [
        'pending'   => 'Inasubiri',
        'approved'  => 'Imeidhinishwa',
        'active'    => 'Inafanya Kazi',
        'completed' => 'Imekamilika',
        'cancelled' => 'Imefutwa',
        'delivered' => 'Imetumwa',
        'confirmed' => 'Imethibitishwa',
        'paid'      => 'Imelipwa',
        'in_transit'=> 'Safarini',
    ];
    return $swahili[$status] ?? escape($status);
}

/**
 * Status badge HTML
 */
function statusBadge($status) {
    $classes = [
        'pending'   => 'warning',
        'approved'  => 'info',
        'active'    => 'success',
        'completed' => 'primary',
        'cancelled' => 'danger',
        'delivered' => 'success',
        'confirmed' => 'info',
        'paid'      => 'success',
    ];
    $cls = $classes[$status] ?? 'secondary';
    $lbl = statusBadgeLabel($status);
    return "<span class='badge bg-{$cls}'>{$lbl}</span>";
}
