<?php
require_once '../config.php';
require_login();

$user = current_user();
if ($user['role'] !== 'faculty') {
    header("Location: ../index.php");
    exit;
}

$pdo = pdo();

// fetch archived students for this faculty across all subjects
$stmt = $pdo->prepare("
    SELECT s.*, subj.name AS subject_name, subj.code AS subject_code
    FROM students s
    JOIN subjects subj ON s.subject_id = subj.id
    WHERE s.archived = 1 AND subj.faculty_id = ?
    ORDER BY s.lastname, s.firstname
");
$stmt->execute([$user['id']]);
$archived = $stmt->fetchAll();

$msg = '';
if (isset($_GET['unarchived']) && intval($_GET['unarchived'])) {
    $msg = 'Student has been moved back to active list.';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Archived Students - ClassFlow</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .archive-tile { transition: transform .15s ease, box-shadow .15s ease; }
    .archive-tile:hover { transform: translateY(-6px); box-shadow: 0 12px 24px rgba(0,0,0,.08); }
    .search-input { max-width:420px; }
  </style>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
  <?php include '../shared/left_nav.php'; ?>
  <main class="main-content">
    <div class="container-fluid py-4">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
          <h3>Archived Students</h3>
          <div class="text-muted small">Students you previously archived. You can restore (unarchive) them.</div>
        </div>
        <div class="d-flex gap-2 align-items-center">
          <input id="search" class="form-control form-control-sm search-input" placeholder="Search by name or subject...">
          <a class="btn btn-outline-secondary" href="dashboard.php">Back</a>
        </div>
      </div>

      <?php if($msg): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($msg) ?></div>
      <?php endif; ?>

      <?php if(empty($archived)): ?>
        <div class="card p-4 text-center">
          <p class="mb-0">No archived students yet.</p>
        </div>
      <?php else: ?>
        <div class="row g-3" id="tiles">
          <?php foreach($archived as $s): ?>
            <div class="col-sm-6 col-md-4 archive-card" data-name="<?php echo htmlspecialchars(strtolower($s['lastname'].' '.$s['firstname'])) ?>" data-subject="<?php echo htmlspecialchars(strtolower($s['subject_name'].' '.$s['subject_code'])) ?>">
              <div class="card archive-tile h-100 p-3">
                <div class="d-flex align-items-center gap-3">
                  <img src="../<?php echo htmlspecialchars($s['avatar'] ?: 'assets/img/default-avatar.png') ?>" style="width:72px;height:72px;object-fit:cover;border-radius:8px;">
                  <div class="flex-fill">
                    <div class="fw-bold"><?php echo htmlspecialchars($s['lastname'].', '.$s['firstname']) ?></div>
                    <div class="small text-muted"><?php echo htmlspecialchars($s['course'].' • '.$s['year_level']) ?></div>
                    <div class="small text-muted">Subject: <?php echo htmlspecialchars($s['subject_name'].' • '.$s['subject_code']) ?></div>
                  </div>
                </div>

                <div class="d-flex gap-2 mt-3">
                  <button class="btn btn-sm btn-outline-secondary view-student" data-id="<?php echo $s['id'] ?>">View</button>

                  <form method="post" action="unarchive_student.php" style="display:inline;">
                    <input type="hidden" name="student_id" value="<?php echo $s['id'] ?>">
                    <input type="hidden" name="redirect" value="archives.php">
                    <button type="submit" name="unarchive" class="btn btn-sm btn-orange">Unarchive</button>
                  </form>

                  <a class="btn btn-sm btn-outline-secondary" href="print_student.php?id=<?php echo $s['id'] ?>" target="_blank">Print</a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    </div>
  </main>

  <!-- student modal loader (dynamically inserted) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // client-side search filter
    $('#search').on('input', function(){
      var q = $(this).val().trim().toLowerCase();
      if(q === ''){
        $('.archive-card').show();
        return;
      }
      $('.archive-card').each(function(){
        var name = $(this).data('name') || '';
        var subj = $(this).data('subject') || '';
        if(name.indexOf(q) !== -1 || subj.indexOf(q) !== -1){
          $(this).show();
        } else {
          $(this).hide();
        }
      });
    });

    // open student modal via AJAX (reuse student_modal.php)
    $(document).on('click', '.view-student', function(){
      var id = $(this).data('id');
      // fetch modal HTML from student_modal.php
      $.get('student_modal.php', { id: id }, function(html){
        var $wrapper = $('<div></div>').html(html);
        $('body').append($wrapper);
        var modalEl = $wrapper.find('.modal').get(0);
        var bsModal = new bootstrap.Modal(modalEl);
        bsModal.show();
        // remove wrapper when hidden
        $(modalEl).on('hidden.bs.modal', function(){ $wrapper.remove(); });
      });
    });
  </script>
</body>
</html>