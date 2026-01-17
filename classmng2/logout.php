<?php
require_once 'config.php';

// Destroy session and clear session cookie immediately so the user is effectively logged out.
// We still render a friendly "Logging out..." animation before redirecting to the login page.
if (session_status() === PHP_SESSION_ACTIVE) {
    // Unset all of the session variables.
    $_SESSION = [];

    // If it's desired to kill the session, also delete the session cookie.
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Finally, destroy the session.
    session_destroy();
}
$redirect = rtrim(BASE_URL, '/') . 'index.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Logging out — ClassFlow</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body{ background: linear-gradient(180deg,#fff,#fff); font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; }
    .logout-wrap { min-height:100vh; display:flex; align-items:center; justify-content:center; }
    .card-anim { width:360px; text-align:center; padding:36px; border-radius:12px; box-shadow: 0 10px 30px rgba(0,0,0,0.06); background: #fff; }
    .brand { font-weight:700; color:#ff8a00; display:flex; align-items:center; gap:10px; justify-content:center; }
    .brand .dot { width:12px; height:12px; border-radius:50%; background:linear-gradient(90deg,#ff9a2a,#ff6b00); box-shadow: 0 6px 18px rgba(255,138,0,0.28); transform-origin:center; animation: pulse 1.6s infinite; }
    @keyframes pulse {
      0% { transform: scale(1); opacity:1; }
      50% { transform: scale(1.18); opacity:0.85; }
      100% { transform: scale(1); opacity:1; }
    }
    .spinner-ring {
      display:inline-block;
      width:72px;
      height:72px;
    }
    .spinner-ring div {
      box-sizing: border-box;
      display: block;
      position: absolute;
      width: 56px;
      height: 56px;
      margin: 8px;
      border: 6px solid #ff8a00;
      border-radius: 50%;
      animation: spinner 1.2s cubic-bezier(.5, .1, .2, 1) infinite;
      border-color: #ff8a00 transparent transparent transparent;
    }
    .spinner-container { position:relative; height:80px; margin-bottom:18px; }
    .spinner-ring div:nth-child(1) { animation-delay: -0.45s; }
    .spinner-ring div:nth-child(2) { animation-delay: -0.3s; }
    .spinner-ring div:nth-child(3) { animation-delay: -0.15s; }
    @keyframes spinner {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    .msg { font-size:16px; margin-top:8px; color:#333; }
    .sub { color:#6b7280; font-size:13px; margin-top:6px; }
    .fade-out { animation: fadeOut 0.6s ease-in forwards; animation-delay:1.2s; }
    @keyframes fadeOut { to { opacity:0; transform: translateY(-6px); } }
  </style>
  <meta http-equiv="refresh" content="1.6;url=<?php echo htmlspecialchars($redirect) ?>">
</head>
<body>
  <div class="logout-wrap">
    <div class="card-anim fade-in" id="card">
      <div class="brand mb-3">
        <span class="dot" aria-hidden="true"></span>
        <span style="font-size:20px;">ClassFlow</span>
      </div>

      <div class="spinner-container d-flex justify-content-center align-items-center">
        <div class="spinner-ring" role="status" aria-hidden="true">
          <div></div><div></div><div></div>
        </div>
      </div>

      <div class="msg">Logging out...</div>
      <div class="sub">You're being safely signed out. Redirecting to the login page.</div>
    </div>
  </div>

<script>
  // Add a short page-exit animation then navigate (serves as a UX polish).
  (function(){
    // Fallback JS redirect in case meta refresh isn't honored
    var redirectTo = "<?php echo htmlspecialchars($redirect) ?>";
    setTimeout(function(){
      // add fade-out class to card for nicer transition
      var c = document.getElementById('card');
      if(c) c.classList.add('fade-out');
    }, 1100);

    setTimeout(function(){
      window.location.href = 'http://localhost:8080/classmng2/index.php';
    }, 1600);
  })();
</script>
</body>
</html>