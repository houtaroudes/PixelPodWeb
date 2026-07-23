<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Lax');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ini_set('session.cookie_secure', 1);
    session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
function sanitize(string $input): string { return htmlspecialchars(trim(strip_tags($input)), ENT_QUOTES, 'UTF-8'); }
function sanitizeEmail(string $email): string { $email = trim($email); return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : ''; }
function isLoggedIn(): bool { return isset($_SESSION['user_id']); }
function isAdmin(): bool { return isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'admin'; }
function requireLogin(): void { if (!isLoggedIn()) { header('Location: ' . SITE_URL . '/public/login.php'); exit; } }
function requireAdmin(): void { if (!isAdmin()) { header('Location: ' . SITE_URL . '/public/index.php'); exit; } }
function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    $stmt = getDB()->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}
function generateRef(): string {
    $year = date('Y');
    $stmt = getDB()->query("SELECT COUNT(*) FROM bookings");
    $count = $stmt->fetchColumn();
    return 'PPB-' . $year . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
}
function loginUser(string $username, string $password, string $csrfToken = ''): array {
    if (!empty($csrfToken) && !validateCsrfToken('login', $csrfToken))
        return ['success' => false, 'message' => 'Invalid security token. Please refresh and try again.'];
    if (!checkRateLimit(5, 900, 'login_' . md5($_SERVER['REMOTE_ADDR'] ?? '')))
        return ['success' => false, 'message' => 'Too many login attempts. Please try again in 15 minutes.'];
    if (empty($username) || empty($password))
        return ['success' => false, 'message' => 'Please enter both username and password.'];
    $stmt = getDB()->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['password']))
        return ['success' => false, 'message' => 'Invalid username or password.'];
    session_regenerate_id(true);
    $_SESSION['user_id']   = (int)$user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_name'] = $user['username'];
    $_SESSION['logged_in_at'] = time();
    return ['success' => true, 'message' => 'Login successful.', 'role' => $user['role']];
}
function registerUser(array $data, string $csrfToken = ''): array {
    if (!empty($csrfToken) && !validateCsrfToken('register', $csrfToken))
        return ['success' => false, 'message' => 'Invalid security token. Please refresh and try again.'];
    if (!checkRateLimit(3, 3600, 'register_' . md5($_SERVER['REMOTE_ADDR'] ?? '')))
        return ['success' => false, 'message' => 'Too many registration attempts. Please try again later.'];
    $username  = trim($data['username'] ?? '');
    $password  = $data['password'] ?? '';
    $name      = sanitize(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));
    $email     = sanitizeEmail($data['email'] ?? '');
    $phone     = sanitize($data['phone'] ?? '');
    if (strlen($username) < 3 || strlen($username) > 30)
        return ['success' => false, 'message' => 'Username must be between 3 and 30 characters.'];
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username))
        return ['success' => false, 'message' => 'Username can only contain letters, numbers, and underscores.'];
    if (strlen($password) < 8)
        return ['success' => false, 'message' => 'Password must be at least 8 characters long.'];
    if (!preg_match('/[A-Z]/', $password))
        return ['success' => false, 'message' => 'Password must contain at least one uppercase letter.'];
    if (!preg_match('/[a-z]/', $password))
        return ['success' => false, 'message' => 'Password must contain at least one lowercase letter.'];
    if (!preg_match('/[0-9]/', $password))
        return ['success' => false, 'message' => 'Password must contain at least one number.'];
    $check = getDB()->prepare("SELECT id FROM users WHERE username = ?");
    $check->execute([$username]);
    if ($check->fetch()) return ['success' => false, 'message' => 'Username already taken. Please choose another.'];
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    getDB()->prepare("INSERT INTO users (username, password, name, email, phone, role, created_at) VALUES (?,?,?,?,?,'customer',NOW())")->execute([$username, $hash, $name, $email, $phone]);
    return ['success' => true, 'message' => 'Account created successfully! You can now log in.'];
}
function logoutUser(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
