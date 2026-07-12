<?php
/**
 * admin/users.php — User Management Panel
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../classes/User.php';

requireRole(['admin']);

$userObj = new User();
$db = $userObj->getConnection();

$errors = [];
// Handle Role update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Tokeni ya usalama imekosekana.';
    } else {
        $targetUserId = (int)($_POST['user_id'] ?? 0);
        $newRole      = trim($_POST['role'] ?? '');

        if ($targetUserId && in_array($newRole, ['farmer','storage_provider','transport_provider','processor','buyer','admin'])) {
            $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$newRole, $targetUserId]);
            setFlash('success', 'Jukumu la mtumiaji limebadilishwa kwa mafanikio!');
            redirect('users.php');
        } else {
            $errors[] = 'Data zilizopokelewa haziko sahihi.';
        }
    }
}

$stmt = $db->query("SELECT * FROM users ORDER BY id DESC");
$rows = $stmt->fetchAll();

$users = [];
foreach ($rows as $row) {
    $users[] = [
        'id'         => $row['id'],
        'username'   => $row['username_hash'], // Display hash/code or keep it anonymous
        'email'      => $userObj->decrypt($row['email']),
        'full_name'  => $userObj->decrypt($row['full_name']),
        'phone'      => $userObj->decrypt($row['phone']),
        'location'   => $userObj->decrypt($row['location']),
        'role'       => $row['role'],
        'created_at' => $row['created_at'],
    ];
}

$pageTitle = 'Usimamizi wa Watumiaji';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="kh-hero mb-4">
  <h1>👥 Usimamizi wa Watumiaji</h1>
  <p>Tazama na dhibiti watumiaji wote waliosajiliwa kwenye mfumo</p>
</div>

<?php if ($errors): ?>
  <div class="alert alert-danger"><?php foreach ($errors as $e): ?><div>&#9888; <?= escape($e) ?></div><?php endforeach; ?></div>
<?php endif; ?>

<div class="kh-card">
  <div class="kh-card-header d-flex justify-content-between align-items-center">
    <span><i class="fas fa-users me-2"></i>Watumiaji Wote</span>
    <input type="text" class="form-control kh-input w-25 btn-sm" id="userSearch"
           placeholder="🔍 Tafuta mtumiaji..." onkeyup="liveSearch('userSearch','usersTable')">
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="kh-table" id="usersTable">
        <thead>
          <tr>
            <th>#</th>
            <th>Jina Kamili</th>
            <th>Barua Pepe</th>
            <th>Simu</th>
            <th>Mahali</th>
            <th>Jukumu (Role)</th>
            <th>Sajili Tarehe</th>
            <th>Badili Jukumu</th>
            <th>Hatua</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
          <tr>
            <td><?= $u['id'] ?></td>
            <td><strong><?= escape($u['full_name']) ?></strong></td>
            <td><?= escape($u['email']) ?></td>
            <td><?= escape($u['phone']) ?></td>
            <td><?= escape($u['location']) ?></td>
            <td><?= statusBadge($u['role']) ?></td>
            <td><?= escape(formatDate($u['created_at'])) ?></td>
            <td>
              <?php if ($u['id'] != $_SESSION['user_id']): ?>
              <form method="POST" class="d-flex gap-1 align-items-center">
                <?= csrfField() ?>
                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                <select name="role" class="form-select form-select-sm kh-input py-1" style="width:130px;" required>
                  <option value="farmer"             <?= $u['role']==='farmer'?'selected':'' ?>>Mkulima</option>
                  <option value="storage_provider"   <?= $u['role']==='storage_provider'?'selected':'' ?>>Uhifadhi</option>
                  <option value="transport_provider" <?= $u['role']==='transport_provider'?'selected':'' ?>>Usafiri</option>
                  <option value="processor"          <?= $u['role']==='processor'?'selected':'' ?>>Msindikaji</option>
                  <option value="buyer"              <?= $u['role']==='buyer'?'selected':'' ?>>Mnunuzi</option>
                  <option value="admin"              <?= $u['role']==='admin'?'selected':'' ?>>Msimamizi</option>
                </select>
                <button type="submit" class="btn btn-sm btn-outline-success"><i class="fas fa-save"></i></button>
              </form>
              <?php else: ?>
              <span class="text-muted small">Akaunti Yako</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($u['id'] != $_SESSION['user_id']): ?>
              <form method="POST" action="delete_user.php" onsubmit="return confirm('Je, una uhakika unataka kufuta mtumiaji huyu? Taarifa zake zote zitafutwa kabisa.')" style="display:inline;">
                <?= csrfField() ?>
                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash-alt"></i> Futa</button>
              </form>
              <?php else: ?>
              <span class="text-muted small">—</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
