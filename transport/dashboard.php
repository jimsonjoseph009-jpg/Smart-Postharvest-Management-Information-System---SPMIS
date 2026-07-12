<?php
/**
 * transport/dashboard.php — Transport Provider Dashboard
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../classes/TransportVehicle.php';

requireRole(['transport_provider','admin']);

$tv = new TransportVehicle();
$db = $tv->getConnection();
$userId = (int)$_SESSION['user_id'];

// Get stats
$stmt = $db->prepare("SELECT COUNT(*) FROM transport_vehicles WHERE owner_id = ?");
$stmt->execute([$userId]); $countVehicles = $stmt->fetchColumn();

$stmt = $db->prepare("
    SELECT COUNT(*) FROM transport_requests tr
    JOIN transport_vehicles tv ON tr.vehicle_id = tv.id
    WHERE tv.owner_id = ?
");
$stmt->execute([$userId]); $countRequests = $stmt->fetchColumn();

// Fetch transport requests
$stmt = $db->prepare("
    SELECT tr.*, tv.plate_number as enc_plate, u.full_name as enc_uname
    FROM transport_requests tr
    JOIN transport_vehicles tv ON tr.vehicle_id = tv.id
    JOIN farmers fm ON tr.farmer_id = fm.id
    JOIN users u ON fm.user_id = u.id
    WHERE tv.owner_id = ?
    ORDER BY tr.id DESC LIMIT 15
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

// Fetch vehicles
$stmt = $db->prepare("SELECT * FROM transport_vehicles WHERE owner_id = ?");
$stmt->execute([$userId]);
$vehicles = [];
foreach ($stmt->fetchAll() as $row) {
    $vehicles[] = [
        'id'           => $row['id'],
        'vehicle_type' => $tv->decrypt($row['vehicle_type']),
        'plate_number' => $tv->decrypt($row['plate_number']),
        'capacity_kg'  => $tv->decrypt($row['capacity_kg']),
        'available'    => $tv->decrypt($row['available']),
        'location'     => $tv->decrypt($row['location']),
        'price_per_km' => $tv->decrypt($row['price_per_km']),
    ];
}

// Toggle vehicle availability
if (isset($_GET['toggle_id'])) {
    $targetId = (int)$_GET['toggle_id'];
    $vehicleObj = $tv->findById($targetId);
    if ($vehicleObj && $vehicleObj->getOwnerId() == $userId) {
        $newStatus = strtolower($vehicleObj->getAvailable()) === 'yes' ? 'no' : 'yes';
        $vehicleObj->toggleAvailability($newStatus);
        setFlash('success', 'Hali ya gari imebadilishwa!');
        redirect('dashboard.php');
    }
}

$pageTitle = 'Dashibodi ya Usafiri';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="kh-hero mb-4">
  <h1>🚛 Dashibodi ya Msafirishaji</h1>
  <p>Usimamizi wa safari na magari yako ya usafirishaji | <?= date('d/m/Y') ?></p>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-6">
    <div class="stat-card"><span class="stat-icon">🚛</span>
      <div class="stat-value" data-target="<?= $countVehicles ?>"><?= $countVehicles ?></div>
      <div class="stat-label">Magari Yaliyosajiliwa</div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="stat-card amber"><span class="stat-icon">📋</span>
      <div class="stat-value" data-target="<?= $countRequests ?>"><?= $countRequests ?></div>
      <div class="stat-label">Maombi ya Usafiri</div>
    </div>
  </div>
</div>

<div class="d-flex gap-2 mb-4">
  <a href="add_vehicle.php" class="btn kh-btn-primary"><i class="fas fa-plus me-1"></i>Sajili Gari Mpya</a>
  <a href="view_requests.php" class="btn btn-outline-secondary"><i class="fas fa-list me-1"></i>Maombi Yote</a>
</div>

<!-- Vehicles List -->
<div class="kh-card mb-4">
  <div class="kh-card-header"><i class="fas fa-truck-moving me-2"></i>Magari Yako</div>
  <div class="card-body p-0">
    <?php if ($vehicles): ?>
      <div class="table-responsive">
        <table class="kh-table">
          <thead><tr><th>#</th><th>Aina</th><th>Nambari ya Gari</th><th>Uwezo (kg)</th><th>Bei/km</th><th>Eneo</th><th>Inapatikana?</th><th>Hatua</th></tr></thead>
          <tbody>
            <?php foreach ($vehicles as $v): ?>
            <tr>
              <td><?= $v['id'] ?></td>
              <td><?= escape($v['vehicle_type']) ?></td>
              <td><strong><?= escape($v['plate_number']) ?></strong></td>
              <td><?= escape($v['capacity_kg']) ?> kg</td>
              <td><?= formatTshs($v['price_per_km']) ?></td>
              <td><?= escape($v['location']) ?></td>
              <td>
                <span class="badge bg-<?= strtolower($v['available'])==='yes'?'success':'danger' ?>">
                  <?= strtolower($v['available'])==='yes'?'Ndio':'Hapana' ?>
                </span>
              </td>
              <td>
                <a href="dashboard.php?toggle_id=<?= $v['id'] ?>" class="btn btn-sm btn-outline-warning">
                  <i class="fas fa-sync-alt me-1"></i>Badili Hali
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty-state"><span class="empty-icon">🚛</span><p>Hujasajili gari lolote bado.</p></div>
    <?php endif; ?>
  </div>
</div>

<!-- Requests list -->
<div class="kh-card">
  <div class="kh-card-header"><i class="fas fa-clipboard-list me-2"></i>Maombi ya Hivi Karibuni</div>
  <div class="card-body p-0">
    <?php if ($requests): ?>
      <div class="table-responsive">
        <table class="kh-table">
          <thead><tr><th>#</th><th>Gari</th><th>Mkulima</th><th>Kutoka</th><th>Kwenda</th><th>Umbali</th><th>Uzito</th><th>Gharama</th><th>Hali</th><th>Hatua</th></tr></thead>
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
              <td><?= statusBadge($r['status']) ?></td>
              <td>
                <a href="approve_request.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-check"></i></a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty-state"><span class="empty-icon">📭</span><p>Hakuna maombi ya safari yaliyotumwa kwako bado.</p></div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
