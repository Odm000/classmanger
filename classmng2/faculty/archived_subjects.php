<?php
require_once '../config.php';
require_login();
$user = current_user();
if($user['role'] !== 'faculty'){
    header("Location: ../index.php"); exit;
}
$pdo = pdo();

// fetch subjects for faculty with student counts (same query as dashboard)
$stmt = $pdo->prepare("
  SELECT s.id, s.name, s.code, s.schedule, s.created_at,
         COUNT(st.id) AS student_count
  FROM subjects s
  LEFT JOIN students st ON st.subject_id = s.id AND st.archived = 0
  WHERE s.faculty_id = ?
  GROUP BY s.id
  ORDER BY s.created_at DESC
");
$stmt->execute([$user['id']]);
$subs = $stmt->fetchAll();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Archived Subjects - ClassFlow</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <style>
    .count-badge { font-weight:600; color:#fff; background:#ff8a00; padding:2px 8px; border-radius:12px; font-size:0.9rem; }
    .subject-card { min-height:160px; }
  </style>
</head>
<body>
  <?php include '../shared/left_nav.php'; ?>
  <main class="main-content">
    <div class="container-fluid py-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h2 style="color:orange;"><b>Archived Subjects</b></h2>
          <h5 class="text-muted"><?php echo htmlspecialchars($user['fullname']); ?></h5>
        </div>
        <div>
          <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
        </div>
      </div>

      <div class="row g-3" id="archivedSubjectsRow">
        <!-- server renders all subjects; client JS will show only archived ones -->
        <?php if(empty($subs)): ?>
          <div class="col-12">
            <div class="card p-4 text-center">
              <p class="mb-0">No subjects yet.</p>
            </div>
          </div>
        <?php endif; ?>

        <?php foreach($subs as $s): ?>
          <div class="col-md-4 archived-subject-col" data-subject-id="<?php echo $s['id'] ?>">
            <div class="card subject-card h-100" style="border-top:3px solid #999;" data-subject-id="<?php echo $s['id'] ?>">
              <div class="card-body d-flex flex-column">
                <h5>
                  <?php echo htmlspecialchars($s['name']); ?>
                  <span class="ms-2 count-badge" data-subject-id="<?php echo $s['id'] ?>"><?php echo (int)$s['student_count']; ?></span>
                </h5>
                <div class="text-muted mb-2 small"><?php echo htmlspecialchars($s['code']); ?> • <?php echo htmlspecialchars($s['schedule']); ?></div>
                <div class="mt-auto d-flex gap-2">
                  <a href="subject.php?id=<?php echo $s['id'] ?>" class="btn btn-outline-secondary btn-sm">Open</a>
                  <button type="button" class="btn btn-success btn-sm restore-subject-btn" data-subject-id="<?php echo $s['id'] ?>">Restore</button>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>

      </div>

      <div id="noArchivedMessage" class="card p-4 text-center mt-3" style="display:none;">
        <p class="mb-0">No archived subjects found.</p>
      </div>

    </div>
  </main>

<script>
(function(){
  const ARCH_KEY = 'classflow_archived_subjects';

  function getArchived(){
    try {
      const v = localStorage.getItem(ARCH_KEY);
      if(!v) return [];
      const arr = JSON.parse(v);
      if(Array.isArray(arr)) return arr.map(x => String(x));
    } catch(e){}
    return [];
  }
  function setArchived(arr){
    try { localStorage.setItem(ARCH_KEY, JSON.stringify(arr)); } catch(e){}
  }
  function isArchived(id){
    if(!id && id !== 0) return false;
    const arr = getArchived();
    return arr.indexOf(String(id)) !== -1;
  }
  function restoreSubject(id){
    let arr = getArchived();
    arr = arr.filter(x => String(x) !== String(id));
    setArchived(arr);
    // remove card from page
    const col = document.querySelector('.archived-subject-col[data-subject-id="'+id+'"]');
    if(col) col.remove();
    checkNoArchived();
  }

  function checkNoArchived(){
    const any = document.querySelectorAll('.archived-subject-col').length;
    const displayed = document.querySelectorAll('.archived-subject-col').length;
    // Actually we removed non-archived ones below; if none visible then show message
    const visible = Array.from(document.querySelectorAll('.archived-subject-col')).filter(c => c.offsetParent !== null).length;
    if(visible === 0){
      document.getElementById('noArchivedMessage').style.display = 'block';
    } else {
      document.getElementById('noArchivedMessage').style.display = 'none';
    }
  }

  document.addEventListener('DOMContentLoaded', function(){
    const archived = getArchived();
    const allCols = document.querySelectorAll('.archived-subject-col');
    // Hide any subject that is not archived, show only archived ones
    allCols.forEach(col=>{
      const id = col.getAttribute('data-subject-id');
      if(!isArchived(id)){
        col.style.display = 'none';
      } else {
        col.style.display = ''; // ensure visible
      }
    });
    checkNoArchived();
  });

  document.addEventListener('click', function(e){
    const t = e.target;
    if(t.matches('.restore-subject-btn')){
      const id = t.getAttribute('data-subject-id');
      if(!id) return;
      if(confirm('Restore this subject to the Dashboard?')){
        restoreSubject(id);
      }
    }
  });
})();
</script>

</body>
</html>