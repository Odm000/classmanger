<?php
require_once 'config.php';
$pdo = pdo();
$err = '';
$show_welcome = false;
$welcome_target = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if($user && password_verify($password, $user['password'])){
        // store minimal user in session
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'fullname' => $user['fullname'],
            'role' => $user['role'],
            'avatar' => $user['avatar'],
            'department' => $user['department']
        ];
        // Instead of immediate header redirect, show welcome animation page that redirects via JS.
        if($user['role'] === 'admin'){
            $welcome_target = 'admin.php';
        } else {
            $welcome_target = 'faculty/dashboard.php';
        }
        $show_welcome = true;
    } else {
        $err = 'Invalid username or password';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>ClassFlow - Login</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    .welcome-brand { font-size:2rem; color:#ff8a00; animation: pop .7s ease; }
    @keyframes pop {
      0% { transform: scale(.6); opacity:0 }
      60% { transform: scale(1.05); opacity:1 }
      100% { transform: scale(1); opacity:1 }
    }
  </style>
</head>
<body class="bg-light">
  <?php if($show_welcome): ?>
    <!-- Welcome screen shown briefly then redirect -->
    <div class="d-flex vh-100 align-items-center justify-content-center">
      <div class="text-center">
        <div class="welcome-brand">Welcome to ClassFlow</div>
        <p class="lead mt-2">Signing you in…</p>
      </div>
    </div>
    <script>
      // brief delay so user sees animation, then redirect
      setTimeout(function(){
        // redirect to the appropriate dashboard
        window.location.href = '<?php echo addslashes($welcome_target) ?>';
      }, 1200);
      // Ensure fallback redirect after 5s
      setTimeout(function(){ window.location.href = '<?php echo addslashes($welcome_target) ?>'; }, 5000);
    </script>
  <?php else: ?>
    <div class="d-flex vh-100 align-items-center justify-content-center">
      <div class="card shadow-sm w-100" style="max-width:900px;">
        <div class="row g-0">
          <div class="col-md-6 d-none d-md-block bg-orange text-dark p-4">
            <img src="assets/img/classflow-logo.svg" alt="">
            <p class="lead">Modern student management — attendance, activities, grades.</p>
            <p class="small">Sign in to manage your classes and students now!.</p>
          </div>
          <div class="col-md-6 p-4">
            <h3 class="mb-3">Sign in</h3>
            <?php if($err): ?>
              <div class="alert alert-danger"><?php echo htmlspecialchars($err) ?></div>
            <?php endif;?>
            <form method="post" class="mb-3">
              <div class="mb-2">
                <label class="form-label">Username</label>
                <input name="username" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Password</label>
                <input name="password" type="password" class="form-control" required>
              </div>
              <button class="btn btn-orange w-100">Login</button>
            </form>
            <h6 class=''>Contact the admin to start you acount today</h6>
            <p class='small'>moralesoalden2@gmail.com</p>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</body>
</html>