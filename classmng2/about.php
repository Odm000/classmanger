<?php
// public About page for ClassFlow
// Place this file at classmng2/about.php
require_once 'config.php';
// it's okay to show this page without forcing login.
// include left navigation if available (left_nav expects current_user() from config.php)
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>About ClassFlow</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    .about-hero { max-width:900px; margin:40px auto; }
    .about-card { padding:30px; border-radius:12px; box-shadow:0 6px 18px rgba(25,25,25,0.06); }
    .logo-box { width:140px; height:140px; display:flex; align-items:center; justify-content:center; border-radius:12px; background:linear-gradient(90deg,#fff,#fff); margin:auto; overflow:hidden; }
    .logo-box img { max-width:100%; max-height:100%; object-fit:contain; }
    .v-badge { background:linear-gradient(90deg,#ff8a00,#ff7000); color:#fff; padding:.25rem .6rem; border-radius:6px; font-weight:600; }
  </style>
</head>
<body>
  <!-- left_nav is included above if available -->
  <main class="main-content">
    <div class="container about-hero">
      <div class="about-card bg-white">
        <div class="text-center mb-4">
          <div class="logo-box mb-3">
            <!-- Put your logo at classmng2/assets/img/classflow-logo.png or adjust the src below -->
          </div>
          <img src="assets/img/svgviewer-png-output.png" width="500px" style="margin-left:100px; margin-top:-180px;" alt="">
          <div class="mb-2"><span class="v-badge">V1</span></div>
        </div>

        <div class="mb-3">
          <h4 class="fw-semibold">About the System</h4>
          <p class="mb-0">
            ClassFlow is an online based class management system — it modernizes the old way of managing students using Excel spreadsheets or handwritten forms.
          </p>
          <p>
            The developer, Oalden Morales (an IT graduate), created ClassFlow to digitalize classroom management by providing features that faculty and teachers need. The ClassFlow system is currently on its V1; additional features will be added soon.
          </p>
        </div>

        <div class="mb-3">
          <h5 class="fw-semibold">Purpose</h5>
          <ul>
            <li>Make class and student management faster and less error-prone.</li>
            <li>Replace manual spreadsheets and paper forms with a centralized digital system.</li>
            <li>Provide faculty-focused features for attendance, grading, activities and reporting.</li>
          </ul>
        </div>

        <div class="d-flex justify-content-between align-items-center">
          <div class="text-muted small">© <?php echo date('Y') ?> ClassFlow</div>
          <div>
            <?php if (function_exists('current_user') && $u = current_user()): ?>
              <?php if (($u['role'] ?? '') === 'faculty'): ?>
                <a href="faculty/dashboard.php" class="btn btn-orange btn-sm ms-2">Faculty Dashboard</a>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>