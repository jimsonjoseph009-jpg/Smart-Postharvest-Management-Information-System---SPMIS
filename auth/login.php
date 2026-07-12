<?php
/**
 * login.php — User Login
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../classes/User.php';

redirectIfLoggedIn();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Tokeni ya usalama imekosekana. Jaribu tena.';
    } else {
        $usernameOrEmail = trim($_POST['username'] ?? '');
        $password        = $_POST['password'] ?? '';

        if (empty($usernameOrEmail) || empty($password)) {
            $errors[] = 'Tafadhali jaza jina la mtumiaji/barua pepe na neno la siri.';
        } else {
            $user = new User();
            if ($user->login($usernameOrEmail, $password)) {
                // Regenerate session ID for security
                session_regenerate_id(true);
                setFlash('success', 'Karibu, ' . htmlspecialchars($_SESSION['full_name']) . '!');
                redirect(dashboardUrl($_SESSION['role']));
            } else {
                $errors[] = 'Jina la mtumiaji au neno la siri si sahihi.';
            }
        }
    }
}

$pageTitle = 'Ingia';
?>
<!DOCTYPE html>
<html lang="sw">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ingia | KILIMO-HIFADHI</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="kh-auth-bg">

<div class="auth-wrapper">
  <div class="auth-card">
    <div class="auth-logo">
      <span class="brand-icon-lg">🌾</span>
      <h2 class="auth-title">KILIMO-HIFADHI</h2>
      <p class="auth-subtitle">Mfumo wa Usimamizi wa Mazao</p>
    </div>

    <?php if ($errors): ?>
      <div class="alert alert-danger">
        <?php foreach ($errors as $e): ?><div>&#9888; <?= escape($e) ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <?= csrfField() ?>

      <div class="mb-3">
        <label class="form-label fw-semibold">Jina la Mtumiaji / Barua Pepe</label>
        <div class="input-group">
          <span class="input-group-text"><i class="fas fa-user"></i></span>
          <input type="text" name="username" class="form-control kh-input"
                 value="<?= escape($_POST['username'] ?? '') ?>"
                 placeholder="Jina la mtumiaji au barua pepe" required>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">Neno la Siri</label>
        <div class="input-group">
          <span class="input-group-text"><i class="fas fa-lock"></i></span>
          <input type="password" name="password" id="loginPassword" class="form-control kh-input"
                 placeholder="Neno la siri" required>
          <button class="btn btn-outline-secondary" type="button" onclick="togglePass('loginPassword')">
            <i class="fas fa-eye"></i>
          </button>
        </div>
      </div>

      <div class="d-grid mt-4">
        <button type="submit" class="btn kh-btn-primary btn-lg">
          <i class="fas fa-sign-in-alt me-2"></i>Ingia
        </button>
      </div>
    </form>

    <p class="text-center mt-3 text-muted small">
      Huna akaunti? <a href="register.php" class="kh-link fw-semibold">Jisajili hapa</a>
    </p>
    <p class="text-center mt-1">
      <a href="../index.php" class="kh-link small"><i class="fas fa-home me-1"></i>Kurudi Nyumbani</a>
    </p>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
