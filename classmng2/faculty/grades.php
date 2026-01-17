<?php
require_once '../config.php';
require_login();
$user = current_user();
if($user['role'] !== 'faculty'){ header("Location: ../index.php"); exit; }
$pdo = pdo();

// fetch subjects for this faculty
$subsStmt = $pdo->prepare("SELECT * FROM subjects WHERE faculty_id = ? ORDER BY created_at DESC");
$subsStmt->execute([$user['id']]);
$subjects = $subsStmt->fetchAll();

// selected subject
$subject_id = intval($_GET['subject_id'] ?? 0);
$students = [];
if($subject_id){
    // students in subject
    $sstmt = $pdo->prepare("SELECT * FROM students WHERE subject_id = ? AND archived = 0 ORDER BY lastname, firstname");
    $sstmt->execute([$subject_id]);
    $students = $sstmt->fetchAll();

    // fetch existing grades for the subject keyed by student_id
    $ids = array_map(function($s){ return (int)$s['id']; }, $students);
    $grades = [];
    if(!empty($ids)){
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $gstmt = $pdo->prepare("SELECT * FROM grades WHERE subject_id = ? AND student_id IN ($placeholders)");
        $gstmt->execute(array_merge([$subject_id], $ids));
        foreach($gstmt->fetchAll() as $g){
            $grades[$g['student_id']] = $g;
        }
    }
}

// Handle POST: bulk save
$msg = '';
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_grades'])){
    $subject_id = intval($_POST['subject_id'] ?? 0);
    $posted = $_POST['grades'] ?? []; // expected format: grades[student_id][prelim|midterm|finals]
    // For each student posted, update or insert
    $updateStmt = $pdo->prepare("UPDATE grades SET prelim = COALESCE(?, prelim), midterm = COALESCE(?, midterm), finals = COALESCE(?, finals), updated_at = NOW() WHERE student_id = ? AND subject_id = ?");
    $insertStmt = $pdo->prepare("INSERT INTO grades (student_id, subject_id, prelim, midterm, finals) VALUES (?, ?, ?, ?, ?)");
    $count = 0;
    foreach($posted as $sid => $vals){
        $sid = intval($sid);
        // Normalize inputs: empty string => null (so we can skip/COALESCE)
        $p = isset($vals['prelim']) && $vals['prelim'] !== '' ? trim($vals['prelim']) : null;
        $m = isset($vals['midterm']) && $vals['midterm'] !== '' ? trim($vals['midterm']) : null;
        $f = isset($vals['finals']) && $vals['finals'] !== '' ? trim($vals['finals']) : null;

        // Skip entirely empty
        if($p === null && $m === null && $f === null) continue;

        // Check if row exists
        $check = $pdo->prepare("SELECT id FROM grades WHERE student_id = ? AND subject_id = ?");
        $check->execute([$sid, $subject_id]);
        $exists = $check->fetch();

        if($exists){
            // For COALESCE to work, we need to pass values or NULL; COALESCE(?, col) will pick ? if not null else col
            // Use null for fields we want to leave unchanged
            // Bind order: prelim, midterm, finals, student_id, subject_id
            $updateStmt->execute([$p, $m, $f, $sid, $subject_id]);
            $count++;
        } else {
            // Insert new row: for missing fields we use 0.00 to match current schema constraints
            $pIns = $p !== null ? $p : 0.00;
            $mIns = $m !== null ? $m : 0.00;
            $fIns = $f !== null ? $f : 0.00;
            $insertStmt->execute([$sid, $subject_id, $pIns, $mIns, $fIns]);
            $count++;
        }
    }

    $msg = $count > 0 ? "Saved grades for {$count} students." : "No grades provided.";
    // reload to reflect changes
    header("Location: grades.php?subject_id={$subject_id}&saved=1");
    exit;
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Grades — ClassFlow</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <?php include '../shared/left_nav.php'; ?>
  <main class="main-content">
    <div class="container py-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h3>Grades</h3>
          <div class="small text-muted">Add prelim, midterm and finals for students per subject. Leave any input blank to keep the existing value.</div>
        </div>
        <div>
          <a href="dashboard.php" class="btn btn-outline-secondary">Back</a>
        </div>
      </div>

      <form method="get" class="mb-3 d-flex gap-2 align-items-center">
        <label class="mb-0">Subject</label>
        <select name="subject_id" class="form-select" style="max-width:420px;">
          <option value="">-- Select subject --</option>
          <?php foreach($subjects as $s): ?>
            <option value="<?php echo $s['id'] ?>" <?php echo $subject_id == $s['id'] ? 'selected' : '' ?>><?php echo htmlspecialchars($s['name'].' • '.$s['code']) ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-outline-secondary">Open</button>
      </form>

      <?php if(isset($_GET['saved'])): ?>
        <div class="alert alert-success">Grades saved.</div>
      <?php endif; ?>

      <?php if($subject_id): ?>
        <form method="post">
          <input type="hidden" name="save_grades" value="1">
          <input type="hidden" name="subject_id" value="<?php echo $subject_id ?>">

          <table class="table table-striped">
            <thead>
              <tr>
                <th>Student</th>
                <th>Course / Year</th>
                <th style="width:110px">Prelim</th>
                <th style="width:110px">Midterm</th>
                <th style="width:110px">Finals</th>
              </tr>
            </thead>
            <tbody>
              <?php if(empty($students)): ?>
                <tr><td colspan="5">No students in this subject.</td></tr>
              <?php else: ?>
                <?php foreach($students as $st): 
                  $g = $grades[$st['id']] ?? null;
                ?>
                  <tr>
                    <td><?php echo htmlspecialchars($st['lastname'].', '.$st['firstname']) ?></td>
                    <td><?php echo htmlspecialchars($st['course'].' • '.$st['year_level']) ?></td>
                    <td><input step="0.01" min="0" type="number" name="grades[<?php echo $st['id'] ?>][prelim]" class="form-control form-control-sm" placeholder="<?php echo $g ? htmlspecialchars($g['prelim']) : '' ?>"></td>
                    <td><input step="0.01" min="0" type="number" name="grades[<?php echo $st['id'] ?>][midterm]" class="form-control form-control-sm" placeholder="<?php echo $g ? htmlspecialchars($g['midterm']) : '' ?>"></td>
                    <td><input step="0.01" min="0" type="number" name="grades[<?php echo $st['id'] ?>][finals]" class="form-control form-control-sm" placeholder="<?php echo $g ? htmlspecialchars($g['finals']) : '' ?>"></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>

          <div class="d-flex gap-2">
            <button class="btn btn-orange">Save grades</button>
            <a class="btn btn-outline-secondary" href="subject.php?id=<?php echo $subject_id ?>">Open subject</a>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </main>
</body>
</html>