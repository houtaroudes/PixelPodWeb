<?php
/**
 * CSRF tokens.
 *
 * A form issues a token under a key with csrfField('admin_form'), and the
 * handler that receives the post checks the same key with
 * requireCsrfToken('admin_form'). The two keys have to match exactly. They did
 * not: one side was passing the key with quotes inside the string ("'admin_form'"),
 * so the token was generated under one name and looked up under another and
 * every one of those submissions failed.
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

/**
 * The token however it arrived: a form field, an X-CSRF-Token header, or a
 * csrf_token member of a JSON body. The last two exist because the JSON APIs
 * are not form posts and used to have no way to carry a token at all.
 */
function csrfTokenFromRequest(): string {
    if (!empty($_POST['csrf_token'])) return (string)$_POST['csrf_token'];
    if (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) return (string)$_SERVER['HTTP_X_CSRF_TOKEN'];
    $raw = file_get_contents('php://input');
    if (is_string($raw) && $raw !== '') {
        $body = json_decode($raw, true);
        if (is_array($body) && !empty($body['csrf_token'])) return (string)$body['csrf_token'];
    }
    return '';
}

function validateCsrfToken(string $key = 'default', ?string $token = null): bool {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $token = $token ?? csrfTokenFromRequest();
    if ($token === '' || !isset($_SESSION['csrf_tokens'][$key])) return false;
    $stored = $_SESSION['csrf_tokens'][$key];
    $valid = hash_equals($stored, $token);
    if ($valid) $_SESSION['csrf_tokens'][$key] = bin2hex(random_bytes(32));
    return $valid;
}

/**
 * Refuses the request when the token is missing or wrong.
 *
 * This used to return early whenever no token was submitted, on the theory that
 * forms without the field should keep working. That is the whole attack: a
 * request that simply omits the field skipped the check, so any endpoint that
 * could be reached without the form, the JSON APIs in particular, had no CSRF
 * protection at all. Every caller now sends a token.
 */
function requireCsrfToken(string $key = 'default'): void {
    if (validateCsrfToken($key)) return;
    $message = 'Invalid or expired security token. Please refresh the page and try again.';
    /* The admin pages run this check after their header is already on the wire,
       so a status code there can only produce a warning. Those pages get the
       message inside the page instead; the JSON APIs still answer properly. */
    if (!headers_sent()) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $message]);
    } else {
        echo '<div class="alert alert-danger">' . $message . '</div>';
    }
    exit;
}
