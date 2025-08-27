<?php
// Migration: populate orders.items JSON column using order_items rows
// Usage from project root (PowerShell):
// php .\scripts\migrate_orders_items_to_json.php

require_once __DIR__ . '/../includes/config.php';

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Confirm orders table has 'items' column
    $colCheck = $pdo->prepare("SHOW COLUMNS FROM orders LIKE 'items'");
    $colCheck->execute();
    $col = $colCheck->fetch(PDO::FETCH_ASSOC);
    if (!$col) {
        echo "Column 'items' not found in orders table. Please add it first (ALTER TABLE orders ADD COLUMN items TEXT NULL)\n";
        exit(1);
    }

    // Select orders that currently have no items stored
    $stmt = $pdo->query("SELECT id FROM orders WHERE items IS NULL OR items = ''");
    $orders = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($orders)) {
        echo "No orders require migration.\n";
        exit(0);
    }

    echo "Found " . count($orders) . " orders to migrate.\n";

    $orderCount = 0;
    foreach ($orders as $orderId) {
        // Fetch order_items + product name for this order
        $sql = "SELECT oi.product_id, oi.quantity, oi.price, COALESCE(p.name, '') AS product_name, COALESCE(p.image, '') AS image
                FROM order_items oi
                LEFT JOIN products p ON p.id = oi.product_id
                WHERE oi.order_id = ?";
        $s = $pdo->prepare($sql);
        $s->execute([$orderId]);
        $items = $s->fetchAll(PDO::FETCH_ASSOC);

        if (empty($items)) {
            // nothing to store
            continue;
        }

        $snapshot = [];
        foreach ($items as $it) {
            $qty = (int)$it['quantity'];
            $price = (float)$it['price'];
            $snapshot[] = [
                'id' => (int)$it['product_id'],
                'name' => $it['product_name'],
                'quantity' => $qty,
                'price' => $price,
                'subtotal' => $qty * $price,
                'image' => $it['image']
            ];
        }

        $json = json_encode($snapshot, JSON_UNESCAPED_UNICODE);

        $u = $pdo->prepare("UPDATE orders SET items = ? WHERE id = ?");
        $u->execute([$json, $orderId]);

        $orderCount++;
        if ($orderCount % 50 === 0) {
            echo "Migrated {$orderCount} orders...\n";
        }
    }

    echo "Migration complete. {$orderCount} orders updated.\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(2);
}
