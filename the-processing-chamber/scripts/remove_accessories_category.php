<?php
// Safe removal of a top-level 'Accessories' category created earlier.
// Behavior:
// - Finds a category named 'Accessories' (case-insensitive)
// - Reparents any children to NULL (make them top-level)
// - Deletes the Accessories category
// Usage (PowerShell):
// php .\scripts\remove_accessories_category.php

require_once __DIR__ . '/../includes/config.php';

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Find category named Accessories
    $s = $pdo->prepare("SELECT id FROM categories WHERE LOWER(name) = LOWER(?) LIMIT 1");
    $s->execute(['Accessories']);
    $row = $s->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo "No 'Accessories' category found. Nothing to remove.\n";
        exit(0);
    }

    $accessoriesId = $row['id'];
    echo "Found Accessories (id={$accessoriesId}). Reparenting children to top-level...\n";

    // Reparent children to NULL
    $u = $pdo->prepare("UPDATE categories SET parent_id = NULL WHERE parent_id = ?");
    $u->execute([$accessoriesId]);
    $count = $u->rowCount();
    echo "Reparented {$count} child categories to top-level.\n";

    // Delete the Accessories row
    $d = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $d->execute([$accessoriesId]);
    echo "Deleted Accessories category.\n";

    echo "Done. Please refresh the site to see the updated category tree.\n";
} catch (Exception $e) {
    echo "Failed: " . $e->getMessage() . "\n";
    exit(1);
}
