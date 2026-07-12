<?php
/**
 * farmer/request_storage.php — Farmer Request Storage Booking
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../classes/Farmer.php';
require_once __DIR__ . '/../classes/StorageFacility.php';

requireRole(['farmer']);

$farmer = new Farmer();
$farmerData = $farmer->findByUserId($_SESSION['user_id']);
if (!$farmerData) { setFlash('danger','Mkulima hajapatikana.'); redirect('dashboard.php'); }
$farmerId = $farmerData->getFarmId();

$db = $farmer->getConnection();

// Fetch facilities
$sfObj = new StorageFacility();
$stmt = $db->query("SELECT * FROM storage_facilities");
$facilities = [];
foreach ($stmt->fetchAll() as $row) {
    if ($sfObj->decrypt($row['status']) === 'active') {
        $facilities[] = [
            'id'                     => $row['id'],
            'name'                   => $sfObj->decrypt($row['name']),
            'type'                   => $sfObj->decrypt($row['type']),
            'location'               => $sfObj->decrypt($row['location']),
            'capacity_kg'            => $sfObj->decrypt($row['capacity_kg']),
            'available_space'        => $sfObj->decrypt($row['available_space']),
            'price_per_kg_per_month' => $sfObj->decrypt($row['price_per_kg_per_month']),
        ];
    }
}

$farmer->setFarmId($farmerId);
$harvests = $farmer->viewHarvests();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Tokeni ya usalama imekosekana.';
    } else {
        $facilityId  = (int)($_POST['facility_id'] ?? 0);
        $harvestId   = (int)($_POST['harvest_id'] ?? 0);
        $quantityKg  = trim($_POST['quantity_kg'] ?? '');
        $startDate   = trim($_POST['start_date'] ?? '');
        $endDate     = trim($_POST['end_date'] ?? '');

        if (!$facilityId) $errors[] = 'Tafadhali chagua kituo cha uhifadhi.';
        if (!$harvestId)  $errors[] = 'Tafadhali chagua zao/mavuno yako.';
        if (!is_numeric($quantityKg) || $quantityKg <= 0) $errors[] = 'Kiasi lazima kiwe nambari zaidi ya 0.';
        if (empty($startDate) || empty($endDate)) $errors[] = 'Tafadhali jaza tarehe za kuanza na kumalizika.';

        if (!$errors) {
            // Find facility details
            $targetFac = $sfObj->findById($facilityId);
            if (!$targetFac) {
                $errors[] = 'Kituo kilichochaguliwa hakipo.';
            } else {
                if (!$targetFac->checkAvailability($quantityKg)) {
                    $errors[] = "Nafasi iliyobaki ({$targetFac->getAvailableSpace()} kg) haitoshi kwa kiasi ulichoomba.";
                } else {
                    // Calculate months difference
                    $d1 = new DateTime($startDate);
                    $d2 = new DateTime($endDate);
                    $interval = $d1->diff($d2);
                    $months = (($interval->y) * 12) + ($interval->m);
                    if ($interval->d > 0 || $months == 0) $months++; // round up days to full month

                    $totalCost = $targetFac->calculateCost($quantityKg, $months);

                    $farmer->setId($farmerData->getId());
                    $farmer->setFarmId($farmerId);
                    $requestId = $farmer->requestStorage($facilityId, $harvestId, $quantityKg, $startDate, $endDate, $totalCost);

                    // Deduct capacity
                    $targetFac->updateSpace($quantityKg, 'deduct');

                    setFlash('success', "Ombi lako la uhifadhi limetunza kwa mafanikio! Jumla ya gharama: " . formatTshs($totalCost));
                    redirect('dashboard.php');
                }
            }
        }
    }
}

$pageTitle = 'Omba Uhifadhi';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="kh-hero mb-4">
  <h1>🏭 Omba Huduma ya Uhifadhi</h1>
  <p>Pata maghala na majokofu salama kwa mazao yako</p>
</div>

<?php if ($errors): ?>
  <div class="alert alert-danger"><?php foreach ($errors as $e): ?><div>&#9888; <?= escape($e) ?></div><?php endforeach; ?></div>
<?php endif; ?>

<div class="row g-4">
  <!-- Form Column -->
  <div class="col-lg-6">
    <div class="kh-card">
      <div class="kh-card-header"><i class="fas fa-file-signature me-2"></i>Fomu ya Maombi ya Uhifadhi</div>
      <div class="card-body p-4">
        <form method="POST">
          <?= csrfField() ?>

          <div class="mb-3">
            <label class="form-label fw-semibold">Kituo cha Uhifadhi *</label>
            <select name="facility_id" id="facility_id" class="form-select kh-input" onchange="calcStorageCost()" required>
              <option value="">-- Chagua Kituo --</option>
              <?php foreach ($facilities as $f): ?>
                <option value="<?= $f['id'] ?>" data-price="<?= $f['price_per_kg_per_month'] ?>"
                  <?= ($_POST['facility_id']??'')==$f['id']?'selected':'' ?>>
                  <?= escape($f['name']) ?> (<?= escape($f['location']) ?>) &mdash; <?= formatTshs($f['price_per_kg_per_month']) ?>/kg kwa mwezi
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Zao/Mavuno *</label>
            <select name="harvest_id" class="form-select kh-input" required>
              <option value="">-- Chagua Mavuno Yako --</option>
              <?php foreach ($harvests as $h): ?>
                <option value="<?= $h['id'] ?>" <?= ($_POST['harvest_id']??'')==$h['id']?'selected':'' ?>>
                  #<?= $h['id'] ?>: <?= escape($h['crop_name']) ?> (Inapatikana: <?= escape($h['quantity_kg']) ?> kg)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Kiasi cha Kuhifadhi (kg) *</label>
            <input type="number" name="quantity_kg" id="qty_kg" class="form-control kh-input"
                   value="<?= escape($_POST['quantity_kg'] ?? '') ?>" oninput="calcStorageCost()" required>
          </div>

          <div class="row g-2">
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">Tarehe ya Kuanza *</label>
              <input type="date" name="start_date" id="start_date" class="form-control kh-input"
                     value="<?= escape($_POST['start_date'] ?? date('Y-m-d')) ?>" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">Tarehe ya Kumalizika *</label>
              <input type="date" name="end_date" id="end_date" class="form-control kh-input"
                     value="<?= escape($_POST['end_date'] ?? date('Y-m-d', strtotime('+1 month'))) ?>" onchange="calcMonths()" required>
            </div>
          </div>

          <!-- Hidden variables to feed calcStorageCost() -->
          <input type="hidden" id="price_per_kg" value="0">
          <input type="hidden" id="duration_months" value="1">

          <div class="p-3 bg-light rounded border mb-4">
            <span class="text-muted d-block small">Kadirio ya Gharama:</span>
            <span class="fs-4 fw-bold text-success" id="calculated_cost">Tshs 0.00</span>
          </div>

          <button type="submit" class="btn kh-btn-primary w-100"><i class="fas fa-paper-plane me-2"></i>Tuma Ombi</button>
        </form>
      </div>
    </div>
  </div>

  <!-- Facilities Details Column -->
  <div class="col-lg-6">
    <div class="kh-card">
      <div class="kh-card-header"><i class="fas fa-warehouse me-2"></i>Vituo Vinavyopatikana</div>
      <div class="card-body p-0">
        <?php if ($facilities): ?>
          <div class="list-group list-group-flush">
            <?php foreach ($facilities as $f): ?>
              <div class="list-group-item p-3">
                <h6 class="fw-bold mb-1"><?= escape($f['name']) ?></h6>
                <div class="row g-2 small text-muted">
                  <div class="col-6"><i class="fas fa-map-marker-alt me-1"></i><?= escape($f['location']) ?></div>
                  <div class="col-6"><i class="fas fa-tag me-1"></i><?= ucfirst(escape($f['type'])) ?></div>
                  <div class="col-6"><i class="fas fa-weight me-1"></i>Nafasi: <strong><?= escape($f['available_space']) ?> kg</strong> / <?= escape($f['capacity_kg']) ?> kg</div>
                  <div class="col-6"><i class="fas fa-money-bill-wave me-1"></i><?= formatTshs($f['price_per_kg_per_month']) ?>/kg/mwezi</div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="empty-state"><span class="empty-icon">📭</span><p>Hakuna vituo vilivyosajiliwa bado.</p></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
function calcMonths() {
    const start = document.getElementById('start_date').value;
    const end = document.getElementById('end_date').value;
    if(!start || !end) return;
    const d1 = new Date(start);
    const d2 = new Date(end);
    const timeDiff = Math.abs(d2.getTime() - d1.getTime());
    let diffDays = Math.ceil(timeDiff / (1000 * 3600 * 24));
    let months = Math.ceil(diffDays / 30);
    if (months <= 0) months = 1;
    document.getElementById('duration_months').value = months;
    calcStorageCost();
}

// Override facility change to store unit price
document.getElementById('facility_id').addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    const price = selected.dataset.price || 0;
    document.getElementById('price_per_kg').value = price;
    calcStorageCost();
});

// Update months on start_date changes
document.getElementById('start_date').addEventListener('change', calcMonths);
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
