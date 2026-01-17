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
    header("Location: subject.php");
    exit;
}

$student_id = intval($_POST['student_id'] ?? 0);
if (!$student_id) {
    header("Location: subject.php");
    exit;
}

// verify student exists and belongs to a subject owned by this faculty
$stmt = $pdo->prepare("
    SELECT s.*, subj.faculty_id, subj.id AS subject_id
    FROM students s
    JOIN subjects subj ON s.subject_id = subj.id
    WHERE s.id = ?
");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student || intval($student['faculty_id']) !== intval($user['id'])) {
    // not found or not owned by this faculty
    header("Location: subject.php");
    exit;
}

$firstname = trim($_POST['firstname'] ?? $student['firstname']);
$lastname  = trim($_POST['lastname'] ?? $student['lastname']);
$course    = trim($_POST['course'] ?? $student['course']);
$year      = trim($_POST['year'] ?? $student['year_level']);

$avatar_path = $student['avatar']; // keep current by default

// Handle avatar upload if provided
if (!empty($_FILES['avatar']['name']) && is_uploaded_file($_FILES['avatar']['tmp_name'])) {
    // validate image
    $check = getimagesize($_FILES['avatar']['tmp_name']);
    if ($check !== false) {
        // prepare uploads directory (store relative path 'uploads/filename' so pages under faculty using "../uploads/..." work)
        $uploadsDir = __DIR__ . '/../uploads/';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }
        $originalName = basename($_FILES['avatar']['name']);
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', pathinfo($originalName, PATHINFO_FILENAME)) . '.' . $ext;
        $destFilesystem = $uploadsDir . $filename;
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $destFilesystem)) {
            // store path relative to project root (no leading ../) so pages that prefix "../" will work (e.g., ../uploads/...)
            $avatar_path = 'uploads/' . $filename;
        }
    }
    // If image validation fails, we silently ignore the upload (you can add flash messages if desired)
}

// update student record
$update = $pdo->prepare("UPDATE students SET firstname = ?, lastname = ?, course = ?, year_level = ?, avatar = ? WHERE id = ?");
$update->execute([$firstname, $lastname, $course, $year, $avatar_path, $student_id]);

// Redirect back to subject students page
$subject_id = intval($student['subject_id'] ?? 0);
if ($subject_id) {
    header("Location: subject.php?id=" . $subject_id);
} else {
    header("Location: subjects.php");
}
exit;