<?php
/**
 * admin/dashboard.php — Admin Dashboard
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../classes/User.php';

requireRole(['admin']);

$db = (new User())->getConnection();

$totalUsers    = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalHarvests = $db->query("SELECT COUNT(*) FROM harvests")->fetchColumn();
$totalOrders   = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalAudit    = $db->query("SELECT COUNT(*) FROM encrypted_data_audit")->fetchColumn();
$totalLogs     = $db->query("SELECT COUNT(*) FROM system_logs")->fetchColumn();
$roleStmt      = $db->query("SELECT role, COUNT(*) as cnt FROM users GROUP BY role");
$roleCounts    = $roleStmt->fetchAll();

// Last 5 system logs
$u = new User();
$logStmt = $db->query("SELECT sl.*, u.role FROM system_logs sl LEFT JOIN users u ON sl.user_id = u.id ORDER BY sl.timestamp DESC LIMIT 10");
$logs = [];
foreach ($logStmt->fetchAll() as $r) {
    $logs[] = [
        'id'        => $r['id'],
        'user_id'   => $r['user_id'],
        'role'      => $r['role'] ?? '—',
        'action'    => $u->decrypt($r['action']),
        'ip'        => $u->decrypt($r['ip_address']),
        'timestamp' => $r['timestamp'],
    ];
}

$roleLabels = array_column($roleCounts, 'cnt');
$roleNames  = array_map(fn($r) => roleLabel($r['role']), $roleCounts);

$pageTitle = 'Dashibodi ya Msimamizi';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="kh-hero mb-4">
  <h1>🛡️ Dashibodi ya Msimamizi</h1>
  <p>Muhtasari wa mfumo wote | <?= date('d/m/Y H:i') ?></p>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="stat-card"><span class="stat-icon">👥</span>
      <div class="stat-value" data-target="<?= $totalUsers ?>"><?= $totalUsers ?></div>
      <div class="stat-label">Watumiaji Wote</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card amber"><span class="stat-icon">🌾</span>
      <div class="stat-value" data-target="<?= $totalHarvests ?>"><?= $totalHarvests ?></div>
      <div class="stat-label">Rekodi za Mavuno</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card info"><span class="stat-icon">🛒</span>
      <div class="stat-value" data-target="<?= $totalOrders ?>"><?= $totalOrders ?></div>
      <div class="stat-label">Maagizo</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card earth"><span class="stat-icon">🔐</span>
      <div class="stat-value" data-target="<?= $totalAudit ?>"><?= $totalAudit ?></div>
      <div class="stat-label">Matukio ya Usimbaji</div>
    </div>
  </div>
</div>

<!-- Charts + Logs -->
<div class="row g-4">
  <div class="col-md-4">
    <div class="kh-card h-100">
      <div class="kh-card-header"><i class="fas fa-chart-pie me-2"></i>Mgawanyo wa Watumiaji</div>
      <div class="card-body">
        <canvas id="roleChart" height="220"></canvas>
      </div>
    </div>
  </div>
  <div class="col-md-8">
    <div class="kh-card h-100">
      <div class="kh-card-header d-flex justify-content-between">
        <span><i class="fas fa-scroll me-2"></i>Kumbukumbu za Mfumo</span>
        <a href="system_logs.php" class="btn btn-sm btn-light">Tazama Zote</a>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="kh-table">
            <thead><tr><th>#</th><th>Mtumiaji</th><th>Jukwaa</th><th>Tendo</th><th>IP</th><th>Muda</th></tr></thead>
            <tbody>
              <?php foreach ($logs as $l): ?>
              <tr>
                <td><?= $l['id'] ?></td>
                <td><?= $l['user_id'] ?? '—' ?></td>
                <td><?= escape(roleLabel($l['role'])) ?></td>
                <td><?= escape($l['action']) ?></td>
                <td><code><?= escape($l['ip']) ?></code></td>
                <td><?= escape($l['timestamp']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('roleChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($roleNames) ?>,
            datasets: [{
                data: <?= json_encode($roleLabels) ?>,
                backgroundColor: ['#1e7e34','#e67e22','#0984e3','#7b4f2e','#00b894','#e74c3c'],
                borderWidth: 2, borderColor: '#fff'
            }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
