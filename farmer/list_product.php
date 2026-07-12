<?php
/**
 * farmer/list_product.php — Sell Product on Marketplace
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../classes/Farmer.php';
require_once __DIR__ . '/../classes/Harvest.php';

requireRole(['farmer']);

$farmer = new Farmer();
$farmerData = $farmer->findByUserId($_SESSION['user_id']);
if (!$farmerData) { setFlash('danger','Mkulima hajapatikana.'); redirect('dashboard.php'); }
$farmerId = $farmerData->getFarmId();

$farmer->setFarmId($farmerId);
$harvests = $farmer->viewHarvests();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Tokeni ya usalama imekosekana.';
    } else {
        $harvestId   = (int)($_POST['harvest_id'] ?? 0);
        $quantityKg  = trim($_POST['quantity_kg'] ?? '');
        $pricePerKg  = trim($_POST['price_per_kg'] ?? '');
        $location    = trim($_POST['location'] ?? '');

        if (!$harvestId)  $errors[] = 'Tafadhali chagua zao la kuuza.';
        if (!is_numeric($quantityKg) || $quantityKg <= 0) $errors[] = 'Kiasi lazima kiwe nambari zaidi ya 0.';
        if (!is_numeric($pricePerKg) || $pricePerKg <= 0) $errors[] = 'Bei lazima iwe nambari zaidi ya 0.';
        if (empty($location))   $errors[] = 'Mahali kilipo bidhaa inahitajika.';

        // Verify harvest exists and has enough quantity
        $hObj = new Harvest();
        $target = $hObj->findById($harvestId);
        if (!$target || $target->getFarmerId() != $farmerId) {
            $errors[] = 'Zao halikupatikana au huna ruhusa.';
        } else {
            if ($quantityKg > $target->getQuantityKg()) {
                $errors[] = 'Kiasi unachotaka kuuza kinazidi kiasi cha mavuno ulichonacho.';
            }
        }

        if (!$errors) {
            $db = $farmer->getConnection();
            $encSellerType  = $farmer->encrypt('farmer');
            $encProductType = $farmer->encrypt('harvest');
            $encQty         = $farmer->encrypt($quantityKg);
            $encPrice       = $farmer->encrypt($pricePerKg);
            $encLoc         = $farmer->encrypt($location);
            $encStatus      = $farmer->encrypt('active');

            $stmt = $db->prepare("
                INSERT INTO market_listings (seller_id, seller_type, product_type, product_id, quantity_kg, price_per_kg, location, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_SESSION['user_id'], $encSellerType, $encProductType, $harvestId, $encQty, $encPrice, $encLoc, $encStatus
            ]);
            $listingId = $db->lastInsertId();

            $farmer->logAudit('market_listings', $listingId, 'status', 'ENCRYPT', $encStatus);

            setFlash('success', 'Bidhaa yako imewekwa sokoni kwa mafanikio!');
            redirect('dashboard.php');
        }
    }
}

$pageTitle = 'Uza Bidhaa';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="kh-hero mb-4">
  <h1>🛒 Uza Mazao Sokoni</h1>
  <p>Weka mazao yako sokoni ili wanunuzi waweze kuagiza moja kwa moja</p>
</div>

<?php if ($errors): ?>
  <div class="alert alert-danger"><?php foreach ($errors as $e): ?><div>&#9888; <?= escape($e) ?></div><?php endforeach; ?></div>
<?php endif; ?>

<div class="kh-card">
  <div class="kh-card-header"><i class="fas fa-store me-2"></i>Fomu ya Kuweka Bidhaa Sokoni</div>
  <div class="card-body p-4">
    <form method="POST">
      <?= csrfField() ?>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Chagua Mavuno ya Kuuza *</label>
          <select name="harvest_id" id="harvest_id" class="form-select kh-input" onchange="setHarvestDetails()" required>
            <option value="">-- Chagua Mavuno --</option>
            <?php foreach ($harvests as $h): ?>
              <option value="<?= $h['id'] ?>" data-qty="<?= $h['quantity_kg'] ?>" data-price="<?= $h['unit_price'] ?>" data-loc="<?= $h['harvest_location'] ?>"
                <?= ($_POST['harvest_id']??'')==$h['id']?'selected':'' ?>>
                <?= escape($h['crop_name']) ?> (Inapatikana: <?= escape($h['quantity_kg']) ?> kg)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Kiasi cha Kuuza (kg) *</label>
          <input type="number" name="quantity_kg" id="qty_kg" step="0.01" class="form-control kh-input"
                 value="<?= escape($_POST['quantity_kg'] ?? '') ?>" placeholder="Kiasi cha kilo" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Bei kwa Kilo (Tshs) *</label>
          <input type="number" name="price_per_kg" id="price_per_kg" step="0.01" class="form-control kh-input"
                 value="<?= escape($_POST['price_per_kg'] ?? '') ?>" placeholder="mfano: 1200" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Mahali Bidhaa Ilipo *</label>
          <input type="text" name="location" id="location" class="form-control kh-input"
                 value="<?= escape($_POST['location'] ?? '') ?>" placeholder="mfano: Kilosa, Morogoro" required>
        </div>
      </div>
      <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn kh-btn-primary"><i class="fas fa-store me-2"></i>Weka Sokoni</button>
        <a href="dashboard.php" class="btn btn-outline-secondary">Rudi</a>
      </div>
    </form>
  </div>
</div>

<script>
function setHarvestDetails() {
    const sel = document.getElementById('harvest_id');
    const option = sel.options[sel.selectedIndex];
    if (!option.value) return;
    document.getElementById('qty_kg').value = option.dataset.qty || '';
    document.getElementById('price_per_kg').value = option.dataset.price || '';
    document.getElementById('location').value = option.dataset.loc || '';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
