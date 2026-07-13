<?php
/**
 * farmer/dashboard.php — Mkulima Dashboard
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../classes/Farmer.php';

requireRole(['farmer']);

$farmer = new Farmer();
$farmerData = $farmer->findByUserId($_SESSION['user_id']);
$farmerId = $farmerData ? $farmerData->getFarmId() : 0;

// Count summaries using direct DB for stats
$db = $farmer->getConnection();

$countHarvests = 0;
$countStorage  = 0;
$countTransport= 0;
$countProcessing = 0;

if ($farmerId) {
    $s = $db->prepare("SELECT COUNT(*) FROM harvests WHERE farmer_id = ?");
    $s->execute([$farmerId]); $countHarvests = $s->fetchColumn();

    $s = $db->prepare("SELECT COUNT(*) FROM storage_requests WHERE farmer_id = ?");
    $s->execute([$farmerId]); $countStorage = $s->fetchColumn();

    $s = $db->prepare("SELECT COUNT(*) FROM transport_requests WHERE farmer_id = ?");
    $s->execute([$farmerId]); $countTransport = $s->fetchColumn();

    $s = $db->prepare("SELECT COUNT(*) FROM processing_requests WHERE farmer_id = ?");
    $s->execute([$farmerId]); $countProcessing = $s->fetchColumn();
}

$harvests = $farmerId ? $farmerData->viewHarvests() : [];

// Fetch recent marketplace orders for farmer's listings
$recentOrders = [];
if ($_SESSION['user_id']) {
    $stmt = $db->prepare("
        SELECT o.id, o.quantity_kg, o.total_price, o.status as order_status_enc, o.created_at,
               u.id as buyer_user_id, u.full_name as buyer_name_enc, u.phone as buyer_phone_enc,
               ml.product_type as enc_ptype,
               c.name as crop_name_enc,
               pp.product_name as proc_name_enc
        FROM `orders` o
        JOIN `market_listings` ml ON o.listing_id = ml.id
        JOIN `users` u ON o.buyer_id = u.id
        LEFT JOIN `harvests` h ON (ml.product_type = 'harvest' AND ml.product_id = h.id)
        LEFT JOIN `crops` c ON h.crop_id = c.id
        LEFT JOIN `processed_products` pp ON (ml.product_type = 'processed' AND ml.product_id = pp.id)
        WHERE ml.seller_id = ?
        ORDER BY o.id DESC LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id']]);
    foreach ($stmt->fetchAll() as $row) {
        $prodName = '';
        if ($farmer->decrypt($row['enc_ptype']) === 'harvest') {
            $prodName = $farmer->decrypt($row['crop_name_enc']);
        } else {
            $prodName = $farmer->decrypt($row['proc_name_enc']);
        }
        $recentOrders[] = [
            'id'          => $row['id'],
            'product'     => $prodName,
            'quantity'    => $farmer->decrypt($row['quantity_kg']),
            'total_price' => $farmer->decrypt($row['total_price']),
            'buyer_name'  => $farmer->decrypt($row['buyer_name_enc']),
            'buyer_phone' => $farmer->decrypt($row['buyer_phone_enc']),
            'status'      => $farmer->decrypt($row['order_status_enc']),
            'created_at'  => $row['created_at']
        ];
        $farmer->logAudit('orders', $row['id'], 'status', 'DECRYPT', $row['order_status_enc']);
        $farmer->logAudit('orders', $row['id'], 'quantity_kg', 'DECRYPT', $row['quantity_kg']);
        $farmer->logAudit('orders', $row['id'], 'total_price', 'DECRYPT', $row['total_price']);
        $farmer->logAudit('users', $row['buyer_user_id'], 'full_name', 'DECRYPT', $row['buyer_name_enc']);
        $farmer->logAudit('users', $row['buyer_user_id'], 'phone', 'DECRYPT', $row['buyer_phone_enc']);
    }
}

$pageTitle = 'Dashibodi ya Mkulima';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="kh-hero animate-in">
  <h1 class="mb-1">Karibu, <?= escape($_SESSION['full_name']) ?>! 🌱</h1>
  <p>Dashibodi yako ya usimamizi wa mavuno | <?= date('d/m/Y') ?></p>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <a href="view_harvests.php" class="text-decoration-none">
      <div class="stat-card animate-in">
        <span class="stat-icon">🌾</span>
        <div class="stat-value" data-target="<?= $countHarvests ?>"><?= $countHarvests ?></div>
        <div class="stat-label">Jumla ya Mavuno</div>
      </div>
    </a>
  </div>
  <div class="col-6 col-md-3">
    <a href="view_requests.php?tab=storage" class="text-decoration-none">
      <div class="stat-card amber animate-in">
        <span class="stat-icon">🏭</span>
        <div class="stat-value" data-target="<?= $countStorage ?>"><?= $countStorage ?></div>
        <div class="stat-label">Maombi ya Uhifadhi</div>
      </div>
    </a>
  </div>
  <div class="col-6 col-md-3">
    <a href="view_requests.php?tab=transport" class="text-decoration-none">
      <div class="stat-card info animate-in">
        <span class="stat-icon">🚛</span>
        <div class="stat-value" data-target="<?= $countTransport ?>"><?= $countTransport ?></div>
        <div class="stat-label">Maombi ya Usafiri</div>
      </div>
    </a>
  </div>
  <div class="col-6 col-md-3">
    <a href="view_requests.php?tab=processing" class="text-decoration-none">
      <div class="stat-card earth animate-in">
        <span class="stat-icon">⚙️</span>
        <div class="stat-value" data-target="<?= $countProcessing ?>"><?= $countProcessing ?></div>
        <div class="stat-label">Maombi ya Usindikaji</div>
      </div>
    </a>
  </div>
</div>

<!-- Quick Actions -->
<div class="row g-3 mb-4">
  <div class="col-12">
    <div class="kh-card">
      <div class="kh-card-header"><i class="fas fa-bolt me-2"></i>Vitendo vya Haraka</div>
      <div class="card-body p-3">
        <div class="d-flex flex-wrap gap-2">
          <a href="add_harvest.php"        class="btn kh-btn-primary"><i class="fas fa-plus me-1"></i>Ongeza Mavuno</a>
          <a href="request_storage.php"    class="btn kh-btn-amber"><i class="fas fa-warehouse me-1"></i>Omba Uhifadhi</a>
          <a href="request_transport.php"  class="btn btn-outline-secondary"><i class="fas fa-truck me-1"></i>Omba Usafiri</a>
          <a href="request_processing.php" class="btn btn-outline-secondary"><i class="fas fa-industry me-1"></i>Omba Usindikaji</a>
          <a href="list_product.php"       class="btn btn-outline-success"><i class="fas fa-store me-1"></i>Weka Sokoni</a>
          <a href="view_requests.php"      class="btn btn-outline-info"><i class="fas fa-tasks me-1"></i>Maombi Yangu</a>
          <a href="view_orders.php"        class="btn btn-outline-success"><i class="fas fa-receipt me-1"></i>Mauzo Yangu (Soko)</a>
          <a href="../reports/generate.php?type=harvest" class="btn btn-outline-info"><i class="fas fa-chart-bar me-1"></i>Ripoti</a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Recent Harvests Table -->
<div class="kh-card">
  <div class="kh-card-header d-flex justify-content-between align-items-center">
    <span><i class="fas fa-seedling me-2"></i>Mavuno ya Hivi Karibuni</span>
    <a href="view_harvests.php" class="btn btn-sm btn-light">Tazama Yote</a>
  </div>
  <div class="card-body p-0">
    <?php if ($harvests): ?>
    <div class="mb-2 p-3">
      <input type="text" class="form-control kh-input" data-search-table="harvestTable"
             placeholder="&#128269; Tafuta mavuno...">
    </div>
    <div class="table-responsive">
      <table class="kh-table" id="harvestTable">
        <thead>
          <tr>
            <th>#</th><th>Zao</th><th>Kiasi (kg)</th><th>Tarehe</th><th>Daraja</th><th>Bei/kg</th><th>Thamani</th><th>Hatua</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (array_slice($harvests, 0, 10) as $h): ?>
          <tr>
            <td><?= $h['id'] ?></td>
            <td><?= escape($h['crop_name']) ?></td>
            <td><?= escape($h['quantity_kg']) ?></td>
            <td><?= escape(formatDate($h['harvest_date'])) ?></td>
            <td><?= escape($h['quality_grade']) ?></td>
            <td><?= formatTshs($h['unit_price']) ?></td>
            <td><?= formatTshs((float)$h['quantity_kg'] * (float)$h['unit_price']) ?></td>
            <td>
              <a href="view_harvests.php?edit=<?= $h['id'] ?>" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-edit"></i>
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
      <span class="empty-icon">🌱</span>
      <p>Bado hujasajili mavuno yoyote.</p>
      <a href="add_harvest.php" class="btn kh-btn-primary">Ongeza Mavuno ya Kwanza</a>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Recent Marketplace Orders Table -->
<div class="kh-card mt-4 animate-in">
  <div class="kh-card-header d-flex justify-content-between align-items-center">
    <span><i class="fas fa-shopping-bag me-2"></i>Maagizo Mapya ya Sokoni (Mauzo)</span>
    <a href="view_orders.php" class="btn btn-sm btn-light">Tazama Mauzo Yote</a>
  </div>
  <div class="card-body p-0">
    <?php if ($recentOrders): ?>
    <div class="table-responsive">
      <table class="kh-table">
        <thead>
          <tr>
            <th>ID ya Agizo</th>
            <th>Mteja (Mnunuzi)</th>
            <th>Namba ya Simu</th>
            <th>Bidhaa</th>
            <th>Kiasi (kg)</th>
            <th>Jumla (Tshs)</th>
            <th>Hali ya Agizo</th>
            <th>Tarehe</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentOrders as $ro): ?>
          <tr>
            <td>#<?= $ro['id'] ?></td>
            <td><strong><?= escape($ro['buyer_name']) ?></strong></td>
            <td><?= escape($ro['buyer_phone']) ?></td>
            <td><?= escape($ro['product']) ?></td>
            <td><?= escape($ro['quantity']) ?> kg</td>
            <td><?= formatTshs($ro['total_price']) ?></td>
            <td><?= statusBadge($ro['status']) ?></td>
            <td><?= escape(formatDate($ro['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
      <span class="empty-icon">🛒</span>
      <p>Hakuna maagizo mapya kutoka sokoni bado.</p>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
