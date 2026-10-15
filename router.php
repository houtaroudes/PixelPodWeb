<?php
/**
 * Front controller for the PHP built-in server.
 *
 * Every link in this app is built as SITE_URL . '/public/...', and the admin
 * pages and uploaded photos sit beside public/ rather than inside it, so the
 * web root has to be the project root. Serving that root straight would also
 * hand out things nobody should be able to fetch (the config folder, this
 * project's notes, a database dump in a local checkout), so this file is what
 * keeps the root honest: it blocks the non-public paths, sends / to the
 * landing page, and hands every real file back to the built-in server.
 *
 * Local run:
 *   php -S 127.0.0.1:8123 router.php
 *
 * Railway runs the same command through railway.json, so a local run and a
 * deployment exercise the same paths.
 */

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = is_string($path) && $path !== '' ? $path : '/';

/** Answer without advertising that anything was ever there. */
$notFound = static function (): bool {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Not found\n";
    return true;
};

// The landing page lives under public/, so the bare root is a redirect.
if ($path === '/') {
    header('Location: /public/index.php', true, 302);
    return true;
}

// Folders that hold code, credentials or notes rather than pages. config/
// carries SITE_URL and the database credentials, database/ a seed dump.
$privateDirs = ['/config/', '/includes/', '/database/', '/docs/'];
foreach ($privateDirs as $dir) {
    if (str_starts_with($path, $dir)) {
        return $notFound();
    }
}

// Repository furniture: dotfiles (.env, .gitignore, .git), notes and manifest
// files, and anything sitting inside a dependency folder.
if (str_starts_with($path, '/.') || str_contains($path, '/.git/') || str_contains($path, '/node_modules/')) {
    return $notFound();
}
if (str_ends_with($path, '.md') || str_ends_with($path, '.sql') || str_ends_with($path, 'composer.json') || str_ends_with($path, 'composer.lock')) {
    return $notFound();
}
if (str_contains($path, '..')) {
    return $notFound();
}

// Everything else is a page or an asset: let the built-in server serve it.
return false;
