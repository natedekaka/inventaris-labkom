<?php
session_start();
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/functions.php';
logActivity($_SESSION['user_id'] ?? 0, $_SESSION['nama'] ?? 'Unknown', 'logout', 'auth', $_SESSION['user_id'] ?? 0, 'User logout');
session_destroy();
header("Location: /login.php");
exit;
