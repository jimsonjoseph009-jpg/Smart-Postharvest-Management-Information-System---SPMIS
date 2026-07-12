<?php
/**
 * transport/add_vehicle.php — Transport Vehicle Registration
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../classes/TransportVehicle.php';

requireRole(['transport_provider','admin']);

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Tokeni ya usalama imekosekana.';
    } else {
        $type      = trim($_POST['vehicle_type'] ?? '');
        $plate     = trim($_POST['plate_number'] ?? '');
        $capacity  = trim($_POST['capacity_kg'] ?? '');
        $location  = trim($_POST['location'] ?? '');
        $price     = trim($_POST['price_per_km'] ?? '');
        $contact   = trim($_POST['contact'] ?? '');

        if (empty($type))     $errors[] = 'Aina ya gari inahitajika.';
        if (empty($plate))    $errors[] = 'Namba ya usajili wa gari inahitajika.';
        if (!is_numeric($capacity) || $capacity <= 0) $errors[] = 'Kiasi cha mzigo (kg) lazima kiwe nambari zaidi ya 0.';
        if (empty($location)) $errors[] = 'Kituo cha gari linahitajika.';
        if (!is_numeric($price) || $price <= 0) $errors[] = 'Bei kwa kilomita lazima iwe zaidi ya 0.';
        if (empty($contact))  $errors[] = 'Nambari ya mawasiliano inahitajika.';

        if (!$errors) {
            $tv = new TransportVehicle();
            $tv->addVehicle([
                'owner_id'     => $_SESSION['user_id'],
                'vehicle_type' => $type,
                'plate_number' => $plate,
                'capacity_kg'  => $capacity,
                'location'     => $location,
                'price_per_km' => $price,
                'contact'      => $contact,
            ]);

            setFlash('success', 'Gari limesajiliwa kwa mafanikio!');
            redirect('dashboard.php');
        }
    }
}

$pageTitle = 'Sajili Gari';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="kh-hero mb-4">
  <h1>🚛 Sajili Gari la Usafiri</h1>
  <p>Sajili malori, pikipiki au magari ya kubebea mazao katika mfumo</p>
</div>

<?php if ($errors): ?>
  <div class="alert alert-danger"><?php foreach ($errors as $e): ?><div>&#9888; <?= escape($e) ?></div><?php endforeach; ?></div>
<?php endif; ?>

<div class="kh-card">
  <div class="kh-card-header"><i class="fas fa-truck me-2"></i>Fomu ya Usajili wa Gari</div>
  <div class="card-body p-4">
    <form method="POST">
      <?= csrfField() ?>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Aina ya Gari *</label>
          <select name="vehicle_type" class="form-select kh-input" required>
            <option value="">-- Chagua Gari --</option>
            <option value="Lori"     <?= ($_POST['vehicle_type']??'')==='Lori'?'selected':'' ?>>Lori (Truck)</option>
            <option value="Pick-up"  <?= ($_POST['vehicle_type']??'')==='Pick-up'?'selected':'' ?>>Pick-up</option>
            <option value="Gari Ndogo" <?= ($_POST['vehicle_type']??'')==='Gari Ndogo'?'selected':'' ?>>Gari Ndogo (Van)</option>
            <option value="Pikipiki" <?= ($_POST['vehicle_type']??'')==='Pikipiki'?'selected':'' ?>>Pikipiki (Motorcycle)</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Namba ya Gari (Plate Number) *</label>
          <input type="text" name="plate_number" class="form-control kh-input"
                 value="<?= escape($_POST['plate_number'] ?? '') ?>" placeholder="mfano: T 123 ABC" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Uwezo wa Kubeba (kg) *</label>
          <input type="number" name="capacity_kg" class="form-control kh-input"
                 value="<?= escape($_POST['capacity_kg'] ?? '') ?>" placeholder="mfano: 5000" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Bei kwa Kilomita (Tshs) *</label>
          <input type="number" name="price_per_km" class="form-control kh-input"
                 value="<?= escape($_POST['price_per_km'] ?? '') ?>" placeholder="mfano: 800" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Mahali Lilipo (Kituo) *</label>
          <input type="text" name="location" class="form-control kh-input"
                 value="<?= escape($_POST['location'] ?? '') ?>" placeholder="mfano: Morogoro Mjini" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Namba ya Simu ya Dereva *</label>
          <input type="text" name="contact" class="form-control kh-input"
                 value="<?= escape($_POST['contact'] ?? $_SESSION['phone'] ?? '') ?>" required>
        </div>
      </div>
      <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn kh-btn-primary"><i class="fas fa-save me-2"></i>Hifadhi Gari</button>
        <a href="dashboard.php" class="btn btn-outline-secondary">Ghairi</a>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
