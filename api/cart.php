<?php
/**
 * Cart endpoint. JSON in, JSON out.
 *
 * Prices are never accepted from the client - the server re-reads them from
 * the menu on every call, so a modified request cannot change a total.
 */
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}
if (!ORDERING_ENABLED) {
    http_response_code(403);
    echo json_encode(['error' => 'Ordering is currently switched off']);
    exit;
}

/*
 * CSRF guard.
 *
 * This endpoint is called only by fetch() in app.js, which always sends this
 * header. A plain cross-site <form method="post"> - the classic CSRF vector -
 * cannot set a custom request header at all, and a cross-origin fetch/XHR
 * that tries to would trigger a CORS preflight that this server never
 * approves (no Access-Control-Allow-Origin is set anywhere in the app), so
 * the browser blocks the request before it reaches here. Every other
 * state-changing endpoint in the app uses csrf_check(); this JSON API used
 * none of it.
 */
if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'fetch') {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$action  = (string) ($_POST['action']  ?? '');
$item    = (int)    ($_POST['item']    ?? 0);
$variant = (int)    ($_POST['variant'] ?? 0);
$qty     = (int)    ($_POST['qty']     ?? 1);

switch ($action) {
    case 'add':
        cart_add($item, $variant, max(1, min(20, $qty)));
        break;
    case 'set':
        cart_set_qty($item, $variant, max(0, min(99, $qty)));
        break;
    case 'remove':
        cart_remove($item, $variant);
        break;
    case 'clear':
        cart_clear();
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action']);
        exit;
}

$summary = cart_summary();
echo json_encode([
    'count'    => $summary['count'],
    'subtotal' => $summary['subtotal'],
    'total'    => $summary['total'],
]);
