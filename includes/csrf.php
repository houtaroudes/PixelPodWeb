<?php
/**
 * CSRF Protection System
 */
function generateCsrfToken(string $key = 'default'): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['csrf_tokens'][$key]))
        $_SESSION['csrf_tokens'][$key] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_tokens'][$key];
}
function csrfField(string $key = 'default'): string {
    $token = generateCsrfToken($key);
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}
function validateCsrfToken(string $key = 'default', ?string $token = null): bool {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $token = $token ?? ($_POST['csrf_token'] ?? '');
    if (empty($token) || !isset($_SESSION['csrf_tokens'][$key])) return false;
    $stored = $_SESSION['csrf_tokens'][$key];
    $valid = hash_equals($stored, $token);
    if ($valid) $_SESSION['csrf_tokens'][$key] = bin2hex(random_bytes(32));
    return $valid;
}
function requireCsrfToken(string $key = 'default'): void {
    if (!validateCsrfToken($key)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid or expired security token. Please refresh the page and try again.']);
        exit;
    }
}
