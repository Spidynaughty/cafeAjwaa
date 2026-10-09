<?php
/**
 * Input validation. Server-side rules are the real ones; the matching
 * client-side checks in assets/js exist only to make the form pleasant.
 */
declare(strict_types=1);

/** Trim and collapse whitespace on a raw request value. */
function clean(?string $v, int $max = 255): string
{
    $v = trim(preg_replace('/\s+/u', ' ', (string) $v) ?? '');
    return mb_substr($v, 0, $max);
}

function valid_name(string $v): bool
{
    return mb_strlen($v) >= 2 && mb_strlen($v) <= 60
        && preg_match('/^[\p{L}\p{M}\s.\'-]+$/u', $v) === 1;
}

/** Indian mobile: 10 digits starting 6-9, with optional +91 / 0 prefix. */
function valid_mobile(string $v): bool
{
    $d = preg_replace('/[^0-9]/', '', $v);
    if (str_starts_with($d, '91') && strlen($d) === 12) {
        $d = substr($d, 2);
    }
    if (str_starts_with($d, '0') && strlen($d) === 11) {
        $d = substr($d, 1);
    }
    return preg_match('/^[6-9][0-9]{9}$/', $d) === 1;
}

function normalise_mobile(string $v): string
{
    $d = preg_replace('/[^0-9]/', '', $v);
    if (str_starts_with($d, '91') && strlen($d) === 12) $d = substr($d, 2);
    if (str_starts_with($d, '0')  && strlen($d) === 11) $d = substr($d, 1);
    return $d;
}

function valid_email(string $v): bool
{
    return $v === '' || filter_var($v, FILTER_VALIDATE_EMAIL) !== false;
}

/** Date in Y-m-d, today or later, within $days ahead. */
function valid_future_date(string $v, int $days = 60): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $v);
    if (!$d || $d->format('Y-m-d') !== $v) {
        return false;
    }
    $today = new DateTime('today');
    $limit = (new DateTime('today'))->modify("+{$days} days");
    return $d >= $today && $d <= $limit;
}

function valid_time(string $v): bool
{
    return preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $v) === 1;
}

function valid_guests(string $v): bool
{
    return ctype_digit($v) && (int) $v >= 1 && (int) $v <= 30;
}

function valid_pincode(string $v): bool
{
    return preg_match('/^[1-9][0-9]{5}$/', $v) === 1;
}

/**
 * Validate a booking payload. Returns a field => message map; empty = valid.
 */
function validate_booking(array $in): array
{
    $err = [];
    if (!valid_name($in['name'] ?? ''))            $err['name']    = 'Enter your name.';
    if (!valid_mobile($in['mobile'] ?? ''))        $err['mobile']  = 'Enter a 10-digit Indian mobile number.';
    if (!valid_future_date($in['date'] ?? ''))     $err['date']    = 'Choose a date within the next 60 days.';
    if (!valid_time($in['time'] ?? ''))            $err['time']    = 'Choose a time.';
    if (!valid_guests((string)($in['guests'] ?? ''))) $err['guests'] = 'Enter between 1 and 30 guests.';
    if (mb_strlen($in['request'] ?? '') > 500)     $err['request'] = 'Keep the note under 500 characters.';
    return $err;
}

/**
 * Validate a checkout payload. $type is 'delivery' or 'pickup'.
 */
function validate_checkout(array $in): array
{
    $err = [];
    if (!valid_name($in['name'] ?? ''))      $err['name']   = 'Enter your name.';
    if (!valid_mobile($in['mobile'] ?? ''))  $err['mobile'] = 'Enter a 10-digit Indian mobile number.';
    if (!valid_email($in['email'] ?? ''))    $err['email']  = 'Enter a valid email or leave it blank.';

    $type = $in['order_type'] ?? '';
    if (!in_array($type, ['delivery', 'pickup'], true)) {
        $err['order_type'] = 'Choose delivery or pickup.';
    }
    if ($type === 'delivery') {
        if (mb_strlen(clean($in['address'] ?? '', 255)) < 10) {
            $err['address'] = 'Enter a full delivery address.';
        }
        if (!valid_pincode($in['pincode'] ?? '')) {
            $err['pincode'] = 'Enter a 6-digit PIN code.';
        }
    }
    if (mb_strlen($in['notes'] ?? '') > 500) $err['notes'] = 'Keep the note under 500 characters.';
    return $err;
}

/**
 * Validate a contact-form payload.
 */
function validate_contact(array $in): array
{
    $err = [];
    if (!valid_name($in['name'] ?? ''))      $err['name']    = 'Enter your name.';
    if (!valid_mobile($in['mobile'] ?? ''))  $err['mobile']  = 'Enter a 10-digit Indian mobile number.';
    if (!valid_email($in['email'] ?? ''))    $err['email']   = 'Enter a valid email or leave it blank.';
    $msg = $in['message'] ?? '';
    if (mb_strlen($msg) < 5 || mb_strlen($msg) > 1000) {
        $err['message'] = 'Write between 5 and 1000 characters.';
    }
    return $err;
}
