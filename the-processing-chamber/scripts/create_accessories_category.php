<?php
// Safe migration to add `Accessories` as a parent category and attach child categories.
// Usage (PowerShell):
// php .\scripts\create_accessories_category.php

require_once __DIR__ . '/../includes/config.php';

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1) Ensure `icon` column exists on categories
    $colCheck = $pdo->prepare("SHOW COLUMNS FROM categories LIKE 'icon'");
    $colCheck->execute();
    $col = $colCheck->fetch(PDO::FETCH_ASSOC);
    if (!$col) {
        echo "Adding 'icon' column to categories...\n";
        $pdo->exec("ALTER TABLE categories ADD COLUMN icon VARCHAR(50) NULL AFTER description");
        echo "Added column 'icon'.\n";
    } else {
        echo "Column 'icon' already exists.\n";
    }

    // 2) Ensure an 'Accessories' parent category exists
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ? LIMIT 1");
    $stmt->execute(['Accessories']);
    $accessories = $stmt->fetch(PDO::FETCH_COLUMN);

    if ($accessories) {
        echo "Accessories category exists (id={$accessories}).\n";
    } else {
        echo "Creating Accessories category...\n";
        $ins = $pdo->prepare("INSERT INTO categories (name, description, icon, parent_id) VALUES (?, ?, ?, NULL)");
        $ins->execute(['Accessories', 'Top-level accessories category', NULL]);
        $accessories = $pdo->lastInsertId();
        echo "Created Accessories (id={$accessories}).\n";
    }

    // 3) Child categories to ensure under Accessories
    $children = ['Cooling', 'Graphics Cards', 'Memory'];
    $created = [];
    $updated = [];

    foreach ($children as $childName) {
        // find existing category by exact name
        $s = $pdo->prepare("SELECT id, parent_id FROM categories WHERE name = ? LIMIT 1");
        $s->execute([$childName]);
        $row = $s->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $catId = $row['id'];
            $parentId = $row['parent_id'];
            if ($parentId != $accessories) {
                $u = $pdo->prepare("UPDATE categories SET parent_id = ? WHERE id = ?");
                $u->execute([$accessories, $catId]);
                $updated[] = [
                    'id' => $catId,
                    'name' => $childName
                ];
                echo "Updated '{$childName}' (id={$catId}) parent_id => Accessories({$accessories}).\n";
            } else {
                echo "'{$childName}' already a child of Accessories (id={$catId}).\n";
            }
        } else {
            // create child under accessories
            $insC = $pdo->prepare("INSERT INTO categories (name, description, icon, parent_id) VALUES (?, ?, ?, ?)");
            $insC->execute([$childName, "$childName category", NULL, $accessories]);
            $newId = $pdo->lastInsertId();
            $created[] = [
                'id' => $newId,
                'name' => $childName
            ];
            echo "Created child category '{$childName}' (id={$newId}) under Accessories.\n";
        }
    }

    echo "\nSummary:\n";
    echo "Accessories id: {$accessories}\n";
    if ($created) {
        echo "Created child categories:\n";
        foreach ($created as $c) echo " - {$c['name']} (id={$c['id']})\n";
    }
    if ($updated) {
        echo "Updated existing categories to set parent_id:\n";
        foreach ($updated as $u) echo " - {$u['name']} (id={$u['id']})\n";
    }

    echo "Done.\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
