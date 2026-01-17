<?php
require_once 'config.php';
require_login();
$user = current_user();
if($user['role'] !== 'admin'){
    header("Location: index.php"); exit;
}
$pdo = pdo();

$msg = '';
// Handle create faculty (existing)
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_faculty'])){
    $username = trim($_POST['username']);
    $fullname = trim($_POST['fullname']);
    $department = trim($_POST['department']);
    $password = password_hash($_POST['password'] ?: 'password123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, fullname, role, department) VALUES (?, ?, ?, 'faculty', ?)");
    try {
        $stmt->execute([$username, $password, $fullname, $department]);
        $msg = "Faculty account created.";
    } catch (Exception $e) {
        $msg = "Error: " . $e->getMessage();
    }
}

// Handle faculty password update
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_faculty_password'])){
    $faculty_id = intval($_POST['faculty_id']);
    $new_password = trim($_POST['new_password'] ?? '');
    if($faculty_id <= 0 || $new_password === ''){
        $msg = 'Invalid input for password update.';
    } else {
        $hash = password_hash($new_password, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ? AND role = 'faculty'");
            $stmt->execute([$hash, $faculty_id]);
            if($stmt->rowCount()){
                $msg = 'Password updated successfully.';
            } else {
                $msg = 'No faculty updated (check ID).';
            }
        } catch (Exception $e){
            $msg = 'Failed to update password: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin - ClassFlow</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <?php include 'shared/left_nav.php'; ?>
  <main class="main-content">
    <div class="container-fluid py-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Admin Panel — ClassFlow</h2>
        <div>
          <a href="backup.php" class="btn btn-outline-secondary">Backup CSV</a>
          <a href="logout.php" class="btn btn-secondary">Logout</a>
        </div>
      </div>

      <?php if($msg): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($msg) ?></div>
      <?php endif; ?>

      <div class="card mb-4">
        <div class="card-body">
          <h5>Create Faculty Account</h5>
          <form method="post" class="row g-3">
            <input type="hidden" name="create_faculty" value="1">
            <div class="col-md-4">
              <label class="form-label">Username</label>
              <input name="username" class="form-control" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Full name</label>
              <input name="fullname" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">Department</label>
              <input name="department" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">Password (optional)</label>
              <input name="password" class="form-control" placeholder="Defaults to password123">
            </div>
            <div class="col-12">
              <button class="btn btn-orange">Create Faculty</button>
            </div>
          </form>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <h5>Faculty accounts</h5>
          <table class="table">
            <thead><tr><th>Username</th><th>Fullname</th><th>Department</th><th>Change password</th><th>Action</th><th>Created</th></tr></thead>
            <tbody>
            <?php
              $rows = $pdo->query("SELECT id,username,fullname,department,created_at FROM users WHERE role='faculty' ORDER BY created_at DESC")->fetchAll();
              foreach ($rows as $r) {
            ?>
              <tr>
                <td><?php echo htmlspecialchars($r['username']) ?></td>
                <td><?php echo htmlspecialchars($r['fullname']) ?></td>
                <td><?php echo htmlspecialchars($r['department']) ?></td>
                <td>
                  <form method="post" style="display:flex;gap:8px;align-items:center;">
                    <input type="hidden" name="faculty_id" value="<?php echo $r['id']; ?>">
                    <input type="password" name="new_password" placeholder="New password" required class="form-control form-control-sm" style="width:200px;">
                    <button type="submit" name="update_faculty_password" class="btn btn-sm btn-outline-primary">Update</button>
                  </form>
                </td>
                <td>
                  <a href='delete_user.php?id=<?php echo $r['id'] ?>' class='btn btn-danger btn-sm' onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                </td>
                <td><?php echo $r['created_at'] ?></td>
              </tr>
            <?php } ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </main>
</body>
</html>