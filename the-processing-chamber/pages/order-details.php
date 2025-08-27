<?php
/**
 * Public order details
 *
 * Renders a user's order summary. The page accepts a GET parameter `id` (order id)
 * and will only display orders belonging to the current logged-in user.
 *
 * Implementation notes:
 * - The page prefers a JSON snapshot stored in `orders.items` for stability.
 * - Falls back to the `order_items` table when snapshot data is absent.
 */
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
// require_login();

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($order_id <= 0) { echo '<h2>Invalid order</h2>'; require_once __DIR__.'/../includes/footer.php'; exit; }

try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) { echo '<h2>Order not found</h2>'; require_once __DIR__.'/../includes/footer.php'; exit; }

    // Safely parse stored items (avoid passing null to json_decode)
    $items = [];
    $rawItems = $order['items'] ?? '';
    if (is_string($rawItems) && $rawItems !== '') {
        $decoded = json_decode($rawItems, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $items = $decoded;
        }
    }

    // Fallback: if orders.items is empty, pull items from order_items table
    if (empty($items)) {
        $stmtItems = $pdo->prepare("SELECT oi.quantity AS qty, oi.price AS price, p.name AS name, oi.product_id FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
        $stmtItems->execute([$order_id]);
        $rows = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($rows)) {
            foreach ($rows as $r) {
                $items[] = [
                    'qty' => isset($r['qty']) ? (int)$r['qty'] : 0,
                    'name' => $r['name'] ?? ('Product #' . ($r['product_id'] ?? '')),
                    'price' => isset($r['price']) ? (float)$r['price'] : 0.0
                ];
            }
        }
    }

    // Safe output for fields that might be null
    $order_id_html = htmlspecialchars((string)($order['id'] ?? ''));
    $order_date_html = htmlspecialchars((string)($order['order_date'] ?? ''));
    $status_html = htmlspecialchars((string)($order['status'] ?? ''));

    echo '<div class="order-details">';
    echo '<div class="order-card">';
    echo '<h2 class="order-title">Order #'.$order_id_html.'</h2>';
    echo '<div class="order-meta">';
    echo '<p class="muted">Date: '.$order_date_html.' &nbsp;•&nbsp; Status: <strong>'. $status_html .'</strong></p>';
    echo '</div>';

    if ($items) {
        echo "<table class='order-table' cellpadding='6' cellspacing='0'>";
        echo "<thead><tr><th>Qty</th><th>Item</th><th>Price</th><th>Subtotal</th></tr></thead>";
        echo "<tbody>";
        $total = 0;
        foreach ($items as $it) {
            // Support both shapes: {'qty', 'price', 'name'} and {'quantity','price','name','subtotal'}
            $qty = (int)($it['qty'] ?? $it['quantity'] ?? 0);
            $name = htmlspecialchars($it['name'] ?? '');
            $price = (float)($it['price'] ?? 0);
            // If subtotal was stored in the snapshot, prefer it (useful for discounts), otherwise compute
            $sub = isset($it['subtotal']) ? (float)$it['subtotal'] : ($qty * $price);
            $total += $sub;
            echo "<tr><td class='qty-col'>{$qty}</td><td class='item-col'>{$name}</td><td class='price-col'>₹".number_format($price,2)."</td><td class='sub-col'>₹".number_format($sub,2)."</td></tr>";
        }
        echo "</tbody>";
        echo "<tfoot><tr class='order-total-row'><td colspan='3' class='order-total-label'>Total</td><td class='order-total-value'><strong>₹".number_format($total,2)."</strong></td></tr></tfoot>";
        echo "</table>";
    } else {
        echo "<p>No items recorded for this order.</p>";
    }
    echo '</div>'; // .order-card
    echo '</div>'; // .order-details
} catch (Exception $e) {
    echo "<p>Could not load order.</p>";
}
    // If an admin is viewing the public order page, show a small return link to the admin dashboard
    if (function_exists('isAdmin') && isAdmin()) {
        echo '<p><a href="' . SITE_URL . '/admin/index.php" class="btn" style="background:var(--primary); margin-bottom:12px; display:inline-block;">Return to Admin Dashboard</a></p>';
    }

    require_once __DIR__ . '/../includes/footer.php';
?>