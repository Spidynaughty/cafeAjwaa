<?php
/**
 * Create the first admin account.
 *
 * Run from the command line only:
 *     php sql/create-admin.php
 *
 * The password is read from the terminal and hashed immediately, so it never
 * appears in a .sql file, in the shell history, or in version control.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This script can only be run from the command line.\n");
}

require dirname(__DIR__) . '/config/config.php';
require dirname(__DIR__) . '/includes/db.php';

if (!db_available()) {
    exit("No database connection. Copy config/database.sample.php to config/database.php and fill it in first.\n");
}

function ask(string $prompt, bool $hidden = false): string
{
    echo $prompt;
    if ($hidden && DIRECTORY_SEPARATOR !== '\\') {
        shell_exec('stty -echo');
        $value = trim((string) fgets(STDIN));
        shell_exec('stty echo');
        echo "\n";
    } else {
        $value = trim((string) fgets(STDIN));
    }
    return $value;
}

$username = ask('Username: ');
if (strlen($username) < 3) {
    exit("Username must be at least 3 characters.\n");
}
if (db_one('SELECT id FROM admins WHERE username = ?', [$username])) {
    exit("That username already exists.\n");
}

$name = ask('Display name: ');

$password = ask('Password (minimum 12 characters): ', true);
if (strlen($password) < 12) {
    exit("Password must be at least 12 characters.\n");
}
if (ask('Confirm password: ', true) !== $password) {
    exit("The passwords did not match.\n");
}

db_run(
    'INSERT INTO admins (username, password_hash, display_name, role, created_at)
     VALUES (?, ?, ?, ?, NOW())',
    [$username, password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
     $name ?: $username, 'owner']
);

echo "Admin account '{$username}' created. Log in at /admin/login.php\n";
