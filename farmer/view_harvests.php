<?php
/**
 * farmer/view_harvests.php — View/Edit/Delete Harvests
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../classes/Farmer.php';
require_once __DIR__ . '/../classes/Harvest.php';
require_once __DIR__ . '/../classes/Crop.php';

requireRole(['farmer']);

$farmer = new Farmer();
$farmerData = $farmer->findByUserId($_SESSION['user_id']);
if (!$farmerData) { setFlash('danger','Mkulima hajapatikana.'); redirect('dashboard.php'); }
$farmerId = $farmerData->getFarmId();

$cropObj = new Crop();
$crops   = $cropObj->getAll();

$errors = [];
$editHarvest = null;

// Handle Edit Fetch
if (!empty($_GET['edit'])) {
    $hObj = new Harvest();
    $editHarvest = $hObj->findById((int)$_GET['edit']);
    if (!$editHarvest || $editHarvest->getFarmerId() != $farmerId) {
        setFlash('danger', 'Mavuno hayakupatikana au huna ruhusa.');
        redirect('view_harvests.php');
    }
}

// Handle POST (Update or Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Tokeni ya usalama imekosekana.';
    } else {
        $action = $_POST['action'] ?? '';
        $id = (int)($_POST['id'] ?? 0);

        $hObj = new Harvest();
        $target = $hObj->findById($id);
        if (!$target || $target->getFarmerId() != $farmerId) {
            $errors[] = 'Mavuno hayo hayapo au huna ruhusa.';
        }

        if (!$errors) {
            if ($action === 'delete') {
                $target->delete($id);
                setFlash('success', 'Mavuno yamefutwa kwa mafanikio!');
                redirect('view_harvests.php');
            } elseif ($action === 'update') {
                $quantity = trim($_POST['quantity_kg'] ?? '');
                $date     = trim($_POST['harvest_date'] ?? '');
                $grade    = trim($_POST['quality_grade'] ?? '');
                $price    = trim($_POST['unit_price'] ?? '');
                $location = trim($_POST['harvest_location'] ?? '');

                if (!is_numeric($quantity) || $quantity <= 0) $errors[] = 'Kiasi lazima kiwe nambari zaidi ya 0.';
                if (empty($date))       $errors[] = 'Tarehe inahitajika.';
                if (empty($grade))      $errors[] = 'Daraja linahitajika.';
                if (!is_numeric($price) || $price <= 0) $errors[] = 'Bei lazima iwe nambari zaidi ya 0.';
                if (empty($location))   $errors[] = 'Mahali linahitajika.';

                if (!$errors) {
                    $target->update($quantity, $date, $grade, $price, $location);
                    setFlash('success', 'Mavuno yamebadilishwa kwa mafanikio!');
                    redirect('view_harvests.php');
                }
            }
        }
    }
}

$farmer->setFarmId($farmerId);
$harvests = $farmer->viewHarvests();

$pageTitle = 'Orodha ya Mavuno';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="kh-hero mb-4">
  <h1>🌾 Rekodi Zako za Mavuno</h1>
  <p>Tazama na uhariri mavuno uliyosajili</p>
</div>

<?php if ($errors): ?>
  <div class="alert alert-danger"><?php foreach ($errors as $e): ?><div>&#9888; <?= escape($e) ?></div><?php endforeach; ?></div>
<?php endif; ?>

<?php if ($editHarvest): ?>
<div class="kh-card mb-4">
  <div class="kh-card-header"><i class="fas fa-edit me-2"></i>Hariri Mavuno #<?= $editHarvest->getId() ?></div>
  <div class="card-body p-4">
    <form method="POST">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" value="<?= $editHarvest->getId() ?>">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Kiasi (kg) *</label>
          <input type="number" name="quantity_kg" step="0.01" class="form-control kh-input"
                 value="<?= escape($editHarvest->getQuantityKg()) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Tarehe ya Kuvuna *</label>
          <input type="date" name="harvest_date" class="form-control kh-input"
                 value="<?= escape($editHarvest->getHarvestDate()) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Daraja la Ubora *</label>
          <select name="quality_grade" class="form-select kh-input" required>
            <?php foreach (['A','B','C','D'] as $g): ?>
              <option value="<?= $g ?>" <?= $editHarvest->getQualityGrade()===$g?'selected':'' ?>><?= $g ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Bei kwa Kilo (Tshs) *</label>
          <input type="number" name="unit_price" step="0.01" class="form-control kh-input"
                 value="<?= escape($editHarvest->getUnitPrice()) ?>" required>
        </div>
        <div class="col-md-12">
          <label class="form-label fw-semibold">Mahali Kilipovunwa *</label>
          <input type="text" name="harvest_location" class="form-control kh-input"
                 value="<?= escape($editHarvest->getHarvestLocation()) ?>" required>
        </div>
      </div>
      <div class="mt-3 d-flex gap-2">
        <button type="submit" class="btn kh-btn-primary"><i class="fas fa-save me-1"></i>Hifadhi</button>
        <a href="view_harvests.php" class="btn btn-outline-secondary">Ghairi</a>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<div class="kh-card">
  <div class="kh-card-header d-flex justify-content-between align-items-center">
    <span><i class="fas fa-list me-2"></i>Mavuno Yote</span>
    <a href="add_harvest.php" class="btn btn-sm btn-light"><i class="fas fa-plus me-1"></i>Ongeza Mavuno</a>
  </div>
  <div class="card-body p-0">
    <?php if ($harvests): ?>
    <div class="p-3 border-bottom">
      <input type="text" class="form-control kh-input" id="searchHarvestInput"
             placeholder="🔍 Tafuta hapa kwa jina la zao au mahali..." onkeyup="liveSearch('searchHarvestInput','harvestTableAll')">
    </div>
    <div class="table-responsive">
      <table class="kh-table" id="harvestTableAll">
        <thead>
          <tr>
            <th>#</th><th>Zao</th><th>Kiasi (kg)</th><th>Tarehe</th><th>Daraja</th><th>Bei/kg</th><th>Jumla</th><th>Mahali</th><th>Hatua</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($harvests as $h): ?>
          <tr>
            <td><?= $h['id'] ?></td>
            <td><strong><?= escape($h['crop_name']) ?></strong></td>
            <td><?= escape($h['quantity_kg']) ?></td>
            <td><?= escape(formatDate($h['harvest_date'])) ?></td>
            <td><span class="badge bg-secondary"><?= escape($h['quality_grade']) ?></span></td>
            <td><?= formatTshs($h['unit_price']) ?></td>
            <td><?= formatTshs((float)$h['quantity_kg'] * (float)$h['unit_price']) ?></td>
            <td><?= escape($h['harvest_location']) ?></td>
            <td>
              <div class="d-flex gap-1">
                <a href="view_harvests.php?edit=<?= $h['id'] ?>" class="btn btn-sm btn-outline-primary" title="Hariri">
                  <i class="fas fa-edit"></i>
                </a>
                <form method="POST" onsubmit="return confirmDelete(this)" style="display:inline">
                  <?= csrfField() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $h['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger" title="Futa">
                    <i class="fas fa-trash-alt"></i>
                  </button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
      <span class="empty-icon">🌾</span>
      <p>Hujasajili mavuno bado.</p>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
