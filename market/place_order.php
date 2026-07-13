<?php
/**
 * market/place_order.php — Place Market Order
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Order.php';
require_once __DIR__ . '/../classes/Payment.php';

requireLogin();

class OrderService extends Database {
    public function __construct() { $this->connect(); }
    public function getConnection() { return $this->conn; }
}

$service = new OrderService();
$db = $service->getConnection();
$errors = [];

$listingId = (int)($_GET['id'] ?? 0);
if (!$listingId) {
    setFlash('danger', 'Bidhaa haikutajwa.');
    redirect('browse.php');
}

// Fetch listing
$stmt = $db->prepare("
    SELECT ml.*,
           c.name as crop_name_enc,
           pp.product_name as proc_name_enc,
           u.full_name as seller_name_enc,
           u.phone as seller_phone_enc
    FROM `market_listings` ml
    LEFT JOIN `harvests` h ON (ml.product_type = 'harvest' AND ml.product_id = h.id)
    LEFT JOIN `crops` c ON h.crop_id = c.id
    LEFT JOIN `processed_products` pp ON (ml.product_type = 'processed' AND ml.product_id = pp.id)
    JOIN `users` u ON ml.seller_id = u.id
    WHERE ml.id = :lid LIMIT 1
");
$stmt->execute([':lid' => $listingId]);
$row = $stmt->fetch();

if (!$row) {
    setFlash('danger', 'Bidhaa haikupatikana.');
    redirect('browse.php');
}

$productName = '';
if ($service->decrypt($row['product_type']) === 'harvest') {
    $productName = $service->decrypt($row['crop_name_enc']);
} else {
    $productName = $service->decrypt($row['proc_name_enc']);
}

$listing = [
    'id'           => $row['id'],
    'seller_id'    => $row['seller_id'],
    'seller_name'  => $service->decrypt($row['seller_name_enc']),
    'seller_phone' => $service->decrypt($row['seller_phone_enc']),
    'product_name' => $productName,
    'quantity_kg'  => (float)$service->decrypt($row['quantity_kg']),
    'price_per_kg' => (float)$service->decrypt($row['price_per_kg']),
    'location'     => $service->decrypt($row['location']),
];

$service->logAudit('users', $row['seller_id'], 'full_name', 'DECRYPT', $row['seller_name_enc']);
$service->logAudit('users', $row['seller_id'], 'phone', 'DECRYPT', $row['seller_phone_enc']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Tokeni ya usalama imekosekana.';
    } else {
        $quantityKg = trim($_POST['quantity_kg'] ?? '');
        $address    = trim($_POST['delivery_address'] ?? '');
        $payMethod  = trim($_POST['payment_method'] ?? '');
        $txnId      = trim($_POST['transaction_id'] ?? '');

        if (!is_numeric($quantityKg) || $quantityKg <= 0) $errors[] = 'Kiasi lazima kiwe zaidi ya 0.';
        if ($quantityKg > $listing['quantity_kg']) $errors[] = 'Kiasi ulichoomba kinazidi kiasi kinachopatikana.';
        if (empty($address)) $errors[] = 'Anwani ya kupeleka mzao inahitajika.';
        if (empty($payMethod)) $errors[] = 'Tafadhali chagua njia ya malipo.';
        if (empty($txnId)) $errors[] = 'Tafadhali weka namba ya mwamala (Transaction ID).';

        if (!$errors) {
            $db->beginTransaction();
            try {
                $totalPrice = $quantityKg * $listing['price_per_kg'];
                $buyerId = $_SESSION['user_id'];

                // Place Order
                $orderObj = new Order();
                $orderId = $orderObj->placeOrder($buyerId, $listingId, $quantityKg, $totalPrice, $address);

                // Record Payment
                $payObj = new Payment();
                $payObj->recordPayment($orderId, $totalPrice, $payMethod, $txnId, date('Y-m-d H:i:s'));

                // Update Order Status to 'confirmed' / 'paid'
                $orderObj->updateStatus($orderId, 'confirmed');

                // Adjust listing quantity
                $newQty = $listing['quantity_kg'] - $quantityKg;
                if ($newQty <= 0) {
                    $encStatus = $service->encrypt('sold');
                    $encQty    = $service->encrypt('0');
                    $upd = $db->prepare("UPDATE `market_listings` SET `quantity_kg` = :q, `status` = :s WHERE `id` = :id");
                    $upd->execute([':q' => $encQty, ':s' => $encStatus, ':id' => $listingId]);
                    $service->logAudit('market_listings', $listingId, 'status', 'ENCRYPT', $encStatus);
                } else {
                    $encQty = $service->encrypt($newQty);
                    $upd = $db->prepare("UPDATE `market_listings` SET `quantity_kg` = :q WHERE `id` = :id");
                    $upd->execute([':q' => $encQty, ':id' => $listingId]);
                    $service->logAudit('market_listings', $listingId, 'quantity_kg', 'ENCRYPT', $encQty);
                }

                $db->commit();
                setFlash('success', "Agizo lako limewekwa kwa mafanikio! Jumla ya gharama: " . formatTshs($totalPrice));
                redirect('my_orders.php');
            } catch (Exception $e) {
                $db->rollBack();
                $errors[] = 'Imeshindwa kukamilisha agizo: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Agiza Bidhaa';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="kh-hero mb-4">
  <h1>Checkout</h1>
  <p>Thibitisha agizo lako la: <strong><?= escape($listing['product_name']) ?></strong></p>
</div>

<?php if ($errors): ?>
  <div class="alert alert-danger"><?php foreach ($errors as $e): ?><div>&#9888; <?= escape($e) ?></div><?php endforeach; ?></div>
<?php endif; ?>

<div class="row g-4">
  <!-- Checkout Form -->
  <div class="col-lg-7">
    <div class="kh-card">
      <div class="kh-card-header"><i class="fas fa-shopping-basket me-2"></i>Fomu ya Kukamilisha Agizo</div>
      <div class="card-body p-4">
        <form method="POST">
          <?= csrfField() ?>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Kiasi cha Kununua (kg) *</label>
              <input type="number" name="quantity_kg" id="qty_kg" class="form-control kh-input"
                     value="<?= escape($_POST['quantity_kg'] ?? $listing['quantity_kg']) ?>"
                     oninput="calcTotal()" max="<?= $listing['quantity_kg'] ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Bei kwa Kilo</label>
              <input type="text" class="form-control kh-input" value="<?= formatTshs($listing['price_per_kg']) ?>" disabled>
            </div>
          </div>

          <div class="mb-3 mt-3">
            <label class="form-label fw-semibold">Anwani ya Kupelekwa Bidhaa *</label>
            <textarea name="delivery_address" class="form-control kh-input" rows="2"
                      placeholder="Weka mahali pa kuleta mzigo, mfano: Kariakoo, Mtaa wa Lumumba, Dar" required><?= escape($_POST['delivery_address'] ?? '') ?></textarea>
          </div>

          <div class="kh-card p-3 mb-3 bg-light">
            <h6 class="fw-bold mb-2"><i class="fas fa-wallet text-success me-2"></i>Maelezo ya Malipo (Lipia Hapa)</h6>
            <div class="p-2 mb-3 bg-white border rounded">
              <div class="small text-muted mb-1">Mlipaji (Muuzaji):</div>
              <div class="fw-bold text-dark"><?= escape($listing['seller_name']) ?></div>
              <div class="small text-muted mt-2 mb-1">Namba ya Simu ya Muuzaji (M-Pesa/Tigo Pesa/Airtel Money/Halopesa):</div>
              <div class="fw-bold text-primary fs-5"><i class="fas fa-phone-alt me-1"></i><?= escape($listing['seller_phone']) ?></div>
            </div>
            <p class="small text-muted mb-2">Tuma kiasi cha malipo kwenda nambari hiyo ya muuzaji hapo juu, kisha chagua njia ya malipo na ujaze namba ya muamala (Transaction ID) hapa chini.</p>
            <div class="row g-2">
              <div class="col-md-6">
                <label class="form-label fw-semibold">Njia ya Malipo *</label>
                <select name="payment_method" class="form-select kh-input" required>
                  <option value="">-- Chagua Njia --</option>
                  <option value="M-Pesa"    <?= ($_POST['payment_method']??'')==='M-Pesa'?'selected':'' ?>>M-Pesa</option>
                  <option value="Tigo Pesa" <?= ($_POST['payment_method']??'')==='Tigo Pesa'?'selected':'' ?>>Tigo Pesa</option>
                  <option value="Airtel Money" <?= ($_POST['payment_method']??'')==='Airtel Money'?'selected':'' ?>>Airtel Money</option>
                  <option value="HaloPesa"  <?= ($_POST['payment_method']??'')==='HaloPesa'?'selected':'' ?>>HaloPesa</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Nambari ya Muamala (Txn ID) *</label>
                <input type="text" name="transaction_id" class="form-control kh-input"
                       value="<?= escape($_POST['transaction_id'] ?? '') ?>" placeholder="mfano: PP12AA34" required>
              </div>
            </div>
          </div>

          <!-- Price Summary -->
          <div class="p-3 bg-light rounded border mb-4">
            <span class="text-muted d-block small">Jumla ya Bei:</span>
            <span class="fs-3 fw-bold text-success" id="total_price">Tshs 0.00</span>
          </div>

          <button type="submit" class="btn kh-btn-primary w-100 btn-lg"><i class="fas fa-check-circle me-2"></i>Kamilisha Agizo</button>
        </form>
      </div>
    </div>
  </div>

  <!-- Listing Details -->
  <div class="col-lg-5">
    <div class="kh-card">
      <div class="kh-card-header"><i class="fas fa-info-circle me-2"></i>Maelezo ya Bidhaa</div>
      <div class="card-body p-3">
        <ul class="list-group list-group-flush">
          <li class="list-group-item d-flex justify-content-between"><span>Jina:</span> <strong><?= escape($listing['product_name']) ?></strong></li>
          <li class="list-group-item d-flex justify-content-between"><span>Muuzaji:</span> <strong><?= escape($listing['seller_name']) ?></strong></li>
          <li class="list-group-item d-flex justify-content-between"><span>Inapatikana:</span> <strong><?= escape($listing['quantity_kg']) ?> kg</strong></li>
          <li class="list-group-item d-flex justify-content-between"><span>Eneo la mzigo:</span> <strong><?= escape($listing['location']) ?></strong></li>
        </ul>
      </div>
    </div>
  </div>
</div>

<script>
const pricePerKg = <?= $listing['price_per_kg'] ?>;
function calcTotal() {
    const qty = parseFloat(document.getElementById('qty_kg').value || 0);
    const total = qty * pricePerKg;
    document.getElementById('total_price').textContent = 'Tshs ' + total.toLocaleString('en', { minimumFractionDigits: 2 });
}
window.addEventListener('DOMContentLoaded', calcTotal);
</script>

<?php require_once __DIR__ . '/../includes/header.php'; ?>
