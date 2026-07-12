<?php
/**
 * processing/add_facility.php — Processing Facility Registration
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../classes/ProcessingFacility.php';

requireRole(['processor','admin']);

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Tokeni ya usalama imekosekana.';
    } else {
        $name     = trim($_POST['name'] ?? '');
        $type     = trim($_POST['type'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $capacity = trim($_POST['capacity'] ?? '');
        $services = trim($_POST['services_offered'] ?? '');
        $price    = trim($_POST['price'] ?? '');
        $contact  = trim($_POST['contact'] ?? '');

        if (empty($name))     $errors[] = 'Jina la kiwanda linahitajika.';
        if (empty($type))     $errors[] = 'Aina ya kiwanda inahitajika.';
        if (empty($location)) $errors[] = 'Mahali kilipo linahitajika.';
        if (!is_numeric($capacity) || $capacity <= 0) $errors[] = 'Uwezo wa usindikaji (kg kwa siku) lazima uwe nambari zaidi ya 0.';
        if (empty($services)) $errors[] = 'Tafadhali taja huduma zinazotolewa.';
        if (!is_numeric($price) || $price <= 0) $errors[] = 'Bei ya usindikaji kwa kilo lazima iwe zaidi ya 0.';
        if (empty($contact))  $errors[] = 'Nambari ya mawasiliano inahitajika.';

        if (!$errors) {
            $pf = new ProcessingFacility();
            $pf->addFacility([
                'owner_id'         => $_SESSION['user_id'],
                'name'             => $name,
                'type'             => $type,
                'location'         => $location,
                'capacity'         => $capacity,
                'services_offered' => $services,
                'price'            => $price,
                'contact'          => $contact,
            ]);

            setFlash('success', 'Kiwanda cha usindikaji kimesajiliwa kwa mafanikio!');
            redirect('dashboard.php');
        }
    }
}

$pageTitle = 'Sajili Kiwanda';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="kh-hero mb-4">
  <h1>⚙️ Sajili Kiwanda cha Usindikaji</h1>
  <p>Sajili vinu vya kusaga nafaka, vikaushio, au viwanda vya kuongeza thamani mazao</p>
</div>

<?php if ($errors): ?>
  <div class="alert alert-danger"><?php foreach ($errors as $e): ?><div>&#9888; <?= escape($e) ?></div><?php endforeach; ?></div>
<?php endif; ?>

<div class="kh-card">
  <div class="kh-card-header"><i class="fas fa-industry me-2"></i>Fomu ya Usajili wa Kiwanda</div>
  <div class="card-body p-4">
    <form method="POST">
      <?= csrfField() ?>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Jina la Kiwanda *</label>
          <input type="text" name="name" class="form-control kh-input"
                 value="<?= escape($_POST['name'] ?? '') ?>" placeholder="mfano: Kiwanda cha Nafaka Morogoro" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Aina ya Kiwanda *</label>
          <select name="type" class="form-select kh-input" required>
            <option value="">-- Chagua Aina --</option>
            <option value="mill"     <?= ($_POST['type']??'')==='mill'?'selected':'' ?>>Kinu cha Kusaga (Mill)</option>
            <option value="dryer"    <?= ($_POST['type']??'')==='dryer'?'selected':'' ?>>Kikaushio cha Mazao (Dryer)</option>
            <option value="packager" <?= ($_POST['type']??'')==='packager'?'selected':'' ?>>Kiwanda cha Kupakia (Packager)</option>
            <option value="other"    <?= ($_POST['type']??'')==='other'?'selected':'' ?>>Mengineyo</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Uwezo kwa Siku (kg) *</label>
          <input type="number" name="capacity" class="form-control kh-input"
                 value="<?= escape($_POST['capacity'] ?? '') ?>" placeholder="mfano: 3000" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Bei ya Usindikaji kwa kilo (Tshs) *</label>
          <input type="number" name="price" class="form-control kh-input"
                 value="<?= escape($_POST['price'] ?? '') ?>" placeholder="mfano: 150" required>
        </div>
        <div class="col-md-12">
          <label class="form-label fw-semibold">Huduma Zinazotolewa (Tenganisha kwa koma) *</label>
          <input type="text" name="services_offered" class="form-control kh-input"
                 value="<?= escape($_POST['services_offered'] ?? '') ?>" placeholder="mfano: Kusaga mahindi, Kukoboa mpunga, Kukausha alizeti" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Mahali (Wilaya/Mkoa) *</label>
          <input type="text" name="location" class="form-control kh-input"
                 value="<?= escape($_POST['location'] ?? '') ?>" placeholder="mfano: Morogoro Mjini" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Nambari ya Simu ya Mawasiliano *</label>
          <input type="text" name="contact" class="form-control kh-input"
                 value="<?= escape($_POST['contact'] ?? $_SESSION['phone'] ?? '') ?>" required>
        </div>
      </div>
      <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn kh-btn-primary"><i class="fas fa-save me-2"></i>Hifadhi Kiwanda</button>
        <a href="dashboard.php" class="btn btn-outline-secondary">Ghairi</a>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/header.php'; ?>
