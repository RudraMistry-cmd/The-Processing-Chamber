<?php
// Apply sensible default Font Awesome icons to common categories when icon is empty.
// Usage (PowerShell):
// php .\scripts\set_default_category_icons.php

require_once __DIR__ . '/../includes/config.php';

$mapping = [
    'Processors' => 'fas fa-microchip',
    'CPU' => 'fas fa-microchip',
    'Processors (CPU)' => 'fas fa-microchip',
    'Graphics Cards' => 'fas fa-video',
    'GPU' => 'fas fa-video',
    'Memory' => 'fas fa-memory',
    'RAM' => 'fas fa-memory',
    'Storage' => 'fas fa-hdd',
    'SSD' => 'fas fa-solid fa-memory',
    'Motherboards' => 'fas fa-motherboard',
    'Power Supplies' => 'fas fa-plug',
    'Cooling' => 'fas fa-wind',
    'Accessories' => 'fas fa-headphones',
    'Peripherals' => 'fas fa-keyboard',
    'Mousepads' => 'fas fa-square',
    'Headphones' => 'fas fa-headphones',
    'Monitors' => 'fas fa-desktop',
    'Networking' => 'fas fa-network-wired',
    'Controllers' => 'fas fa-gamepad'
];

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $updated = 0;
    foreach ($mapping as $name => $icon) {
        // update only where name matches (case-insensitive) and icon is null/empty
        $sql = "UPDATE categories SET icon = ? WHERE LOWER(name) = LOWER(?) AND (icon IS NULL OR icon = '')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$icon, $name]);
        $count = $stmt->rowCount();
        if ($count > 0) {
            echo "Set icon '{$icon}' for {$count} category(ies) matching '{$name}'\n";
            $updated += $count;
        }
    }

    echo "Done. Total updated: {$updated}\n";
} catch (Exception $e) {
    echo "Failed: " . $e->getMessage() . "\n";
    exit(1);
}
