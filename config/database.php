<?php
/**
 * Database credentials.
 *
 * These are XAMPP's defaults: user "root" with no password. They are fine on a
 * local machine and must NOT be used on a live server - create a dedicated
 * MySQL user there with a real password.
 *
 * This file is listed in .gitignore so credentials never reach version control.
 */
declare(strict_types=1);

return [
    'host'     => '127.0.0.1',
    'port'     => 3306,
    'database' => 'cafe_ajwaa',   // change if you named the database differently
    'username' => 'root',
    'password' => '',
    'charset'  => 'utf8mb4',
];
