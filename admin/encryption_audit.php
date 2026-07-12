<?php
/**
 * admin/encryption_audit.php — Encryption & Cryptographic Audit Trails
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../classes/User.php';

requireRole(['admin']);

$userObj = new User();
$db = $userObj->getConnection();

$stmt = $db->query("
    SELECT * FROM encrypted_data_audit
    ORDER BY id DESC LIMIT 100
");
$auditLogs = $stmt->fetchAll();

$pageTitle = 'Auditi ya Usimbaji';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="kh-hero mb-4">
  <h1>🔐 Auditi ya Usimbaji wa Data (Encryption Audit)</h1>
  <p>Fuatilia matukio yote ya usimbaji (Encrypt) na ufunguaji (Decrypt) wa data nyeti za mfumo</p>
</div>

<div class="alert alert-info">
  <i class="fas fa-info-circle me-2"></i>
  <strong>Kumbuka:</strong> Kurasa hii inaonyesha kumbukumbu zote za kiusalama za <strong>AES-256-CBC</strong> zinazotokea wakati wa kuhifadhi au kusoma data kutoka kwenye kanzidata (database).
</div>

<div class="kh-card">
  <div class="kh-card-header d-flex justify-content-between align-items-center">
    <span><i class="fas fa-key me-2"></i>Kumbukumbu za Usimbaji</span>
    <input type="text" class="form-control kh-input w-25 btn-sm" id="auditSearch"
           placeholder="🔍 Tafuta..." onkeyup="liveSearch('auditSearch','auditTable')">
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="kh-table" id="auditTable">
        <thead>
          <tr>
            <th>#</th>
            <th>Jedwali (Table)</th>
            <th>Mstari ID</th>
            <th>Safu (Field)</th>
            <th>Tendo (Operation)</th>
            <th>Hash ya Thamani (SHA-256 Hash)</th>
            <th>Muda</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($auditLogs as $a): ?>
          <tr>
            <td><?= $a['id'] ?></td>
            <td><code><?= escape($a['table_name']) ?></code></td>
            <td><?= $a['record_id'] ?? '—' ?></td>
            <td><code><?= escape($a['field_name']) ?></code></td>
            <td>
              <span class="badge bg-<?= $a['operation']==='ENCRYPT'?'primary':'warning' ?>">
                <?= escape($a['operation']) ?>
              </span>
            </td>
            <td><small class="text-muted text-break" style="font-size:0.75rem;"><?= escape($a['encrypted_value_hash']) ?></small></td>
            <td><?= escape(formatDate($a['encryption_date'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
