<?php
/**
 * storage/manage_facility.php — Manage Storage Facilities
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../classes/StorageFacility.php';

requireRole(['storage_provider','admin']);

$sf = new StorageFacility();
$db = $sf->getConnection();
$userId = (int)$_SESSION['user_id'];

// Handle status updates
if (isset($_GET['toggle_id'])) {
    $targetId = (int)$_GET['toggle_id'];
    $facility = $sf->findById($targetId);
    if ($facility && ($facility->getOwnerId() == $userId || $_SESSION['role'] === 'admin')) {
        $newStatus = strtolower($facility->getStatus()) === 'active' ? 'inactive' : 'active';
        $encStatus = $sf->encrypt($newStatus);

        $stmt = $db->prepare("UPDATE `storage_facilities` SET `status` = ? WHERE `id` = ?");
        $stmt->execute([$encStatus, $targetId]);

        $sf->logAudit('storage_facilities', $targetId, 'status', 'ENCRYPT', $encStatus);
        setFlash('success', 'Hali ya kituo imesasishwa kwa mafanikio!');
        redirect('manage_facility.php');
    }
}

// Fetch facilities
$stmt = $db->prepare("SELECT * FROM `storage_facilities` WHERE `owner_id` = ? ORDER BY `id` DESC");
$stmt->execute([$userId]);
$facilities = [];
foreach ($stmt->fetchAll() as $row) {
    $facilities[] = [
        'id'               => $row['id'],
        'name'             => $sf->decrypt($row['name']),
        'type'             => $sf->decrypt($row['type']),
        'location'         => $sf->decrypt($row['location']),
        'capacity_kg'      => $sf->decrypt($row['capacity_kg']),
        'available_space'  => $sf->decrypt($row['available_space']),
        'price_per_kg'     => $sf->decrypt($row['price_per_kg_per_month']),
        'status'           => $sf->decrypt($row['status']),
    ];
}

$pageTitle = 'Simamia Vituo vya Uhifadhi';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="kh-hero mb-4">
  <h1>🏭 Simamia Vituo vya Uhifadhi</h1>
  <p>Tazama orodha na badilisha hali (status) ya maghala yako ya uhifadhi</p>
</div>

<div class="kh-card">
  <div class="kh-card-header d-flex justify-content-between align-items-center">
    <span><i class="fas fa-warehouse me-2"></i>Vituo Vyako vya Uhifadhi</span>
    <a href="add_facility.php" class="btn btn-sm kh-btn-primary"><i class="fas fa-plus me-1"></i>Sajili Kituo Mpya</a>
  </div>
  <div class="card-body p-0">
    <?php if ($facilities): ?>
    <div class="table-responsive">
      <table class="kh-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Jina la Ghala</th>
            <th>Aina</th>
            <th>Uwezo (kg)</th>
            <th>Nafasi Wazi (kg)</th>
            <th>Bei / kg / mwezi</th>
            <th>Mahali</th>
            <th>Hali</th>
            <th>Hatua</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($facilities as $f): ?>
          <tr>
            <td><?= $f['id'] ?></td>
            <td><strong><?= escape($f['name']) ?></strong></td>
            <td><?= ucfirst(escape($f['type'])) ?></td>
            <td><?= escape($f['capacity_kg']) ?> kg</td>
            <td><?= escape($f['available_space']) ?> kg</td>
            <td><?= formatTshs($f['price_per_kg']) ?></td>
            <td><?= escape($f['location']) ?></td>
            <td><?= statusBadge($f['status']) ?></td>
            <td>
              <a href="manage_facility.php?toggle_id=<?= $f['id'] ?>" class="btn btn-sm btn-outline-warning">
                <i class="fas fa-sync-alt me-1"></i>Badili Hali
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
      <span class="empty-icon">🏭</span>
      <p>Hujasajili kituo chochote bado.</p>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
