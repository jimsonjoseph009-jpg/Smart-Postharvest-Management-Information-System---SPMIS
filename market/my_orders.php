<?php
/**
 * market/my_orders.php — Buyer Orders Tracking
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../classes/Order.php';
require_once __DIR__ . '/../classes/Payment.php';

requireLogin();

$orderObj = new Order();
$orders = $orderObj->getByBuyer($_SESSION['user_id']);

$pageTitle = 'Maagizo Yangu';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="kh-hero mb-4">
  <h1>🛒 Maagizo Yangu</h1>
  <p>Fuatilia historia na hali ya maagizo yako ya mazao na bidhaa</p>
</div>

<div class="kh-card">
  <div class="kh-card-header"><i class="fas fa-list me-2"></i>Orodha ya Maagizo</div>
  <div class="card-body p-0">
    <?php if ($orders): ?>
    <div class="table-responsive">
      <table class="kh-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Mavuno / Bidhaa</th>
            <th>Kiasi (kg)</th>
            <th>Jumla ya Bei</th>
            <th>Mahali pa Kuleta</th>
            <th>Hali ya Agizo</th>
            <th>Tarehe</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $o): ?>
          <tr>
            <td><?= $o['id'] ?></td>
            <td>
              <strong>
                <?php
                  // Retrieve listing details to show item name
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
            <td><?= escape($o['delivery_address']) ?></td>
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
      <p>Bado hujaweka agizo lolote.</p>
      <a href="browse.php" class="btn kh-btn-primary">Nenda Sokoni sasa</a>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
