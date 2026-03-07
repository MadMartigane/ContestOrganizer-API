# Contest Organizer CLI

A command-line administration tool for the Contest Organizer API. Provides complete database management and user administration capabilities.

## Requirements

- PHP 8.x
- SQLite3 extension
- Terminal access

## Installation

No installation required. This is a standalone PHP script.

1. Navigate to the CLI directory:
   ```bash
   cd cli
   ```

2. Make the script executable (optional, for convenience):
   ```bash
   chmod +x cli.php
   ```

3. Run commands using:
   ```bash
   php cli.php <command>
   ```

## Quick Start

### Initialize New Installation

```bash
php cli.php init
```

This creates:
- SQLite database at `src/database/contest.db`
- `.env` file with JWT_SECRET
- Default admin user (email: admin@contest.local, password: admin)

### Development Workflow

```bash
# Initialize database
php cli.php init

# Seed test users
php cli.php dev:seed

# List created users
php cli.php user:list
```

### Production Maintenance

```bash
# Initialize production database
php cli.php init

# Create admin user
php cli.php user:create admin@yourdomain.com admin

# Set up cron for token cleanup
# Add to crontab: 0 3 * * * /path/to/cli.php db:clean
```

## Commands Reference

| Command | Arguments | Description |
|---------|-----------|-------------|
| `init` | `[--force]` | Initialize database and generate configuration |
| `user:create` | `<email> <role>` | Create a new user interactively |
| `user:list` | - | List all users in table format |
| `user:delete` | `<id>` | Delete a user (with confirmation) |
| `user:password` | `<email>` | Change user password |
| `user:role` | `<email> <role>` | Change user role |
| `db:clean` | - | Clean expired tokens from blacklist |
| `db:stats` | - | Show database statistics |
| `dev:seed` | `[--force]` | Seed test users (development only) |

## Detailed Usage Examples

### init

Initialize the database and configuration.

```bash
php cli.php init
```

**Output:**
```
[INFO] Loading schema from: /path/to/database/auth_schema.sql
[SUCCESS] Database schema created successfully.
[SUCCESS] .env file created with JWT_SECRET.

Initialization complete!

[INFO] Default admin credentials:
  Email: admin@contest.local
  Password: admin (PLEASE CHANGE IMMEDIATELY)

[WARNING] IMPORTANT: The default password hash in the database is a placeholder.
[WARNING] You must update it with a proper bcrypt hash before production use.
```

**Options:**
- `--force` - Recreate database if it already exists

**Notes:**
- Fails if database exists (use `--force` to override)
- Generates a secure random JWT_SECRET

---

### user:create

Create a new user with email and role.

```bash
php cli.php user:create john@example.com organizer
```

**Interactive Prompts:**
```
Password: ********
Confirm password: ********
```

**Output:**
```
[SUCCESS] User created with ID 2
```

**Arguments:**
- `<email>` - User email address
- `<role>` - Must be: admin, organizer, or spectator

**Notes:**
- Password must be at least 8 characters
- Email must be unique

---

### user:list

Display all users in a formatted table.

```bash
php cli.php user:list
```

**Output:**
```
--------------------------------------------------------------------------------
| ID  | Email                          | Role        | Created At          |
--------------------------------------------------------------------------------
| 1   | admin@contest.local            | admin       | 2024-01-15 10:30:00 |
| 2   | john@example.com              | organizer   | 2024-01-15 11:45:00 |
--------------------------------------------------------------------------------
[SUCCESS] Total: 2 user(s)
```

---

### user:delete

Delete a user by ID (with confirmation).

```bash
php cli.php user:delete 2
```

**Interactive Prompt:**
```
Are you sure you want to delete user 'john@example.com'? (y/n): y
```

**Output:**
```
[SUCCESS] User deleted
```

**Notes:**
- Cannot delete user with ID 1 (default admin)
- Requires confirmation to prevent accidental deletion

---

### user:password

Change a user's password.

```bash
php cli.php user:password john@example.com
```

**Interactive Prompts:**
```
New password: ********
Confirm new password: ********
```

**Output:**
```
[SUCCESS] Password updated
```

**Notes:**
- Password must be at least 8 characters
- User must exist in the database

---

### user:role

Change a user's role.

```bash
php cli.php user:role john@example.com spectator
```

**Output:**
```
[SUCCESS] Role updated to 'spectator'
```

**Valid Roles:**
- `admin` - Full access
- `organizer` - Manage contests
- `spectator` - Read-only access

---

### db:clean

Remove expired tokens from the blacklist.

```bash
php cli.php db:clean
```

**Output:**
```
[SUCCESS] Cleaned 15 expired token(s).
```

Or if no tokens to clean:
```
[SUCCESS] No expired tokens to clean.
```

**Notes:**
- Safe to run via cron job
- Recommended: Run nightly or weekly

---

### db:stats

Display database statistics.

```bash
php cli.php db:stats
```

**Output:**
```
╔══════════════════════════════════════════╗
║         DATABASE STATISTICS               ║
╚══════════════════════════════════════════╝

USERS:
  [SUCCESS] Admin: 1
  [SUCCESS] Organizer: 3
  [SUCCESS] Spectator: 2
  [INFO] Total: 6

FILES:
  [SUCCESS] Total: 25

BLACKLISTED TOKENS:
  [SUCCESS] Total: 15

DATABASE:
  [SUCCESS] File: /path/to/src/database/contest.db
  [SUCCESS] Size: 256.00 KB
  [SUCCESS] Free space: 10.24 GB
```

---

### dev:seed

Create test users for development.

```bash
php cli.php dev:seed
```

**Output:**
```
[SUCCESS] Created: dev-admin@test.com (admin)
[SUCCESS] Created: dev-organizer@test.com (organizer)
[SUCCESS] Created: dev-spectator@test.com (spectator)
[SUCCESS] Created: dev-user@test.com (organizer)

╔══════════════════════════════════════════╗
║         CREATED TEST USERS              ║
╚══════════════════════════════════════════╝

| ID  | Email                          | Role        |
─────────────────────────────────────────────────
| 5   | dev-admin@test.com            | admin       |
| 6   | dev-organizer@test.com        | organizer   |
| 7   | dev-spectator@test.com       | spectator   |
| 8   | dev-user@test.com            | organizer   |
─────────────────────────────────────────────────

[SUCCESS] All test users created with password: password123
```

**Users Created:**
- dev-admin@test.com (admin)
- dev-organizer@test.com (organizer)
- dev-spectator@test.com (spectator)
- dev-user@test.com (organizer)

**Default Password:** `password123`

**Options:**
- `--force` - Override production safety check

**Notes:**
- Blocked in production by default (use `--force` to override)
- Skips users that already exist
- Use only in development environments

---

## Environment-Specific Workflows

### Development Workflow

```bash
# 1. Initialize fresh database
php cli.php init

# 2. Seed test data
php cli.php dev:seed

# 3. Verify users
php cli.php user:list

# 4. Check database status
php cli.php db:stats
```

### Production Workflow

```bash
# 1. Initialize production database
php cli.php init

# 2. Create admin user
php cli.php user:create admin@yourdomain.com admin
# Follow prompts to set secure password

# 3. Create organizer accounts as needed
php cli.php user:create john@yourdomain.com organizer
php cli.php user:create jane@yourdomain.com organizer

# 4. Verify database stats
php cli.php db:stats

# 5. Set up automated token cleanup (cron)
# Add to crontab: 0 3 * * * /path/to/cli.php db:clean
```

### Scheduled Maintenance

For automated token cleanup, add to crontab:

```bash
crontab -e
```

Add line:
```
0 3 * * * /usr/bin/php /var/www/html/contest/api/cli/cli.php db:clean >> /var/log/contest-clean.log 2>&1
```

This runs token cleanup daily at 3 AM.

---

## Security Notes

### CLI-Only Execution

This tool can only be run from the command line. Web access is blocked:

```bash
# This will fail in a browser
php cli.php user:list
# Error: This script can only be run from the command line.
```

### Password Requirements

- Minimum 8 characters
- Uses Argon2id hashing (PHP 8.x)
- Secure memory settings: 64MB memory cost, 4 iterations, 3 threads

### Production Safety

The `dev:seed` command is blocked in production by default:

```bash
php cli.php dev:seed
# [ERROR] ERROR: This command cannot be run in production mode.
# [WARNING] Use --force to override this safety check.
```

This prevents accidental seeding of test data on production systems.

---

## Troubleshooting

### "Database already exists"

```bash
# Error
[WARNING] Database already exists at: /path/to/contest.db
Use --force to recreate the database.

# Solution
php cli.php init --force
```

### "Permission denied"

```bash
# Error
bash: ./cli.php: Permission denied

# Solution
chmod +x cli.php
# Or run with PHP directly
php cli.php <command>
```

### "User already exists"

```bash
# Error
[ERROR] Email already exists

# Solution: Check existing users
php cli.php user:list

# Or use a different email
php cli.php user:create newemail@example.com organizer
```

### "User not found"

```bash
# Error
[ERROR] User not found

# Solution: Verify email spelling
php cli.php user:list
# Check the email column for exact match
```

### "Invalid role"

```bash
# Error
[ERROR] Invalid role. Must be one of: admin, organizer, spectator

# Solution: Use valid role
php cli.php user:create user@example.com organizer
```

### "Password must be at least 8 characters"

```bash
# Error
[ERROR] Password must be at least 8 characters

# Solution: Use longer password
# Minimum: 8 characters
# Recommended: 12+ characters with mixed case, numbers, symbols
```

### "Cannot delete the default admin user"

```bash
# Error
[ERROR] Cannot delete the default admin user (ID 1)

# Solution: This is a security feature
# The default admin account cannot be deleted
# You can demote it to spectator instead:
php cli.php user:role admin@contest.local spectator
```

---

## Exit Codes

| Code | Meaning |
|------|---------|
| 0 | Success |
| 1 | Error (invalid arguments, failed operation, etc.) |

Use exit codes in scripts:

```bash
php cli.php user:list
if [ $? -eq 0 ]; then
    echo "Command succeeded"
fi
```

---

## Additional Help

Display all available commands:

```bash
php cli.php help
```

Display help for specific command (check command source for details):
```bash
# Most commands display usage info with invalid arguments
php cli.php user:create
# Displays: Usage: php cli.php user:create <email> <role>
```
