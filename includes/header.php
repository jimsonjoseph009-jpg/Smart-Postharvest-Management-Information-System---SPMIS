<?php
/**
 * header.php — Global page header / navbar
 */
require_once __DIR__ . '/../includes/session_check.php';
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base     = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
    define('BASE_URL', $protocol . '://' . $host . $base);
}
$loggedIn  = !empty($_SESSION['user_id']);
$role      = $_SESSION['role'] ?? '';
$userName  = $_SESSION['full_name'] ?? ($_SESSION['username'] ?? 'Mtumiaji');
$pageTitle = $pageTitle ?? 'KILIMO-HIFADHI';
?>
<!DOCTYPE html>
<html lang="sw">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= escape($pageTitle) ?> | KILIMO-HIFADHI</title>
<meta name="description" content="Mfumo wa Usimamizi wa Upotevu wa Mazao Baada ya Kuvuna - Tanzania">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg kh-navbar">
  <div class="container">
    <a class="navbar-brand kh-brand" href="<?= BASE_URL ?>/index.php">
      <span class="brand-icon">🌾</span>
      <span class="brand-text">KILIMO<span class="brand-accent">-HIFADHI</span></span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav ms-auto align-items-center gap-1">
        <?php if ($loggedIn): ?>

          <?php if ($role === 'farmer'): ?>
            <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/farmer/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashibodi</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/farmer/add_harvest.php"><i class="fas fa-seedling"></i> Mavuno</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/farmer/request_storage.php"><i class="fas fa-warehouse"></i> Uhifadhi</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/farmer/request_transport.php"><i class="fas fa-truck"></i> Usafiri</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/farmer/request_processing.php"><i class="fas fa-industry"></i> Usindikaji</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/market/browse.php"><i class="fas fa-store"></i> Soko</a></li>

          <?php elseif ($role === 'storage_provider'): ?>
            <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/storage/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashibodi</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/storage/add_facility.php"><i class="fas fa-plus"></i> Ongeza Kituo</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/storage/view_requests.php"><i class="fas fa-list"></i> Maombi</a></li>

          <?php elseif ($role === 'transport_provider'): ?>
            <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/transport/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashibodi</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/transport/add_vehicle.php"><i class="fas fa-plus"></i> Ongeza Gari</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/transport/view_requests.php"><i class="fas fa-list"></i> Maombi</a></li>

          <?php elseif ($role === 'processor'): ?>
            <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/processing/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashibodi</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/processing/add_facility.php"><i class="fas fa-plus"></i> Ongeza Kituo</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/processing/view_requests.php"><i class="fas fa-list"></i> Maombi</a></li>

          <?php elseif ($role === 'buyer'): ?>
            <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/market/browse.php"><i class="fas fa-store"></i> Soko</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/market/my_orders.php"><i class="fas fa-shopping-cart"></i> Maagizo Yangu</a></li>

          <?php elseif ($role === 'admin'): ?>
            <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashibodi</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/admin/users.php"><i class="fas fa-users"></i> Watumiaji</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/admin/reports.php"><i class="fas fa-chart-bar"></i> Ripoti</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/admin/system_logs.php"><i class="fas fa-scroll"></i> Kumbukumbu</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/admin/encryption_audit.php"><i class="fas fa-shield-alt"></i> Ukaguzi</a></li>
          <?php endif; ?>

          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle kh-user-btn" href="#" data-bs-toggle="dropdown">
              <i class="fas fa-user-circle"></i> <?= escape($userName) ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="<?= BASE_URL ?>/farmer/profile.php"><i class="fas fa-id-card me-2"></i>Wasifu Wangu</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Toka</a></li>
            </ul>
          </li>

        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/auth/login.php">Ingia</a></li>
          <li class="nav-item"><a class="btn kh-btn-primary ms-2" href="<?= BASE_URL ?>/auth/register.php">Jisajili</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-4">
<?= renderFlash() ?>
