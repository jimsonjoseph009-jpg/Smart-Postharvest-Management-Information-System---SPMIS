<?php
/**
 * transport/view_requests.php — Transport Requests Log
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../classes/TransportVehicle.php';

requireRole(['transport_provider','admin']);

$tv = new TransportVehicle();
$db = $tv->getConnection();
$userId = (int)$_SESSION['user_id'];

$stmt = $db->prepare("
    SELECT tr.*, tv.plate_number as enc_plate, u.full_name as enc_uname
    FROM transport_requests tr
    JOIN transport_vehicles tv ON tr.vehicle_id = tv.id
    JOIN farmers fm ON tr.farmer_id = fm.id
    JOIN users u ON fm.user_id = u.id
    WHERE tv.owner_id = ?
    ORDER BY tr.id DESC
");
$stmt->execute([$userId]);

$requests = [];
foreach ($stmt->fetchAll() as $r) {
    $requests[] = [
        'id'          => $r['id'],
        'plate'       => $tv->decrypt($r['enc_plate']),
        'farmer'      => $tv->decrypt($r['enc_uname']),
        'pickup'      => $tv->decrypt($r['pickup_location']),
        'delivery'    => $tv->decrypt($r['delivery_location']),
        'distance'    => $tv->decrypt($r['distance_km']),
        'quantity'    => $tv->decrypt($r['quantity_kg']),
        'cost'        => $tv->decrypt($r['total_cost']),
        'status'      => $tv->decrypt($r['status']),
        'date'        => $tv->decrypt($r['requested_date']),
    ];
}

$pageTitle = 'Maombi ya Usafiri';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="kh-hero mb-4">
  <h1>🚛 Kumbukumbu ya Safari za Usafiri</h1>
  <p>Fuatilia maombi ya safari, mizigo, na malipo ya wateja wako</p>
</div>

<div class="kh-card">
  <div class="kh-card-header d-flex justify-content-between align-items-center">
    <span><i class="fas fa-list me-2"></i>Maombi Yote</span>
    <input type="text" class="form-control kh-input w-25 btn-sm" id="reqSearch"
           placeholder="🔍 Tafuta hapa..." onkeyup="liveSearch('reqSearch','reqTable')">
  </div>
  <div class="card-body p-0">
    <?php if ($requests): ?>
    <div class="table-responsive">
      <table class="kh-table" id="reqTable">
        <thead>
          <tr>
            <th>#</th>
            <th>Namba ya Gari</th>
            <th>Mkulima</th>
            <th>Kutoka</th>
            <th>Kwenda</th>
            <th>Umbali</th>
            <th>Uzito (kg)</th>
            <th>Gharama</th>
            <th>Tarehe Safari</th>
            <th>Hali</th>
            <th>Hatua</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($requests as $r): ?>
          <tr>
            <td><?= $r['id'] ?></td>
            <td><code><?= escape($r['plate']) ?></code></td>
            <td><?= escape($r['farmer']) ?></td>
            <td><?= escape($r['pickup']) ?></td>
            <td><?= escape($r['delivery']) ?></td>
            <td><?= escape($r['distance']) ?> km</td>
            <td><?= escape($r['quantity']) ?> kg</td>
            <td><?= formatTshs($r['cost']) ?></td>
            <td><?= escape(formatDate($r['date'])) ?></td>
            <td><?= statusBadge($r['status']) ?></td>
            <td>
              <?php if (strtolower($r['status']) !== 'completed'): ?>
                <a href="approve_request.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-success">
                  <?php if (strtolower($r['status']) === 'pending'): ?>
                    <i class="fas fa-check me-1"></i>Thibitisha
                  <?php else: ?>
                    <i class="fas fa-check-double me-1"></i>Kamilisha Safari
                  <?php endif; ?>
                </a>
              <?php else: ?>
                <span class="text-muted small">Safari Imetimia</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
      <span class="empty-icon">📭</span>
      <p>Hakuna maombi ya usafiri bado.</p>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
