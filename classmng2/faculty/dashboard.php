<?php
require_once '../config.php';
require_login();
$user = current_user();
if($user['role'] !== 'faculty'){
    header("Location: ../index.php"); exit;
}
$pdo = pdo();

// fetch subjects for faculty with student counts
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

// fetch distinct courses for dropdown
$coursesStmt = $pdo->query("SELECT DISTINCT course FROM students WHERE course != '' ORDER BY course");
$courses = $coursesStmt->fetchAll(PDO::FETCH_COLUMN);

// Handle add subject (unchanged)
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subject'])){
    $name = trim($_POST['name']);
    $code = trim($_POST['code']);
    $schedule = trim($_POST['schedule']);
    $stmt = $pdo->prepare("INSERT INTO subjects (faculty_id,name,code,schedule) VALUES (?,?,?,?)");
    $stmt->execute([$user['id'],$name,$code,$schedule]);
    header("Location: dashboard.php");
    exit;
}

// Handle add multiple students
$addMultipleError = '';
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_multiple'])){
    $subject_id = intval($_POST['subject_id'] ?? 0);
    $year_level = trim($_POST['year_level'] ?? '');
    $course_select = trim($_POST['course_select'] ?? '');
    $course_new = trim($_POST['course_new'] ?? '');
    $lastnames = $_POST['lastname'] ?? [];
    $firstnames = $_POST['firstname'] ?? [];

    if($subject_id <= 0){
        $addMultipleError = 'Please select a subject to enroll students into.';
    } else {
        // determine course to use
        $course_to_use = $course_new !== '' ? $course_new : $course_select;

        if($course_to_use === ''){
            // allow empty course but warn? We'll allow empty string
            $course_to_use = '';
        }

        // prepare insert
        $insert = $pdo->prepare("INSERT INTO students (subject_id, lastname, firstname, course, year_level, created_at) VALUES (?,?,?,?,?,NOW())");
        $addedCount = 0;
        foreach($lastnames as $i => $ln){
            $ln = trim($ln);
            $fn = trim($firstnames[$i] ?? '');
            if($ln === '' && $fn === '') continue; // skip empty rows
            try {
                $insert->execute([$subject_id, $ln, $fn, $course_to_use, $year_level]);
                $addedCount++;
            } catch (Exception $e){
                $addMultipleError .= "Failed to add {$ln} {$fn}: " . $e->getMessage() . "\n";
            }
        }

        if($addedCount > 0 && $addMultipleError === ''){
            // success: redirect so counts poller refreshes
            header("Location: dashboard.php?added_multiple=1");
            exit;
        } elseif($addedCount > 0) {
            // partial success
            header("Location: dashboard.php?added_multiple=1&partial=1");
            exit;
        } else {
            if($addMultipleError === '') $addMultipleError = 'No valid student rows provided.';
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Faculty Dashboard - ClassFlow</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <style>
    .count-badge { font-weight:600; color:#fff; background:#ff8a00; padding:2px 8px; border-radius:12px; font-size:0.9rem; }
    .subject-card { min-height:160px; }
    .student-row .remove-row { margin-top: 0.25rem; }
  </style>
</head>
<body>
  <?php include '../shared/left_nav.php'; ?>
  <main class="main-content">
    <div class="container-fluid py-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h2 style="color:orange;"><b>ClassFlow</b></h2>
          <h5 class="text-muted"><?php echo htmlspecialchars($user['fullname']); ?></h5>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#addSubModal">Add Subject</button>
          <button class="btn btn-orange" data-bs-toggle="modal" data-bs-target="#addMultipleModal">Add Multiple</button>
        </div>
      </div>

      <!-- subjects list -->
      <div class="row g-3" id="subjectsRow">
        <?php if(empty($subs)): ?>
          <div class="col-12">
            <div class="card p-4 text-center">
              <p class="mb-0">No subjects yet. Add one to begin.</p>
            </div>
          </div>
        <?php endif; ?>
        <?php foreach($subs as $s): ?>
          <div class="col-md-4 subject-col" data-subject-id="<?php echo $s['id'] ?>">
            <div class="card subject-card h-100" style="border-top:3px solid #ff8a00;" data-subject-id="<?php echo $s['id'] ?>">
              <div class="card-body d-flex flex-column">
                <h5>
                  <?php echo htmlspecialchars($s['name']); ?>
                  <span class="ms-2 count-badge" data-subject-id="<?php echo $s['id'] ?>"><?php echo (int)$s['student_count']; ?></span>
                </h5>
                <div class="text-muted mb-2 small"><?php echo htmlspecialchars($s['code']); ?> • <?php echo htmlspecialchars($s['schedule']); ?></div>
                <div class="mt-auto d-flex gap-2">
                  <a href="subject.php?id=<?php echo $s['id'] ?>" class="btn btn-orange btn-sm">Open</a>
                  <a href="edit_subject.php?id=<?php echo $s['id'] ?>" class="btn btn-outline-secondary btn-sm">Edit</a>
                  <button type="button" class="btn btn-outline-danger btn-sm archive-subject-btn" data-subject-id="<?php echo $s['id'] ?>">Archive</button>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

    </div>
  </main>

  <!-- Add Subject Modal -->
  <div class="modal fade" id="addSubModal" tabindex="-1">
    <div class="modal-dialog">
      <form method="post" class="modal-content">
        <input type="hidden" name="add_subject" value="1">
        <div class="modal-header">
          <h5 class="modal-title">Add Subject</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2">
            <label>Subject name</label>
            <input name="name" class="form-control" required>
          </div>
          <div class="mb-2">
            <label>Code</label>
            <input name="code" class="form-control">
          </div>
          <div class="mb-2">
            <label>Schedule (e.g. M,W,F - 5:00-7:00)</label>
            <input name="schedule" class="form-control">
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-orange">Add Subject</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Add Multiple Modal -->
  <div class="modal fade" id="addMultipleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <form method="post" class="modal-content" id="addMultipleForm">
        <input type="hidden" name="add_multiple" value="1">
        <div class="modal-header">
          <h5 class="modal-title">Add Multiple Students</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <?php if($addMultipleError): ?>
            <div class="alert alert-danger"><?php echo nl2br(htmlspecialchars($addMultipleError)); ?></div>
          <?php endif; ?>

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label">Enroll into Subject <span class="text-danger">*</span></label>
              <select name="subject_id" class="form-select" required id="enrollSubjectSelect">
                <option value="">-- Select subject --</option>
                <?php foreach($subs as $s): ?>
                  <option value="<?php echo $s['id'] ?>"><?php echo htmlspecialchars($s['name'] . ' — ' . $s['code']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Course (choose existing)</label>
              <select name="course_select" class="form-select">
                <option value="">-- Select course --</option>
                <?php foreach($courses as $c): ?>
                  <option value="<?php echo htmlspecialchars($c) ?>"><?php echo htmlspecialchars($c) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Or add new course</label>
              <input name="course_new" class="form-control" placeholder="Type new course (optional)">
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-3">
              <label class="form-label">Year Level</label>
              <select name="year_level" class="form-select">
                <option value="">-- Year level --</option>
                <option>1</option>
                <option>2</option>
                <option>3</option>
                <option>4</option>
              </select>
            </div>
          </div>

          <hr>
          <div id="multipleRows">
            <div class="row mb-2 student-row">
              <div class="col-md-5">
                <input type="text" name="lastname[]" class="form-control" placeholder="Lastname">
              </div>
              <div class="col-md-5">
                <input type="text" name="firstname[]" class="form-control" placeholder="Firstname">
              </div>
              <div class="col-md-2">
                <button type="button" class="btn btn-danger remove-row">Remove</button>
              </div>
            </div>
          </div>

          <div class="mt-2">
            <button type="button" id="addRowBtn" class="btn btn-outline-secondary btn-sm">Add another student row</button>
          </div>

        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-orange">Add Students</button>
        </div>
      </form>
    </div>
  </div>

<script>
(function(){
  // localStorage key used for archived subjects
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
  function archiveSubject(id){
    if(!id && id !== 0) return;
    const arr = getArchived();
    if(arr.indexOf(String(id)) === -1){
      arr.push(String(id));
      setArchived(arr);
    }
    // hide card
    const col = document.querySelector('.subject-col[data-subject-id="'+id+'"]');
    if(col) col.remove();
    // remove option from enroll select
    const opt = document.querySelector('#enrollSubjectSelect option[value="'+id+'"]');
    if(opt) opt.remove();
  }
  function restoreSubject(id){
    if(!id && id !== 0) return;
    let arr = getArchived();
    arr = arr.filter(x => String(x) !== String(id));
    setArchived(arr);
    // If user remains on dashboard, optionally reload so subject reappears
    // For now do nothing (page reload recommended)
    location.reload();
  }

  // fetch counts periodically (uses get_subject_counts.php in your repo)
  async function fetchCounts(){
    try {
      const res = await fetch('get_subject_counts.php');
      if(!res.ok) return;
      const data = await res.json();
      document.querySelectorAll('.count-badge').forEach(el=>{
        const id = el.getAttribute('data-subject-id');
        el.textContent = data[id] ?? el.textContent;
      });
    } catch(e){
      console.error(e);
    }
  }
  fetchCounts();
  setInterval(fetchCounts, 5000);

  // Add/remove student rows
  const addRowBtn = document.getElementById('addRowBtn');
  const multipleRows = document.getElementById('multipleRows');

  function attachRemoveHandlers(){
    multipleRows.querySelectorAll('.remove-row').forEach(btn=>{
      btn.onclick = function(){
        const rows = multipleRows.querySelectorAll('.student-row');
        if(rows.length <= 1){
          // clear inputs instead of removing last
          rows[0].querySelectorAll('input').forEach(i=> i.value = '');
          return;
        }
        this.closest('.student-row').remove();
      };
    });
  }
  attachRemoveHandlers();

  addRowBtn.addEventListener('click', function(){
    const first = multipleRows.querySelector('.student-row');
    const clone = first.cloneNode(true);
    clone.querySelectorAll('input').forEach(i => i.value = '');
    multipleRows.appendChild(clone);
    attachRemoveHandlers();
  });

  // If redirected after adding multiple, refresh counts and show a brief success
  if(location.search.indexOf('added_multiple=1') !== -1){
    fetchCounts();
    // optional: show a toast or alert
    history.replaceState({}, document.title, location.pathname);
  }

  // On load: remove archived subject elements from dashboard
  document.addEventListener('DOMContentLoaded', function(){
    const archived = getArchived();
    if(archived.length){
      archived.forEach(id=>{
        const col = document.querySelector('.subject-col[data-subject-id="'+id+'"]');
        if(col) col.remove();
      });

      // remove archived subjects from enroll select
      const select = document.getElementById('enrollSubjectSelect');
      if(select){
        archived.forEach(id=>{
          const opt = select.querySelector('option[value="'+id+'"]');
          if(opt) opt.remove();
        });
      }
    }
  });

  // Delegate clicks for archive/restore
  document.addEventListener('click', function(e){
    const target = e.target;
    if(target.matches('.archive-subject-btn')){
      const id = target.getAttribute('data-subject-id');
      if(!id) return;
      if(confirm('Archive this subject? It will appear under Archived Subjects.')){
        archiveSubject(id);
      }
      return;
    }
    if(target.matches('.restore-subject-btn')){
      const id = target.getAttribute('data-subject-id');
      if(!id) return;
      if(confirm('Restore this subject to Dashboard?')){
        restoreSubject(id);
      }
      return;
    }
  });

})();
</script>

</body>
</html>