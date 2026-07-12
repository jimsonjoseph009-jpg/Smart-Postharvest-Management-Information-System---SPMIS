<?php
/**
 * market/browse.php — Marketplace Catalog
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../classes/Database.php';

// Allows anyone logged in to browse.
requireLogin();

class MarketCatalog extends Database {
    public function __construct() { $this->connect(); }
    public function getConnection() { return $this->conn; }
}

$catalog = new MarketCatalog();
$db = $catalog->getConnection();

// Fetch listings with details
$stmt = $db->query("
    SELECT ml.*,
           c.name as crop_name_enc,
           pp.product_name as proc_name_enc,
           u.full_name as seller_name_enc
    FROM `market_listings` ml
    LEFT JOIN `harvests` h ON (ml.product_type = 'harvest' AND ml.product_id = h.id)
    LEFT JOIN `crops` c ON h.crop_id = c.id
    LEFT JOIN `processed_products` pp ON (ml.product_type = 'processed' AND ml.product_id = pp.id)
    JOIN `users` u ON ml.seller_id = u.id
");
$rows = $stmt->fetchAll();

$listings = [];
foreach ($rows as $row) {
    $status = $catalog->decrypt($row['status']);
    if ($status !== 'active') continue;

    $productName = '';
    if ($catalog->decrypt($row['product_type']) === 'harvest') {
        $productName = $catalog->decrypt($row['crop_name_enc']);
    } else {
        $productName = $catalog->decrypt($row['proc_name_enc']);
    }

    $listings[] = [
        'id'           => $row['id'],
        'seller_id'    => $row['seller_id'],
        'seller_name'  => $catalog->decrypt($row['seller_name_enc']),
        'seller_type'  => $catalog->decrypt($row['seller_type']),
        'product_type' => $catalog->decrypt($row['product_type']),
        'product_name' => $productName,
        'quantity_kg'  => $catalog->decrypt($row['quantity_kg']),
        'price_per_kg' => $catalog->decrypt($row['price_per_kg']),
        'location'     => $catalog->decrypt($row['location']),
        'created_at'   => $row['created_at']
    ];
}

$pageTitle = 'Soko la Mazao';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="kh-hero mb-4">
  <h1>🛒 Soko la Mazao na Bidhaa</h1>
  <p>Nunua mazao ghafi au yaliyosindikwa moja kwa moja kutoka kwa wakulima na wasindikaji</p>
</div>

<div class="row mb-4">
  <div class="col-md-8 mx-auto">
    <div class="kh-search">
      <i class="fas fa-search search-icon"></i>
      <input type="text" class="form-control kh-input py-2" id="marketSearch"
             placeholder="Tafuta kwa jina la bidhaa, muuzaji au mkoa..." onkeyup="filterListings()">
    </div>
  </div>
</div>

<div class="row g-3" id="listingsContainer">
  <?php if ($listings): ?>
    <?php foreach ($listings as $l): ?>
      <div class="col-md-6 col-lg-4 listing-card-item"
           data-search="<?= strtolower($l['product_name'] . ' ' . $l['seller_name'] . ' ' . $l['location']) ?>">
        <div class="kh-card h-100 d-flex flex-column">
          <div class="kh-card-header d-flex justify-content-between align-items-center">
            <span><?= escape($l['product_name']) ?></span>
            <span class="badge bg-success small"><?= escape($l['product_type'] === 'harvest' ? 'Mavuno Ghafi' : 'Yaliyosindikwa') ?></span>
          </div>
          <div class="card-body d-flex flex-column justify-content-between p-3">
            <div>
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted small">Muuzaji:</span>
                <span class="fw-semibold small"><?= escape($l['seller_name']) ?> (<?= escape(roleLabel($l['seller_type'])) ?>)</span>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted small">Kiasi kinachopatikana:</span>
                <span class="fw-semibold text-dark"><?= escape($l['quantity_kg']) ?> kg</span>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted small">Mahali:</span>
                <span class="fw-semibold"><i class="fas fa-map-marker-alt text-danger me-1"></i><?= escape($l['location']) ?></span>
              </div>
              <hr>
              <div class="d-flex justify-content-between align-items-center mt-3">
                <span class="text-muted small">Bei kwa Kilo:</span>
                <span class="fs-5 fw-bold text-success"><?= formatTshs($l['price_per_kg']) ?></span>
              </div>
            </div>
            <div class="d-grid mt-4">
              <?php if ($_SESSION['user_id'] != $l['seller_id']): ?>
                <a href="place_order.php?id=<?= $l['id'] ?>" class="btn kh-btn-primary">
                  <i class="fas fa-shopping-cart me-2"></i>Nunua Sasa
                </a>
              <?php else: ?>
                <button class="btn btn-outline-secondary" disabled>Bidhaa Yako</button>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <div class="col-12 text-center py-5">
      <div class="empty-state">
        <span class="empty-icon">🛒</span>
        <p>Hakuna bidhaa zilizopo sokoni kwa sasa.</p>
      </div>
    </div>
  <?php endif; ?>
</div>

<script>
function filterListings() {
    const q = document.getElementById('marketSearch').value.toLowerCase();
    const items = document.querySelectorAll('.listing-card-item');
    items.forEach(item => {
        const text = item.dataset.search;
        item.style.display = text.includes(q) ? '' : 'none';
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
