<?php

$con = mysqli_connect('localhost', 'root', '', 'shop_db') or die('connection failed');
mysqli_set_charset($con, 'utf8mb4');

// CSRF token helper functions
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        die('<h2 style="text-align:center;font-family:Cairo,sans-serif;margin-top:50px;">طلب غير صالح. يرجى المحاولة مجدداً.</h2>');
    }
}

// Safe output helper
function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Allowed image types
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp', 'gif']);
define('MAX_IMAGE_SIZE', 5 * 1024 * 1024); // 5MB
define('MAX_IMAGES_PER_AD', 5);


$host = $_ENV['MYSQLHOST'];
$user = $_ENV['MYSQLUSER'];
$password = $_ENV['MYSQLPASSWORD'];
$database = $_ENV['MYSQLDATABASE'];
$port = $_ENV['MYSQLPORT'];

$con = mysqli_connect($host, $user, $password, $database, $port);

if (!$con) {
    die("Database connection failed: " . mysqli_connect_error());
}
