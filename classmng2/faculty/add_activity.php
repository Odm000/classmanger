<?php
require_once '../config.php';
require_login();

$user = current_user();
if ($user['role'] !== 'faculty') {
    header("Location: ../index.php");
    exit;
}

$pdo = pdo();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php");
    exit;
}

$student_id = intval($_POST['student_id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$score = $_POST['score'] ?? '';
$score = $score === '' ? 0.0 : floatval($score);

if (!$student_id) {
    // nothing to do
    header("Location: dashboard.php");
    exit;
}

// Verify that the student exists and that the subject belongs to this faculty
$stmt = $pdo->prepare("
    SELECT s.id AS student_id, s.subject_id AS subject_id, subj.faculty_id
    FROM students s
    JOIN subjects subj ON s.subject_id = subj.id
    WHERE s.id = ?
");
$stmt->execute([$student_id]);
$record = $stmt->fetch();

if (!$record) {
    // invalid student
    header("Location: dashboard.php");
    exit;
}

$subject_id = intval($record['subject_id']);
$faculty_id = intval($record['faculty_id']);

if ($faculty_id !== intval($user['id'])) {
    // not authorized to add activity for this student
    header("Location: dashboard.php");
    exit;
}

// Insert activity
$ins = $pdo->prepare("INSERT INTO activities (student_id, subject_id, title, score, created_at) VALUES (?,?,?,?,NOW())");
$ins->execute([$student_id, $subject_id, $title, $score]);

// Redirect back to the subject page so the UI updates. If you prefer JSON/AJAX responses,
// you can detect an XMLHttpRequest and return JSON instead.
header("Location: subject.php?id=" . $subject_id);
exit;