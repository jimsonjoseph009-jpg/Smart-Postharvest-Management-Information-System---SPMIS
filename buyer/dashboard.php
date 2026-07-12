<?php
/**
 * buyer/dashboard.php — Buyer Dashboard
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../classes/Order.php';

requireRole(['buyer','admin']);

$orderObj = new Order();
$orders = $orderObj->getByBuyer($_SESSION['user_id']);

$totalOrders = count($orders);
$totalSpent = 0;
foreach ($orders as $o) {
    if (strtolower($o['status']) === 'confirmed' || strtolower($o['status']) === 'completed') {
        $totalSpent += (float)$o['total_price'];
    }
}

$pageTitle = 'Dashibodi ya Mnunuzi';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="kh-hero mb-4">
  <h1>🛒 Dashibodi ya Mnunuzi</h1>
  <p>Karibu tena, <?= escape($_SESSION['full_name']) ?> | Fuatilia bidhaa zako hapa</p>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-6">
    <div class="stat-card"><span class="stat-icon">📦</span>
      <div class="stat-value" data-target="<?= $totalOrders ?>"><?= $totalOrders ?></div>
      <div class="stat-label">Maagizo Yako</div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="stat-card info"><span class="stat-icon">💰</span>
      <div class="stat-value" data-target="<?= $totalSpent ?>"><?= $totalSpent ?></div>
      <div class="stat-label">Jumla ya Matumizi (Tshs)</div>
    </div>
  </div>
</div>

<div class="d-flex gap-2 mb-4">
  <a href="<?= BASE_URL ?>/market/browse.php" class="btn kh-btn-primary"><i class="fas fa-search me-1"></i>Nunua Bidhaa</a>
  <a href="<?= BASE_URL ?>/market/my_orders.php" class="btn btn-outline-secondary"><i class="fas fa-history me-1"></i>Maagizo Yote</a>
</div>

<div class="kh-card">
  <div class="kh-card-header"><i class="fas fa-shopping-basket me-2"></i>Maagizo ya Hivi Karibuni</div>
  <div class="card-body p-0">
    <?php if ($orders): ?>
    <div class="table-responsive">
      <table class="kh-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Mavuno / Bidhaa</th>
            <th>Kiasi</th>
            <th>Jumla Kuu</th>
            <th>Hali ya Agizo</th>
            <th>Tarehe ya Agizo</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (array_slice($orders, 0, 5) as $o): ?>
          <tr>
            <td><?= $o['id'] ?></td>
            <td>
              <strong>
                <?php
                  $db = $orderObj->getConnection();
                  $stmt = $db->prepare("
                      SELECT ml.product_type, c.name as crop_name_enc, pp.product_name as proc_name_enc
                      FROM `market_listings` ml
                      LEFT JOIN `harvests` h ON (ml.product_type = 'harvest' AND ml.product_id = h.id)
                      LEFT JOIN `crops` c ON h.crop_id = c.id
                      LEFT JOIN `processed_products` pp ON (ml.product_type = 'processed' AND ml.product_id = pp.id)
                      WHERE ml.id = :lid LIMIT 1
                  ");
                  $stmt->execute([':lid' => $o['listing_id']]);
                  $row = $stmt->fetch();
                  if ($row) {
                      $pType = $orderObj->decrypt($row['product_type']);
                      if ($pType === 'harvest') {
                          echo escape($orderObj->decrypt($row['crop_name_enc']));
                      } else {
                          echo escape($orderObj->decrypt($row['proc_name_enc']));
                      }
                  } else {
                      echo '—';
                  }
                ?>
              </strong>
            </td>
            <td><?= escape($o['quantity_kg']) ?> kg</td>
            <td><?= formatTshs($o['total_price']) ?></td>
            <td><?= statusBadge($o['status']) ?></td>
            <td><?= escape(formatDate($o['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
      <span class="empty-icon">📭</span>
      <p>Hakuna maagizo yaliyopatikana bado.</p>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
