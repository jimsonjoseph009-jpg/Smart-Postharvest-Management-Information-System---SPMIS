<?php
/**
 * index.php — KILIMO-HIFADHI Landing Page
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/session_check.php';

// Redirect logged-in users to their dashboard
if (!empty($_SESSION['user_id'])) {
    redirect(dashboardUrl($_SESSION['role']));
}

$pageTitle = 'Nyumbani';
?>
<!DOCTYPE html>
<html lang="sw">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>KILIMO-HIFADHI — Mfumo wa Usimamizi wa Mavuno Tanzania</title>
<meta name="description" content="Kilimo-Hifadhi ni mfumo unaounganisha wakulima na huduma za uhifadhi, usafiri, usindikaji na soko la mazao Tanzania.">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg kh-navbar">
  <div class="container">
    <a class="navbar-brand kh-brand" href="index.php">
      <span class="brand-icon">🌾</span>
      <span class="brand-text">KILIMO<span class="brand-accent">-HIFADHI</span></span>
    </a>
    <div class="d-flex gap-2">
      <a href="auth/login.php"    class="btn btn-outline-light btn-sm">Ingia</a>
      <a href="auth/register.php" class="btn kh-btn-primary btn-sm">Jisajili Bure</a>
    </div>
  </div>
</nav>

<!-- Hero Section -->
<section class="landing-hero">
  <div class="container position-relative">
    <div class="row align-items-center py-5">
      <div class="col-lg-6" style="animation: fadeInUp 0.8s ease forwards;">
        <div class="mb-3">
          <span class="badge" style="background:rgba(0,184,148,0.2);color:#00b894;padding:.5rem 1rem;border-radius:50px;font-size:.85rem;border:1px solid rgba(0,184,148,0.3);">
            🇹🇿 Iliyoundwa kwa Tanzania
          </span>
        </div>
        <h1 class="hero-title">
          Punguza Upotevu wa Mazao<br>
          <span class="accent">Ongeza Mapato Yako</span>
        </h1>
        <p style="font-size:1.1rem;opacity:.85;line-height:1.7;margin-bottom:2rem;">
          Kilimo-Hifadhi inaunganisha wakulima na huduma za uhifadhi, usafiri, 
          usindikaji na soko la mazao — yote mahali pamoja.
        </p>
        <div class="d-flex gap-3 flex-wrap">
          <a href="auth/register.php" class="btn kh-btn-primary btn-lg px-4">
            <i class="fas fa-user-plus me-2"></i>Anza Sasa — Bure
          </a>
          <a href="market/browse.php" class="btn btn-outline-light btn-lg px-4">
            <i class="fas fa-store me-2"></i>Tazama Soko
          </a>
        </div>
        <div class="d-flex gap-4 mt-4">
          <div class="text-center">
            <div style="font-size:1.6rem;font-weight:700;font-family:'Poppins',sans-serif;">30%+</div>
            <div style="font-size:.78rem;opacity:.7;">Upotevu unaopunguzwa</div>
          </div>
          <div class="text-center">
            <div style="font-size:1.6rem;font-weight:700;font-family:'Poppins',sans-serif;">6</div>
            <div style="font-size:.78rem;opacity:.7;">Aina za watumiaji</div>
          </div>
          <div class="text-center">
            <div style="font-size:1.6rem;font-weight:700;font-family:'Poppins',sans-serif;">100%</div>
            <div style="font-size:.78rem;opacity:.7;">Data imesimbwa</div>
          </div>
        </div>
      </div>
      <div class="col-lg-6 mt-5 mt-lg-0">
        <div class="row g-3">
          <?php
          $features = [
            ['🏭','Uhifadhi','Maghala, majokofu, na vituo vya kisasa vya kuhifadhia mazao yako.'],
            ['🚛','Usafiri','Malori, pikipiki na magari ya kubeba mazao salama hadi sokoni.'],
            ['⚙️','Usindikaji','Saga, kausha, pakiti, na ongeza thamani mazao yako kabla ya kuuza.'],
            ['🛒','Soko','Uza mazao safi au yaliyosindikwa moja kwa moja kwa wanunuzi.'],
          ]; ?>
          <?php foreach ($features as $f): ?>
          <div class="col-6">
            <div class="feature-card">
              <span class="feature-icon"><?= $f[0] ?></span>
              <h6 style="font-weight:700;margin-bottom:.4rem;"><?= $f[1] ?></h6>
              <p style="font-size:.82rem;opacity:.75;margin:0;"><?= $f[2] ?></p>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- How It Works -->
<section class="py-5 bg-white">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="section-title">Jinsi Inavyofanya Kazi</h2>
      <p class="text-muted">Hatua rahisi 4 za kuanza kupunguza upotevu wako</p>
    </div>
    <div class="row g-4 text-center">
      <?php $steps = [
        ['1','fas fa-user-plus','Jisajili','Tengeneza akaunti yako kwa dakika moja, bila gharama.'],
        ['2','fas fa-seedling','Sajili Mavuno','Ongeza rekodi za mavuno yako na bei za soko.'],
        ['3','fas fa-handshake','Pata Huduma','Omba uhifadhi, usafiri, au usindikaji kwa urahisi.'],
        ['4','fas fa-money-bill-wave','Pata Faida','Uza sokoni na pokea malipo salama.'],
      ]; ?>
      <?php foreach ($steps as $s): ?>
      <div class="col-md-3">
        <div class="kh-card p-4 h-100">
          <div style="width:56px;height:56px;background:linear-gradient(135deg,#1e7e34,#00b894);
            border-radius:50%;display:flex;align-items:center;justify-content:center;
            margin:0 auto 1rem;color:#fff;font-size:1.3rem;font-weight:700;">
            <?= $s[0] ?>
          </div>
          <i class="fas <?= $s[1] ?> fa-2x text-success mb-3"></i>
          <h6 class="fw-bold"><?= $s[2] ?></h6>
          <p class="text-muted small mb-0"><?= $s[3] ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Call To Action -->
<section class="py-5" style="background:linear-gradient(135deg,#1e7e34,#00b894);">
  <div class="container text-center text-white py-3">
    <h2 style="font-family:'Poppins',sans-serif;font-weight:700;font-size:2rem;">Anza Leo Hii!</h2>
    <p style="opacity:.9;font-size:1.05rem;margin-bottom:2rem;">
      Jiunge na wakulima, wafanyabiashara na watoa huduma wanaotumia KILIMO-HIFADHI.
    </p>
    <div class="d-flex gap-3 justify-content-center flex-wrap">
      <a href="auth/register.php" class="btn btn-light btn-lg fw-bold px-5">
        <i class="fas fa-user-plus me-2"></i>Jisajili Bure
      </a>
      <a href="auth/login.php" class="btn btn-outline-light btn-lg px-5">
        <i class="fas fa-sign-in-alt me-2"></i>Ingia
      </a>
    </div>
  </div>
</section>

<footer class="kh-footer">
  <div class="container">
    <div class="row gy-3">
      <div class="col-md-4">
        <div class="kh-brand mb-2"><span class="brand-icon">🌾</span> KILIMO-HIFADHI</div>
        <p class="small" style="color:rgba(255,255,255,.6)">Mfumo wa Usimamizi wa Upotevu wa Mazao — Tanzania.</p>
      </div>
      <div class="col-md-4">
        <h6 class="fw-bold mb-2">Viungo</h6>
        <ul class="list-unstyled small">
          <li><a href="market/browse.php"  class="footer-link">Soko la Bidhaa</a></li>
          <li><a href="auth/login.php"     class="footer-link">Ingia</a></li>
          <li><a href="auth/register.php"  class="footer-link">Jisajili</a></li>
        </ul>
      </div>
      <div class="col-md-4">
        <h6 class="fw-bold mb-2">Mawasiliano</h6>
        <p class="small" style="color:rgba(255,255,255,.6)">
          <i class="fas fa-envelope me-1"></i> info@kilimohifadhi.tz<br>
          <i class="fas fa-phone me-1"></i> +255 700 000 000
        </p>
      </div>
    </div>
    <hr style="border-color:rgba(255,255,255,.15)">
    <p class="text-center small mb-0" style="color:rgba(255,255,255,.5)">
      &copy; <?= date('Y') ?> KILIMO-HIFADHI. Haki zote zimehifadhiwa.
    </p>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
