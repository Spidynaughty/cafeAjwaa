<?php
/**
 * Admin authentication. Passwords are bcrypt hashes; nothing is ever stored
 * or compared in plain text.
 */
declare(strict_types=1);

function admin_user(): ?array
{
    return $_SESSION['admin'] ?? null;
}

function admin_logged_in(): bool
{
    return admin_user() !== null;
}

/** Only owners can manage other admin accounts. */
function admin_is_owner(): bool
{
    $u = admin_user();
    return $u !== null && $u['role'] === 'owner';
}

/** Redirect non-owners away from owner-only pages. */
function require_owner(): void
{
    require_admin();
    if (!admin_is_owner()) {
        redirect('/admin/dashboard.php');
    }
}

/** Redirect to the login screen unless an admin session exists. */
function require_admin(): void
{
    if (!admin_logged_in()) {
        redirect('/admin/login.php');
    }
}

/**
 * Verify credentials. Runs a dummy hash check when the user is unknown so the
 * response time does not reveal whether a username exists.
 */
function admin_attempt(string $username, string $password): bool
{
    $row = db_one(
        'SELECT id, username, password_hash, role FROM admins WHERE username = ? LIMIT 1',
        [$username]
    );

    if (!$row) {
        password_verify($password, '$2y$12$usesomesillystringforsalt0123456789abcdefghijklmnopq');
        return false;
    }
    if (!password_verify($password, $row['password_hash'])) {
        return false;
    }

    // Rotate the session id so a pre-set cookie cannot be reused.
    session_regenerate_id(true);
    $_SESSION['admin'] = [
        'id'       => (int) $row['id'],
        'username' => $row['username'],
        'role'     => $row['role'],
    ];
    db_run('UPDATE admins SET last_login = NOW() WHERE id = ?', [(int) $row['id']]);
    return true;
}

function admin_logout(): void
{
    unset($_SESSION['admin']);
    session_regenerate_id(true);
}

/**
 * Simple per-session throttle for the login form.
 * Returns the number of seconds the caller must wait, or 0.
 */
function login_throttle_wait(): int
{
    $a = $_SESSION['login_attempts'] ?? ['n' => 0, 'at' => 0];
    if ($a['n'] < 5) {
        return 0;
    }
    $wait = 60 - (time() - $a['at']);
    return max(0, $wait);
}

function login_record_failure(): void
{
    $a = $_SESSION['login_attempts'] ?? ['n' => 0, 'at' => 0];
    if (time() - $a['at'] > 300) {
        $a = ['n' => 0, 'at' => 0];
    }
    $_SESSION['login_attempts'] = ['n' => $a['n'] + 1, 'at' => time()];
}

function login_clear_failures(): void
{
    unset($_SESSION['login_attempts']);
}
