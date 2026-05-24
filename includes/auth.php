<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start(['cookie_httponly' => true]);
}
require_once __DIR__ . '/../config/database.php';

function sanitize(string $v): string {
    return htmlspecialchars(strip_tags(trim($v)), ENT_QUOTES, 'UTF-8');
}

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function isAdmin(): bool {
    return isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'admin';
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/public/login.php');
        exit;
    }
}

function requireAdmin(): void {
    if (!isAdmin()) {
        header('Location: ' . SITE_URL . '/public/index.php');
        exit;
    }
}

function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    $s = getDB()->prepare("SELECT id,username,first_name,last_name,email,phone,role FROM users WHERE id=?");
    $s->execute([$_SESSION['user_id']]);
    return $s->fetch() ?: null;
}

// Login using Username
// Changed from email to username lookup
function loginUser(string $username, string $password): array {
    $username = trim($username);
    if (empty($username) || empty($password)) {
        return ['success' => false, 'message' => 'Please enter your username and password.'];
    }
    $s = getDB()->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $s->execute([$username]);
    $u = $s->fetch();
    if (!$u || !password_verify($password, $u['password'])) {
        return ['success' => false, 'message' => 'Invalid username or password.'];
    }
    session_regenerate_id(true);
    $_SESSION['user_id']   = $u['id'];
    $_SESSION['user_role'] = $u['role'];
    $_SESSION['user_name'] = $u['first_name'] . ' ' . $u['last_name'];
    return ['success' => true, 'role' => $u['role']];
}

// Register with Username
// New accounts use username + password
function registerUser(array $d): array {
    $username = trim($d['username'] ?? '');

    // Validate username
    if (empty($username)) {
        return ['success' => false, 'message' => 'Username is required.'];
    }
    if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        return ['success' => false, 'message' => 'Username must be 3-30 characters. Only letters, numbers, and underscores allowed.'];
    }
    if (strlen($d['password'] ?? '') < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters.'];
    }

    // Check if username already exists
    $s = getDB()->prepare("SELECT id FROM users WHERE username = ?");
    $s->execute([$username]);
    if ($s->fetch()) {
        return ['success' => false, 'message' => 'Username already taken. Please choose another.'];
    }

    $hash = password_hash($d['password'], PASSWORD_BCRYPT, ['cost' => 12]);
    $s = getDB()->prepare(
        "INSERT INTO users (username, first_name, last_name, email, password, phone) VALUES (?,?,?,?,?,?)"
    );
    $s->execute([
        $username,
        sanitize($d['first_name'] ?? ''),
        sanitize($d['last_name']  ?? ''),
        trim($d['email']          ?? ''),
        $hash,
        sanitize($d['phone']      ?? '')
    ]);
    return ['success' => true, 'message' => 'Account created! Please log in.'];
}

function logoutUser(): void {
    session_unset();
    session_destroy();
}

function generateRef(): string {
    $y = date('Y');
    $c = (int) getDB()->query("SELECT COUNT(*) FROM bookings WHERE YEAR(created_at)=$y")->fetchColumn() + 1;
    return 'PPB-' . $y . '-' . str_pad($c, 4, '0', STR_PAD_LEFT);
}
