<?php
require_once '../config.php';
require_login();
$user = current_user();
$pdo = pdo();
$id = intval($_GET['id'] ?? 0);
$subject = intval($_GET['subject'] ?? 0);

// fetch student and their grade for the student's subject (if any)
$st = $pdo->prepare("
  SELECT s.*, g.prelim, g.midterm, g.finals
  FROM students s
  LEFT JOIN grades g ON g.student_id = s.id AND g.subject_id = s.subject_id
  WHERE s.id = ?
");
$st->execute([$id]);
$student = $st->fetch();
if(!$student){ echo "<div class='modal'>Student not found</div>"; exit; }

$att = $pdo->prepare("SELECT * FROM attendance WHERE student_id=? ORDER BY date DESC");
$att->execute([$id]);
$att_rows = $att->fetchAll();
$activities = $pdo->prepare("SELECT * FROM activities WHERE student_id = ?");
$activities->execute([$id]);
$acts = $activities->fetchAll();
?>
<div class="modal fade" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo htmlspecialchars($student['lastname'] . ', ' . $student['firstname']) ?></h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-4 text-center">
            <img src="../<?php echo htmlspecialchars($student['avatar']) ?>" style="width:150px;height:150px;object-fit:cover;border-radius:8px;">
            <div class="mt-2">
              <form method="post" action="update_student_profile.php" enctype="multipart/form-data" id="updateProfileForm">
                <input type="hidden" name="student_id" value="<?php echo $student['id'] ?>">
                <input type="file" name="avatar" class="form-control form-control-sm mb-2">
                <input name="firstname" class="form-control mb-2" value="<?php echo htmlspecialchars($student['firstname']) ?>">
                <input name="lastname" class="form-control mb-2" value="<?php echo htmlspecialchars($student['lastname']) ?>">
                <input name="course" class="form-control mb-2" value="<?php echo htmlspecialchars($student['course']) ?>">
                <input name="year" class="form-control mb-2" value="<?php echo htmlspecialchars($student['year_level']) ?>">
                <div class="d-flex gap-2">
                  <button class="btn btn-orange btn-sm">Update Profile</button>

                  <!-- Bootstrap confirmation modal already handles archiving in your other change -->
                  <button formaction="archive_student.php" formmethod="post" name="archive" class="btn btn-outline-danger btn-sm"
                    onclick="return confirm('Are you sure you want to archive this student? Archiving will move the student to the Archived list.');">
                    Archive
                  </button>

                  <a href="print_student.php?id=<?php echo $student['id'] ?>" target="_blank" class="btn btn-outline-secondary btn-sm">Print Record</a>
                </div>
              </form>
            </div>
          </div>

          <div class="col-md-8">
            <h6>Attendance</h6>
            <div class="small text-muted mb-2">Present and Absences with dates</div>
            <ul class="list-group mb-3">
              <?php foreach($att_rows as $a): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <?php echo htmlspecialchars($a['date']) ?>
                  <span class="badge <?php echo $a['status'] === 'present' ? 'bg-success' : 'bg-danger' ?>"><?php echo $a['status'] ?></span>
                </li>
              <?php endforeach; ?>
            </ul>

            <h6>Activities</h6>
            <table class="table table-sm mb-3">
              <thead><tr><th>Title</th><th>Score</th></tr></thead>
              <tbody>
                <?php foreach($acts as $ac): ?>
                  <tr><td><?php echo htmlspecialchars($ac['title']) ?></td><td><?php echo htmlspecialchars($ac['score']) ?></td></tr>
                <?php endforeach; ?>
              </tbody>
            </table>

            <h6>Grades</h6>
            <div class="row g-2 mb-2">
              <div class="col">
                <label class="small">Prelim</label>
                <div class="form-control-plaintext"><?php echo isset($student['prelim']) ? htmlspecialchars($student['prelim']) : '-' ?></div>
              </div>
              <div class="col">
                <label class="small">Midterm</label>
                <div class="form-control-plaintext"><?php echo isset($student['midterm']) ? htmlspecialchars($student['midterm']) : '-' ?></div>
              </div>
              <div class="col">
                <label class="small">Finals</label>
                <div class="form-control-plaintext"><?php echo isset($student['finals']) ? htmlspecialchars($student['finals']) : '-' ?></div>
              </div>
            </div>

            <div class="text-muted small">Grades are managed from the Grades page (Faculty → Grades). Use that page to add or update prelim, midterm and finals for multiple students at once.</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>