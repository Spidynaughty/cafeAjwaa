<?php
/**
 * Turn the session cart + checkout details into an order.
 *
 * Writes to MySQL when it is available. Without a database the order is held
 * in the session so the confirmation screen still works, and the page tells
 * the customer plainly that the order was not recorded.
 */
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';

$pending = $_SESSION['pending_order'] ?? null;
if (!$pending || !ORDERING_ENABLED) {
    redirect('/cart.php');
}

$customer  = $pending['customer'];
$orderType = $customer['order_type'];
$summary   = cart_summary($orderType);

if (!$summary['lines']) {
    unset($_SESSION['pending_order']);
    redirect('/cart.php');
}

$orderNumber = 'AJ' . date('ymd') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
$stored      = false;

if (db_available()) {
    $pdo = db();
    try {
        $pdo->beginTransaction();

        $mobile = normalise_mobile($customer['mobile']);
        $existing = db_one('SELECT id FROM customers WHERE phone = ? LIMIT 1', [$mobile]);
        if ($existing) {
            $customerId = (int) $existing['id'];
            db_run('UPDATE customers SET name = ?, email = ? WHERE id = ?',
                   [$customer['name'], $customer['email'] ?: null, $customerId]);
        } else {
            db_run('INSERT INTO customers (name, phone, email, created_at) VALUES (?, ?, ?, NOW())',
                   [$customer['name'], $mobile, $customer['email'] ?: null]);
            $customerId = (int) $pdo->lastInsertId();
        }

        db_run(
            'INSERT INTO orders (customer_id, order_number, order_type, address_line, landmark,
                                 pincode, subtotal, delivery_charge, grand_total, status,
                                 payment_status, notes, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
            [
                $customerId, $orderNumber, $orderType,
                $orderType === 'delivery' ? $customer['address']  : null,
                $orderType === 'delivery' ? ($customer['landmark'] ?: null) : null,
                $orderType === 'delivery' ? $customer['pincode']  : null,
                $summary['subtotal'], $summary['delivery'], $summary['total'],
                'pending', 'unpaid', $customer['notes'] ?: null,
            ]
        );
        $orderId = (int) $pdo->lastInsertId();

        foreach ($summary['lines'] as $line) {
            db_run(
                'INSERT INTO order_items (order_id, menu_item_id, variant_id, item_name_snapshot,
                                          variant_label_snapshot, unit_price_snapshot, quantity, line_total)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $orderId, $line['item_id'], $line['variant_id'],
                    $line['name'], $line['variant'],
                    $line['price'], $line['qty'], $line['line_total'],
                ]
            );
        }

        $pdo->commit();
        $stored = true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('[cafe-ajwaa] order failed: ' . $e->getMessage());
        $stored = false;
    }
}

/* Keep a copy for the confirmation screen, then empty the cart. */
$_SESSION['last_order'] = [
    'number'   => $orderNumber,
    'stored'   => $stored,
    'type'     => $orderType,
    'customer' => $customer,
    'summary'  => $summary,
];
unset($_SESSION['pending_order'], $_SESSION['checkout_old']);
cart_clear();

redirect('/order-confirmation.php');
