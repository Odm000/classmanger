<?php
require_once '../config.php';
require_login();
$user = current_user();
if ($user['role'] !== 'faculty') { header("Location: ../index.php"); exit; }
$pdo = pdo();

$subject = intval($_GET['subject'] ?? 0);

// fetch updated-student IDs from session (semi-permanent until logout)
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$updated_students = $_SESSION['recently_updated_students'] ?? [];

// subjects for this faculty
$subsStmt = $pdo->prepare("SELECT * FROM subjects WHERE faculty_id = ?");
$subsStmt->execute([$user['id']]);
$subs = $subsStmt->fetchAll();

// students in selected subject
$students = [];
if ($subject) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE subject_id = ? AND archived = 0 ORDER BY lastname, firstname");
    $stmt->execute([$subject]);
    $students = $stmt->fetchAll();
}

// add activity
$addMultipleError = '';
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_activity'])){
    $student_id = intval($_POST['student_id']);
    $subject_id = intval($_POST['subject_id']);
    $title = trim($_POST['title'] ?? '');
    $score = floatval($_POST['score'] ?? 0.0);

    // Grades fields from modal (default to 0.0)
    $prelim = floatval($_POST['prelim'] ?? 0.0);
    $midterm = floatval($_POST['midterm'] ?? 0.0);
    $finals = floatval($_POST['finals'] ?? 0.0);

    // Max score for this activity (default to 100.0)
    $max_score = floatval($_POST['max_score'] ?? 100.0);

    // Date for the activity (default to today). Accepts YYYY-MM-DD.
    $act_date_raw = trim($_POST['date'] ?? '');
    if ($act_date_raw === '') {
        $act_date = date('Y-m-d');
    } else {
        // basic validation and normalization
        $ts = strtotime($act_date_raw);
        if ($ts === false) {
            $act_date = date('Y-m-d');
        } else {
            $act_date = date('Y-m-d', $ts);
        }
    }

    // Determine activity table columns to decide whether to store the optional columns.
    try {
        $colsInfo = $pdo->query("SHOW COLUMNS FROM activities")->fetchAll(PDO::FETCH_ASSOC);
        $cols = array_column($colsInfo, 'Field');
    } catch (Exception $e) {
        // If table doesn't exist or SHOW COLUMNS fails, fall back to only inserting basic fields.
        $cols = [];
    }

    $fields = ['student_id','subject_id','title','score'];
    $values = [$student_id, $subject_id, $title, $score];

    if (in_array('prelim', $cols)) {
        $fields[] = 'prelim';
        $values[] = $prelim;
    }
    if (in_array('midterm', $cols)) {
        $fields[] = 'midterm';
        $values[] = $midterm;
    }
    if (in_array('finals', $cols)) {
        $fields[] = 'finals';
        $values[] = $finals;
    }
    if (in_array('max_score', $cols)) {
        $fields[] = 'max_score';
        $values[] = $max_score;
    }
    if (in_array('date', $cols)) {
        $fields[] = 'date';
        $values[] = $act_date;
    }

    // Build placeholders and execute
    $placeholders = implode(',', array_fill(0, count($values), '?'));
    $insSql = "INSERT INTO activities (" . implode(',', $fields) . ") VALUES ($placeholders)";
    $ins = $pdo->prepare($insSql);
    $ins->execute($values);

    header("Location: activities.php?subject=" . $subject_id);
    exit;
}

// add activity for ALL students in a subject (new behaviour)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_activity_all'])) {
    $subject_id = intval($_POST['subject_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $max_score = floatval($_POST['max_score'] ?? 100.0);
    $act_date_raw = trim($_POST['date'] ?? '');
    if ($act_date_raw === '') {
        $act_date = date('Y-m-d');
    } else {
        $ts = strtotime($act_date_raw);
        $act_date = $ts === false ? date('Y-m-d') : date('Y-m-d', $ts);
    }

    // Basic validation
    if ($subject_id <= 0 || $title === '') {
        header("Location: activities.php?subject=" . $subject_id . "&added_all=0");
        exit;
    }

    // Ensure the subject belongs to current faculty
    $chk = $pdo->prepare("SELECT id FROM subjects WHERE id = ? AND faculty_id = ?");
    $chk->execute([$subject_id, $user['id']]);
    if (!$chk->fetch()) {
        header("Location: activities.php?subject=" . $subject_id . "&added_all=0");
        exit;
    }

    // fetch student ids for that subject
    $stStmt = $pdo->prepare("SELECT id FROM students WHERE subject_id = ? AND archived = 0");
    $stStmt->execute([$subject_id]);
    $studentIds = $stStmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($studentIds)) {
        // nothing to add to
        header("Location: activities.php?subject=" . $subject_id . "&added_all=0");
        exit;
    }

    // Inspect columns to know which optional columns are present
    try {
        $colsInfo = $pdo->query("SHOW COLUMNS FROM activities")->fetchAll(PDO::FETCH_ASSOC);
        $cols = array_column($colsInfo, 'Field');
    } catch (Exception $e) {
        $cols = [];
    }

    $baseFields = ['student_id','subject_id','title','score'];
    // default score for new activity entries is 0.0
    $optionalFields = [];
    if (in_array('prelim', $cols)) $optionalFields[] = 'prelim';
    if (in_array('midterm', $cols)) $optionalFields[] = 'midterm';
    if (in_array('finals', $cols)) $optionalFields[] = 'finals';
    if (in_array('max_score', $cols)) $optionalFields[] = 'max_score';
    if (in_array('date', $cols)) $optionalFields[] = 'date';

    $fields = array_merge($baseFields, $optionalFields);
    $placeholders = implode(',', array_fill(0, count($fields), '?'));
    $insSql = "INSERT INTO activities (" . implode(',', $fields) . ") VALUES ($placeholders)";
    $ins = $pdo->prepare($insSql);

    // Use transaction for bulk insert
    try {
        $pdo->beginTransaction();
        foreach ($studentIds as $sid) {
            $values = [
                intval($sid),
                $subject_id,
                $title,
                0.0 // initial score
            ];
            // optional defaults
            if (in_array('prelim', $cols)) $values[] = 0.0;
            if (in_array('midterm', $cols)) $values[] = 0.0;
            if (in_array('finals', $cols)) $values[] = 0.0;
            if (in_array('max_score', $cols)) $values[] = $max_score;
            if (in_array('date', $cols)) $values[] = $act_date;

            $ins->execute($values);
        }
        $pdo->commit();
    } catch (Exception $e) {
        try { $pdo->rollBack(); } catch(Exception $_){}
        header("Location: activities.php?subject=" . $subject_id . "&added_all=0");
        exit;
    }

    header("Location: activities.php?subject=" . $subject_id . "&added_all=1");
    exit;
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Activities - ClassFlow</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    /* small visual tweak for the updated indicator */
    .updated-badge { font-weight:600; }
    .highlight-updated { transition: background-color 0.3s ease; }
  </style>
</head>
<body>
  <?php include '../shared/left_nav.php'; ?>
  <main class="main-content">
    <div class="container py-4">
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

      <form method="get" class="mb-3">
        <select name="subject" class="form-select w-auto d-inline">
          <option value="">-- Select subject --</option>
          <?php foreach ($subs as $s): ?>
            <option value="<?php echo (int)$s['id'] ?>" <?php echo $subject == $s['id'] ? 'selected' : '' ?>>
              <?php echo htmlspecialchars($s['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-outline-secondary">Open</button>
        <button type='button' class="btn btn-orange" onclick="window.location.href='grades.php'">Add Grade</button>

        <?php if ($subject): ?>
          <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addActivityAllModal">Add Activity</button>
        <?php else: ?>
          <button type="button" class="btn btn-outline-primary" onclick="alert('Please select a subject first')">Add Activity</button>
        <?php endif; ?>
      </form>

      <?php if ($subject): ?>
        <table class="table">
          <thead><tr><th>Pic</th><th>Lastname</th><th>Firstname</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($students as $st):
              $avatar = !empty($st['avatar']) ? $st['avatar'] : 'assets/img/default-avatar.png';
              $modalId = 'actModal-' . (int)$st['id'];
              $studentId = (int)$st['id'];
              $isUpdated = in_array($studentId, $updated_students);
            ?>
              <tr class="<?php echo $isUpdated ? 'table-success highlight-updated updated-row' : '' ?>">
                <td><img src="../<?php echo htmlspecialchars($avatar) ?>" style="width:40px;height:40px;object-fit:cover;border-radius:6px;"></td>
                <td>
                  <?php echo htmlspecialchars($st['lastname']) ?>
                  <?php if($isUpdated): ?>
                    <span class="badge bg-success ms-2 updated-badge">Updated</span>
                  <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($st['firstname']) ?></td>
                <td>
                  <button type="button" class="btn btn-sm btn-outline-success load-student-modal" 
                          data-student-id="<?php echo $studentId ?>" 
                          data-bs-toggle="modal" 
                          data-bs-target="#ajaxModal">
                    View Record
                  </button>

                  <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#<?php echo $modalId; ?>">Add Activity</button>
                  <a class="btn btn-sm btn-outline-secondary" href="update_activities.php?student=<?php echo $studentId ?>&subject=<?php echo $subject ?>">Update</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <?php foreach ($students as $st):
          $modalId = 'actModal-' . (int)$st['id'];
          $today = date('Y-m-d');
        ?>
          <div class="modal fade" id="<?php echo $modalId; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
              <form method="post" class="modal-content">
                <input type="hidden" name="add_activity" value="1">
                <input type="hidden" name="student_id" value="<?php echo (int)$st['id'] ?>">
                <input type="hidden" name="subject_id" value="<?php echo (int)$subject ?>">
                <div class="modal-header">
                  <h5 class="modal-title">Add Activity for <?php echo htmlspecialchars($st['firstname'] . ' ' . $st['lastname']) ?></h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <div class="mb-2">
                    <label class="form-label">Title</label>
                    <input name="title" class="form-control" required>
                  </div>

                  <div class="row gx-2">
                    <div class="col-md-3 mb-2">
                      <label class="form-label">Score</label>
                      <input name="score" class="form-control" type="number" step="0.01" value="0.00">
                    </div>

                    <div class="col-md-3 mb-2">
                      <label class="form-label">Max Score</label>
                      <input name="max_score" class="form-control" type="number" step="0.01" value="100.00">
                    </div>

                    <div class="col-md-4 mb-2">
                      <label class="form-label">Date</label>
                      <input name="date" class="form-control" type="date" value="<?php echo $today; ?>" >
                    </div>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="submit" class="btn btn-orange">Add</button>
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
              </form>
            </div>
          </div>
        <?php endforeach; ?>

        <!-- Modal for adding activity to ALL students in the selected subject -->
        <div class="modal fade" id="addActivityAllModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog">
            <form method="post" class="modal-content">
              <input type="hidden" name="add_activity_all" value="1">
              <input type="hidden" name="subject_id" value="<?php echo (int)$subject ?>">
              <div class="modal-header">
                <h5 class="modal-title">Add Activity to All Students</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <div class="mb-2">
                  <label class="form-label">Activity Title</label>
                  <input name="title" class="form-control" required>
                </div>
                <div class="mb-2">
                  <label class="form-label">Max Score</label>
                  <input name="max_score" class="form-control" type="number" step="0.01" value="100.00" required>
                </div>
                <div class="mb-2">
                  <label class="form-label">Date (optional)</label>
                  <input name="date" class="form-control" type="date" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="alert alert-warning small">This will create an activity entry for every non-archived student in the selected subject with initial score 0.</div>
              </div>
              <div class="modal-footer">
                <button type="submit" class="btn btn-orange">Add to All Students</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              </div>
            </form>
          </div>
          
        </div>

      <?php endif; ?>
    </div>
  </main>

<div class="modal fade" id="ajaxModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-body text-center py-5">
        Loading student data...
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
  $(document).ready(function() {
    // When the "View Record" button is clicked
    $('.load-student-modal').on('click', function() {
      var studentId = $(this).data('student-id');
      var subjectId = <?php echo $subject ? $subject : '0'; ?>; // Pass the current subject ID
      var modalContent = $('#ajaxModal .modal-content');
      
      // Reset modal content to a loading state
      modalContent.html('<div class="modal-body text-center py-5">Loading student data...</div>');
      
      // Use AJAX to load the content of student_modal.php
      $.ajax({
        url: 'student_modal.php',
        type: 'GET',
        data: { id: studentId, subject: subjectId },
        success: function(response) {
          // student_modal.php returns the full modal HTML, so we extract its inner content
          var tempDiv = $('<div>').html(response);
          var innerContent = tempDiv.find('.modal-content').html();
          
          if(innerContent) {
            // Replace the loading content with the actual student record content
            modalContent.html(innerContent);
          } else {
             // Fallback if the inner content wasn't found (maybe due to structure change)
             modalContent.html(response);
          }
        },
        error: function() {
          modalContent.html('<div class="modal-body text-danger">Error loading student record. Please try again.</div>');
        }
      });
    });

    // Note: highlight is session-persisted and will be cleared on logout. No auto-dismiss here.
  });
</script>

</body>
</html>