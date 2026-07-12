<?php
/**
 * storage/dashboard.php — Storage Provider Dashboard
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../classes/StorageFacility.php';

requireRole(['storage_provider','admin']);

$db = (new StorageFacility())->getConnection();
$userId = (int)$_SESSION['user_id'];

$s = $db->prepare("SELECT COUNT(*) FROM storage_facilities WHERE owner_id = ?");
$s->execute([$userId]); $countFacilities = $s->fetchColumn();

$s = $db->prepare("SELECT COUNT(*) FROM storage_requests sr JOIN storage_facilities sf ON sr.facility_id = sf.id WHERE sf.owner_id = ?");
$s->execute([$userId]); $countRequests = $s->fetchColumn();

// Fetch requests with decryption
$sf   = new StorageFacility();
$stmt = $db->prepare("
    SELECT sr.*, sf.name as enc_fname, u.full_name as enc_uname
    FROM storage_requests sr
    JOIN storage_facilities sf ON sr.facility_id = sf.id
    JOIN farmers fm ON sr.farmer_id = fm.id
    JOIN users u ON fm.user_id = u.id
    WHERE sf.owner_id = ?
    ORDER BY sr.id DESC LIMIT 20
");
$stmt->execute([$userId]);
$requests = [];
foreach ($stmt->fetchAll() as $r) {
    $requests[] = [
        'id'         => $r['id'],
        'facility'   => $sf->decrypt($r['enc_fname']),
        'farmer'     => $sf->decrypt($r['enc_uname']),
        'quantity'   => $sf->decrypt($r['quantity_kg']),
        'start_date' => $sf->decrypt($r['start_date']),
        'end_date'   => $sf->decrypt($r['end_date']),
        'cost'       => $sf->decrypt($r['total_cost']),
        'status'     => $sf->decrypt($r['status']),
    ];
}

$pageTitle = 'Dashibodi ya Uhifadhi';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="kh-hero mb-4"><h1>🏭 Dashibodi ya Uhifadhi</h1><p>Simamia vituo na maombi yako | <?= date('d/m/Y') ?></p></div>

<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="stat-card"><span class="stat-icon">🏭</span>
      <div class="stat-value" data-target="<?= $countFacilities ?>"><?= $countFacilities ?></div>
      <div class="stat-label">Vituo Vyako</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card amber"><span class="stat-icon">📋</span>
      <div class="stat-value" data-target="<?= $countRequests ?>"><?= $countRequests ?></div>
      <div class="stat-label">Jumla ya Maombi</div>
    </div>
  </div>
</div>

<div class="d-flex gap-2 mb-4">
  <a href="add_facility.php"    class="btn kh-btn-primary"><i class="fas fa-plus me-1"></i>Ongeza Kituo</a>
  <a href="manage_facility.php" class="btn kh-btn-amber"><i class="fas fa-cog me-1"></i>Simamia Vituo</a>
  <a href="view_requests.php"   class="btn btn-outline-secondary"><i class="fas fa-list me-1"></i>Maombi Yote</a>
</div>

<div class="kh-card">
  <div class="kh-card-header"><i class="fas fa-list me-2"></i>Maombi ya Hivi Karibuni</div>
  <div class="card-body p-0">
    <?php if ($requests): ?>
    <div class="table-responsive">
      <table class="kh-table" id="storReqTable">
        <thead><tr><th>#</th><th>Kituo</th><th>Mkulima</th><th>Kiasi (kg)</th><th>Kuanza</th><th>Kumalizika</th><th>Gharama</th><th>Hali</th><th>Hatua</th></tr></thead>
        <tbody>
          <?php foreach ($requests as $r): ?>
          <tr>
            <td><?= $r['id'] ?></td>
            <td><?= escape($r['facility']) ?></td>
            <td><?= escape($r['farmer']) ?></td>
            <td><?= escape($r['quantity']) ?></td>
            <td><?= escape(formatDate($r['start_date'])) ?></td>
            <td><?= escape(formatDate($r['end_date'])) ?></td>
            <td><?= formatTshs($r['cost']) ?></td>
            <td><?= statusBadge($r['status']) ?></td>
            <td><a href="approve_request.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-check"></i></a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <div class="empty-state"><span class="empty-icon">📭</span><p>Hakuna maombi bado.</p></div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
