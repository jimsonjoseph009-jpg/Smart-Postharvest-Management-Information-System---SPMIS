<?php
/**
 * admin/reports.php — Admin Reports Dashboard
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session_check.php';

requireRole(['admin']);

$pageTitle = 'Ripoti za Mfumo';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="kh-hero mb-4">
  <h1>📊 Ripoti za Mfumo</h1>
  <p>Tengeneza na upakue ripoti mbalimbali za uendeshaji wa mfumo</p>
</div>

<div class="row g-4">
  <!-- Harvest Report Card -->
  <div class="col-md-4">
    <div class="kh-card h-100 text-center p-4">
      <div class="fs-1 text-success mb-3">🌾</div>
      <h5 class="fw-bold">Ripoti ya Mavuno</h5>
      <p class="text-muted small">Muhtasari wa mazao yote yaliyovunwa, maeneo na thamani yake nchini.</p>
      <div class="mt-4 d-grid gap-2">
        <a href="<?= BASE_URL ?>/reports/generate.php?type=harvest&format=pdf" target="_blank" class="btn kh-btn-primary">
          <i class="fas fa-print me-2"></i>Chapisha (PDF)
        </a>
        <a href="<?= BASE_URL ?>/reports/generate.php?type=harvest&format=csv" class="btn btn-outline-success">
          <i class="fas fa-file-csv me-2"></i>Pakua CSV
        </a>
      </div>
    </div>
  </div>

  <!-- Storage Report Card -->
  <div class="col-md-4">
    <div class="kh-card h-100 text-center p-4">
      <div class="fs-1 text-warning mb-3">🏭</div>
      <h5 class="fw-bold">Ripoti ya Uhifadhi</h5>
      <p class="text-muted small">Tazama matumizi ya maghala, kiasi cha mzigo kilichohifadhiwa na gharama zake.</p>
      <div class="mt-4 d-grid gap-2">
        <a href="<?= BASE_URL ?>/reports/generate.php?type=storage&format=pdf" target="_blank" class="btn kh-btn-amber">
          <i class="fas fa-print me-2"></i>Chapisha (PDF)
        </a>
        <a href="<?= BASE_URL ?>/reports/generate.php?type=storage&format=csv" class="btn btn-outline-warning">
          <i class="fas fa-file-csv me-2"></i>Pakua CSV
        </a>
      </div>
    </div>
  </div>

  <!-- Financial Report Card -->
  <div class="col-md-4">
    <div class="kh-card h-100 text-center p-4">
      <div class="fs-1 text-info mb-3">💰</div>
      <h5 class="fw-bold">Ripoti ya Fedha & Mauzo</h5>
      <p class="text-muted small">Muhtasari wa mauzo sokoni, malipo yaliyofanyika na miamala yote ya kifedha.</p>
      <div class="mt-4 d-grid gap-2">
        <a href="<?= BASE_URL ?>/reports/generate.php?type=financial&format=pdf" target="_blank" class="btn btn-primary">
          <i class="fas fa-print me-2"></i>Chapisha (PDF)
        </a>
        <a href="<?= BASE_URL ?>/reports/generate.php?type=financial&format=csv" class="btn btn-outline-primary">
          <i class="fas fa-file-csv me-2"></i>Pakua CSV
        </a>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
