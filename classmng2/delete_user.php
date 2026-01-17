<?php
require_once 'config.php';
require_login();

$user = current_user();
if ($user['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$pdo = pdo();

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Delete the user
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);

    header("Location: admin.php?msg=User deleted");
    exit;
}
?>