# Contributing to ContestOrganizer-API

Thank you for your interest in contributing! This document will help you get started with setting up the development environment and contributing to the project.

## 1. Project Overview

ContestOrganizer-API is a lightweight PHP REST API for managing sports contests (tournaments, matches, teams). It provides endpoints for creating tournaments, managing teams, and tracking matches.

**Tech Stack:**
- PHP 8.x (with strict types)
- SQLite database
- JWT authentication (firebase/php-jwt)
- Custom MVC-like architecture (no heavy framework)

## 2. Prerequisites

Before you begin, ensure you have the following installed:

- **PHP 8.0 or higher** - Check with `php --version`
- **Composer** - PHP dependency manager - [Install guide](https://getcomposer.org/download/)
- **Node.js** (v14+) - For development tools - [Install guide](https://nodejs.org/)
- **SQLite extension** - Ensure PHP has SQLite enabled (`php -m | grep sqlite`)

## 3. Setup Instructions

### Clone the Repository

```bash
git clone https://github.com/MadMartigane/ContestOrganizer-API.git
cd ContestOrganizer-API
```

### Install PHP Dependencies

```bash
composer install
```

### Install Node.js Tools

```bash
npm install
```

### Set Up Environment Variables

Create a `.env` file or set environment variables. The API requires:

```bash
export JWT_SECRET="your-secret-key-here"
```

The JWT secret should be a strong, unique string (minimum 32 characters recommended).

### Create the Database

The database schema is located in `database/auth_schema.sql`. To create the database:

```bash
sqlite3 database/contest.db < database/auth_schema.sql
```

Or manually:

```bash
sqlite3 database/contest.db
sqlite> .read database/auth_schema.sql
sqlite> .quit
```

### Create the First Admin User

After database creation, you need to create an admin user. Use a PHP script or manually insert:

```php
<?php
// create_admin.php
require_once 'vendor/autoload.php';

$password = 'your-secure-password';
$hash = password_hash($password, PASSWORD_BCRYPT);

$db = new SQLite3('database/contest.db');
$db->exec("UPDATE users SET password_hash = '$hash' WHERE email = 'admin@contest.local'");

echo "Admin user created!\n";
```

Run it with:
```bash
php create_admin.php
```

## 4. Development Workflow

### Running the Development Server

You can use PHP's built-in server:

```bash
php -S localhost:8000 -t src/
```

Then access the API at `http://localhost:8000/`

### Watching for Changes

The project includes a watch script that monitors changes and runs syntax checks:

```bash
npm run watch
```

This will:
- Monitor `src/` for file changes
- Automatically copy files to the local web server
- Run PHP syntax validation using php-parser

### Testing Endpoints

Use curl or Postman to test API endpoints:

```bash
# Login to get JWT token
curl -X POST http://localhost:8000/index.php/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@contest.local","password":"your-password"}'

# Use the token for authenticated requests
curl -X GET http://localhost:8000/index.php/list/tournament \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### Database Migrations

Since this is a simple project, migrations are handled manually:

1. Edit `database/auth_schema.sql` with your changes
2. Backup existing database: `cp database/contest.db database/contest.db.backup`
3. Recreate database: `sqlite3 database/contest.db < database/auth_schema.sql`
4. Restore any data needed

## 5. Useful Commands

### NPM Scripts

```bash
npm run watch      # Development mode with file watching
npm run prod       # Production deployment to server
npm run pre-prod   # Pre-production deployment
npm run start      # Deploy and start watching
```

### PHP Commands

```bash
composer install   # Install PHP dependencies
php -l src/index.php  # Syntax check a file
php -S localhost:8000 -t src/  # Start local server
```

### SQLite Commands

```bash
sqlite3 database/contest.db           # Open database
sqlite3 database/contest.db ".tables" # List tables
sqlite3 database/contest.db ".schema" # Show schema
sqlite3 database/contest.db "SELECT * FROM users;"  # Run query
```

## 6. Coding Standards

### PHP Requirements

- **Type Hints**: Always use PHP 8 type hinting for function arguments and return types
  ```php
  public function sendOutput(string $code, array $httpHeaders = []): void
  ```

- **Indentation**: 4 spaces (no tabs)

- **Security Check**: Every PHP file must include the PROJECT_ROOT_PATH check at the top:
  ```php
  if (!defined('PROJECT_ROOT_PATH')) {
      require_once('../utils/403.php');
  }
  ```

### Naming Conventions

- **Classes**: PascalCase (e.g., `CommonController`, `UserModel`)
- **Methods/Properties**: camelCase (e.g., `getUriSegmentsData()`)
- **Variables**: camelCase
- **Filenames**: snake_case (e.g., `db.php`, `common.php`)

### Code Style

- Use early returns and guard clauses for error handling
- Keep functions small and focused
- Add PHPDoc comments for classes and methods

## 7. How to Contribute

### Fork and Branch Workflow

1. **Fork** the repository on GitHub
2. **Clone** your fork locally:
   ```bash
   git clone https://github.com/YOUR_USERNAME/ContestOrganizer-API.git
   ```

3. **Create a feature branch**:
   ```bash
   git checkout -b feature/your-feature-name
   # or
   git checkout -b fix/bug-description
   ```

4. **Make your changes** following the coding standards

5. **Test locally**:
   - Run syntax checks: `php -l src/**/*.php`
   - Test with curl/Postman

6. **Commit your changes**:
   ```bash
   git add .
   git commit -m "feat: add new feature"
   ```

7. **Push to your fork**:
   ```bash
   git push origin feature/your-feature-name
   ```

8. **Submit a Pull Request** on GitHub

### Commit Message Format

Use conventional commits:
- `feat:` for new features
- `fix:` for bug fixes
- `refactor:` for code refactoring
- `docs:` for documentation changes

## 8. Testing

### Manual Testing

Use curl or Postman to test endpoints:

```bash
# Test login
curl -X POST http://localhost:8000/index.php/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@contest.local","password":"your-password"}'

# List tournaments
curl -X GET "http://localhost:8000/index.php/list/tournament" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Get single tournament
curl -X GET "http://localhost:8000/index.php/get/tournament/1" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Syntax Validation

Always validate PHP syntax before committing:

```bash
# Check single file
php -l src/index.php

# Check all PHP files
find src -name "*.php" -exec php -l {} \;
```

## 9. Project Structure

```
ContestOrganizer-API/
├── src/
│   ├── controllers/      # Request handlers
│   │   ├── common.php    # CommonController (routing)
│   │   ├── procedures.php # Main entry point
│   │   ├── AuthController.php
│   │   ├── UserController.php
│   │   └── FileController.php
│   ├── models/           # Data layer
│   │   ├── db.php        # Database connection
│   │   ├── user.php
│   │   ├── tournament.php
│   │   ├── team.php
│   │   └── match.php
│   ├── services/         # Business logic
│   │   └── JwtService.php
│   ├── middleware/       # Request middleware
│   │   └── AuthMiddleware.php
│   ├── utils/            # Helper functions
│   │   ├── common.php
│   │   ├── 401.php
│   │   ├── 403.php
│   │   └── 404.php
│   ├── config/           # Configuration
│   │   ├── db.php
│   │   └── template.php
│   └── index.php         # Entry point
├── database/
│   └── auth_schema.sql   # Database schema
├── tools/
│   ├── watch.js          # File watcher
│   └── deploy.sh         # Deployment script
├── vendor/               # PHP dependencies
├── package.json          # Node.js config
├── composer.json         # PHP config
└── CONTRIBUTING.md       # This file
```

## Questions?

If you have questions or need help, feel free to open an issue on GitHub or reach out to the maintainers.

Thank you for contributing!
