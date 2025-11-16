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
  } catch (PDOException $e) {
      $pdo->rollBack();
      // ... error handling
  }
  ```
- **Guideline:** When modifying existing code, follow the style already in use in that file. For new features, **always use PDO with prepared statements**.

## Frontend and AJAX

- **jQuery:** The frontend heavily relies on jQuery for DOM manipulation, event handling, and AJAX requests. The main JavaScript file is `assets/js/jquery.app.js`.
- **AJAX Handlers:** AJAX requests are typically handled by dedicated PHP files in the `includes/ajaxFile/` directory (e.g., `ajaxSmartRequest.php`). These files process the request and often return JSON.
- **DataTables:** The plugin `DataTables` is used for displaying and managing tables (sorting, searching, pagination). Server-side processing for these tables is implemented in files like `includes/ajaxFile/smartRequestAjaxTbl.php`.

## Common Workflows

- **Adding a new page:** Create a new `.php` file. Include `includes/header.php` at the beginning and `includes/footer.php` at the end.
- **Handling Form Submissions:** Forms are typically submitted to the same page (`$_SERVER['PHP_SELF']`). The logic for processing the `POST` request is at the top of the file, before any HTML is rendered.
- **User Authentication:** Session management is handled in `includes/header.php`. Check for user authentication and authorization at the beginning of pages that require it. User details are stored in the `$_SESSION` superglobal.

## Key Files and Directories

- `system/`: The root directory for the application.
- `system/includes/`: Contains shared PHP code (DB connection, functions, header/footer).
- `system/includes/ajaxFile/`: Contains backend handlers for AJAX requests.
- `system/assets/`: Contains static assets like CSS, JavaScript, and images.
- `system/db/`: Contains the database connection file `conn.php`.
