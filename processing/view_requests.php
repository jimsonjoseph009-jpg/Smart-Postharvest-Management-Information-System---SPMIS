<?php
/**
 * processing/view_requests.php — Processing Requests Log
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../classes/ProcessingFacility.php';

requireRole(['processor','admin']);

$pf = new ProcessingFacility();
$db = $pf->getConnection();
$userId = (int)$_SESSION['user_id'];

$stmt = $db->prepare("
    SELECT pr.*, pf.name as enc_pfname, u.full_name as enc_uname
    FROM processing_requests pr
    JOIN processing_facilities pf ON pr.facility_id = pf.id
    JOIN farmers fm ON pr.farmer_id = fm.id
    JOIN users u ON fm.user_id = u.id
    WHERE pf.owner_id = ?
    ORDER BY pr.id DESC
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

$pageTitle = 'Maombi ya Usindikaji';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="kh-hero mb-4">
  <h1>⚙️ Kumbukumbu ya Maombi ya Usindikaji</h1>
  <p>Fuatilia na kukamilisha maombi yote ya usindikaji (value addition)</p>
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
            <th>Kiwanda</th>
            <th>Mkulima</th>
            <th>Kiasi (kg)</th>
            <th>Huduma</th>
            <th>Gharama</th>
            <th>Muda</th>
            <th>Hali</th>
            <th>Hatua</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($requests as $r): ?>
          <tr>
            <td><?= $r['id'] ?></td>
            <td><strong><?= escape($r['facility']) ?></strong></td>
            <td><?= escape($r['farmer']) ?></td>
            <td><?= escape($r['quantity']) ?> kg</td>
            <td><?= escape($r['service']) ?></td>
            <td><?= formatTshs($r['cost']) ?></td>
            <td><?= escape(formatDate($r['created_at'])) ?></td>
            <td><?= statusBadge($r['status']) ?></td>
            <td>
              <?php if (strtolower($r['status']) !== 'completed'): ?>
                <a href="approve_request.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-success">
                  <?php if (strtolower($r['status']) === 'pending'): ?>
                    <i class="fas fa-check me-1"></i>Thibitisha
                  <?php else: ?>
                    <i class="fas fa-check-double me-1"></i>Kamilisha
                  <?php endif; ?>
                </a>
              <?php else: ?>
                <span class="text-muted small">Kamilifu</span>
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
      <p>Hakuna maombi ya usindikaji bado.</p>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
