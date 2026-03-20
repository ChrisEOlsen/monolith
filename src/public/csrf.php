<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Validate if a URL is internal to prevent Open Redirects.
 */
function is_internal_url($url) {
    if (empty($url)) return false;
    // Must start with / and not contain // (which could be protocol-relative)
    return str_starts_with($url, '/') && !str_starts_with($url, '//');
}
