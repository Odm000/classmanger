<?php
require_once '../config.php';
require_login();
$user = current_user();
if($user['role'] !== 'faculty'){ header("Location: ../index.php"); exit; }
$pdo = pdo();
$subs = $pdo->prepare("SELECT * FROM subjects WHERE faculty_id = ? ORDER BY created_at DESC");
$subs->execute([$user['id']]);
$subs = $subs->fetchAll();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Subjects - ClassFlow</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <?php include '../shared/left_nav.php'; ?>
  <main class="main-content">
    <div class="container py-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Your Subjects</h3>
        <a href="dashboard.php" class="btn btn-outline-secondary">Back</a>
      </div>
      <div class="row g-3">
        <?php foreach($subs as $s): ?>
          <div class="col-md-4">
            <div class="card" style="border-top:3px solid #ff8a00;">
              <div class="card-body">
                <h5><?php echo htmlspecialchars($s['name']) ?></h5>
                <div class="text-muted small"><?php echo htmlspecialchars($s['code']) ?> • <?php echo htmlspecialchars($s['schedule']) ?></div>
                <div class="mt-3">
                  <a href="subject.php?id=<?php echo $s['id'] ?>" class="btn btn-orange btn-sm">View Students</a>
                  <a href="edit_subject.php?id=<?php echo $s['id'] ?>" class="btn btn-outline-secondary btn-sm">Edit</a>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach;?>
      </div>
    </div>
  </main>
</body>
</html>