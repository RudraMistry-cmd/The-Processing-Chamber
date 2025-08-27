Includes directory README

This folder contains shared PHP includes used across the application. The
project follows a small, procedural include pattern to keep the code easy to
reason about for beginners and maintainers.

Recommended include order for pages:
1. requires `config.php` (DB connection, SITE_URL constants, session)
2. requires `functions.php` (application helpers)
3. requires `auth.php` (optional; handles remember-me and auth helpers)
4. handle any GET/POST actions that may redirect (before rendering)
5. require `header.php` (renders sidebar/header)
6. render page content
7. require `footer.php`

Styling / commenting conventions used in this repo:
- File-level docblocks: short description and responsibilities.
- Small inline comments to explain non-obvious SQL or business logic.
- Keep procedural flow: the file should be readable top-to-bottom.

If you change this pattern, update this README so future contributors follow the
same conventions.
