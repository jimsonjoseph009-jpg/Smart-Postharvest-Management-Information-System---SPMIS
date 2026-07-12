<?php
/**
 * register.php — User Registration
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Farmer.php';

redirectIfLoggedIn();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Tokeni ya usalama imekosekana.';
    } else {
        // Collect & sanitize
        $username   = trim($_POST['username'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $full_name  = trim($_POST['full_name'] ?? '');
        $phone      = trim($_POST['phone'] ?? '');
        $location   = trim($_POST['location'] ?? '');
        $role       = trim($_POST['role'] ?? '');
        $password   = $_POST['password'] ?? '';
        $password2  = $_POST['password_confirm'] ?? '';

        // Farmer extras
        $farm_name           = trim($_POST['farm_name'] ?? '');
        $farm_location       = trim($_POST['farm_location'] ?? '');
        $farm_size           = trim($_POST['farm_size'] ?? '');
        $crops_grown         = trim($_POST['crops_grown'] ?? '');
        $farming_experience  = trim($_POST['farming_experience'] ?? '');

        // Validation
        if (empty($username))         $errors[] = 'Jina la mtumiaji linahitajika.';
        if (empty($email))            $errors[] = 'Barua pepe inahitajika.';
        if (!validateEmail($email))   $errors[] = 'Barua pepe si sahihi.';
        if (empty($full_name))        $errors[] = 'Jina kamili linahitajika.';
        if (!validatePhone($phone))   $errors[] = 'Nambari ya simu si sahihi (mfano: 0712345678).';
        if (empty($location))         $errors[] = 'Mahali linahitajika.';
        if (!in_array($role, ['farmer','storage_provider','transport_provider','processor','buyer'], true))
            $errors[] = 'Tafadhali chagua aina ya mtumiaji.';
        if (!validatePassword($password)) $errors[] = 'Neno la siri lazima liwe na angalau herufi 8.';
        if ($password !== $password2) $errors[] = 'Maneno ya siri hayafanani.';
        if ($role === 'farmer' && empty($farm_name)) $errors[] = 'Jina la shamba linahitajika.';

        if (!$errors) {
            try {
                $userData = compact('username','email','full_name','phone','location','password','role');

                if ($role === 'farmer') {
                    $farmer = new Farmer();
                    $farmerData = compact('farm_name','farm_location','farm_size','crops_grown','farming_experience');
                    $farmer->registerFarmer($userData, $farmerData);
                } else {
                    $user = new User();
                    $user->register($userData);
                }

                setFlash('success', 'Umejisajili kwa mafanikio! Tafadhali ingia.');
                redirect(BASE_URL . '/auth/login.php');
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="sw">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Jisajili | KILIMO-HIFADHI</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="kh-auth-bg">

<div class="auth-wrapper" style="max-width:600px">
  <div class="auth-card">
    <div class="auth-logo">
      <span class="brand-icon-lg">🌾</span>
      <h2 class="auth-title">Jisajili</h2>
      <p class="auth-subtitle">Tengeneza akaunti yako mpya</p>
    </div>

    <?php if ($errors): ?>
      <div class="alert alert-danger">
        <?php foreach ($errors as $e): ?><div>&#9888; <?= escape($e) ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <?= csrfField() ?>

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Jina la Mtumiaji *</label>
          <input type="text" name="username" class="form-control kh-input"
                 value="<?= escape($_POST['username'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Barua Pepe *</label>
          <input type="email" name="email" class="form-control kh-input"
                 value="<?= escape($_POST['email'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Jina Kamili *</label>
          <input type="text" name="full_name" class="form-control kh-input"
                 value="<?= escape($_POST['full_name'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Simu *</label>
          <input type="text" name="phone" class="form-control kh-input"
                 value="<?= escape($_POST['phone'] ?? '') ?>" placeholder="0712345678" required>
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold">Mahali Unapoishi *</label>
          <input type="text" name="location" class="form-control kh-input"
                 value="<?= escape($_POST['location'] ?? '') ?>" required>
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold">Aina ya Mtumiaji *</label>
          <select name="role" id="roleSelect" class="form-select kh-input" required onchange="toggleFarmerFields()">
            <option value="">-- Chagua Aina --</option>
            <option value="farmer"             <?= ($_POST['role']??'')==='farmer'?'selected':'' ?>>Mkulima</option>
            <option value="storage_provider"   <?= ($_POST['role']??'')==='storage_provider'?'selected':'' ?>>Mtoa Huduma ya Uhifadhi</option>
            <option value="transport_provider" <?= ($_POST['role']??'')==='transport_provider'?'selected':'' ?>>Msafirishaji</option>
            <option value="processor"          <?= ($_POST['role']??'')==='processor'?'selected':'' ?>>Msindikaji</option>
            <option value="buyer"              <?= ($_POST['role']??'')==='buyer'?'selected':'' ?>>Mnunuzi</option>
          </select>
        </div>

        <!-- Farmer-only fields -->
        <div id="farmerFields" class="col-12" style="display:none">
          <div class="kh-card p-3">
            <h6 class="fw-bold mb-3"><i class="fas fa-seedling me-2 text-success"></i>Maelezo ya Shamba</h6>
            <div class="row g-2">
              <div class="col-md-6">
                <label class="form-label">Jina la Shamba *</label>
                <input type="text" name="farm_name" class="form-control kh-input"
                       value="<?= escape($_POST['farm_name'] ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Eneo la Shamba</label>
                <input type="text" name="farm_location" class="form-control kh-input"
                       value="<?= escape($_POST['farm_location'] ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Ukubwa wa Shamba (Ekari)</label>
                <input type="text" name="farm_size" class="form-control kh-input"
                       value="<?= escape($_POST['farm_size'] ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Mazao Yanayolimwa</label>
                <input type="text" name="crops_grown" class="form-control kh-input"
                       placeholder="mfano: Mahindi, Mpunga" value="<?= escape($_POST['crops_grown'] ?? '') ?>">
              </div>
              <div class="col-12">
                <label class="form-label">Uzoefu wa Kilimo (Miaka)</label>
                <input type="number" name="farming_experience" class="form-control kh-input"
                       value="<?= escape($_POST['farming_experience'] ?? '') ?>">
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Neno la Siri *</label>
          <div class="input-group">
            <input type="password" name="password" id="reg_pass" class="form-control kh-input"
                   placeholder="Min. herufi 8" required>
            <button class="btn btn-outline-secondary" type="button" onclick="togglePass('reg_pass')">
              <i class="fas fa-eye"></i>
            </button>
          </div>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Thibitisha Neno la Siri *</label>
          <input type="password" name="password_confirm" class="form-control kh-input" required>
        </div>
      </div>

      <div class="d-grid mt-4">
        <button type="submit" class="btn kh-btn-primary btn-lg">
          <i class="fas fa-user-plus me-2"></i>Jisajili
        </button>
      </div>
    </form>

    <p class="text-center mt-3 text-muted small">
      Una akaunti? <a href="login.php" class="kh-link fw-semibold">Ingia hapa</a>
    </p>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/app.js"></script>
<script>
function toggleFarmerFields() {
    const role = document.getElementById('roleSelect').value;
    document.getElementById('farmerFields').style.display = role === 'farmer' ? 'block' : 'none';
}
// On load if POST failed
window.addEventListener('DOMContentLoaded', toggleFarmerFields);
</script>
</body>
</html>
