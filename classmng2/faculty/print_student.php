<?php
require_once '../config.php';
require_login();

$user = current_user();
$pdo = pdo();

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    http_response_code(404);
    echo "Student not found";
    exit;
}

// Fetch student and their subject and faculty ownership
$stmt = $pdo->prepare("
  SELECT s.*, subj.id AS subject_id, subj.name AS subject_name, subj.code AS subject_code, subj.schedule AS subject_schedule, subj.faculty_id
  FROM students s
  JOIN subjects subj ON s.subject_id = subj.id
  WHERE s.id = ?
");
$stmt->execute([$id]);
$student = $stmt->fetch();
if (!$student) {
    http_response_code(404);
    echo "Student not found";
    exit;
}

// Only allow the faculty who owns the subject or admin to view/print
if ($user['role'] !== 'admin' && intval($student['faculty_id']) !== intval($user['id'])) {
    http_response_code(403);
    echo "Not authorized";
    exit;
}

// Attendance records
$attStmt = $pdo->prepare("SELECT date, status, created_at FROM attendance WHERE student_id = ? AND subject_id = ? ORDER BY date DESC");
$attStmt->execute([$id, $student['subject_id']]);
$attendance = $attStmt->fetchAll();

// Activities
$actStmt = $pdo->prepare("SELECT title, score, created_at FROM activities WHERE student_id = ? AND subject_id = ? ORDER BY created_at DESC");
$actStmt->execute([$id, $student['subject_id']]);
$activities = $actStmt->fetchAll();

// Grades
$gradeStmt = $pdo->prepare("SELECT prelim, midterm, finals, updated_at FROM grades WHERE student_id = ? AND subject_id = ? LIMIT 1");
$gradeStmt->execute([$id, $student['subject_id']]);
$grades = $gradeStmt->fetch();

// Friendly display values
$avatar = $student['avatar'] ?: '../assets/img/default-avatar.png';
$fullname = trim($student['lastname'] . ', ' . $student['firstname']);
$course = $student['course'] ?: '-';
$year = $student['year_level'] ?: '-';
$subjectName = $student['subject_name'] ?: '-';
$subjectCode = $student['subject_code'] ?: '-';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Print — <?php echo htmlspecialchars($fullname) ?> — ClassFlow</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body{ font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; color:#222; padding:20px; }
    .brand { color: #ff8a00; font-weight:700; }
    .card{ border:1px solid #eee; border-radius:8px; padding:18px; margin-bottom:16px; }
    .avatar-lg{ width:120px; height:120px; object-fit:cover; border-radius:10px; border:1px solid #ddd; }
    .muted { color:#666; }
    @media print {
      .no-print{ display:none!important; }
      body{ padding:0; }
    }
    table.table thead th { background: #fff7ef; color:#8a4b00; border-bottom:1px solid #eee; }
  </style>
</head>
<body>
  <div class="d-flex justify-content-between align-items-center mb-3;" style="margin:50px;">
    <div>
      &nbsp;&nbsp;<img width="380px" src="../assets/img/svgviewer-png-output.png"> &nbsp; &nbsp;
      <div class="small text-muted"> &nbsp;&nbsp;&nbsp; Student Record Printout</div>
    </div>
    <div class="text-end no-print">
      <button class="btn btn-outline-secondary me-2" onclick="window.close()">Close</button>
      <button class="btn btn-orange" onclick="window.print()">Print</button>
    </div>
  </div>

  <div class="card" style="margin:50px;">
    <div class="row align-items-center">
      <div class="col-auto">
        &nbsp;&nbsp;&nbsp;<img src="../<?php echo htmlspecialchars($avatar) ?>" alt="avatar" class="avatar-lg">
      </div>
      <div class="col" >
        <h4 class="mb-1"><?php echo htmlspecialchars($fullname) ?></h4>
        <div class="muted mb-1"><?php echo htmlspecialchars($course) ?> • <?php echo htmlspecialchars($year) ?></div>
        <div class="small text-muted">Student ID: <?php echo htmlspecialchars($student['id']) ?> — Created: <?php echo htmlspecialchars($student['created_at']) ?></div>
      </div>
      <div class="col-auto text-end" style="margin:50px;">
        <div class="small text-muted">Subject</div>
        <div class="fw-bold"><?php echo htmlspecialchars($subjectName) ?></div>
        <div class="muted"><?php echo htmlspecialchars($subjectCode) ?> • <?php echo htmlspecialchars($student['subject_schedule']) ?></div>
      </div>
    </div>
  </div>

  <div class="row" >
    <div class="col-md-6">
      <div class="card" style="margin:50px; margin-top:-40px;">
        <h6 class="mb-3">Attendance (<?php echo count($attendance) ?>)</h6>
        <?php if(empty($attendance)): ?>
          <div class="text-muted small">No attendance records.</div>
        <?php else: ?>
          <table class="table table-sm">
            <thead><tr><th>Date</th><th>Status</th></tr></thead>
            <tbody>
              <?php foreach($attendance as $a): ?>
                <tr>
                  <td><?php echo htmlspecialchars($a['date']) ?></td>
                  <td>
                    <?php if($a['status'] === 'present'): ?>
                      <span class="badge bg-success">Present</span>
                    <?php else: ?>
                      <span class="badge bg-danger">Absent</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>

    <div class="col-md-6">
      <div class="card" style="margin:50px; margin-top:-40px;">
        <h6 class="mb-3">Activities (<?php echo count($activities) ?>)</h6>
        <?php if(empty($activities)): ?>
          <div class="text-muted small">No activities recorded.</div>
        <?php else: ?>
          <table class="table table-sm">
            <thead><tr><th>Title</th><th class="text-end">Score</th><th class="text-muted">Added</th></tr></thead>
            <tbody>
              <?php foreach($activities as $ac): ?>
                <tr>
                  <td><?php echo htmlspecialchars($ac['title']) ?></td>
                  <td class="text-end"><?php echo number_format($ac['score'], 2) ?></td>
                  <td class="muted small"><?php echo htmlspecialchars($ac['created_at']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="card" style="margin:50px; margin-top:-40px;">
    <h6 class="mb-3">Grades</h6>
    <div class="row">
      <div class="col-md-3">
        <div class="muted small">Prelim</div>
        <div class="fs-5 fw-bold"><?php echo isset($grades['prelim']) ? number_format($grades['prelim'],2) : '0.00' ?></div>
      </div>
      <div class="col-md-3">
        <div class="muted small">Midterm</div>
        <div class="fs-5 fw-bold"><?php echo isset($grades['midterm']) ? number_format($grades['midterm'],2) : '0.00' ?></div>
      </div>
      <div class="col-md-3">
        <div class="muted small">Finals</div>
        <div class="fs-5 fw-bold"><?php echo isset($grades['finals']) ? number_format($grades['finals'],2) : '0.00' ?></div>
      </div>
      <div class="col-md-3">
        <div class="muted small">Last updated</div>
        <div class="small"><?php echo htmlspecialchars($grades['updated_at'] ?? '-') ?></div>
      </div>
    </div>
  </div>
  <div style="margin:50px;" class="text-muted small mt-3">Generated by ClassFlow — <?php echo date('Y-m-d H:i') ?> Developed by Oalden Morales</div>    
  <script>
    // If ?autoprint=1 is passed, auto invoke print and then close.
    (function(){
      const params = new URLSearchParams(location.search);
      if(params.get('autoprint') === '1'){
        window.print();
        setTimeout(()=> window.close(), 500);
      }
    })();
  </script>
</body>
</html>