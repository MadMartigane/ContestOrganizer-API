# Agent Guidelines: ContestOrganizer-API

This document provides technical context and coding standards for AI agents working on this repository.

## 1. Project Overview
A PHP-based API for managing sports contests (basketball, soccer, etc.). It uses a custom MVC-like architecture without a heavy framework, focusing on simplicity and direct file-to-server deployment.

## 2. Technical Stack
- **Backend**: PHP 8.x (utilizing strict types and modern features).
- **Tooling**: Node.js for development utilities (watching and linting).
- **Dependencies**: Managed via `composer` (PHP) and `npm` (tools).

## 3. Commands & Workflow

### Development & Build
- **Watch & Lint**: `npm run watch`
  - Monitors `src/` for changes.
  - Automatically copies files to the local web server.
  - Runs a syntax check using `php-parser`.
- **Production Sync**: `npm run prod`
  - Backs up data, clears old controllers/models, and copies fresh files to `/var/www/marius.click/html/contest/api`.
- **Pre-prod Sync**: `npm run pre-prod`
  - Similar to prod but targets the `contest-preprod` directory.

### Linting & Validation
- **Syntax Check**: `php -l <filename>`
  - Use this to verify individual PHP files before committing.
- **Static Analysis**: The project uses `nikic/php-parser` via the watch script.

### Testing
- **Current State**: No automated test suite (like PHPUnit) is currently configured.
- **Manual Testing**: Use `curl` or Postman against the local endpoint.
- **Single File Test**: You can create a temporary PHP script that includes the target file and executes its logic:
  ```bash
  php -r 'require "src/index.php"; /* call your function here */'
  ```
- **Example Curl Test**:
  ```bash
  curl -X GET "http://localhost/contest/api/index.php/get/tournament/1"
  ```

## 4. Code Style & Conventions

### PHP Guidelines
- **Indentation**: 4 spaces.
- **Naming Conventions**:
  - **Classes**: `PascalCase` (e.g., `CommonController`, `Procedures`).
  - **Methods/Properties**: `camelCase` (e.g., `getUriSegmentsData()`, `$debugMessages`).
  - **Variables**: `camelCase`.
  - **Filenames**: `snake_case` (e.g., `db.php`, `common.php`, `tournament.php`).
- **Types**: Always use PHP 8 type hinting for function arguments and return values.
  ```php
  public function sendOutput(string $code, array $httpHeaders = []): void
  ```
- **Tags**: Always use long PHP tags `<?php`.

### Imports & File Structure
- **Root Path**: Every file must check for `PROJECT_ROOT_PATH` to prevent direct access:
  ```php
  if (!defined('PROJECT_ROOT_PATH')) {
      require_once('../utils/403.php');
  }
  ```
- **Includes**: Use `require_once` with `PROJECT_ROOT_PATH`.

## 5. Architecture Patterns

### Controllers
- Located in `src/controllers/`.
- **CommonController**: A Singleton handling URI parsing and output buffering. Access via `CommonController::getInstance()`.
- **Procedures**: The main entry point for business logic. It maps URI segments to specific actions.

### Models
- Located in `src/models/`.
- Handle data persistence and business logic.
- **DB Model**: `db.php` manages database interactions, often relying on a `db_config.json` file.

### Routing
- Handled in `src/index.php` via `CommonController::getUriSegmentsData()`.
- Routes follow the pattern: `index.php/{action}/{subject}/{option}`.
- Example: `index.php/list/tournament` -> action=`list`, subject=`tournament`.

## 6. Error Handling & Debugging

### Logging
- Use the global `message()` function for logging/debugging:
  ```php
  message(string $log, mixed $thing = '', int $severity = 0);
  ```
- Severity levels are mapped to standard PHP error constants (E_ERROR, E_WARNING, etc.) defined in `index.php`.

### Exceptions
- A global exception handler is defined in `index.php` which returns a JSON error response.
- Always wrap risky operations in `try-catch` blocks and throw `Throwable` if necessary.

## 7. Utilities
- **Common Utils**: `src/utils/common.php` contains helper functions like `sanitizeArgument()`.
- **HTTP Errors**: `src/utils/40x.php` files are used for standard HTTP error responses.

## 8. Security
- **Sanitization**: Use `utils\common\sanitizeArgument($value)` for any URI segments or user input.
- **Access Control**: Ensure the `PROJECT_ROOT_PATH` check is present at the top of every included file.
- **Headers**: `Access-Control-Allow-Origin: *` is currently enabled in `CommonController`.

## 9. Database Configuration
- The API expects a configuration file (usually `db_config.json`) for database connection details.
- Ensure this file is not committed if it contains sensitive credentials (check `.gitignore`).

## 10. Deployment Notes
- The project is designed to be deployed directly to a Linux/Nginx environment.
- Deployment scripts in `package.json` handle file permissions (`chown`, `chmod`) and SELinux contexts (`chcon`).
- **Warning**: Do not modify deployment paths in `package.json` without explicit instruction.
- **Syncing**: Use `npm run prod` to synchronize the `src` directory with the live server path.

## 11. Best Practices for Agents
- **Strict Typing**: Enforce PHP 8 types in all new code.
- **Singleton Pattern**: Respect the Singleton pattern for `CommonController`.
- **No Direct Access**: Always include the `PROJECT_ROOT_PATH` check.
- **Documentation**: Use PHPDoc for methods and classes.
