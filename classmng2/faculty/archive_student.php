<?php
require_once '../config.php';
require_login();

$user = current_user();
if ($user['role'] !== 'faculty') {
    header("Location: ../index.php");
    exit;
}

$pdo = pdo();

// Expect POST with student_id and either "archive" (button name) or "unarchive"
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../faculty/subjects.php");
    exit;
}

$student_id = intval($_POST['student_id'] ?? 0);
if (!$student_id) {
    header("Location: ../faculty/subjects.php");
    exit;
}

// fetch student and subject to validate ownership
$stmt = $pdo->prepare("
    SELECT s.*, subj.id AS subject_id, subj.faculty_id
    FROM students s
    JOIN subjects subj ON s.subject_id = subj.id
    WHERE s.id = ?
    LIMIT 1
");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    // nothing found
    header("Location: ../faculty/subjects.php");
    exit;
}

// ensure the logged-in faculty owns the subject
if (intval($student['faculty_id']) !== intval($user['id'])) {
    // unauthorized
    header("Location: ../faculty/subjects.php");
    exit;
}

$subject_id = intval($student['subject_id']);

// Determine action: archive vs unarchive
if (isset($_POST['archive'])) {
    // Archive the student
    $pdo->prepare("UPDATE students SET archived = 1 WHERE id = ?")->execute([$student_id]);
    // Redirect to subject page
    header("Location: subject.php?id=" . $subject_id);
    exit;
}

if (isset($_POST['unarchive'])) {
    // Unarchive the student
    $pdo->prepare("UPDATE students SET archived = 0 WHERE id = ?")->execute([$student_id]);
    // Redirect back to archive listing if provided, otherwise to subject page
    if (!empty($_POST['redirect']) && strpos($_POST['redirect'], 'archive.php') !== false) {
        header("Location: archive.php");
    } else {
        header("Location: subject.php?id=" . $subject_id);
    }
    exit;
}

// If neither archive nor unarchive provided, simply redirect back
header("Location: subject.php?id=" . $subject_id);
exit;