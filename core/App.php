<?php

class App {
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function redirect($url) {
        header("Location: $url");
        exit;
    }

    public static function setFlash($msg, $type = 'info') {
        $_SESSION['flash'] = [
            'message' => $msg,
            'type' => $type
        ];
    }

    public static function getFlash() {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }

    public static function requireLogin() {
        if (!isset($_SESSION['user_id'])) {
            self::redirect('/login.php');
        }
    }

    public static function requireRole($allowedRoles) {
        if (!isset($_SESSION['user_id'])) {
            self::redirect('/login.php');
        }
        if (!is_array($allowedRoles)) {
            $allowedRoles = [$allowedRoles];
        }
        if (!in_array($_SESSION['role'], $allowedRoles)) {
            self::setFlash('Akses ditolak', 'danger');
            self::redirect('/');
        }
    }

    public static function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }

    public static function isLabAssistant() {
        return isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'lab_assistant']);
    }

    public static function generateCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateCsrfToken($token) {
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        $valid = hash_equals($_SESSION['csrf_token'], $token);
        if ($valid) {
            unset($_SESSION['csrf_token']);
        }
        return $valid;
    }

    public static function csrfField() {
        $token = self::generateCsrfToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
}

$app = new App();
