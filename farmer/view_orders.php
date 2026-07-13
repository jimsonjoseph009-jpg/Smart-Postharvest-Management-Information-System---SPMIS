<?php
/**
 * farmer/view_orders.php — view and manage marketplace orders for farmer
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../classes/Farmer.php';

requireRole(['farmer']);

$farmer = new Farmer();
$farmerData = $farmer->findByUserId($_SESSION['user_id']);
if (!$farmerData) {
    setFlash('danger', 'Mkulima hajapatikana.');
    redirect('dashboard.php');
}
$db = $farmer->getConnection();

// Handle complete request
if (isset($_GET['complete'])) {
    $orderId = (int)$_GET['complete'];
    // Verify ownership of the listing
    $stmt = $db->prepare("
        SELECT o.id, ml.seller_id
        FROM `orders` o
        JOIN `market_listings` ml ON o.listing_id = ml.id
        WHERE o.id = ?
    ");
    $stmt->execute([$orderId]);
    $orderCheck = $stmt->fetch();
    if ($orderCheck && $orderCheck['seller_id'] == $_SESSION['user_id']) {
        $encStatus = $farmer->encrypt('completed');
        $upd = $db->prepare("UPDATE `orders` SET `status` = ? WHERE id = ?");
        $upd->execute([$encStatus, $orderId]);
        $farmer->logAudit('orders', $orderId, 'status', 'ENCRYPT', $encStatus);
        
        $farmer->logSystemAction($_SESSION['user_id'], "Ameashiria uwasilishaji wa agizo umekamilika (Completed Order ID: {$orderId})");
        setFlash('success', "Agizo #{$orderId} limeashiriwa kuwa limekamilika na kuwasilishwa!");
    } else {
        setFlash('danger', "Huna ruhusa ya kudhibiti agizo hili.");
    }
    redirect('view_orders.php');
}

// Fetch all orders for farmer's listings
$orders = [];
$stmt = $db->prepare("
    SELECT o.id, o.quantity_kg, o.total_price, o.status as order_status_enc, o.created_at, o.delivery_address as enc_addr,
           u.id as buyer_user_id, u.full_name as buyer_name_enc, u.phone as buyer_phone_enc,
           ml.product_type as enc_ptype,
           c.name as crop_name_enc,
           pp.product_name as proc_name_enc,
           p.payment_method as enc_pm, p.transaction_id as enc_tx
    FROM `orders` o
    JOIN `market_listings` ml ON o.listing_id = ml.id
    JOIN `users` u ON o.buyer_id = u.id
    LEFT JOIN `payments` p ON o.id = p.order_id
    LEFT JOIN `harvests` h ON (ml.product_type = 'harvest' AND ml.product_id = h.id)
    LEFT JOIN `crops` c ON h.crop_id = c.id
    LEFT JOIN `processed_products` pp ON (ml.product_type = 'processed' AND ml.product_id = pp.id)
    WHERE ml.seller_id = ?
    ORDER BY o.id DESC
");
$stmt->execute([$_SESSION['user_id']]);
foreach ($stmt->fetchAll() as $row) {
    $prodName = '';
    if ($farmer->decrypt($row['enc_ptype']) === 'harvest') {
        $prodName = $farmer->decrypt($row['crop_name_enc']);
    } else {
        $prodName = $farmer->decrypt($row['proc_name_enc']);
    }
    
    $orders[] = [
        'id'               => $row['id'],
        'product'          => $prodName,
        'quantity'         => $farmer->decrypt($row['quantity_kg']),
        'total_price'      => $farmer->decrypt($row['total_price']),
        'delivery_address' => $farmer->decrypt($row['enc_addr']),
        'buyer_name'       => $farmer->decrypt($row['buyer_name_enc']),
        'buyer_phone'      => $farmer->decrypt($row['buyer_phone_enc']),
        'payment_method'   => $row['enc_pm'] ? $farmer->decrypt($row['enc_pm']) : 'N/A',
        'transaction_id'   => $row['enc_tx'] ? $farmer->decrypt($row['enc_tx']) : 'N/A',
        'status'           => $farmer->decrypt($row['order_status_enc']),
        'created_at'       => $row['created_at']
    ];
    // Log decryption audits
    $farmer->logAudit('orders', $row['id'], 'status', 'DECRYPT', $row['order_status_enc']);
    $farmer->logAudit('orders', $row['id'], 'quantity_kg', 'DECRYPT', $row['quantity_kg']);
    $farmer->logAudit('orders', $row['id'], 'total_price', 'DECRYPT', $row['total_price']);
    $farmer->logAudit('orders', $row['id'], 'delivery_address', 'DECRYPT', $row['enc_addr']);
    $farmer->logAudit('users', $row['buyer_user_id'], 'full_name', 'DECRYPT', $row['buyer_name_enc']);
    $farmer->logAudit('users', $row['buyer_user_id'], 'phone', 'DECRYPT', $row['buyer_phone_enc']);
    if ($row['enc_pm']) $farmer->logAudit('payments', $row['id'], 'payment_method', 'DECRYPT', $row['enc_pm']);
    if ($row['enc_tx']) $farmer->logAudit('payments', $row['id'], 'transaction_id', 'DECRYPT', $row['enc_tx']);
}

$pageTitle = 'Maagizo ya Mauzo ya Sokoni';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="kh-hero mb-4">
  <h1>🛒 Mauzo ya Sokoni (Orders)</h1>
  <p>Tazama wateja walioweka maagizo ya kununua mazao yako na udhibiti uwasilishaji</p>
</div>

<div class="kh-card animate-in">
  <div class="kh-card-header d-flex justify-content-between align-items-center">
    <span><i class="fas fa-receipt me-2"></i>Maagizo Yote ya Mauzo</span>
    <input type="text" class="form-control kh-input w-25 btn-sm" id="orderSearch" placeholder="🔍 Tafuta hapa..." onkeyup="liveSearch()">
  </div>
  <div class="card-body p-0">
    <?php if ($orders): ?>
      <div class="table-responsive">
        <table class="kh-table" id="ordersTable">
          <thead>
            <tr>
              <th>ID ya Agizo</th>
              <th>Bidhaa</th>
              <th>Kiasi (kg)</th>
              <th>Jumla (Tshs)</th>
              <th>Mteja (Mnunuzi)</th>
              <th>Anwani ya Kupelekewa</th>
              <th>Malipo na Muamala</th>
              <th>Hali ya Agizo</th>
              <th>Hatua</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($orders as $o): ?>
              <tr>
                <td>#<?= $o['id'] ?></td>
                <td><strong><?= escape($o['product']) ?></strong></td>
                <td><?= escape($o['quantity']) ?> kg</td>
                <td><?= formatTshs($o['total_price']) ?></td>
                <td>
                  <strong><?= escape($o['buyer_name']) ?></strong><br>
                  <a href="tel:<?= escape($o['buyer_phone']) ?>" class="btn btn-sm btn-light text-primary mt-1"><i class="fas fa-phone-alt me-1"></i><?= escape($o['buyer_phone']) ?></a>
                </td>
                <td><span class="small text-muted"><?= escape($o['delivery_address']) ?></span></td>
                <td>
                  <span class="badge bg-info mb-1"><?= escape($o['payment_method']) ?></span><br>
                  <span class="small text-muted">ID:</span> <code><?= escape($o['transaction_id']) ?></code>
                </td>
                <td><?= statusBadge($o['status']) ?></td>
                <td>
                  <?php if (strtolower($o['status']) !== 'completed'): ?>
                    <a href="view_orders.php?complete=<?= $o['id'] ?>" class="btn btn-sm btn-outline-success" onclick="return confirm('Je, una uhakika kuwa umepokea malipo na kuwasilisha bidhaa hii kwa mteja?');">
                      <i class="fas fa-check me-1"></i>Kamilisha Delivery
                    </a>
                  <?php else: ?>
                    <span class="text-success small"><i class="fas fa-check-double me-1"></i>Imekamilika</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <span class="empty-icon">🛒</span>
        <p>Bado hakuna mteja yeyote aliyenunua bidhaa zako sokoni.</p>
        <a href="list_product.php" class="btn kh-btn-primary">Weka Bidhaa Sokoni</a>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
function liveSearch() {
    const input = document.getElementById("orderSearch");
    const filter = input.value.toLowerCase();
    const table = document.getElementById("ordersTable");
    const tr = table.getElementsByTagName("tr");

    for (let i = 1; i < tr.length; i++) {
        let match = false;
        const tds = tr[i].getElementsByTagName("td");
        for (let j = 0; j < tds.length; j++) {
            if (tds[j]) {
                const txtValue = tds[j].textContent || tds[j].innerText;
                if (txtValue.toLowerCase().indexOf(filter) > -1) {
                    match = true;
                    break;
                }
            }
        }
        tr[i].style.display = match ? "" : "none";
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
