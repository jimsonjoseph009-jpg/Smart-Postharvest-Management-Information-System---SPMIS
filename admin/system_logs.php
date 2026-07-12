<?php
/**
 * admin/system_logs.php — View System Activity Logs
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../classes/User.php';

requireRole(['admin']);

$userObj = new User();
$db = $userObj->getConnection();

$stmt = $db->query("
    SELECT sl.*, u.username_hash, u.role
    FROM system_logs sl
    LEFT JOIN users u ON sl.user_id = u.id
    ORDER BY sl.id DESC LIMIT 100
");
$rows = $stmt->fetchAll();

$logs = [];
foreach ($rows as $r) {
    $logs[] = [
        'id'         => $r['id'],
        'user_id'    => $r['user_id'] ?? 'Mgeni (Guest)',
        'role'       => $r['role'] ? roleLabel($r['role']) : '—',
        'action'     => $userObj->decrypt($r['action']),
        'ip_address' => $userObj->decrypt($r['ip_address']),
        'timestamp'  => $r['timestamp']
    ];
}

$pageTitle = 'Kumbukumbu za Mfumo';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="kh-hero mb-4">
  <h1>🛡️ Kumbukumbu za Mfumo (System Logs)</h1>
  <p>Fuatilia shughuli zote za watumiaji na matendo ya usalama</p>
</div>

<div class="kh-card">
  <div class="kh-card-header d-flex justify-content-between align-items-center">
    <span><i class="fas fa-scroll me-2"></i>Kumbukumbu 100 za Hivi Karibuni</span>
    <input type="text" class="form-control kh-input w-25 btn-sm d-inline-block" id="logSearch"
           placeholder="🔍 Tafuta hapa..." onkeyup="liveSearch('logSearch','logTable')">
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="kh-table" id="logTable">
        <thead>
          <tr>
            <th>#</th>
            <th>ID Mtumiaji</th>
            <th>Jukumu</th>
            <th>Tendo Lililofanyika</th>
            <th>IP Address</th>
            <th>Muda (Timestamp)</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($logs as $l): ?>
          <tr>
            <td><?= $l['id'] ?></td>
            <td><strong><?= escape($l['user_id']) ?></strong></td>
            <td><span class="badge bg-secondary"><?= escape($l['role']) ?></span></td>
            <td><?= escape($l['action']) ?></td>
            <td><code><?= escape($l['ip_address']) ?></code></td>
            <td><?= escape(formatDate($l['timestamp'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
