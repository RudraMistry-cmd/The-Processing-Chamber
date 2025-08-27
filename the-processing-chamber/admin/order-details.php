<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Only admins may view this page
if (!isAdmin()) {
    redirect('admin/login.php');
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($order_id <= 0) {
    // include admin header so layout is consistent when showing error
    require_once __DIR__ . '/includes/admin-header.php';
    echo '<h2>Invalid order</h2>'; require_once __DIR__.'/includes/admin-footer.php'; exit;
}

// Handle status update before any output so header() works
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = trim((string)($_POST['status'] ?? ''));
    $allowed = ['pending','processing','shipped','delivered','cancelled','refunded'];
    if (in_array($newStatus, $allowed, true)) {
        $u = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
        $u->execute([$newStatus, $order_id]);
    }
    // Redirect to avoid form re-submit
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// Now include admin header so admin sidebar/layout is used
require_once __DIR__ . '/includes/admin-header.php';

try {
    // Fetch order and user info for admin
    $stmt = $pdo->prepare("SELECT o.*, u.name as customer_name, u.email as customer_email FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ? LIMIT 1");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) { echo '<h2>Order not found</h2>'; require_once __DIR__.'/includes/admin-footer.php'; exit; }

    // Safely parse stored items (could be NULL or empty)
    $items = [];
    $raw = $order['items'] ?? '';
    if (is_string($raw) && $raw !== '') {
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $items = $decoded;
        }
    }

    // Fallback to order_items table if JSON not available
    if (empty($items)) {
        $stmtItems = $pdo->prepare("SELECT oi.quantity AS qty, oi.price AS price, COALESCE(p.name, '') AS name, oi.product_id FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
        $stmtItems->execute([$order_id]);
        $rows = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            $items[] = [
                'quantity' => (int)($r['qty'] ?? 0),
                'qty' => (int)($r['qty'] ?? 0),
                'name' => $r['name'] ?: ('Product #' . ($r['product_id'] ?? '')),
                'price' => (float)($r['price'] ?? 0)
            ];
        }
    }

    // If items include product_id, pre-fetch product images/names for thumbnails
    $productMap = [];
    $pids = [];
    foreach ($items as $it) {
        if (!empty($it['product_id'])) $pids[] = (int)$it['product_id'];
    }
    $pids = array_values(array_unique($pids));
    if (!empty($pids)) {
        $placeholders = implode(',', array_fill(0, count($pids), '?'));
        $stmtP = $pdo->prepare("SELECT id, name, image FROM products WHERE id IN ($placeholders)");
        $stmtP->execute($pids);
        $prodRows = $stmtP->fetchAll(PDO::FETCH_ASSOC);
        foreach ($prodRows as $pr) {
            $productMap[(int)$pr['id']] = $pr;
        }
    }

    // Display summary with improved markup
    $orderIdHtml = htmlspecialchars($order['id']);
    $date = htmlspecialchars((string)($order['created_at'] ?? $order['order_date'] ?? ''));
    $status = htmlspecialchars($order['status'] ?? '');
    $customer = htmlspecialchars($order['customer_name'] ?? 'Guest');
    $customerEmail = htmlspecialchars($order['customer_email'] ?? '');
    $shipping = nl2br(htmlspecialchars($order['shipping_address'] ?? 'N/A'));
    $payment = htmlspecialchars($order['payment_method'] ?? 'N/A') . ' — ' . htmlspecialchars($order['payment_status'] ?? '');

    echo '<div class="admin-order">';
    echo "<div style=\"display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;\">";
    echo "<h2 style=\"margin:0\">Order #{$orderIdHtml}</h2>";
    // print button and quick status form area
    echo '<div style="display:flex; gap:8px; align-items:center;">';
    echo '<button onclick="window.print();" class="btn">Print</button>';
    echo '<form method="POST" style="display:inline-block; margin:0;">';
    echo '<select name="status" style="padding:6px; margin-left:6px;">';
    $statuses = ['pending','processing','shipped','delivered','cancelled','refunded'];
    foreach ($statuses as $s) {
        $sel = ($s === ($order['status'] ?? '')) ? ' selected' : '';
        echo "<option value=\"".htmlspecialchars($s)."\"{$sel}>".ucfirst($s)."</option>";
    }
    echo '</select>';
    echo '<input type="hidden" name="update_status" value="1">';
    echo '<button type="submit" class="btn" style="margin-left:6px;">Update</button>';
    echo '</form>';
    echo '</div>';
    echo "</div>";
    echo '<div class="order-meta">';
    echo "<p>Date: {$date}</p>";
    echo "<p>Status: {$status}</p>";
    echo "<p>Customer: {$customer} ({$customerEmail})</p>";
    echo "<p>Shipping address: {$shipping}</p>";
    echo "<p>Payment method: {$payment}</p>";
    echo '</div>';

    if ($items) {
        echo '<table class="admin-table">';
        echo '<thead><tr><th>Qty</th><th>Item</th><th>Price</th><th>Subtotal</th></tr></thead>';
        echo '<tbody>';
        $total = 0;
        foreach ($items as $it) {
            $qty = (int)($it['qty'] ?? $it['quantity'] ?? 0);
            $name = htmlspecialchars($it['name'] ?? '');
            $price = (float)($it['price'] ?? 0);
            $sub = isset($it['subtotal']) ? (float)$it['subtotal'] : ($qty * $price);
            $total += $sub;
            $thumbHtml = '';
            $pid = !empty($it['product_id']) ? (int)$it['product_id'] : 0;
            if ($pid && isset($productMap[$pid]) && !empty($productMap[$pid]['image'])) {
                $img = htmlspecialchars($productMap[$pid]['image']);
                $thumbHtml = "<img src='".SITE_URL."/uploads/". $img ."' alt='thumbnail' style='width:60px; height:auto; margin-right:8px; vertical-align:middle;'>";
            }
            echo "<tr><td>{$qty}</td><td>{$thumbHtml}{$name}</td><td>₹".number_format($price,2)."</td><td>₹".number_format($sub,2)."</td></tr>";
        }
        echo '</tbody>';
        echo '<tfoot><tr><td colspan="3" class="total">Total</td><td class="total">₹'.number_format($total,2).'</td></tr></tfoot>';
        echo '</table>';
    } else {
        echo '<p>No items recorded for this order.</p>';
    }

    echo '</div>';

} catch (Exception $e) {
    echo "<p>Could not load order.</p>";
    // Helpful debug output for admins
    echo '<pre style="color:#b91c1c; background:#fff0f0; padding:10px; border-radius:6px;">' . htmlspecialchars($e->getMessage()) . '</pre>';
}

// Use the public footer to close the page (admin header opened main-content area)
require_once __DIR__ . '/includes/admin-footer.php';
?>