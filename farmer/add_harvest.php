<?php
/**
 * farmer/add_harvest.php — Add new harvest
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../classes/Farmer.php';
require_once __DIR__ . '/../classes/Crop.php';

requireRole(['farmer']);

$farmer = new Farmer();
$farmerData = $farmer->findByUserId($_SESSION['user_id']);
if (!$farmerData) { setFlash('danger','Wasifu wa mkulima haukupatikana.'); redirect(BASE_URL.'/farmer/dashboard.php'); }

$cropObj = new Crop();
$crops   = $cropObj->getAll();
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Tokeni ya usalama imekosekana.';
    } else {
        $cropId   = (int)($_POST['crop_id'] ?? 0);
        $quantity = trim($_POST['quantity_kg'] ?? '');
        $date     = trim($_POST['harvest_date'] ?? '');
        $grade    = trim($_POST['quality_grade'] ?? '');
        $price    = trim($_POST['unit_price'] ?? '');
        $location = trim($_POST['harvest_location'] ?? '');

        if (!$cropId)           $errors[] = 'Tafadhali chagua zao.';
        if (!is_numeric($quantity) || $quantity <= 0) $errors[] = 'Kiasi lazima kiwe nambari zaidi ya 0.';
        if (empty($date))       $errors[] = 'Tarehe ya kuvuna inahitajika.';
        if (empty($grade))      $errors[] = 'Daraja la ubora linahitajika.';
        if (!is_numeric($price) || $price <= 0) $errors[] = 'Bei lazima iwe nambari zaidi ya 0.';
        if (empty($location))   $errors[] = 'Mahali pa kuvunia linahitajika.';

        if (!$errors) {
            $farmer->setFarmId($farmerData->getFarmId());
            $farmer->setId($farmerData->getId());
            $farmer->addHarvest($cropId, $quantity, $date, $grade, $price, $location);
            setFlash('success','Mavuno yamesajiliwa kwa mafanikio!');
            redirect(BASE_URL . '/farmer/view_harvests.php');
        }
    }
}

$pageTitle = 'Ongeza Mavuno';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="kh-hero mb-4"><h1>🌾 Sajili Mavuno Mapya</h1><p>Ongeza rekodi ya mavuno yako</p></div>

<?php if ($errors): ?>
  <div class="alert alert-danger"><?php foreach ($errors as $e): ?><div>&#9888; <?= escape($e) ?></div><?php endforeach; ?></div>
<?php endif; ?>

<div class="kh-card">
  <div class="kh-card-header"><i class="fas fa-seedling me-2"></i>Fomu ya Mavuno</div>
  <div class="card-body p-4">
    <form method="POST">
      <?= csrfField() ?>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Zao *</label>
          <select name="crop_id" class="form-select kh-input" required>
            <option value="">-- Chagua Zao --</option>
            <?php foreach ($crops as $c): ?>
              <option value="<?= $c['id'] ?>" <?= ($_POST['crop_id']??'')==$c['id']?'selected':'' ?>>
                <?= escape($c['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Kiasi (kg) *</label>
          <input type="number" name="quantity_kg" step="0.01" class="form-control kh-input"
                 value="<?= escape($_POST['quantity_kg'] ?? '') ?>" placeholder="mfano: 500" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Tarehe ya Kuvuna *</label>
          <input type="date" name="harvest_date" class="form-control kh-input"
                 value="<?= escape($_POST['harvest_date'] ?? date('Y-m-d')) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Daraja la Ubora *</label>
          <select name="quality_grade" class="form-select kh-input" required>
            <option value="">-- Chagua Daraja --</option>
            <?php foreach (['A','B','C','D'] as $g): ?>
              <option value="<?= $g ?>" <?= ($_POST['quality_grade']??'')===$g?'selected':'' ?>><?= $g ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Bei kwa Kilo (Tshs) *</label>
          <input type="number" name="unit_price" step="0.01" class="form-control kh-input"
                 value="<?= escape($_POST['unit_price'] ?? '') ?>" placeholder="mfano: 1200" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Mahali Kilipovunwa *</label>
          <input type="text" name="harvest_location" class="form-control kh-input"
                 value="<?= escape($_POST['harvest_location'] ?? '') ?>" placeholder="mfano: Morogoro" required>
        </div>
      </div>
      <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn kh-btn-primary"><i class="fas fa-save me-2"></i>Hifadhi Mavuno</button>
        <a href="dashboard.php" class="btn btn-outline-secondary">Rudi</a>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
