<?php
/**
 * farmer/request_transport.php — Farmer Request Transport Booking
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../classes/Farmer.php';
require_once __DIR__ . '/../classes/TransportVehicle.php';

requireRole(['farmer']);

$farmer = new Farmer();
$farmerData = $farmer->findByUserId($_SESSION['user_id']);
if (!$farmerData) { setFlash('danger','Mkulima hajapatikana.'); redirect('dashboard.php'); }
$farmerId = $farmerData->getFarmId();

$db = $farmer->getConnection();

// Fetch vehicles
$tvObj = new TransportVehicle();
$stmt = $db->query("SELECT * FROM transport_vehicles");
$vehicles = [];
foreach ($stmt->fetchAll() as $row) {
    if ($tvObj->decrypt($row['available']) === 'yes') {
        $vehicles[] = [
            'id'           => $row['id'],
            'vehicle_type' => $tvObj->decrypt($row['vehicle_type']),
            'plate_number' => $tvObj->decrypt($row['plate_number']),
            'capacity_kg'  => $tvObj->decrypt($row['capacity_kg']),
            'location'     => $tvObj->decrypt($row['location']),
            'price_per_km' => $tvObj->decrypt($row['price_per_km']),
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
        $vehicleId      = (int)($_POST['vehicle_id'] ?? 0);
        $pickupLoc      = trim($_POST['pickup_location'] ?? '');
        $deliveryLoc    = trim($_POST['delivery_location'] ?? '');
        $distanceKm     = trim($_POST['distance_km'] ?? '');
        $quantityKg     = trim($_POST['quantity_kg'] ?? '');
        $requestedDate  = trim($_POST['requested_date'] ?? '');

        if (!$vehicleId)    $errors[] = 'Tafadhali chagua gari.';
        if (empty($pickupLoc) || empty($deliveryLoc)) $errors[] = 'Mahali pa kuchukua na kupeleka mazao inahitajika.';
        if (!is_numeric($distanceKm) || $distanceKm <= 0) $errors[] = 'Umbali lazima uwe nambari zaidi ya 0.';
        if (!is_numeric($quantityKg) || $quantityKg <= 0) $errors[] = 'Uzito wa mazao lazima uwe zaidi ya 0.';
        if (empty($requestedDate)) $errors[] = 'Tarehe ya kusafirisha inahitajika.';

        if (!$errors) {
            $targetVehicle = $tvObj->findById($vehicleId);
            if (!$targetVehicle) {
                $errors[] = 'Gari lililochaguliwa halipo.';
            } else {
                // Calculate Cost
                $totalCost = $targetVehicle->calculateCost($distanceKm);

                $farmer->setId($farmerData->getId());
                $farmer->setFarmId($farmerId);
                $farmer->requestTransport($vehicleId, $pickupLoc, $deliveryLoc, $distanceKm, $quantityKg, $totalCost, $requestedDate);

                setFlash('success', "Ombi la usafiri limetunza! Gharama ya safari: " . formatTshs($totalCost));
                redirect('dashboard.php');
            }
        }
    }
}

$pageTitle = 'Omba Usafiri';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="kh-hero mb-4">
  <h1>🚛 Omba Huduma ya Usafiri</h1>
  <p>Tafuta wasafirishaji wa kuaminika kusafirisha mazao yako salama</p>
</div>

<?php if ($errors): ?>
  <div class="alert alert-danger"><?php foreach ($errors as $e): ?><div>&#9888; <?= escape($e) ?></div><?php endforeach; ?></div>
<?php endif; ?>

<div class="row g-4">
  <!-- Booking Form -->
  <div class="col-lg-6">
    <div class="kh-card">
      <div class="kh-card-header"><i class="fas fa-truck me-2"></i>Fomu ya Maombi ya Usafiri</div>
      <div class="card-body p-4">
        <form method="POST">
          <?= csrfField() ?>

          <div class="mb-3">
            <label class="form-label fw-semibold">Chagua Gari *</label>
            <select name="vehicle_id" id="vehicle_id" class="form-select kh-input" onchange="updateVehiclePrice()" required>
              <option value="">-- Chagua Gari --</option>
              <?php foreach ($vehicles as $v): ?>
                <option value="<?= $v['id'] ?>" data-price="<?= $v['price_per_km'] ?>"
                  <?= ($_POST['vehicle_id']??'')==$v['id']?'selected':'' ?>>
                  <?= ucfirst(escape($v['vehicle_type'])) ?> (<?= escape($v['plate_number']) ?>) &mdash; <?= formatTshs($v['price_per_km']) ?>/km
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Mahali pa Kuchukua Mazao (Pickup) *</label>
            <input type="text" name="pickup_location" class="form-control kh-input"
                   value="<?= escape($_POST['pickup_location'] ?? '') ?>" placeholder="mfano: Shambani Kilosa" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Mahali pa Kupeleka Mazao (Delivery) *</label>
            <input type="text" name="delivery_location" class="form-control kh-input"
                   value="<?= escape($_POST['delivery_location'] ?? '') ?>" placeholder="mfano: Soko Kuu la Kariakoo" required>
          </div>

          <div class="row g-2">
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">Kadirio ya Umbali (km) *</label>
              <input type="number" name="distance_km" id="distance_km" step="0.1" class="form-control kh-input"
                     value="<?= escape($_POST['distance_km'] ?? '') ?>" oninput="calcTransportCost()" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">Uzito wa Mazao (kg) *</label>
              <input type="number" name="quantity_kg" class="form-control kh-input"
                     value="<?= escape($_POST['quantity_kg'] ?? '') ?>" placeholder="mfano: 1000" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Tarehe ya Kusafirishwa *</label>
            <input type="date" name="requested_date" class="form-control kh-input"
                   value="<?= escape($_POST['requested_date'] ?? date('Y-m-d')) ?>" required>
          </div>

          <!-- Hidden parameter for JS calculations -->
          <input type="hidden" id="price_per_km" value="0">

          <div class="p-3 bg-light rounded border mb-4">
            <span class="text-muted d-block small">Kadirio ya Gharama ya Safari:</span>
            <span class="fs-4 fw-bold text-success" id="calculated_cost">Tshs 0.00</span>
          </div>

          <button type="submit" class="btn kh-btn-primary w-100"><i class="fas fa-paper-plane me-2"></i>Agiza Usafiri</button>
        </form>
      </div>
    </div>
  </div>

  <!-- Available Vehicles -->
  <div class="col-lg-6">
    <div class="kh-card">
      <div class="kh-card-header"><i class="fas fa-truck-moving me-2"></i>Wasafirishaji Wanaopatikana</div>
      <div class="card-body p-0">
        <?php if ($vehicles): ?>
          <div class="list-group list-group-flush">
            <?php foreach ($vehicles as $v): ?>
              <div class="list-group-item p-3">
                <h6 class="fw-bold mb-1"><?= ucfirst(escape($v['vehicle_type'])) ?> (<?= escape($v['plate_number']) ?>)</h6>
                <div class="row g-2 small text-muted">
                  <div class="col-6"><i class="fas fa-map-marker-alt me-1"></i>Kituo: <?= escape($v['location']) ?></div>
                  <div class="col-6"><i class="fas fa-weight-hanging me-1"></i>Kiwango: <?= escape($v['capacity_kg']) ?> kg</div>
                  <div class="col-12"><i class="fas fa-money-bill-wave me-1"></i>Bei kwa Kilomita: <strong><?= formatTshs($v['price_per_km']) ?></strong></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="empty-state"><span class="empty-icon">📭</span><p>Hakuna magari yanayopatikana kwa sasa.</p></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
function updateVehiclePrice() {
    const sel = document.getElementById('vehicle_id');
    const price = sel.options[sel.selectedIndex].dataset.price || 0;
    document.getElementById('price_per_km').value = price;
    calcTransportCost();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
