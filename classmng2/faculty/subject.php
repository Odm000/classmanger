<?php
require_once '../config.php';
require_login();
$user = current_user();
if($user['role'] !== 'faculty'){ header("Location: ../index.php"); exit; }
$pdo = pdo();
$id = intval($_GET['id'] ?? 0);
$sub = $pdo->prepare("SELECT * FROM subjects WHERE id=? AND faculty_id=?");
$sub->execute([$id,$user['id']]);
$sub = $sub->fetch();
if(!$sub){ header("Location: subjects.php"); exit; }

// Add student
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_student'])){
    $ln = $_POST['lastname']; $fn = $_POST['firstname']; $course = $_POST['course']; $year = $_POST['year'];
    $avatar = 'assets/img/default-avatar.png';
    $stmt = $pdo->prepare("INSERT INTO students (subject_id,lastname,firstname,course,year_level,avatar) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$id,$ln,$fn,$course,$year,$avatar]);
    $sid = $pdo->lastInsertId();
    // create initial grades row
    $pdo->prepare("INSERT INTO grades (student_id,subject_id) VALUES (?,?)")->execute([$sid,$id]);
    header("Location: subject.php?id=$id");
    exit;
}

// Multi-add students (simple newline-separated rows: firstname,lastname,course,year)
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['multi_add'])){
    $bulk = trim($_POST['bulk']);
    $lines = preg_split("/\r\n|\n|\r/", $bulk);
    foreach($lines as $ln){
        if(trim($ln)==='') continue;
        // expecting: Lastname, Firstname, Course, Year
        $parts = array_map('trim', explode(',', $ln));
        if(count($parts) < 2) continue;
        $lastname = $parts[0];
        $firstname = $parts[1];
        $course = $parts[2] ?? '';
        $year = $parts[3] ?? '';
        $pdo->prepare("INSERT INTO students (subject_id,lastname,firstname,course,year_level) VALUES (?,?,?,?,?)")->execute([$id,$lastname,$firstname,$course,$year]);
        $last = $pdo->lastInsertId();
        $pdo->prepare("INSERT INTO grades (student_id,subject_id) VALUES (?,?)")->execute([$last,$id]);
    }
    header("Location: subject.php?id=$id");
    exit;
}

// fetch students
$students = $pdo->prepare("SELECT * FROM students WHERE subject_id = ? AND archived = 0 ORDER BY lastname");
$students->execute([$id]);
$students = $students->fetchAll();

// counts present today
$today = date('Y-m-d');
$present_counts = $pdo->prepare("SELECT student_id, COUNT(*) AS cnt FROM attendance WHERE date = ? AND status='present' GROUP BY student_id");
$present_counts->execute([$today]);
$present_map = [];
foreach($present_counts->fetchAll() as $p) $present_map[$p['student_id']] = $p['cnt'];

// Additional aggregates for list view: activities, attendance counts, present/absent totals and detailed activities
$activities_map = [];         // student_id => array of activities (each: ['name'=>..., 'score'=>..., 'created_at'=>...])
$activities_count_map = [];   // student_id => count (all activities - used for tiles)
$activities_list_map = [];    // student_id => array of activities filtered for list view (score != 0)
$activities_list_count_map = []; // student_id => count (filtered - used for list view)
$attendance_map = [];         // student_id => total attendance rows
$present_total_map = [];      // student_id => present days
$absent_total_map = [];       // student_id => absent days

if(!empty($students)){
    $ids = array_column($students, 'id');
    // build placeholders
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    // Inspect activities table columns to know which fields to select
    $actCols = [];
    try {
        $colsInfo = $pdo->query("SHOW COLUMNS FROM activities")->fetchAll(PDO::FETCH_ASSOC);
        $actCols = array_column($colsInfo, 'Field');
    } catch (Exception $e) {
        // table might not exist or permission denied; keep actCols empty
        $actCols = [];
    }

    // Determine which column to use for activity "name/title"
    if (in_array('title', $actCols)) {
        $nameCol = 'title';
    } elseif (in_array('name', $actCols)) {
        $nameCol = 'name';
    } elseif (in_array('activity', $actCols)) {
        $nameCol = 'activity';
    } else {
        $nameCol = null;
    }

    // Determine score/date column availability
    $scoreCol = in_array('score', $actCols) ? 'score' : (in_array('points', $actCols) ? 'points' : null);
    $dateCol  = in_array('created_at', $actCols) ? 'created_at' : (in_array('date', $actCols) ? 'date' : null);

    // Build select list dynamically and alias columns to consistent keys (name, score, created_at)
    $selectCols = ['student_id'];
    if ($nameCol) $selectCols[] = "`$nameCol` AS `name`";
    if ($scoreCol) $selectCols[] = "`$scoreCol` AS `score`";
    if ($dateCol)  $selectCols[] = "`$dateCol` AS `created_at`";

    $selectSqlPart = implode(',', $selectCols);

    // Build base SQL with student filter
    $baseSql = "SELECT $selectSqlPart FROM activities WHERE student_id IN ($placeholders)";

    // If subject_id exists in activities table, include subject filter
    $useSubjectFilter = in_array('subject_id', $actCols);

    try {
        if ($useSubjectFilter) {
            $sqlAct = $baseSql . " AND subject_id = ? ORDER BY " . ($dateCol ? 'created_at DESC' : 'student_id DESC');
            $stmtAct = $pdo->prepare($sqlAct);
            $params = $ids;
            $params[] = $id;
            $stmtAct->execute($params);
        } else {
            $sqlAct = $baseSql . " ORDER BY " . ($dateCol ? 'created_at DESC' : 'student_id DESC');
            $stmtAct = $pdo->prepare($sqlAct);
            $stmtAct->execute($ids);
        }
    } catch (PDOException $e) {
        // fallback select minimal columns
        $fallbackCols = ['student_id'];
        if ($scoreCol) $fallbackCols[] = "`$scoreCol` AS `score`";
        $fallbackSelect = implode(',', $fallbackCols);
        $fallbackSql = "SELECT $fallbackSelect FROM activities WHERE student_id IN ($placeholders)";

        try {
            $stmtAct = $pdo->prepare($fallbackSql);
            $stmtAct->execute($ids);
        } catch (Exception $_e) {
            $stmtAct = false;
        }
    }

    if ($stmtAct) {
        foreach($stmtAct->fetchAll(PDO::FETCH_ASSOC) as $r){
            $sid = $r['student_id'];
            if(!isset($activities_map[$sid])) $activities_map[$sid] = [];
            $activities_map[$sid][] = [
                'name' => isset($r['name']) ? $r['name'] : (isset($r['title']) ? $r['title'] : 'Activity'),
                'score' => isset($r['score']) ? $r['score'] : '',
                'created_at' => isset($r['created_at']) ? $r['created_at'] : null
            ];
        }
    }

    foreach($activities_map as $k=>$v) $activities_count_map[$k] = count($v);

    // Build filtered activities for list view: exclude those with numeric score == 0
    foreach($activities_map as $sid => $alist){
        $filtered = array_filter($alist, function($a){
            // treat missing score as exclude (user asked to not include 0.00 or 0)
            if(!isset($a['score']) || $a['score'] === '') return false;
            return floatval($a['score']) != 0.0;
        });
        $filtered = array_values($filtered);
        $activities_list_map[$sid] = $filtered;
        $activities_list_count_map[$sid] = count($filtered);
    }

    // total attendance per student (total rows, regardless of status)
    $sql2 = "SELECT student_id, COUNT(*) AS cnt FROM attendance WHERE student_id IN ($placeholders) GROUP BY student_id";
    $stmt2 = $pdo->prepare($sql2);
    $stmt2->execute($ids);
    foreach($stmt2->fetchAll() as $r) $attendance_map[$r['student_id']] = $r['cnt'];

    // present / absent counts per student.
    try {
        // check attendance columns
        $attCols = [];
        try {
            $attColsInfo = $pdo->query("SHOW COLUMNS FROM attendance")->fetchAll(PDO::FETCH_ASSOC);
            $attCols = array_column($attColsInfo, 'Field');
        } catch (Exception $e) {
            $attCols = [];
        }

        if (in_array('subject_id', $attCols)) {
            $sql3 = "SELECT student_id,
                        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) AS present_cnt,
                        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) AS absent_cnt
                     FROM attendance
                     WHERE student_id IN ($placeholders) AND subject_id = ?
                     GROUP BY student_id";
            $stmt3 = $pdo->prepare($sql3);
            $params = $ids;
            $params[] = $id;
            $stmt3->execute($params);
        } else {
            $sql3 = "SELECT student_id,
                        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) AS present_cnt,
                        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) AS absent_cnt
                     FROM attendance
                     WHERE student_id IN ($placeholders)
                     GROUP BY student_id";
            $stmt3 = $pdo->prepare($sql3);
            $stmt3->execute($ids);
        }
    } catch (PDOException $e) {
        $stmt3 = false;
    }

    if ($stmt3) {
        foreach($stmt3->fetchAll() as $r){
            $present_total_map[$r['student_id']] = intval($r['present_cnt'] ?? 0);
            $absent_total_map[$r['student_id']] = intval($r['absent_cnt'] ?? 0);
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title><?php echo htmlspecialchars($sub['name']) ?> - ClassFlow</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    /* minor adjustments for table view */
    #listView { display: none; }
    .cursor-pointer { cursor: pointer; }
    .tile-attendance { font-size: 0.85rem; color: #6c757d; margin-top: 6px; }
    .activities-list { margin-top: 8px; text-align: left; }
    .activities-list .act-item { font-size: 0.9rem; padding: 4px 0; border-bottom: 1px dashed #e9ecef; }
    .activity-details-row { display: none; background: #fafafa; }
    .activity-details-table td, .activity-details-table th { vertical-align: middle; }
  </style>
</head>
<body>
  <?php include '../shared/left_nav.php'; ?>
  <main class="main-content">
    <div class="container py-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h3><?php echo htmlspecialchars($sub['name']); ?></h3>
          <div class="text-muted small"><?php echo htmlspecialchars($sub['code']) ?> • <?php echo htmlspecialchars($sub['schedule']) ?></div>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#multiAddModal">Add Multiple</button>
          <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#addStudentModal">Add Student</button>
          <button id="toggleViewBtn" class="btn btn-outline-secondary">View as list</button>
        </div>
      </div>
       <input id="search" class="form-control form-control-sm search-input" placeholder="Search by name or subject..."> <br>
       <br>
      <div id="tileView" class="row g-3">
        <?php if(empty($students)): ?>
          <div class="col-12"><div class="card p-4 text-center">No students yet. Add some.</div></div>
        <?php endif; ?>
        <?php foreach($students as $st):
            $pid = $st['id'];
            $presentDays = $present_total_map[$pid] ?? 0;
            $absentDays  = $absent_total_map[$pid] ?? 0;
            $acts_tile = $activities_map[$pid] ?? []; // tile view shows all activities (including zero-score)
        ?>
        <div class="col-sm-6 col-md-4 student-tile-col">
  <div class="card student-tile h-100"
       data-id="<?php echo $st['id'] ?>"
       data-lastname="<?php echo strtolower($st['lastname']) ?>"
       data-firstname="<?php echo strtolower($st['firstname']) ?>">
              <div class="card-body text-center">
                <img src="../<?php echo htmlspecialchars($st['avatar']) ?>" class="rounded-circle mb-2" style="width:80px;height:80px;object-fit:cover;">
                <h6 class="mb-0"><?php echo htmlspecialchars($st['lastname'] . ', ' . $st['firstname']) ?></h6>
                <div class="small text-muted"><?php echo htmlspecialchars($st['course'].' • '.$st['year_level']) ?></div>
                <div class="mt-2">
                  <button class="btn btn-sm btn-secondary view-student" data-id="<?php echo $st['id'] ?>">View</button>
                  <button class="btn btn-sm btn-outline-primary toggle-activities" data-id="<?php echo $st['id'] ?>">Activities (<?php echo intval($activities_count_map[$pid] ?? 0) ?>)</button>
                </div>
                <div class="mt-2">
                  <?php if(!empty($present_map[$st['id']])): ?>
                    <span class="badge bg-success">Present</span>
                  <?php endif; ?>
                </div>
                <div class="tile-attendance">
                  Present: <?php echo intval($presentDays) ?> &nbsp;•&nbsp; Absent: <?php echo intval($absentDays) ?>
                </div>

                <div class="activities-list mt-2" id="tile-activities-<?php echo $pid ?>" style="display:none;">
                  <?php if(empty($acts_tile)): ?>
                    <div class="text-muted small">No activities recorded.</div>
                  <?php else: ?>
                    <?php foreach($acts_tile as $a): ?>
                      <div class="act-item"><strong><?php echo htmlspecialchars($a['name']) ?></strong> — <span class="text-muted">Score: <?php echo htmlspecialchars($a['score']) ?></span></div>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </div>

              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- List view -->
      <div id="listView" class="card">
        <div class="card-body">
          <div class="d-flex justify-content-end mb-2">
            <!-- This button appears only in list view and switches back to tiles -->
            <button id="toggleViewBtnList" class="btn btn-outline-secondary" style="display:none;">View as tiles</button>
          </div>
          <div class="table-responsive">
            <table id="studentsTable" class="table table-striped table-hover align-middle">
              <thead>
                <tr>
                  <th>Lastname</th>
                   <th>Firstname</th>
                  <th>Course</th>
                  <th>Activities</th>
                  <th>Attendances</th>
                  <th>Present Days</th>
                  <th>Absent Days</th>
                  <th>Present Today</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($students as $st):
                  $aid = $st['id'];
                  // Use filtered (non-zero) activities for list view
                  $actCnt = $activities_list_count_map[$aid] ?? 0;
                  $acts = $activities_list_map[$aid] ?? [];
                  $attCnt = $attendance_map[$aid] ?? 0;
                  $presentToday = !empty($present_map[$aid]);
                  $presentDays = $present_total_map[$aid] ?? 0;
                  $absentDays  = $absent_total_map[$aid] ?? 0;
                ?>
                <tr class="student-row" data-lastname="<?php echo strtolower($st['lastname']) ?>" data-firstname="<?php echo strtolower($st['firstname']) ?>">
                  <td><?php echo htmlspecialchars($st['lastname']) ?></td>
                  <td><?php echo htmlspecialchars($st['firstname']) ?></td>
                  <td><?php echo htmlspecialchars($st['course'].' • '.$st['year_level']) ?></td>
                  <td>
                    <button class="btn btn-sm btn-outline-primary toggle-activities" data-id="<?php echo $aid ?>">Show (<?php echo intval($actCnt) ?>)</button>
                  </td>
                  <td><?php echo intval($attCnt) ?></td>
                  <td><?php echo intval($presentDays) ?></td>
                  <td><?php echo intval($absentDays) ?></td>
                  <td><?php if($presentToday) echo '<span class="badge bg-success">Yes</span>'; else echo '<span class="text-muted small">No</span>'; ?></td>
                  <td><button class="btn btn-sm btn-secondary view-student" data-id="<?php echo $st['id'] ?>">View</button></td>
                </tr>
                <!-- hidden activity details row (uses filtered activities) -->
                <tr id="act-row-<?php echo $aid ?>" class="activity-details-row">
                  <td colspan="9">
                    <?php if(empty($acts)): ?>
                      <div class="text-muted small">No activities recorded (non-zero scores).</div>
                    <?php else: ?>
                      <div class="table-responsive">
                        <table class="table activity-details-table mb-0">
                          <thead>
                            <tr><th style="width:60%">Activity</th><th style="width:20%">Score</th><th style="width:20%">Date</th></tr>
                          </thead>
                          <tbody>
                            <?php foreach($acts as $a): ?>
                              <tr>
                                <td><?php echo htmlspecialchars($a['name']) ?></td>
                                <td><?php echo htmlspecialchars($a['score']) ?></td>
                                <td class="text-muted small"><?php echo htmlspecialchars(substr($a['created_at'] ?? '',0,19)) ?></td>
                              </tr>
                            <?php endforeach; ?>
                          </tbody>
                        </table>
                      </div>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($students)): ?>
                  <tr><td colspan="9" class="text-center text-muted">No students yet. Add some.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </main>

  <!-- Add Student Modal -->
  <div class="modal fade" id="addStudentModal" tabindex="-1">
    <div class="modal-dialog">
      <form method="post" class="modal-content">
        <input type="hidden" name="add_student" value="1">
        <div class="modal-header"><h5 class="modal-title">Add Student</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-2"><label>Lastname</label><input name="lastname" class="form-control" required></div>
          <div class="mb-2"><label>Firstname</label><input name="firstname" class="form-control" required></div>
          <div class="mb-2"><label>Course</label><input name="course" class="form-control"></div>
          <div class="mb-2"><label>Year</label><select name="year" class="form-select"><option>1st year</option>
            <option>2nd year</option>
            <option>3rd year</option>
            <option>4th year</option>
            </select></div>
        </div>
        <div class="modal-footer"><button class="btn btn-orange">Add Student</button></div>
      </form>
    </div>
  </div>

  <!-- Multi Add Modal -->
  <div class="modal fade" id="multiAddModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <form method="post" class="modal-content">
        <input type="hidden" name="multi_add" value="1">
        <div class="modal-header"><h5 class="modal-title">Add Multiple Students</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <p class="small text-muted">Enter one student per line (Lastname, Firstname, Course, Year). Only lastname and firstname are required.</p>
          <textarea name="bulk" class="form-control" rows="8" placeholder="Doe, John, BSIT, 2nd"></textarea>
        </div>
        <div class="modal-footer"><button class="btn btn-orange">Add All</button></div>
      </form>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
  // open student modal via AJAX (works for both tile and list buttons)
  function attachViewStudentHandlers(root=document){
    root.querySelectorAll('.view-student').forEach(btn=>{
      btn.addEventListener('click', function(){
        const id = this.dataset.id;
        const modal = new bootstrap.Modal(document.createElement('div'));
        // fetch modal content
        fetch('student_modal.php?id='+id+'&subject=<?php echo $id ?>')
          .then(r=>r.text()).then(html=>{
            const wrapper = document.createElement('div');
            wrapper.innerHTML = html;
            document.body.appendChild(wrapper);
            var m = new bootstrap.Modal(wrapper.querySelector('.modal'));
            m.show();
            wrapper.querySelector('.modal').addEventListener('hidden.bs.modal', function(){ wrapper.remove();});
          });
      });
    });
  }
  attachViewStudentHandlers();

  // toggle between tile and list views
  const toggleBtn = document.getElementById('toggleViewBtn');
  const toggleBtnList = document.getElementById('toggleViewBtnList');
  const tileView = document.getElementById('tileView');
  const listView = document.getElementById('listView');
  function showListView() {
    listView.style.display = 'block';
    tileView.style.display = 'none';
    toggleBtn.textContent = 'View as tiles';
    toggleBtnList.style.display = 'inline-block';
    // re-attach view handlers inside the list
    attachViewStudentHandlers(listView);
  }
  function showTileView() {
    listView.style.display = 'none';
    tileView.style.display = 'flex';
    toggleBtn.textContent = 'View as list';
    toggleBtnList.style.display = 'none';
    attachViewStudentHandlers(tileView);
  }
  toggleBtn.addEventListener('click', function(){
    if(listView.style.display === 'none' || listView.style.display === ''){
      showListView();
    } else {
      showTileView();
    }
  });
  // list-specific toggle button: when clicked, switch back to tiles
  toggleBtnList.addEventListener('click', function(){
    showTileView();
  });

  // toggle activities (both in tiles and list)
  document.addEventListener('click', function(e){
    const t = e.target;
    if(t.classList && t.classList.contains('toggle-activities')){
      const sid = t.dataset.id;
      // tile: toggle inner activities container
      const tileEl = document.getElementById('tile-activities-' + sid);
      if(tileEl){
        tileEl.style.display = tileEl.style.display === 'none' || tileEl.style.display === '' ? 'block' : 'none';
      }
      // list: toggle detail row visibility
      const row = document.getElementById('act-row-' + sid);
      if(row){
        if(row.style.display === 'none' || row.style.display === '') row.style.display = 'table-row';
        else row.style.display = 'none';
      }
    }
  });

  // client-side search filter: applies to both tiles and table rows
  $('#search').on('input', function(){
    var q = $(this).val().trim().toLowerCase();
    // tiles
    if(q === ''){
      $('.student-tile-col').show();
    } else {
      $('.student-tile-col').each(function(){
        var lastname = $(this).find('.student-tile').data('lastname') || '';
        var firstname = $(this).find('.student-tile').data('firstname') || '';
        if(lastname.indexOf(q) !== -1 || firstname.indexOf(q) !== -1){
          $(this).show();
        } else {
          $(this).hide();
        }
      });
    }
    // table rows
    $('#studentsTable tbody tr.student-row').each(function(){
      var lname = $(this).data('lastname') || '';
      var fname = $(this).data('firstname') || '';
      if(q === '' || lname.indexOf(q) !== -1 || fname.indexOf(q) !== -1){
        $(this).show();
      } else {
        $(this).hide();
        // also hide details row if present
        var next = $(this).next('tr.activity-details-row');
        if(next.length) next.hide();
      }
    });
  });
  </script>
  <script>
  async function loadStudentGrades(subjectId, studentId) {
    try {
      const res = await fetch('/faculty/get_grades.php?subject_id=' + encodeURIComponent(subjectId) + '&student_id=' + encodeURIComponent(studentId));
      const data = await res.json();
      if (data.success) {
        const grades = data.data;
        // Example: append grades to modal body area with id #studentGrades
        const container = document.getElementById('studentGrades');
        if (container) {
          if (grades.length === 0) container.innerHTML = '<div class="text-muted">No grades found</div>';
          else {
            container.innerHTML = grades.map(g => `<div>Grade: ${g.grade} <small class="text-muted">(${g.updated_at})</small></div>`).join('');
          }
        }
      } else {
        console.error('get_grades error', data.message);
      }
    } catch (err) {
      console.error(err);
    }
  }

  // Example: call loadStudentGrades when opening the student modal
  // $('#studentModal').on('show.bs.modal', function (e) {
  //    const studentId = ...; // extract from clicked element
  //    const subjectId = ...;
  //    loadStudentGrades(subjectId, studentId);
  // });
  </script>

</body>
</html>