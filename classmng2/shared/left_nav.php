<?php
// left navigation - included in pages
// This updated version highlights the clicked nav item and persists the highlight across pages.
// It uses server-side detection when possible and client-side localStorage fallback for click persistence.

require_once __DIR__ . '/../config.php';
$user = current_user();

// Helper to build absolute hrefs using BASE_URL (ensures links work from any folder)
function url($path) {
    $base = rtrim(BASE_URL, '/');
    return $base . '/' . ltrim($path, '/');
}

// Current full URL and path for server-side active detection
$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$currentPath = parse_url($currentUrl, PHP_URL_PATH);
?>
<style>
.main-content{ margin-left:260px; padding-top:20px; }
.left-sidebar{ width:260px; position:fixed; top:0; left:0; bottom:0; background:#fff; border-right:1px solid #eee; padding:20px; z-index:1000; overflow:auto;}
.avatar-sm{width:48px;height:48px;border-radius:8px;object-fit:cover;}
.nav-link.active{ background:linear-gradient(90deg, #ff8a00, #ff7000); color:#fff !important; border-radius:6px; }
.btn-orange{ background:#ff8a00; border-color:#ff8a00; color:white;}
.bg-orange{ background: linear-gradient(90deg,#ff8a00,#ff7000); }
.left-sidebar .nav-link{ display:block; padding:8px 10px; color:#333; border-radius:6px; text-decoration:none; margin-bottom:6px; }
.left-sidebar .nav-link:hover{ background:#fff1e6; color:#000; text-decoration:none; }
</style>

<div class="left-sidebar" id="classflow-left-nav">
  <div class="d-flex align-items-center mb-3">
    <img src="<?php echo htmlspecialchars($user['avatar'] ?? 'assets/img/default-avatar.png') ?>" class="avatar-sm me-2" alt="avatar">
    <div>
      <div class="fw-bold"><?php echo htmlspecialchars($user['fullname'] ?: $user['username']); ?></div>
      <small class="text-muted"><?php echo htmlspecialchars($user['role']); ?></small>
    </div>
  </div>
  <hr>

  <?php if($user['role']==='admin'): ?>
    <?php
      $links = [
        ['label'=>'Admin Panel','href'=>url('classmng2/admin.php')],
        ['label'=>'Backup / Export','href'=>url('classmng2/backup.php')],
        ['label'=>'Logout','href'=>url('classmng2/logout.php')],
      ];
    ?>
    <?php foreach($links as $l): 
      // Server-side active detection by path
      $isActive = ($currentPath === parse_url($l['href'], PHP_URL_PATH));
    ?>
      <a class="nav-link <?php echo $isActive ? 'active' : '' ?>" href="<?php echo $l['href'] ?>" data-route="<?php echo htmlspecialchars(parse_url($l['href'], PHP_URL_PATH)) ?>">
        <?php echo htmlspecialchars($l['label']) ?>
      </a>
    <?php endforeach; ?>

  <?php else: ?>
    <?php
      $links = [
        ['label'=>'Dashboard','href'=>url('classmng2/faculty/dashboard.php')],
        //['label'=>'Subjects','href'=>url('classmng2/faculty/subjects.php')],
        ['label'=>'Attendance','href'=>url('classmng2/faculty/attendance.php')],
        ['label'=>'Activities','href'=>url('classmng2/faculty/activities.php')],
        ['label'=>'Archived Students','href'=>url('classmng2/faculty/archives.php')],
        ['label'=>'Archived Subjects','href'=>url('classmng2/faculty/archived_subjects.php')],
        ['label'=>'Settings','href'=>url('classmng2/faculty/settings.php')],
        ['label'=>'Logout','href'=>url('classmng2/logout.php')],
        ['label'=>'About','href'=>url('classmng2/about.php')],
      ];
    ?>
    <?php foreach($links as $l):
      $isActive = ($currentPath === parse_url($l['href'], PHP_URL_PATH));
    ?>
      <a class="nav-link <?php echo $isActive ? 'active' : '' ?>" href="<?php echo $l['href'] ?>" data-route="<?php echo htmlspecialchars(parse_url($l['href'], PHP_URL_PATH)) ?>">
        <?php echo htmlspecialchars($l['label']) ?>
      </a>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
<script>
// Client-side behavior to persist highlight when a nav item is clicked.
// It uses the absolute resolved pathname stored in data-route to mark active links.
// It also falls back to localStorage (key: 'classflow_nav_active') so the clicked link remains highlighted across page loads.

(function(){
  const nav = document.getElementById('classflow-left-nav');
  if (!nav) return;

  const links = nav.querySelectorAll('.nav-link[data-route]');

  // Utility to clear active
  function clearActive() {
    links.forEach(l => l.classList.remove('active'));
  }

  // Mark active by matching current location or by localStorage key
  function applyActiveFromLocationOrStorage() {
    const currentPath = window.location.pathname;
    let matched = false;

    // First try to match by exact pathname
    links.forEach(link => {
      const route = link.getAttribute('data-route');
      if (!route) return;
      // Normalize both - ensure they start with '/'
      const r = route.startsWith('/') ? route : ('/' + route);
      if (currentPath === r) {
        clearActive();
        link.classList.add('active');
        matched = true;
      }
    });

    if (matched) {
      // store for future
      try { localStorage.setItem('classflow_nav_active', currentPath); } catch(e){}
      return;
    }

    // If no match, try localStorage
    try {
      const saved = localStorage.getItem('classflow_nav_active');
      if (saved) {
        links.forEach(link => {
          const route = link.getAttribute('data-route');
          const r = route && route.startsWith('/') ? route : ('/' + route);
          if (r === saved) {
            clearActive();
            link.classList.add('active');
            matched = true;
          }
        });
      }
    } catch(e){ /* localStorage may be unavailable */ }
  }

  // Attach click listeners to set localStorage before navigation
  links.forEach(link => {
    link.addEventListener('click', function(e){
      try {
        const route = this.getAttribute('data-route');
        const r = route && route.startsWith('/') ? route : ('/' + route);
        localStorage.setItem('classflow_nav_active', r);
      } catch(err){}
      // allow navigation to proceed naturally
    });
  });

  // Apply initial active state on DOMContentLoaded
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', applyActiveFromLocationOrStorage);
  } else {
    applyActiveFromLocationOrStorage();
  }
})();
</script>