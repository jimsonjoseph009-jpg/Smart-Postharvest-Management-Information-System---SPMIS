<?php
/**
 * storage/view_requests.php — Storage Provider Bookings Log
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../classes/StorageFacility.php';

requireRole(['storage_provider','admin']);

$sf = new StorageFacility();
$db = $sf->getConnection();
$userId = (int)$_SESSION['user_id'];

$stmt = $db->prepare("
    SELECT sr.*, sf.name as enc_fname, u.full_name as enc_uname
    FROM storage_requests sr
    JOIN storage_facilities sf ON sr.facility_id = sf.id
    JOIN farmers fm ON sr.farmer_id = fm.id
    JOIN users u ON fm.user_id = u.id
    WHERE sf.owner_id = ?
    ORDER BY sr.id DESC
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

$pageTitle = 'Maombi ya Uhifadhi';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="kh-hero mb-4">
  <h1>🏭 Kumbukumbu ya Maombi ya Uhifadhi</h1>
  <p>Tazama na udhibiti maombi yote yaliyotumwa kwa maghala yako</p>
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
            <th>Kituo</th>
            <th>Mkulima</th>
            <th>Kiasi (kg)</th>
            <th>Tarehe Kuanza</th>
            <th>Tarehe Kumalizika</th>
            <th>Jumla Kuu</th>
            <th>Hali ya Uhifadhi</th>
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
            <td><?= escape(formatDate($r['start_date'])) ?></td>
            <td><?= escape(formatDate($r['end_date'])) ?></td>
            <td><?= formatTshs($r['cost']) ?></td>
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
      <p>Hakuna maombi ya uhifadhi bado.</p>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
