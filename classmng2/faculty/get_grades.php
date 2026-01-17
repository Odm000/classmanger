<?php
// faculty/get_grades.php
// Returns JSON grades for a subject and (optionally) a student_id query param
session_start();
// TODO: check auth

header('Content-Type: application/json; charset=utf-8');

$subject_id = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;
$student_id = isset($_GET['student_id']) ? trim($_GET['student_id']) : null;

if ($subject_id <= 0) {
  echo json_encode(['success'=>false,'message'=>'Missing subject_id']);
  exit;
}

require_once __DIR__ . '/../config.php'; // must create $mysqli

if (!isset($mysqli) || !($mysqli instanceof mysqli)) {
  echo json_encode(['success'=>false,'message'=>'DB connection not found.']);
  exit;
}

try {
  if ($student_id) {
    $stmt = $mysqli->prepare("SELECT student_id, grade, updated_at FROM grades WHERE subject_id = ? AND student_id = ?");
    $stmt->bind_param('is', $subject_id, $student_id);
  } else {
    $stmt = $mysqli->prepare("SELECT student_id, grade, updated_at FROM grades WHERE subject_id = ?");
    $stmt->bind_param('i', $subject_id);
  }
  $stmt->execute();
  $res = $stmt->get_result();
  $rows = [];
  while ($r = $res->fetch_assoc()) $rows[] = $r;
  echo json_encode(['success'=>true,'data'=>$rows]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}