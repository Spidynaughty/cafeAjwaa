<?php
/**
 * Cart.
 *
 * The browser keeps a copy in localStorage so the sticky bar and badge update
 * instantly, but the session copy here is authoritative and prices are ALWAYS
 * re-read from the menu. A tampered client price can never reach an order.
 *
 * Session shape:  $_SESSION['cart'] = [ "itemId:variantId" => qty, ... ]
 */
declare(strict_types=1);

function cart_key(int $itemId, int $variantId): string
{
    return $itemId . ':' . $variantId;
}

function cart_raw(): array
{
    return $_SESSION['cart'] ?? [];
}

function cart_set_raw(array $cart): void
{
    $_SESSION['cart'] = $cart;
}

function cart_add(int $itemId, int $variantId, int $qty = 1): void
{
    $menu = load_menu();
    $item = find_item($menu, $itemId);
    if (!$item || !find_variant($item, $variantId)) {
        return;                              // unknown item: ignore silently
    }
    $cart = cart_raw();
    $k    = cart_key($itemId, $variantId);
    $cart[$k] = min(99, max(1, ($cart[$k] ?? 0) + $qty));
    cart_set_raw($cart);
}

function cart_set_qty(int $itemId, int $variantId, int $qty): void
{
    $cart = cart_raw();
    $k    = cart_key($itemId, $variantId);
    if ($qty <= 0) {
        unset($cart[$k]);
    } else {
        $cart[$k] = min(99, $qty);
    }
    cart_set_raw($cart);
}

function cart_remove(int $itemId, int $variantId): void
{
    cart_set_qty($itemId, $variantId, 0);
}

function cart_clear(): void
{
    cart_set_raw([]);
}

/**
 * Expand the session cart into full lines with authoritative prices.
 * Lines whose item or variant no longer exists are dropped.
 *
 * @return array{lines: array, subtotal: float, delivery: float, total: float,
 *               count: int, has_unpriced: bool}
 */
function cart_summary(string $orderType = 'delivery'): array
{
    $menu  = load_menu();
    $lines = [];
    $subtotal = 0.0;
    $count    = 0;
    $unpriced = false;

    foreach (cart_raw() as $key => $qty) {
        [$itemId, $variantId] = array_map('intval', explode(':', (string) $key) + [0, 0]);
        $item = find_item($menu, $itemId);
        if (!$item) {
            continue;
        }
        $variant = find_variant($item, $variantId);
        if (!$variant) {
            continue;
        }
        $qty   = max(1, min(99, (int) $qty));
        $price = $variant['price'];
        if ($price === null) {
            $unpriced = true;
        }
        $lineTotal = $price === null ? null : $price * $qty;

        $lines[] = [
            'key'        => $key,
            'item_id'    => $itemId,
            'variant_id' => $variantId,
            'name'       => $item['name'],
            'variant'    => $variant['label'],
            'food_type'  => $item['food_type'],
            'image'      => $item['image'] ?? null,
            'price'      => $price,
            'qty'        => $qty,
            'line_total' => $lineTotal,
        ];
        $subtotal += (float) ($lineTotal ?? 0);
        $count    += $qty;
    }

    // These were previously read from the config.php constants directly,
    // which meant an owner changing delivery charge, free-delivery threshold
    // or minimum order value in Admin -> Settings had no effect on what a
    // customer was actually charged - the same class of bug already fixed
    // above for site_settings() and active_offers().
    $settings = site_settings();
    $minOrder = (float) $settings['min_order_value'];

    $delivery = 0.0;
    if ($orderType === 'delivery' && $count > 0) {
        $delivery = (float) $settings['delivery_charge'];
        if ($settings['free_delivery_over'] > 0 && $subtotal >= $settings['free_delivery_over']) {
            $delivery = 0.0;
        }
    }

    return [
        'lines'           => $lines,
        'subtotal'        => $subtotal,
        'delivery'        => $delivery,
        'total'           => $subtotal + $delivery,
        'count'           => $count,
        'has_unpriced'    => $unpriced,
        'min_order_value' => $minOrder,
        'meets_minimum'   => $minOrder <= 0 || $subtotal >= $minOrder,
    ];
}

/** Item count for the header badge. */
function cart_count(): int
{
    $n = 0;
    foreach (cart_raw() as $qty) {
        $n += max(0, (int) $qty);
    }
    return $n;
}
