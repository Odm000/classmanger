<?php
// Run from CLI after editing config.php, or manually create admin in DB
require_once __DIR__ . '/../config.php';
$pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$pw = password_hash('password123', PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT IGNORE INTO users (username,password,fullname,role,department) VALUES (?,?,?,?,?)");
$stmt->execute(['admin',$pw,'Administrator','admin','IT Department']);
echo "Admin created (username: admin, password: password123)\n";