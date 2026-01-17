<?php
require_once 'config.php';
require_login();
$user = current_user();
if($user['role'] !== 'admin'){
    header("Location: index.php"); exit;
}
$pdo = pdo();

// export CSV helper
function export_csv($filename, $rows, $headers = []){
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    $out = fopen('php://output','w');
    if($headers) fputcsv($out,$headers);
    foreach($rows as $r) fputcsv($out, $r);
    fclose($out);
    exit;
}

if(isset($_GET['type'])){
    $type = $_GET['type'];
    if($type === 'students'){
        $data = $pdo->query("SELECT s.id, subj.name AS subject, s.lastname, s.firstname, s.course, s.year_level, s.archived, s.created_at FROM students s JOIN subjects subj ON s.subject_id = subj.id")->fetchAll(PDO::FETCH_ASSOC);
        export_csv('students.csv', $data, array_keys($data[0] ?? []));
    } elseif($type === 'attendance'){
        $data = $pdo->query("SELECT a.id,a.student_id,a.subject_id,a.status,a.date,a.created_at FROM attendance a")->fetchAll(PDO::FETCH_ASSOC);
        export_csv('attendance.csv', $data, array_keys($data[0] ?? []));
    } elseif($type === 'activities'){
        $data = $pdo->query("SELECT id,student_id,subject_id,title,score,created_at FROM activities")->fetchAll(PDO::FETCH_ASSOC);
        export_csv('activities.csv', $data, array_keys($data[0] ?? []));
    } elseif($type === 'grades'){
        $data = $pdo->query("SELECT id,student_id,subject_id,prelim,midterm,finals,updated_at FROM grades")->fetchAll(PDO::FETCH_ASSOC);
        export_csv('grades.csv', $data, array_keys($data[0] ?? []));
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Backup - ClassFlow</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <?php include 'shared/left_nav.php'; ?>
  <main class="main-content">
    <div class="container py-4">
      <h3>Backup / Export</h3>
      <p>Download CSV exports of the system data.</p>
      <div class="list-group">
        <a class="list-group-item list-group-item-action" href="?type=students">Export Students</a>
        <a class="list-group-item list-group-item-action" href="?type=attendance">Export Attendance</a>
        <a class="list-group-item list-group-item-action" href="?type=activities">Export Activities</a>
        <a class="list-group-item list-group-item-action" href="?type=grades">Export Grades</a>
      </div>
    </div>
  </main>
</body>
</html>