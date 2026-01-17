<?php
require_once '../config.php';
require_login();
$user = current_user();
if($user['role'] !== 'faculty'){ header("Location: ../index.php"); exit; }
$pdo = pdo();
$msg = '';
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $fullname = $_POST['fullname'];
    $department = $_POST['department'];
    $avatar = $user['avatar'];
    if(!empty($_FILES['avatar']['name'])){
        $target = '../uploads/';
        if(!is_dir($target)) mkdir($target,0755,true);
        $f = basename($_FILES['avatar']['name']);
        $dest = $target.time().'_'.$f;
        move_uploaded_file($_FILES['avatar']['tmp_name'],$dest);
        $avatar = $dest;
    }
    $pdo->prepare("UPDATE users SET fullname=?,department=?,avatar=? WHERE id=?")->execute([$fullname,$department,$avatar,$user['id']]);
    $_SESSION['user']['fullname'] = $fullname;
    $_SESSION['user']['department'] = $department;
    $_SESSION['user']['avatar'] = $avatar;
    $msg = 'Saved';
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Settings - ClassFlow</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <?php include '../shared/left_nav.php'; ?>
  <main class="main-content">
    <div class="container py-4">
      <h3>Settings</h3>
      <?php if($msg): ?><div class="alert alert-success"><?php echo $msg; ?></div><?php endif; ?>
      <form method="post" enctype="multipart/form-data" class="card p-3">
        <div class="mb-2"><label>Full name</label><input name="fullname" class="form-control" value="<?php echo htmlspecialchars($user['fullname']) ?>"></div>
        <div class="mb-2"><label>Department</label><input name="department" class="form-control" value="<?php echo htmlspecialchars($user['department']) ?>"></div>
        <div class="mb-2"><label>Avatar</label><input type="file" name="avatar" class="form-control"></div>
        <div><button class="btn btn-orange">Save</button></div>
      </form>
    </div>
  </main>
</body>
</html>