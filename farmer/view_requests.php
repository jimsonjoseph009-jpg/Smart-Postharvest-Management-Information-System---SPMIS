<?php
/**
 * farmer/view_requests.php — view farmer requests (storage, transport, processing)
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
$farmerId = $farmerData->getFarmId();
$db = $farmer->getConnection();

$activeTab = $_GET['tab'] ?? 'storage';
if (!in_array($activeTab, ['storage', 'transport', 'processing'])) {
    $activeTab = 'storage';
}

// 1. Fetch Storage Requests
$storageRequests = [];
if ($farmerId) {
    $stmt = $db->prepare("
        SELECT sr.*, sf.name as enc_sfname, u.id as provider_user_id, u.full_name as enc_sowner, u.phone as enc_sphone
        FROM `storage_requests` sr
        JOIN `storage_facilities` sf ON sr.facility_id = sf.id
        JOIN `users` u ON sf.owner_id = u.id
        WHERE sr.farmer_id = ?
        ORDER BY sr.id DESC
    ");
    $stmt->execute([$farmerId]);
    foreach ($stmt->fetchAll() as $row) {
        $storageRequests[] = [
            'id'             => $row['id'],
            'facility_name'  => $farmer->decrypt($row['enc_sfname']),
            'provider_name'  => $farmer->decrypt($row['enc_sowner']),
            'provider_phone' => $farmer->decrypt($row['enc_sphone']),
            'quantity'       => $farmer->decrypt($row['quantity_kg']),
            'start_date'     => $farmer->decrypt($row['start_date']),
            'end_date'       => $farmer->decrypt($row['end_date']),
            'total_cost'     => $farmer->decrypt($row['total_cost']),
            'payment_status' => $farmer->decrypt($row['payment_status']),
            'status'         => $farmer->decrypt($row['status']),
            'created_at'     => $row['created_at']
        ];
        // Log decrypt audits
        $farmer->logAudit('storage_requests', $row['id'], 'quantity_kg', 'DECRYPT', $row['quantity_kg']);
        $farmer->logAudit('storage_requests', $row['id'], 'start_date', 'DECRYPT', $row['start_date']);
        $farmer->logAudit('storage_requests', $row['id'], 'end_date', 'DECRYPT', $row['end_date']);
        $farmer->logAudit('storage_requests', $row['id'], 'total_cost', 'DECRYPT', $row['total_cost']);
        $farmer->logAudit('storage_requests', $row['id'], 'payment_status', 'DECRYPT', $row['payment_status']);
        $farmer->logAudit('storage_requests', $row['id'], 'status', 'DECRYPT', $row['status']);
        $farmer->logAudit('storage_facilities', $row['facility_id'], 'name', 'DECRYPT', $row['enc_sfname']);
        $farmer->logAudit('users', $row['provider_user_id'], 'full_name', 'DECRYPT', $row['enc_sowner']);
        $farmer->logAudit('users', $row['provider_user_id'], 'phone', 'DECRYPT', $row['enc_sphone']);
    }
}

// 2. Fetch Transport Requests
$transportRequests = [];
if ($farmerId) {
    $stmt = $db->prepare("
        SELECT tr.*, tv.vehicle_type as enc_vtype, tv.plate_number as enc_vplate, u.id as provider_user_id, u.full_name as enc_towner, u.phone as enc_tphone
        FROM `transport_requests` tr
        JOIN `transport_vehicles` tv ON tr.vehicle_id = tv.id
        JOIN `users` u ON tv.owner_id = u.id
        WHERE tr.farmer_id = ?
        ORDER BY tr.id DESC
    ");
    $stmt->execute([$farmerId]);
    foreach ($stmt->fetchAll() as $row) {
        $transportRequests[] = [
            'id'                => $row['id'],
            'vehicle_type'      => $farmer->decrypt($row['enc_vtype']),
            'plate_number'      => $farmer->decrypt($row['enc_vplate']),
            'provider_name'     => $farmer->decrypt($row['enc_towner']),
            'provider_phone'    => $farmer->decrypt($row['enc_tphone']),
            'pickup_location'   => $farmer->decrypt($row['pickup_location']),
            'delivery_location' => $farmer->decrypt($row['delivery_location']),
            'distance_km'       => $farmer->decrypt($row['distance_km']),
            'quantity'          => $farmer->decrypt($row['quantity_kg']),
            'total_cost'        => $farmer->decrypt($row['total_cost']),
            'status'            => $farmer->decrypt($row['status']),
            'requested_date'    => $farmer->decrypt($row['requested_date']),
            'created_at'        => $row['created_at']
        ];
        // Log decrypt audits
        $farmer->logAudit('transport_requests', $row['id'], 'pickup_location', 'DECRYPT', $row['pickup_location']);
        $farmer->logAudit('transport_requests', $row['id'], 'delivery_location', 'DECRYPT', $row['delivery_location']);
        $farmer->logAudit('transport_requests', $row['id'], 'distance_km', 'DECRYPT', $row['distance_km']);
        $farmer->logAudit('transport_requests', $row['id'], 'quantity_kg', 'DECRYPT', $row['quantity_kg']);
        $farmer->logAudit('transport_requests', $row['id'], 'total_cost', 'DECRYPT', $row['total_cost']);
        $farmer->logAudit('transport_requests', $row['id'], 'status', 'DECRYPT', $row['status']);
        $farmer->logAudit('transport_requests', $row['id'], 'requested_date', 'DECRYPT', $row['requested_date']);
        $farmer->logAudit('transport_vehicles', $row['vehicle_id'], 'vehicle_type', 'DECRYPT', $row['enc_vtype']);
        $farmer->logAudit('transport_vehicles', $row['vehicle_id'], 'plate_number', 'DECRYPT', $row['enc_vplate']);
        $farmer->logAudit('users', $row['provider_user_id'], 'full_name', 'DECRYPT', $row['enc_towner']);
        $farmer->logAudit('users', $row['provider_user_id'], 'phone', 'DECRYPT', $row['enc_tphone']);
    }
}

// 3. Fetch Processing Requests
$processingRequests = [];
if ($farmerId) {
    $stmt = $db->prepare("
        SELECT pr.*, pf.name as enc_pfname, u.id as provider_user_id, u.full_name as enc_powner, u.phone as enc_pphone
        FROM `processing_requests` pr
        JOIN `processing_facilities` pf ON pr.facility_id = pf.id
        JOIN `users` u ON pf.owner_id = u.id
        WHERE pr.farmer_id = ?
        ORDER BY pr.id DESC
    ");
    $stmt->execute([$farmerId]);
    foreach ($stmt->fetchAll() as $row) {
        $processingRequests[] = [
            'id'             => $row['id'],
            'facility_name'  => $farmer->decrypt($row['enc_pfname']),
            'provider_name'  => $farmer->decrypt($row['enc_powner']),
            'provider_phone' => $farmer->decrypt($row['enc_pphone']),
            'quantity'       => $farmer->decrypt($row['quantity_kg']),
            'service_type'   => $farmer->decrypt($row['service_type']),
            'cost'           => $farmer->decrypt($row['cost']),
            'status'         => $farmer->decrypt($row['status']),
            'created_at'     => $row['created_at']
        ];
        // Log decrypt audits
        $farmer->logAudit('processing_requests', $row['id'], 'quantity_kg', 'DECRYPT', $row['quantity_kg']);
        $farmer->logAudit('processing_requests', $row['id'], 'service_type', 'DECRYPT', $row['service_type']);
        $farmer->logAudit('processing_requests', $row['id'], 'cost', 'DECRYPT', $row['cost']);
        $farmer->logAudit('processing_requests', $row['id'], 'status', 'DECRYPT', $row['status']);
        $farmer->logAudit('processing_facilities', $row['facility_id'], 'name', 'DECRYPT', $row['enc_pfname']);
        $farmer->logAudit('users', $row['provider_user_id'], 'full_name', 'DECRYPT', $row['enc_powner']);
        $farmer->logAudit('users', $row['provider_user_id'], 'phone', 'DECRYPT', $row['enc_pphone']);
    }
}

$pageTitle = 'Maombi Yangu ya Huduma';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="kh-hero mb-4">
  <h1>📋 Maombi Yangu ya Huduma</h1>
  <p>Fuatilia hali ya maombi yako ya uhifadhi, usafirishaji, na usindikaji wa mazao</p>
</div>

<!-- Tabs Navigation -->
<ul class="nav nav-tabs kh-tabs mb-4" id="requestsTab" role="tablist">
  <li class="nav-item">
    <a class="nav-link <?= $activeTab === 'storage' ? 'active' : '' ?>" href="?tab=storage">
      <i class="fas fa-warehouse me-2"></i>Uhifadhi (<?= count($storageRequests) ?>)
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?= $activeTab === 'transport' ? 'active' : '' ?>" href="?tab=transport">
      <i class="fas fa-truck me-2"></i>Usafirishaji (<?= count($transportRequests) ?>)
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?= $activeTab === 'processing' ? 'active' : '' ?>" href="?tab=processing">
      <i class="fas fa-industry me-2"></i>Usindikaji (<?= count($processingRequests) ?>)
    </a>
  </li>
</ul>

<!-- Tab Content -->
<div class="tab-content" id="requestsTabContent">

  <!-- 1. STORAGE TAB -->
  <?php if ($activeTab === 'storage'): ?>
    <div class="kh-card">
      <div class="kh-card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-warehouse me-2"></i>Historia ya Maombi ya Uhifadhi</span>
        <input type="text" class="form-control kh-input w-25 btn-sm" id="storageSearch" placeholder="🔍 Tafuta hapa..." onkeyup="liveSearch('storageSearch','storageTable')">
      </div>
      <div class="card-body p-0">
        <?php if ($storageRequests): ?>
          <div class="table-responsive">
            <table class="kh-table" id="storageTable">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Kituo cha Uhifadhi</th>
                  <th>Mtoa Huduma</th>
                  <th>Nambari ya Simu</th>
                  <th>Kiasi (kg)</th>
                  <th>Tarehe za Uhifadhi</th>
                  <th>Gharama</th>
                  <th>Malipo</th>
                  <th>Hali ya Ombi</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($storageRequests as $r): ?>
                  <tr>
                    <td><?= $r['id'] ?></td>
                    <td><strong><?= escape($r['facility_name']) ?></strong></td>
                    <td><?= escape($r['provider_name']) ?></td>
                    <td><a href="tel:<?= escape($r['provider_phone']) ?>" class="btn btn-sm btn-light text-primary"><i class="fas fa-phone-alt me-1"></i><?= escape($r['provider_phone']) ?></a></td>
                    <td><?= escape($r['quantity']) ?> kg</td>
                    <td>
                      <span class="small text-muted">Kuanzia:</span> <?= escape(formatDate($r['start_date'])) ?><br>
                      <span class="small text-muted">Hadi:</span> <?= escape(formatDate($r['end_date'])) ?>
                    </td>
                    <td><?= formatTshs($r['total_cost']) ?></td>
                    <td>
                      <?php if (strtolower($r['payment_status']) === 'paid'): ?>
                        <span class="badge bg-success">Imelipwa</span>
                      <?php else: ?>
                        <span class="badge bg-secondary">Haijalipwa</span>
                      <?php endif; ?>
                    </td>
                    <td><?= statusBadge($r['status']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="empty-state">
            <span class="empty-icon">🏭</span>
            <p>Bado hujaomba huduma yoyote ya uhifadhi.</p>
            <a href="request_storage.php" class="btn kh-btn-primary">Omba Hifadhi Sasa</a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <!-- 2. TRANSPORT TAB -->
  <?php if ($activeTab === 'transport'): ?>
    <div class="kh-card">
      <div class="kh-card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-truck me-2"></i>Historia ya Maombi ya Usafiri</span>
        <input type="text" class="form-control kh-input w-25 btn-sm" id="transportSearch" placeholder="🔍 Tafuta hapa..." onkeyup="liveSearch('transportSearch','transportTable')">
      </div>
      <div class="card-body p-0">
        <?php if ($transportRequests): ?>
          <div class="table-responsive">
            <table class="kh-table" id="transportTable">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Aina ya Gari (Namba)</th>
                  <th>Dereva/Msafirishaji</th>
                  <th>Nambari ya Simu</th>
                  <th>Kiasi (kg)</th>
                  <th>Njia (Kutoka -> Kwenda)</th>
                  <th>Umbali</th>
                  <th>Gharama</th>
                  <th>Tarehe ya Safari</th>
                  <th>Hali ya Safari</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($transportRequests as $r): ?>
                  <tr>
                    <td><?= $r['id'] ?></td>
                    <td><strong><?= escape(ucfirst($r['vehicle_type'])) ?></strong> (<?= escape($r['plate_number']) ?>)</td>
                    <td><?= escape($r['provider_name']) ?></td>
                    <td><a href="tel:<?= escape($r['provider_phone']) ?>" class="btn btn-sm btn-light text-primary"><i class="fas fa-phone-alt me-1"></i><?= escape($r['provider_phone']) ?></a></td>
                    <td><?= escape($r['quantity']) ?> kg</td>
                    <td>
                      <span class="small text-muted">Kutoka:</span> <?= escape($r['pickup_location']) ?><br>
                      <span class="small text-muted">Kwenda:</span> <?= escape($r['delivery_location']) ?>
                    </td>
                    <td><?= escape($r['distance_km']) ?> km</td>
                    <td><?= formatTshs($r['total_cost']) ?></td>
                    <td><?= escape(formatDate($r['requested_date'])) ?></td>
                    <td><?= statusBadge($r['status']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="empty-state">
            <span class="empty-icon">🚛</span>
            <p>Bado hujaomba huduma yoyote ya usafirishaji.</p>
            <a href="request_transport.php" class="btn kh-btn-primary">Omba Usafiri Sasa</a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <!-- 3. PROCESSING TAB -->
  <?php if ($activeTab === 'processing'): ?>
    <div class="kh-card">
      <div class="kh-card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-industry me-2"></i>Historia ya Maombi ya Usindikaji</span>
        <input type="text" class="form-control kh-input w-25 btn-sm" id="processingSearch" placeholder="🔍 Tafuta hapa..." onkeyup="liveSearch('processingSearch','processingTable')">
      </div>
      <div class="card-body p-0">
        <?php if ($processingRequests): ?>
          <div class="table-responsive">
            <table class="kh-table" id="processingTable">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Kiwanda cha Usindikaji</th>
                  <th>Msindikaji</th>
                  <th>Nambari ya Simu</th>
                  <th>Zao la Kusindika (kg)</th>
                  <th>Huduma</th>
                  <th>Kadirio la Gharama</th>
                  <th>Tarehe ya Kuomba</th>
                  <th>Hali ya Ombi</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($processingRequests as $r): ?>
                  <tr>
                    <td><?= $r['id'] ?></td>
                    <td><strong><?= escape($r['facility_name']) ?></strong></td>
                    <td><?= escape($r['provider_name']) ?></td>
                    <td><a href="tel:<?= escape($r['provider_phone']) ?>" class="btn btn-sm btn-light text-primary"><i class="fas fa-phone-alt me-1"></i><?= escape($r['provider_phone']) ?></a></td>
                    <td><?= escape($r['quantity']) ?> kg</td>
                    <td><span class="badge bg-secondary"><?= escape(ucfirst($r['service_type'])) ?></span></td>
                    <td><?= formatTshs($r['cost']) ?></td>
                    <td><?= escape(formatDate($r['created_at'])) ?></td>
                    <td><?= statusBadge($r['status']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="empty-state">
            <span class="empty-icon">⚙️</span>
            <p>Bado hujaomba huduma yoyote ya usindikaji.</p>
            <a href="request_processing.php" class="btn kh-btn-primary">Omba Usindikaji Sasa</a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

</div>

<script>
function liveSearch(inputId, tableId) {
    const input = document.getElementById(inputId);
    const filter = input.value.toLowerCase();
    const table = document.getElementById(tableId);
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
