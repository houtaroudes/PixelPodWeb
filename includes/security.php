<?php
function sendSecurityHeaders(): void {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()");
    $csp = [
        "default-src 'self'",
        "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://code.jquery.com",
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net",
        "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net",
        "img-src 'self' data: https: http:",
        "frame-src 'none'",
        "object-src 'none'",
        "base-uri 'self'",
        "form-action 'self'",
    ];
    header('Content-Security-Policy: ' . implode('; ', $csp));
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}
function hardenSession(): void {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Lax');
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
        ini_set('session.cookie_secure', 1);
    if (!isset($_SESSION['_last_regenerated']))
        $_SESSION['_last_regenerated'] = time();
    elseif (time() - $_SESSION['_last_regenerated'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['_last_regenerated'] = time();
    }
}
function checkRateLimit(int $maxRequests = 30, int $windowSeconds = 60, string $prefix = 'default'): bool {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = $prefix . '_' . md5($ip);
    $file = sys_get_temp_dir() . '/ratelimit_' . $key . '.tmp';
    $now = time();
    $data = [];
    if (file_exists($file)) {
        $contents = @file_get_contents($file);
        if ($contents !== false) {
            $data = json_decode($contents, true) ?? [];
            $data = array_filter($data, fn($t) => $t > $now - $windowSeconds);
        }
    }
    if (count($data) >= $maxRequests) return false;
    $data[] = $now;
    @file_put_contents($file, json_encode($data), LOCK_EX);
    return true;
}
function rateLimitError(): void {
    http_response_code(429);
    header('Retry-After: 60');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Too many requests. Please try again in 60 seconds.']);
    exit;
}
function e(string $input): string {
    return htmlspecialchars($input, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function validateEmail(string $email) {
    $email = trim($email);
    return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : false;
}
