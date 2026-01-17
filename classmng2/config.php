<?php
// Database config
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'classflow');
define('DB_USER', 'root');
define('DB_PASS', '');

// Base url for links (adjust when deploying)
define('BASE_URL', '/'); // set to project subfolder if needed, e.g., '/classflow/'

// Start session only if none exists
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function pdo() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

function is_logged_in(){
    return !empty($_SESSION['user']);
}
function require_login(){
    if(!is_logged_in()){
        header("Location: ".BASE_URL."index.php");
        exit;
    }
}
function current_user(){
    return $_SESSION['user'] ?? null;
}