<?php
require_once '../config.php';
require_login();
$user = current_user();
if($user['role'] !== 'faculty'){ header("Location: ../index.php"); exit; }
$pdo = pdo();
$subs = $pdo->prepare("SELECT * FROM subjects WHERE faculty_id = ?");
$subs->execute([$user['id']]);
$subs = $subs->fetchAll();

$selected = intval($_GET['subject'] ?? 0);
if($selected){
    $students = $pdo->prepare("SELECT * FROM students WHERE subject_id=? AND archived=0 ORDER BY lastname");
    $students->execute([$selected]);
    $students = $students->fetchAll();
}

// Handle AJAX marking (returns JSON)
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['toggle'])){
    $student_id = intval($_POST['student_id']);
    $subject_id = intval($_POST['subject_id']);
    $status = $_POST['status'] ?? '';
    $date = date('Y-m-d');

    if($status === 'present'){
        // Immediate conflict detection:
        // If student already has a 'present' record today in a DIFFERENT subject, block immediately.
        $check = $pdo->prepare("
            SELECT COUNT(*) FROM attendance
            WHERE student_id = ? AND status = 'present' AND date = ? AND subject_id != ?
        ");
        $check->execute([$student_id, $date, $subject_id]);
        $exists = (int)$check->fetchColumn();
        if($exists > 0){
            // return exactly the alert text requested
            echo json_encode(['error' => "the student you're trying to amrk as present is still on another class"]);
            exit;
        }
    }

    // upsert attendance for this student + subject + date
    $stmt = $pdo->prepare("SELECT id FROM attendance WHERE student_id=? AND subject_id=? AND date = ?");
    $stmt->execute([$student_id,$subject_id,$date]);
    $existsRow = $stmt->fetch();
    if($existsRow){
        $pdo->prepare("UPDATE attendance SET status=? WHERE id=?")->execute([$status,$existsRow['id']]);
    } else {
        $pdo->prepare("INSERT INTO attendance (student_id,subject_id,status,date,created_at) VALUES (?,?,?,?,NOW())")->execute([$student_id,$subject_id,$status,$date]);
    }
    echo json_encode(['ok'=>1]); exit;
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Attendance - ClassFlow</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <?php include '../shared/left_nav.php'; ?>
  <main class="main-content">
    <div class="container py-4">
      <h3>Attendance</h3>
      <form method="get" class="mb-3 d-flex align-items-end gap-3">
        <div>
          <label class="form-label">Subject</label>
          <select name="subject" class="form-select w-auto d-inline">
            <option value="">-- Select subject --</option>
            <?php foreach($subs as $s): ?>
              <option value="<?php echo $s['id']?>" <?php echo $selected==$s['id'] ? 'selected' : '' ?>><?php echo htmlspecialchars($s['name'].' • '.$s['schedule'])?></option>
            <?php endforeach;?>
          </select>
        </div>
        <div>
          <button class="btn btn-outline-secondary">Open</button>
        </div>
      </form>

      <?php if(isset($students)): ?>
        <div class="mb-3">
          <input id="studentSearch" class="form-control" placeholder="Search by lastname or firstname...">
        </div>

        <table class="table" id="studentsTable">
          <thead><tr><th></th><th>Lastname</th><th>Firstname</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
          <tbody>
            <?php foreach($students as $st):
              $att = $pdo->prepare("SELECT status,date FROM attendance WHERE student_id=? AND subject_id=? AND date = ?");
              $att->execute([$st['id'],$selected,date('Y-m-d')]);
              $row = $att->fetch();
            ?>
              <tr id="r<?php echo $st['id'] ?>" data-name="<?php echo htmlspecialchars(strtolower($st['lastname'].' '.$st['firstname'])); ?>">
                <td><img src="../<?php echo $st['avatar'] ?>" style="width:40px;height:40px;object-fit:cover;border-radius:6px;"></td>
                <td><?php echo htmlspecialchars($st['lastname']) ?></td>
                <td><?php echo htmlspecialchars($st['firstname']) ?></td>
                <td class="status"><?php echo $row ? ucfirst($row['status']) : 'Not set' ?></td>
                <td class="date"><?php echo $row ? $row['date'] : date('Y-m-d') ?></td>
                <td>
                  <button class="btn btn-sm btn-success mark" data-id="<?php echo $st['id'] ?>" data-status="present">Present</button>
                  <button class="btn btn-sm btn-danger mark" data-id="<?php echo $st['id'] ?>" data-status="absent">Absent</button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <div class="text-muted small">Note: Attendance recorded per date.</div>
      <?php endif; ?>

    </div>
  </main>

<script>
$(function(){
  $('#studentSearch').on('input', function(){
    const q = $(this).val().trim().toLowerCase();
    $('#studentsTable tbody tr').each(function(){
      const name = $(this).data('name') || '';
      $(this).toggle(name.indexOf(q) !== -1);
    });
  });

  $('.mark').click(function(){
    var id = $(this).data('id');
    var status = $(this).data('status');
    var subject_id = <?php echo (int)$selected ?>;
    $.post('', {toggle:1, student_id:id, subject_id:subject_id, status:status}, function(resp){
      if(typeof resp === 'string'){
        try{ resp = JSON.parse(resp); } catch(e){ alert('Server error'); return; }
      }
      if(resp.error){ alert(resp.error); return; }
      if(resp.ok){
        $('#r'+id+' .status').text(status.charAt(0).toUpperCase()+status.slice(1));
        $('#r'+id+' .date').text('<?php echo date('Y-m-d') ?>');
      }
    });
  });
});
</script>
</body>
</html>