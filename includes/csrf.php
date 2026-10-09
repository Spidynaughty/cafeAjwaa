<?php
/**
 * CSRF protection. Every state-changing POST must carry a valid token.
 */
declare(strict_types=1);

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Hidden input to drop inside every <form>. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_valid(?string $token): bool
{
    return is_string($token)
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

/** Abort the request when the token is missing or wrong. */
function csrf_check(): void
{
    if (!csrf_valid($_POST['csrf_token'] ?? null)) {
        http_response_code(419);
        exit('Your session expired. Please go back, reload the page and try again.');
    }
}
