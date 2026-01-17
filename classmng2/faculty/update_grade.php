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
$prelim = isset($_POST['prelim']) ? trim($_POST['prelim']) : '';
$midterm = isset($_POST['midterm']) ? trim($_POST['midterm']) : '';
$finals = isset($_POST['finals']) ? trim($_POST['finals']) : '';

if (!$student_id) {
    header("Location: dashboard.php");
    exit;
}

// Fetch student and subject, ensure the subject belongs to this faculty
$stmt = $pdo->prepare("
    SELECT s.id AS student_id, s.subject_id AS subject_id, subj.faculty_id
    FROM students s
    JOIN subjects subj ON s.subject_id = subj.id
    WHERE s.id = ?
");
$stmt->execute([$student_id]);
$rec = $stmt->fetch();

if (!$rec) {
    header("Location: dashboard.php");
    exit;
}

$subject_id = intval($rec['subject_id']);
$faculty_id = intval($rec['faculty_id']);
if ($faculty_id !== intval($user['id'])) {
    // not authorized
    header("Location: dashboard.php");
    exit;
}

// normalize grade values to decimals (default 0.00)
function norm_grade($v) {
    if ($v === '' || $v === null) return 0.00;
    // remove commas/spaces and ensure numeric
    $v = str_replace(',', '', $v);
    return is_numeric($v) ? floatval($v) : 0.00;
}

$prelim_v = norm_grade($prelim);
$midterm_v = norm_grade($midterm);
$finals_v = norm_grade($finals);

// Upsert into grades table: if exists update, else insert
$check = $pdo->prepare("SELECT id FROM grades WHERE student_id = ? AND subject_id = ? LIMIT 1");
$check->execute([$student_id, $subject_id]);
$existing = $check->fetch();

if ($existing) {
    $pdo->prepare("UPDATE grades SET prelim = ?, midterm = ?, finals = ?, updated_at = NOW() WHERE id = ?")
        ->execute([$prelim_v, $midterm_v, $finals_v, $existing['id']]);
} else {
    $pdo->prepare("INSERT INTO grades (student_id, subject_id, prelim, midterm, finals, updated_at) VALUES (?,?,?,?,?,NOW())")
        ->execute([$student_id, $subject_id, $prelim_v, $midterm_v, $finals_v]);
}

// Redirect back to the subject page (so the modal will reflect changes when reopened)
header("Location: subject.php?id=" . $subject_id);
exit;