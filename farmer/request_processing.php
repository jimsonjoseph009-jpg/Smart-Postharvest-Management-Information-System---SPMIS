<?php
/**
 * farmer/request_processing.php — Farmer Request Processing Booking
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../classes/Farmer.php';
require_once __DIR__ . '/../classes/ProcessingFacility.php';

requireRole(['farmer']);

$farmer = new Farmer();
$farmerData = $farmer->findByUserId($_SESSION['user_id']);
if (!$farmerData) { setFlash('danger','Mkulima hajapatikana.'); redirect('dashboard.php'); }
$farmerId = $farmerData->getFarmId();

$db = $farmer->getConnection();

// Fetch processing facilities
$pfObj = new ProcessingFacility();
$stmt = $db->query("SELECT * FROM processing_facilities");
$facilities = [];
foreach ($stmt->fetchAll() as $row) {
    $facilities[] = [
        'id'               => $row['id'],
        'name'             => $pfObj->decrypt($row['name']),
        'type'             => $pfObj->decrypt($row['type']),
        'location'         => $pfObj->decrypt($row['location']),
        'capacity'         => $pfObj->decrypt($row['capacity']),
        'services_offered' => $pfObj->decrypt($row['services_offered']),
        'price'            => $pfObj->decrypt($row['price']),
    ];
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
        $serviceType = trim($_POST['service_type'] ?? '');

        if (!$facilityId)  $errors[] = 'Tafadhali chagua kiwanda cha usindikaji.';
        if (!$harvestId)   $errors[] = 'Tafadhali chagua mavuno yanayosindikwa.';
        if (!is_numeric($quantityKg) || $quantityKg <= 0) $errors[] = 'Kiasi lazima kiwe nambari zaidi ya 0.';
        if (empty($serviceType)) $errors[] = 'Tafadhali taja huduma inayohitajika (mfano: Kusaga, Kukausha).';

        if (!$errors) {
            $targetFac = $pfObj->findById($facilityId);
            if (!$targetFac) {
                $errors[] = 'Kiwanda kilichochaguliwa hakipo.';
            } else {
                if (!$targetFac->checkCapacity($quantityKg)) {
                    $errors[] = "Kiwango cha juu cha usindikaji kwa siku ({$targetFac->getCapacity()} kg) kimezidiwa.";
                } else {
                    // Calculate cost
                    $totalCost = $targetFac->calculateCost($quantityKg);

                    $farmer->setId($farmerData->getId());
                    $farmer->setFarmId($farmerId);
                    $farmer->requestProcessing($facilityId, $harvestId, $quantityKg, $serviceType, $totalCost);

                    setFlash('success', "Ombi lako la usindikaji limefanikiwa! Gharama ya usindikaji: " . formatTshs($totalCost));
                    redirect('dashboard.php');
                }
            }
        }
    }
}

$pageTitle = 'Omba Usindikaji';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="kh-hero mb-4">
  <h1>⚙️ Huduma ya Usindikaji (Value Addition)</h1>
  <p>Ongeza thamani ya mazao yako kwa kusaga, kukausha au kupakia</p>
</div>

<?php if ($errors): ?>
  <div class="alert alert-danger"><?php foreach ($errors as $e): ?><div>&#9888; <?= escape($e) ?></div><?php endforeach; ?></div>
<?php endif; ?>

<div class="row g-4">
  <!-- Request Form -->
  <div class="col-lg-6">
    <div class="kh-card">
      <div class="kh-card-header"><i class="fas fa-file-invoice-dollar me-2"></i>Fomu ya Usindikaji</div>
      <div class="card-body p-4">
        <form method="POST">
          <?= csrfField() ?>

          <div class="mb-3">
            <label class="form-label fw-semibold">Kiwanda cha Usindikaji *</label>
            <select name="facility_id" id="facility_id" class="form-select kh-input" onchange="updateProcessingPrice()" required>
              <option value="">-- Chagua Kiwanda --</option>
              <?php foreach ($facilities as $f): ?>
                <option value="<?= $f['id'] ?>" data-price="<?= $f['price'] ?>"
                  <?= ($_POST['facility_id']??'')==$f['id']?'selected':'' ?>>
                  <?= escape($f['name']) ?> (<?= escape($f['location']) ?>) &mdash; <?= formatTshs($f['price']) ?>/kg
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Zao Lako la Kusindika *</label>
            <select name="harvest_id" id="harvest_id" class="form-select kh-input" onchange="setHarvestQuantity()" required>
              <option value="">-- Chagua Zao --</option>
              <?php foreach ($harvests as $h): ?>
                <option value="<?= $h['id'] ?>" data-qty="<?= $h['quantity_kg'] ?>"
                  <?= ($_POST['harvest_id']??'')==$h['id']?'selected':'' ?>>
                  #<?= $h['id'] ?>: <?= escape($h['crop_name']) ?> (Kiasi kinachopatikana: <?= escape($h['quantity_kg']) ?> kg)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Kiasi cha Kusindika (kg) *</label>
            <input type="number" name="quantity_kg" id="qty_kg" class="form-control kh-input"
                   value="<?= escape($_POST['quantity_kg'] ?? '') ?>" oninput="calcProcessingCost()" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Huduma Inayohitajika *</label>
            <input type="text" name="service_type" class="form-control kh-input"
                   value="<?= escape($_POST['service_type'] ?? '') ?>" placeholder="mfano: Kusaga unga, Kukausha nafaka" required>
          </div>

          <!-- Hidden params for JS calculation -->
          <input type="hidden" id="price_per_kg" value="0">

          <div class="p-3 bg-light rounded border mb-4">
            <span class="text-muted d-block small">Kadirio ya Gharama:</span>
            <span class="fs-4 fw-bold text-success" id="calculated_cost">Tshs 0.00</span>
          </div>

          <button type="submit" class="btn kh-btn-primary w-100"><i class="fas fa-industry me-2"></i>Tuma Ombi la Usindikaji</button>
        </form>
      </div>
    </div>
  </div>

  <!-- Processing Facilities Overview -->
  <div class="col-lg-6">
    <div class="kh-card">
      <div class="kh-card-header"><i class="fas fa-warehouse me-2"></i>Kiwanda na Huduma Zake</div>
      <div class="card-body p-0">
        <?php if ($facilities): ?>
          <div class="list-group list-group-flush">
            <?php foreach ($facilities as $f): ?>
              <div class="list-group-item p-3">
                <h6 class="fw-bold mb-1"><?= escape($f['name']) ?> (<?= ucfirst(escape($f['type'])) ?>)</h6>
                <div class="row g-2 small text-muted">
                  <div class="col-6"><i class="fas fa-map-marker-alt me-1"></i><?= escape($f['location']) ?></div>
                  <div class="col-6"><i class="fas fa-weight me-1"></i>Uwezo kwa siku: <?= escape($f['capacity']) ?> kg</div>
                  <div class="col-12"><i class="fas fa-concierge-bell me-1"></i>Huduma: <strong><?= escape($f['services_offered']) ?></strong></div>
                  <div class="col-12"><i class="fas fa-money-bill-wave me-1"></i>Bei ya usindikaji: <strong><?= formatTshs($f['price']) ?>/kg</strong></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="empty-state"><span class="empty-icon">📭</span><p>Hakuna viwanda vya usindikaji vilivyosajiliwa.</p></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
function updateProcessingPrice() {
    const sel = document.getElementById('facility_id');
    const price = sel.options[sel.selectedIndex].dataset.price || 0;
    document.getElementById('price_per_kg').value = price;
    calcProcessingCost();
}

function setHarvestQuantity() {
    const sel = document.getElementById('harvest_id');
    const qty = sel.options[sel.selectedIndex].dataset.qty || '';
    document.getElementById('qty_kg').value = qty;
    calcProcessingCost();
}

function calcProcessingCost() {
    const qty = parseFloat(document.getElementById('qty_kg').value || 0);
    const price = parseFloat(document.getElementById('price_per_kg').value || 0);
    const total = qty * price;
    document.getElementById('calculated_cost').textContent = 'Tshs ' + total.toLocaleString('en', { minimumFractionDigits: 2 });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
