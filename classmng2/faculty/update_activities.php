<?php
require_once '../config.php';
require_login();
$user = current_user();
if ($user['role'] !== 'faculty') { header("Location: ../index.php"); exit; }
$pdo = pdo();

$student_id = intval($_GET['student'] ?? 0);
$subject_id = intval($_GET['subject'] ?? 0);

if (!$student_id || !$subject_id) {
    header("Location: activities.php");
    exit;
}

// load student and subject for display
$stStmt = $pdo->prepare("SELECT * FROM students WHERE id = ? AND subject_id = ?");
$stStmt->execute([$student_id, $subject_id]);
$student = $stStmt->fetch();
if (!$student) {
    header("Location: activities.php?subject=" . $subject_id);
    exit;
}

$subStmt = $pdo->prepare("SELECT * FROM subjects WHERE id = ?");
$subStmt->execute([$subject_id]);
$subject = $subStmt->fetch();

// Handle updates and deletes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update' && isset($_POST['activity_id'])) {
        $aid = intval($_POST['activity_id']);
        $title = trim($_POST['title'] ?? '');
        $score = floatval($_POST['score'] ?? 0);
        $u = $pdo->prepare("UPDATE activities SET title = ?, score = ? WHERE id = ? AND student_id = ? AND subject_id = ?");
        $u->execute([$title, $score, $aid, $student_id, $subject_id]);

        // mark student as updated in session so Activities page can highlight them
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if (!isset($_SESSION['recently_updated_students']) || !is_array($_SESSION['recently_updated_students'])) {
            $_SESSION['recently_updated_students'] = [];
        }
        $_SESSION['recently_updated_students'][] = $student_id;
        // keep unique ids
        $_SESSION['recently_updated_students'] = array_values(array_unique($_SESSION['recently_updated_students']));

        // Redirect to activities list for the subject after saving
        header("Location: activities.php?subject=" . $subject_id);
        exit;
    }
    if ($action === 'delete' && isset($_POST['activity_id'])) {
        $aid = intval($_POST['activity_id']);
        $d = $pdo->prepare("DELETE FROM activities WHERE id = ? AND student_id = ? AND subject_id = ?");
        $d->execute([$aid, $student_id, $subject_id]);
        header("Location: update_activities.php?student={$student_id}&subject={$subject_id}");
        exit;
    }
}

// fetch activities
$actsStmt = $pdo->prepare("SELECT * FROM activities WHERE student_id = ? AND subject_id = ? ORDER BY id DESC");
$actsStmt->execute([$student_id, $subject_id]);
$activities = $actsStmt->fetchAll();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Update Activities - ClassFlow</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <?php include '../shared/left_nav.php'; ?>
  <main class="main-content">
    <div class="container py-4">
      <h4>Activities for <?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']) ?> <?php echo isset($subject['name']) ? '(' . htmlspecialchars($subject['name']) . ')' : '' ?></h4>
      <p>
        <a href="activities.php?subject=<?php echo (int)$subject_id ?>" class="btn btn-outline-secondary btn-sm">&larr; Back to Activities</a>
      </p>

      <?php if (empty($activities)): ?>
        <div class="alert alert-info">No activities recorded yet.</div>
      <?php else: ?>
        <form method="post" id="activitiesForm">
          <table class="table">
            <thead><tr><th>#</th><th>Title</th><th>Score</th><th>Actions</th></tr></thead>
            <tbody>
              <?php foreach ($activities as $a): ?>
                <tr>
                  <td><?php echo (int)$a['id'] ?></td>

                  <!-- Inline editable title -->
                  <td>
                    <input type="text" name="title_<?php echo (int)$a['id'] ?>" value="<?php echo htmlspecialchars($a['title']) ?>" class="form-control" />
                  </td>

                  <!-- Inline editable score -->
                  <td style="width:160px;">
                    <input type="number" step="0.01" name="score_<?php echo (int)$a['id'] ?>" value="<?php echo htmlspecialchars($a['score']) ?>" class="form-control" />
                  </td>

                  <td style="white-space:nowrap; width:220px;">
                    <!-- Individual save form -->
                    <form method="post" style="display:inline;">
                      <input type="hidden" name="action" value="update">
                      <input type="hidden" name="activity_id" value="<?php echo (int)$a['id'] ?>">
                      <input type="hidden" name="student_id" value="<?php echo (int)$student_id ?>">
                      <input type="hidden" name="subject_id" value="<?php echo (int)$subject_id ?>">
                      <!-- when submitting, copy the corresponding inline inputs into the POST (via small inline script) -->
                      <button type="submit" class="btn btn-sm btn-primary save-btn" data-act-id="<?php echo (int)$a['id'] ?>">Save</button>
                    </form>

                    <!-- Delete form -->
                    <form method="post" style="display:inline" onsubmit="return confirm('Delete this activity?');">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="activity_id" value="<?php echo (int)$a['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </form>

        <script>
          // When a Save button is clicked, create a temporary form and submit
          // This copies the inline title and score inputs into that form so server receives them.
          document.addEventListener('click', function(e){
            var btn = e.target.closest('.save-btn');
            if(!btn) return;
            e.preventDefault();
            var actId = btn.getAttribute('data-act-id');
            var titleInput = document.querySelector('input[name="title_' + actId + '"]');
            var scoreInput = document.querySelector('input[name="score_' + actId + '"]');

            var form = document.createElement('form');
            form.method = 'post';
            form.style.display = 'none';

            var actionInput = document.createElement('input');
            actionInput.name = 'action';
            actionInput.value = 'update';
            form.appendChild(actionInput);

            var aid = document.createElement('input');
            aid.name = 'activity_id';
            aid.value = actId;
            form.appendChild(aid);

            var sid = document.createElement('input');
            sid.name = 'student_id';
            sid.value = '<?php echo (int)$student_id ?>';
            form.appendChild(sid);

            var subid = document.createElement('input');
            subid.name = 'subject_id';
            subid.value = '<?php echo (int)$subject_id ?>';
            form.appendChild(subid);

            var t = document.createElement('input');
            t.name = 'title';
            t.value = titleInput ? titleInput.value : '';
            form.appendChild(t);

            var sc = document.createElement('input');
            sc.name = 'score';
            sc.value = scoreInput ? scoreInput.value : '';
            form.appendChild(sc);

            document.body.appendChild(form);
            form.submit();
          });
        </script>

      <?php endif; ?>

    </div>
  </main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>