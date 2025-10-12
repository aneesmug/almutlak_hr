# Al-Mutlak WMS Copilot Instructions

This document provides guidance for AI coding agents to effectively contribute to the Al-Mutlak Warehouse Management System (WMS) codebase.

## Architecture and Conventions

The application is a legacy PHP project without a modern MVC framework. The structure is largely flat, with individual `.php` files in the root directory corresponding to specific pages or actions.

- **File Naming:** Files are named procedurally based on their function, e.g., `add_customer.php`, `edit_employee.php`, `all_requests.php`.
- **UI and Business Logic:** HTML markup and PHP business logic are often mixed within the same file.
- **Shared Code:** The `includes/` directory contains shared components.
  - `includes/header.php`: Contains the page header, navigation, and session start logic. It's included at the top of most user-facing pages.
  - `includes/footer.php`: Contains the page footer and JavaScript includes.
  - `includes/functions.php`: Contains shared utility functions.
  - `includes/conn.php`: Contains the database connection logic.

## Database Interaction

The project uses a mix of `mysqli` and `PDO` for database operations. This is a critical point to be aware of when making changes.

- **`mysqli`:** Older parts of the application use the procedural `mysqli_*` functions. The connection object is typically stored in a global variable `$conDB`.
  ```php
  // Example from open_request.php
  $queryempdocu = mysqli_query($conDB, "SELECT * FROM `smt_attachment` WHERE `inv_no`='" . $_GET['id'] . "' ");
  while ($recempdoc = mysqli_fetch_assoc($queryempdocu)) {
      // ...
  }
  ```
- **`PDO`:** Newer or refactored parts of the application use `PDO` with prepared statements. This is the preferred method for all new database queries to prevent SQL injection.
  ```php
  // Example from add_emp_slry.php
  try {
      $pdo->beginTransaction();
      $stmt = $pdo->prepare("UPDATE emp_salary SET status = 0 WHERE id = :id");
      $stmt->execute(['id' => $existing['id']]);
      $pdo->commit();
  ## Al‑Mutlak HR — Copilot instructions (concise)

  This repo is a legacy PHP web app (flat file architecture) for HR/operations. Pages are procedural `.php` scripts that include shared helpers from `includes/`. The goal of these notes is to make an AI code agent productive quickly by pointing out the repo's patterns, pitfalls, and places to look.

  1) Big picture & dataflow
  - UI pages (root .php files) include bootstrapping files (`includes/init.php` or `includes/db.php` / `includes/session_check.php`) which expose two common DB objects:
    - $conDB — mysqli connection (many legacy files use procedural mysqli_* and occasional PDO-style ->prepare() on $conDB)
    - $pdo — PDO instance (used by newer code)
  - Typical flow: browser → page JS (assets/js/*.js) → AJAX endpoint (includes/ajaxFile/*.php) → DB ($conDB or $pdo) → JSON/HTML response. Example: `emp_end_of_service.php` uses `./includes/ajaxFile/ajax_eos_calculator.php` for server-side calc; `assets/js/jquery.app.js` calls many `includes/ajaxFile/` endpoints.

  2) When to use which DB API
  - Follow the DB style already used in the file you edit. If adding a new feature, prefer PDO with prepared statements and transactions (use $pdo). Example pattern: beginTransaction/commit/rollBack in `add_emp_slry.php`.
  - If you must touch legacy mysqli queries that use `$conDB`, sanitize inputs with `mysqli_real_escape_string()` or convert the block to PDO carefully and keep callers consistent.

  3) Common conventions and examples (copy/paste friendly)
  - Bootstrapping: prefer require_once __DIR__ . '/includes/init.php' (many ajax files use relative paths like `__DIR__ . '/../../includes/db.php'`).
  - Authentication / session: pages include `includes/session_check.php` or `includes/header.php`. User info is often in variables like `$empid`, `$username`, `$userwel` and in `$_SESSION`.
  - AJAX endpoints live under `includes/ajaxFile/` and expect `includes/db.php` or `init.php` to be loaded. Example endpoints: `ajaxSmartRequest.php`, `ajaxEmployee.php`, `ajax_eos_calculator.php`.
  - Frontend: jQuery-first stack. Look at `assets/js/jquery.app.js` for client behaviour and routes. DataTables server-side handlers are in `includes/ajaxFile/` (e.g., `smartRequestAjaxTbl.php`).

  4) Project‑specific patterns & gotchas
  - Mixed DB styles: both `$conDB` (mysqli) and `$pdo` (PDO) coexist. New code: PDO. Small fixes: keep file style.
  - Lots of inline HTML + PHP in the same file. Form POST handlers are at top-of-file before HTML is emitted.
  - File upload directories and naming are application-specific (e.g., `assets/smt_payment_invoices/`), and many code paths call move_uploaded_file().
  - cURL calls: some pages (e.g., `emp_end_of_service.php`) call external APIs and disable SSL verification for local/XAMPP environments. Be conservative—do not leak secrets.
  - Translation helper: `__()` is used widely. Global translations are available via window.lang in templates.

  5) Developer workflow & quick checks
  - There is no build tool; run the app with PHP (XAMPP) and access pages via the browser. Composer is present (vendor/ and composer.json)—run `composer install` if you update PHP dependencies.
  - To debug: enable the site setting `developer_mode` (see `includes/db.php` which reads settings and sets timezone / error modes). Inspect web server (Apache/XAMPP) and PHP error logs.

  6) Files to read first (high‑leverage)
  - `includes/db.php` — creates `$conDB` and `$pdo`, timezone, charset, developer_mode.
  - `includes/init.php` — bootstrapping used by many ajax handlers.
  - `includes/session_check.php` / `includes/header.php` — session/auth model and user globals.
  - `includes/ajaxFile/*` — how AJAX handlers accept input and return JSON.
  - `assets/js/jquery.app.js` — client-side AJAX routes and UX patterns.
  - Example pages: `emp_end_of_service.php`, `open_request.php`, `add_emp_slry.php`, `view_employee.php` — representative of common patterns.

  7) Short rules for successful edits
  - If you change DB access in a file, keep the same DB object style unless you update all call sites. When adding new endpoints, use PDO prepared statements and transactions.
  - Sanitize inputs: use `mysqli_real_escape_string($conDB, $v)` in mysqli code; use bound params in PDO.
  - Keep include paths correct: many ajax files use relative includes (e.g., `__DIR__ . '/../../includes/db.php'`). Prefer absolute `__DIR__` expressions.

  8) Security & testing notes
  - Avoid introducing raw string interpolation into SQL. The codebase contains many examples of direct interpolation—do not copy that pattern for new code.
  - There are no automated tests in the repo. Use a local XAMPP instance and a disposable DB snapshot for manual verification.

  If anything here is unclear or you want this condensed into a shorter checklist for non-expert agents, tell me which areas to trim or expand and I'll iterate.
