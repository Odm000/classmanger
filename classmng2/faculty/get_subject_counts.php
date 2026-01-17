<?php
// Returns JSON counts for subjects for the logged-in faculty
require_once '../config.php';
require_login();
$user = current_user();
if($user['role'] !== 'faculty'){
    http_response_code(403);
    echo json_encode(['error'=>'unauthorized']);
    exit;
}
$pdo = pdo();

// Query counts grouped by subject (students.subject_id per schema)
$stmt = $pdo->prepare("
  SELECT s.id, COUNT(st.id) AS c
  FROM subjects s
  LEFT JOIN students st ON st.subject_id = s.id AND st.archived = 0
  WHERE s.faculty_id = ?
  GROUP BY s.id
");
$stmt->execute([$user['id']]);
$rows = $stmt->fetchAll();

$out = [];
foreach($rows as $r){
  $out[(int)$r['id']] = (int)$r['c'];
}

header('Content-Type: application/json');
echo json_encode($out);
exit;
?>