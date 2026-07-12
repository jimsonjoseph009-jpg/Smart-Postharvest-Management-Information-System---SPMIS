<?php
/**
 * storage/add_facility.php — Storage Facility Registration
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../classes/StorageFacility.php';

requireRole(['storage_provider','admin']);

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Tokeni ya usalama imekosekana.';
    } else {
        $name     = trim($_POST['name'] ?? '');
        $type     = trim($_POST['type'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $capacity = trim($_POST['capacity_kg'] ?? '');
        $price    = trim($_POST['price_per_kg_per_month'] ?? '');
        $contact  = trim($_POST['contact_person'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');

        if (empty($name))     $errors[] = 'Jina la kituo linahitajika.';
        if (empty($type))     $errors[] = 'Aina ya kituo inahitajika.';
        if (empty($location)) $errors[] = 'Mahali kilipo kituo linahitajika.';
        if (!is_numeric($capacity) || $capacity <= 0) $errors[] = 'Uwezo wa kituo (kg) lazima uwe nambari zaidi ya 0.';
        if (!is_numeric($price) || $price <= 0) $errors[] = 'Bei kwa kilo kwa mwezi lazima iwe zaidi ya 0.';
        if (empty($contact))  $errors[] = 'Jina la mtu wa mawasiliano linahitajika.';
        if (empty($phone))    $errors[] = 'Nambari ya simu inahitajika.';

        if (!$errors) {
            $sf = new StorageFacility();
            $sf->addFacility([
                'owner_id'               => $_SESSION['user_id'],
                'name'                   => $name,
                'type'                   => $type,
                'location'               => $location,
                'capacity_kg'            => $capacity,
                'price_per_kg_per_month' => $price,
                'contact_person'         => $contact,
                'phone'                  => $phone,
            ]);

            setFlash('success', 'Kituo cha uhifadhi kimesajiliwa kwa mafanikio!');
            redirect('dashboard.php');
        }
    }
}

$pageTitle = 'Sajili Kituo cha Uhifadhi';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="kh-hero mb-4">
  <h1>🏭 Sajili Kituo cha Uhifadhi</h1>
  <p>Sajili maghala, majokofu (cold rooms) au silo kwa ajili ya kuhifadhi mazao ya wakulima</p>
</div>

<?php if ($errors): ?>
  <div class="alert alert-danger"><?php foreach ($errors as $e): ?><div>&#9888; <?= escape($e) ?></div><?php endforeach; ?></div>
<?php endif; ?>

<div class="kh-card">
  <div class="kh-card-header"><i class="fas fa-warehouse me-2"></i>Fomu ya Usajili wa Kituo</div>
  <div class="card-body p-4">
    <form method="POST">
      <?= csrfField() ?>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Jina la Kituo/Ghala *</label>
          <input type="text" name="name" class="form-control kh-input"
                 value="<?= escape($_POST['name'] ?? '') ?>" placeholder="mfano: Maghala ya Ushirika Morogoro" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Aina ya Kituo *</label>
          <select name="type" class="form-select kh-input" required>
            <option value="">-- Chagua Aina --</option>
            <option value="warehouse"    <?= ($_POST['type']??'')==='warehouse'?'selected':'' ?>>Ghala la Kawaida (Warehouse)</option>
            <option value="cold_storage" <?= ($_POST['type']??'')==='cold_storage'?'selected':'' ?>>Jokofu la Mazao (Cold Storage)</option>
            <option value="silo"         <?= ($_POST['type']??'')==='silo'?'selected':'' ?>>Silo</option>
            <option value="other"        <?= ($_POST['type']??'')==='other'?'selected':'' ?>>Mengineyo</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Uwezo wa Uhifadhi (kg) *</label>
          <input type="number" name="capacity_kg" class="form-control kh-input"
                 value="<?= escape($_POST['capacity_kg'] ?? '') ?>" placeholder="mfano: 20000" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Bei kwa Kilo kwa Mwezi (Tshs) *</label>
          <input type="number" name="price_per_kg_per_month" class="form-control kh-input"
                 value="<?= escape($_POST['price_per_kg_per_month'] ?? '') ?>" placeholder="mfano: 50" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Mahali Lilipo (Kituo) *</label>
          <input type="text" name="location" class="form-control kh-input"
                 value="<?= escape($_POST['location'] ?? '') ?>" placeholder="mfano: Kilosa, Morogoro" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Mtu wa Mawasiliano *</label>
          <input type="text" name="contact_person" class="form-control kh-input"
                 value="<?= escape($_POST['contact_person'] ?? $_SESSION['full_name'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Namba ya Simu *</label>
          <input type="text" name="phone" class="form-control kh-input"
                 value="<?= escape($_POST['phone'] ?? $_SESSION['phone'] ?? '') ?>" required>
        </div>
      </div>
      <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn kh-btn-primary"><i class="fas fa-save me-2"></i>Hifadhi Kituo</button>
        <a href="dashboard.php" class="btn btn-outline-secondary">Ghairi</a>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
