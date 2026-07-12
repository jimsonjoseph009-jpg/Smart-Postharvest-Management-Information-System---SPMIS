<?php
/**
 * farmer/profile.php — User Profile Page (view + edit)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Farmer.php';

requireLogin();

$errors   = [];
$success  = false;
$isFarmer = ($_SESSION['role'] === 'farmer');

// Load data
$userObj  = new User();
$userData = $userObj->findById($_SESSION['user_id']);

$farmerObj  = null;
$farmerData = null;
if ($isFarmer) {
    $farmerObj  = new Farmer();
    $farmerData = $farmerObj->findByUserId($_SESSION['user_id']);
}

// --- Handle POST (save) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Tokeni ya usalama imekosekana. Jaribu tena.';
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $phone    = trim($_POST['phone']     ?? '');
        $location = trim($_POST['location']  ?? '');

        if (empty($fullName)) $errors[] = 'Jina Kamili haliwezi kuwa tupu.';
        if (empty($phone))    $errors[] = 'Nambari ya Simu haiwezi kuwa tupu.';
        if (empty($location)) $errors[] = 'Mahali/Mkoa hauwezi kuwa tupu.';

        if (!$errors) {
            try {
                if ($isFarmer && $farmerData) {
                    $farmName   = trim($_POST['farm_name']          ?? '');
                    $farmLoc    = trim($_POST['farm_location']      ?? '');
                    $farmSize   = trim($_POST['farm_size']          ?? '');
                    $cropsGrown = trim($_POST['crops_grown']        ?? '');
                    $farmExp    = trim($_POST['farming_experience'] ?? '');

                    if (empty($farmName)) $errors[] = 'Jina la Shamba haliwezi kuwa tupu.';

                    if (!$errors) {
                        $farmerData->setId($_SESSION['user_id']);
                        $farmerData->setFarmId($farmerData->getFarmId());
                        $farmerData->updateFarmerProfile(
                            ['full_name' => $fullName, 'phone' => $phone, 'location' => $location],
                            ['farm_name' => $farmName, 'farm_location' => $farmLoc,
                             'farm_size' => $farmSize, 'crops_grown' => $cropsGrown,
                             'farming_experience' => $farmExp]
                        );
                        $success = true;
                    }
                } else {
                    $userObj->setId($_SESSION['user_id']);
                    $userObj->updateProfile([
                        'full_name' => $fullName,
                        'phone'     => $phone,
                        'location'  => $location,
                    ]);
                    $success = true;
                }

                if ($success) {
                    // Refresh loaded data after save
                    $userData   = $userObj->findById($_SESSION['user_id']);
                    $_SESSION['full_name'] = $userData->getFullName();
                    if ($isFarmer) {
                        $farmerData = $farmerObj->findByUserId($_SESSION['user_id']);
                    }
                    setFlash('success', 'Taarifa zako zimesasishwa kwa mafanikio! ✅');
                    redirect('profile.php');
                }
            } catch (Exception $e) {
                $errors[] = 'Hitilafu imetokea wakati wa kusasisha: ' . $e->getMessage();
            }
        }
    }
}

// Determine edit mode: either from GET param or after a validation error on POST
$editMode = (isset($_GET['edit']) && $_GET['edit'] === '1') || (!empty($errors));

$pageTitle = 'Wasifu Wangu';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="kh-hero mb-4">
  <h1>👤 Wasifu Wako</h1>
  <p><?= $editMode ? 'Hariri na usasishe taarifa zako za kibinafsi' : 'Tazama na uhakiki taarifa zako zilizosajiliwa kwenye mfumo' ?></p>
</div>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <?php foreach ($errors as $e): ?><div>&#9888; <?= escape($e) ?></div><?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="row g-4">

  <!-- === PROFILE CARD (View / Edit) === -->
  <div class="col-lg-6">
    <div class="kh-card h-100">
      <div class="kh-card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-user me-2"></i>Taarifa Binafsi</span>
        <?php if (!$editMode): ?>
          <a href="profile.php?edit=1" class="btn btn-sm btn-outline-light">
            <i class="fas fa-pen me-1"></i>Hariri
          </a>
        <?php endif; ?>
      </div>
      <div class="card-body p-4">

        <!-- Avatar -->
        <div class="d-flex align-items-center mb-4">
          <div style="width:70px;height:70px;background:linear-gradient(135deg,#1e7e34,#00b894);
            border-radius:50%;display:flex;align-items:center;justify-content:center;
            color:#fff;font-size:1.8rem;font-weight:bold;">
            <?= strtoupper(substr($userData->getFullName(), 0, 1)) ?>
          </div>
          <div class="ms-3">
            <h4 class="fw-bold mb-0"><?= escape($userData->getFullName()) ?></h4>
            <span class="badge bg-success"><?= escape(roleLabel($_SESSION['role'])) ?></span>
          </div>
        </div>

        <?php if ($editMode): ?>
        <!-- ===== EDIT FORM ===== -->
        <form method="POST" id="profileForm">
          <?= csrfField() ?>

          <div class="mb-3">
            <label class="form-label fw-semibold"><i class="fas fa-id-card me-1 text-success"></i>Jina Kamili *</label>
            <input type="text" name="full_name" class="form-control kh-input"
                   value="<?= escape($_POST['full_name'] ?? $userData->getFullName()) ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold"><i class="fas fa-envelope me-1 text-success"></i>Barua Pepe</label>
            <input type="email" class="form-control kh-input bg-light" value="<?= escape($userData->getEmail()) ?>" disabled>
            <small class="text-muted">Barua pepe haiwezi kubadilishwa.</small>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold"><i class="fas fa-phone me-1 text-success"></i>Nambari ya Simu *</label>
            <input type="text" name="phone" class="form-control kh-input"
                   value="<?= escape($_POST['phone'] ?? $userData->getPhone()) ?>" required>
          </div>

          <div class="mb-4">
            <label class="form-label fw-semibold"><i class="fas fa-map-marker-alt me-1 text-success"></i>Mahali/Mkoa *</label>
            <input type="text" name="location" class="form-control kh-input"
                   value="<?= escape($_POST['location'] ?? $userData->getLocation()) ?>" required>
          </div>

          <!-- Farmer-specific fields -->
          <?php if ($isFarmer && $farmerData): ?>
          <hr class="my-3">
          <p class="fw-semibold text-success mb-2"><i class="fas fa-seedling me-1"></i>Taarifa za Shamba</p>

          <div class="mb-3">
            <label class="form-label fw-semibold">Jina la Shamba *</label>
            <input type="text" name="farm_name" class="form-control kh-input"
                   value="<?= escape($_POST['farm_name'] ?? $farmerData->getFarmName()) ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Eneo la Shamba</label>
            <input type="text" name="farm_location" class="form-control kh-input"
                   value="<?= escape($_POST['farm_location'] ?? $farmerData->getFarmLocation()) ?>">
          </div>
          <div class="row g-2 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Ukubwa (Acres)</label>
              <input type="number" step="0.01" name="farm_size" class="form-control kh-input"
                     value="<?= escape($_POST['farm_size'] ?? $farmerData->getFarmSize()) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Uzoefu (Miaka)</label>
              <input type="number" name="farming_experience" class="form-control kh-input"
                     value="<?= escape($_POST['farming_experience'] ?? $farmerData->getFarmingExperience()) ?>">
            </div>
          </div>
          <div class="mb-4">
            <label class="form-label fw-semibold">Mazao Unayolima</label>
            <input type="text" name="crops_grown" class="form-control kh-input"
                   placeholder="mfano: Mahindi, Mpunga, Maharage"
                   value="<?= escape($_POST['crops_grown'] ?? $farmerData->getCropsGrown()) ?>">
          </div>
          <?php endif; ?>

          <div class="d-flex gap-2">
            <button type="submit" class="btn kh-btn-primary flex-grow-1">
              <i class="fas fa-save me-2"></i>Hifadhi Mabadiliko
            </button>
            <a href="profile.php" class="btn btn-outline-secondary">
              <i class="fas fa-times me-1"></i>Ghairi
            </a>
          </div>
        </form>

        <?php else: ?>
        <!-- ===== VIEW MODE ===== -->
        <ul class="list-group list-group-flush">
          <li class="list-group-item py-3 d-flex justify-content-between">
            <span class="text-muted"><i class="fas fa-envelope me-2"></i>Barua Pepe:</span>
            <strong><?= escape($userData->getEmail()) ?></strong>
          </li>
          <li class="list-group-item py-3 d-flex justify-content-between">
            <span class="text-muted"><i class="fas fa-phone me-2"></i>Nambari ya Simu:</span>
            <strong><?= escape($userData->getPhone()) ?></strong>
          </li>
          <li class="list-group-item py-3 d-flex justify-content-between">
            <span class="text-muted"><i class="fas fa-map-marker-alt me-2"></i>Mahali/Mkoa:</span>
            <strong><?= escape($userData->getLocation()) ?></strong>
          </li>
          <li class="list-group-item py-3 d-flex justify-content-between">
            <span class="text-muted"><i class="fas fa-calendar-alt me-2"></i>Kujiunga:</span>
            <strong><?= escape(formatDate($userData->getCreatedAt())) ?></strong>
          </li>
        </ul>
        <?php endif; ?>

      </div>
    </div>
  </div>

  <!-- === FARM DETAILS (View Only - right column) === -->
  <?php if ($isFarmer && $farmerData && !$editMode): ?>
  <div class="col-lg-6">
    <div class="kh-card h-100">
      <div class="kh-card-header"><i class="fas fa-seedling me-2"></i>Taarifa za Shamba</div>
      <div class="card-body p-4">
        <ul class="list-group list-group-flush">
          <li class="list-group-item py-3 d-flex justify-content-between">
            <span class="text-muted">Jina la Shamba:</span>
            <strong><?= escape($farmerData->getFarmName()) ?></strong>
          </li>
          <li class="list-group-item py-3 d-flex justify-content-between">
            <span class="text-muted">Eneo la Shamba:</span>
            <strong><?= escape($farmerData->getFarmLocation()) ?></strong>
          </li>
          <li class="list-group-item py-3 d-flex justify-content-between">
            <span class="text-muted">Ukubwa (Acres):</span>
            <strong><?= escape($farmerData->getFarmSize()) ?></strong>
          </li>
          <li class="list-group-item py-3 d-flex justify-content-between">
            <span class="text-muted">Mazao Unayolima:</span>
            <strong><?= escape($farmerData->getCropsGrown()) ?></strong>
          </li>
          <li class="list-group-item py-3 d-flex justify-content-between">
            <span class="text-muted">Uzoefu (Miaka):</span>
            <strong><?= escape($farmerData->getFarmingExperience()) ?> miaka</strong>
          </li>
        </ul>
      </div>
    </div>
  </div>
  <?php elseif (!$editMode): ?>
  <div class="col-lg-6">
    <div class="kh-card h-100 bg-light border-0 d-flex flex-column align-items-center justify-content-center text-center p-4">
      <div class="fs-1 text-muted mb-2">🔐</div>
      <h6 class="fw-bold">Mfumo wa Encryption Uliothibitishwa</h6>
      <p class="text-muted small px-3">
        Data zako zote zimesimbwa kwa usalama kwa kutumia algoriti ya <strong>AES-256-CBC</strong>.
        Ni wewe pekee unayeweza kuzisoma unapoingia kwenye akaunti yako.
      </p>
    </div>
  </div>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
