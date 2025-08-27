This folder contains small migration helpers for the project.

migrate_orders_items_to_json.php
- Purpose: Populate `orders.items` (TEXT/JSON) column by aggregating rows from `order_items`.
- Usage (from project root on Windows PowerShell):
  php .\scripts\migrate_orders_items_to_json.php

Notes and safe steps before running
1. Backup your database (mysqldump or use phpMyAdmin export).
2. Ensure `orders.items` column exists. If not, run one of the ALTERs below.

If your MySQL/MariaDB supports JSON type and you want JSON:
ALTER TABLE orders
  ADD COLUMN items JSON NULL;

If JSON not supported, use TEXT:
ALTER TABLE orders
  ADD COLUMN items TEXT NULL;

Why the previous ALTER/UPDATE errors happened
- The ALTER with DROP FOREIGN KEY <fk_name> failed because the placeholder `<fk_name_categories_parent>` is literal; you must replace it with the actual constraint name from INFORMATION_SCHEMA or omit the DROP if the FK is not present.
- The UPDATE using JSON_ARRAYAGG/JSON_OBJECT failed likely because your MariaDB version does not support those JSON helper functions or has a different syntax. The PHP migration script avoids relying on server-side JSON functions and will work on any version that supports standard SELECT/UPDATE statements and PHP PDO.

Recommended FK/INDEX changes (manual safe steps)
- Add indexes for speed:
  CREATE INDEX idx_products_category ON products(category_id);
  CREATE INDEX idx_orders_user ON orders(user_id);
  CREATE INDEX idx_order_items_order ON order_items(order_id);

- If you want to add/modify foreign keys, first list current FKs for the table:
  SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
  FROM information_schema.KEY_COLUMN_USAGE
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'categories';

  Replace <fk_name> with the constraint name shown, then drop it:
    ALTER TABLE categories DROP FOREIGN KEY `<fk_name>`;

  Then re-create with desired behavior, for example:
    ALTER TABLE categories
      ADD CONSTRAINT fk_categories_parent FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL ON UPDATE CASCADE;

If you want me to detect actual FK names and generate exact ALTER statements for you, tell me and I'll add a script that prints the current FKs and suggested ALTERs.
