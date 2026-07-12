<?php
/**
 * processing/dashboard.php — Processor Dashboard
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../classes/ProcessingFacility.php';

requireRole(['processor','admin']);

$pf = new ProcessingFacility();
$db = $pf->getConnection();
$userId = (int)$_SESSION['user_id'];

// Get counts
$stmt = $db->prepare("SELECT COUNT(*) FROM processing_facilities WHERE owner_id = ?");
$stmt->execute([$userId]); $countFacilities = $stmt->fetchColumn();

$stmt = $db->prepare("
    SELECT COUNT(*) FROM processing_requests pr
    JOIN processing_facilities pf ON pr.facility_id = pf.id
    WHERE pf.owner_id = ?
");
$stmt->execute([$userId]); $countRequests = $stmt->fetchColumn();

// Fetch requests with decryption
$stmt = $db->prepare("
    SELECT pr.*, pf.name as enc_pfname, u.full_name as enc_uname
    FROM processing_requests pr
    JOIN processing_facilities pf ON pr.facility_id = pf.id
    JOIN farmers fm ON pr.farmer_id = fm.id
    JOIN users u ON fm.user_id = u.id
    WHERE pf.owner_id = ?
    ORDER BY pr.id DESC LIMIT 15
");
$stmt->execute([$userId]);
$requests = [];
foreach ($stmt->fetchAll() as $r) {
    $requests[] = [
        'id'          => $r['id'],
        'facility'    => $pf->decrypt($r['enc_pfname']),
        'farmer'      => $pf->decrypt($r['enc_uname']),
        'quantity'    => $pf->decrypt($r['quantity_kg']),
        'service'     => $pf->decrypt($r['service_type']),
        'cost'        => $pf->decrypt($r['cost']),
        'status'      => $pf->decrypt($r['status']),
        'created_at'  => $r['created_at']
    ];
}

// Fetch facilities
$stmt = $db->prepare("SELECT * FROM processing_facilities WHERE owner_id = ?");
$stmt->execute([$userId]);
$facilities = [];
foreach ($stmt->fetchAll() as $row) {
    $facilities[] = [
        'id'               => $row['id'],
        'name'             => $pf->decrypt($row['name']),
        'type'             => $pf->decrypt($row['type']),
        'location'         => $pf->decrypt($row['location']),
        'capacity'         => $pf->decrypt($row['capacity']),
        'services_offered' => $pf->decrypt($row['services_offered']),
        'price'            => $pf->decrypt($row['price']),
    ];
}

$pageTitle = 'Dashibodi ya Usindikaji';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="kh-hero mb-4">
  <h1>⚙️ Dashibodi ya Msindikaji</h1>
  <p>Usimamizi wa viwanda na maombi ya usindikaji (value addition) | <?= date('d/m/Y') ?></p>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-6">
    <div class="stat-card"><span class="stat-icon">⚙️</span>
      <div class="stat-value" data-target="<?= $countFacilities ?>"><?= $countFacilities ?></div>
      <div class="stat-label">Viwanda Vyako</div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="stat-card amber"><span class="stat-icon">📋</span>
      <div class="stat-value" data-target="<?= $countRequests ?>"><?= $countRequests ?></div>
      <div class="stat-label">Maombi ya Usindikaji</div>
    </div>
  </div>
</div>

<div class="d-flex gap-2 mb-4">
  <a href="add_facility.php" class="btn kh-btn-primary"><i class="fas fa-plus me-1"></i>Sajili Kiwanda Mpya</a>
  <a href="view_requests.php" class="btn btn-outline-secondary"><i class="fas fa-list me-1"></i>Maombi Yote</a>
</div>

<!-- Facilities List -->
<div class="kh-card mb-4">
  <div class="kh-card-header"><i class="fas fa-industry me-2"></i>Viwanda Vyako</div>
  <div class="card-body p-0">
    <?php if ($facilities): ?>
      <div class="table-responsive">
        <table class="kh-table">
          <thead><tr><th>#</th><th>Jina la Kiwanda</th><th>Aina</th><th>Uwezo kwa Siku</th><th>Bei/kg</th><th>Mahali</th><th>Huduma</th></tr></thead>
          <tbody>
            <?php foreach ($facilities as $f): ?>
            <tr>
              <td><?= $f['id'] ?></td>
              <td><strong><?= escape($f['name']) ?></strong></td>
              <td><?= ucfirst(escape($f['type'])) ?></td>
              <td><?= escape($f['capacity']) ?> kg</td>
              <td><?= formatTshs($f['price']) ?></td>
              <td><?= escape($f['location']) ?></td>
              <td><span class="badge bg-secondary"><?= escape($f['services_offered']) ?></span></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty-state"><span class="empty-icon">⚙️</span><p>Hujasajili kiwanda cha usindikaji bado.</p></div>
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
          <thead><tr><th>#</th><th>Kiwanda</th><th>Mkulima</th><th>Kiasi (kg)</th><th>Huduma</th><th>Gharama</th><th>Hali</th><th>Hatua</th></tr></thead>
          <tbody>
            <?php foreach ($requests as $r): ?>
            <tr>
              <td><?= $r['id'] ?></td>
              <td><?= escape($r['facility']) ?></td>
              <td><?= escape($r['farmer']) ?></td>
              <td><?= escape($r['quantity']) ?> kg</td>
              <td><?= escape($r['service']) ?></td>
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
      <div class="empty-state"><span class="empty-icon">📭</span><p>Hakuna maombi ya usindikaji yaliyotumwa kwako bado.</p></div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
