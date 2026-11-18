<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

// Logging out must be a POST with a CSRF token so other sites cannot log you out.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    session_start();
    flash('success', 'You have been logged out. See you soon! 👋');
}
redirect('index.php');
