<?php
require_once '../config.php';
require_login();
$user = current_user();
if($user['role'] !== 'faculty'){ header("Location: ../index.php"); exit; }
$pdo = pdo();
$id = $_GET['id'] ?? null;
$stmt = $pdo->prepare("SELECT * FROM subjects WHERE id = ? AND faculty_id = ?");
$stmt->execute([$id,$user['id']]);
$sub = $stmt->fetch();
if(!$sub){ header("Location: subjects.php"); exit; }
$msg = '';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $name = $_POST['name']; $code = $_POST['code']; $schedule = $_POST['schedule'];
    $pdo->prepare("UPDATE subjects SET name=?,code=?,schedule=? WHERE id=?")->execute([$name,$code,$schedule,$id]);
    $msg = 'Updated';
    $sub = array_merge($sub, ['name'=>$name,'code'=>$code,'schedule'=>$schedule]);
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Edit Subject - ClassFlow</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <?php include '../shared/left_nav.php'; ?>
  <main class="main-content">
    <div class="container py-4">
      <h3>Edit Subject</h3>
      <?php if($msg): ?><div class="alert alert-success"><?php echo $msg; ?></div><?php endif; ?>
      <form method="post" class="card p-3">
        <div class="mb-2">
          <label>Subject name</label>
          <input name="name" class="form-control" value="<?php echo htmlspecialchars($sub['name']) ?>">
        </div>
        <div class="mb-2">
          <label>Code</label>
          <input name="code" class="form-control" value="<?php echo htmlspecialchars($sub['code']) ?>">
        </div>
        <div class="mb-2">
          <label>Schedule</label>
          <input name="schedule" class="form-control" value="<?php echo htmlspecialchars($sub['schedule']) ?>">
        </div>
        <div>
          <button class="btn btn-orange">Save</button>
          <a class="btn btn-outline-secondary" href="dashboard.php">Back</a>
        </div>
      </form>
    </div>
  </main>
</body>
</html>