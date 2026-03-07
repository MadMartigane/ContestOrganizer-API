#!/usr/bin/env php
<?php

/**
 * Contest Organizer CLI Admin Tool
 * Complete unified CLI for administering the Contest API
 */

if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

// ============================================
// CONSTANTS AND CONFIGURATION
// ============================================

define('PROJECT_ROOT_PATH', __DIR__ . '/../');
define('DATABASE_PATH', PROJECT_ROOT_PATH . 'src/database/contest.db');
define('SCHEMA_PATH', PROJECT_ROOT_PATH . 'database/auth_schema.sql');
define('ENV_PATH', PROJECT_ROOT_PATH . '.env');

// ============================================
// COLOR UTILITIES
// ============================================

function green(string $text): string
{
    return "\033[32m{$text}\033[0m";
}

function red(string $text): string
{
    return "\033[31m{$text}\033[0m";
}

function yellow(string $text): string
{
    return "\033[33m{$text}\033[0m";
}

function blue(string $text): string
{
    return "\033[34m{$text}\033[0m";
}

function info(string $text): string
{
    return blue("[INFO] {$text}");
}

function success(string $text): string
{
    return green("[SUCCESS] {$text}");
}

function error(string $text): string
{
    return red("[ERROR] {$text}");
}

function warning(string $text): string
{
    return yellow("[WARNING] {$text}");
}

// ============================================
// PROMPT FUNCTION
// ============================================

function prompt(string $message, bool $hidden = false): string
{
    echo $message;
    
    if ($hidden && posix_isatty(STDIN)) {
        shell_exec('stty -echo');
    }
    
    $input = trim(fgets(STDIN));
    
    if ($hidden && posix_isatty(STDIN)) {
        shell_exec('stty echo');
        echo PHP_EOL;
    }
    
    return $input;
}

// ============================================
// DATABASE CLASS
// ============================================

class Database
{
    private static ?PDO $pdo = null;

    public static function getConnection(): PDO
    {
        if (self::$pdo === null) {
            $dbDir = dirname(DATABASE_PATH);
            if (!is_dir($dbDir)) {
                if (!mkdir($dbDir, 0755, true)) {
                    throw new RuntimeException("Failed to create database directory: {$dbDir}");
                }
            }

            self::$pdo = new PDO('sqlite:' . DATABASE_PATH, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            self::$pdo->exec('PRAGMA foreign_keys = ON');
        }

        return self::$pdo;
    }

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = self::query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::query($sql, $params);
        return $stmt->rowCount();
    }

    public static function lastInsertId(): int
    {
        return (int) self::getConnection()->lastInsertId();
    }
}

// ============================================
// PASSWORD HASH FUNCTION
// ============================================

function hashPassword(string $password): string
{
    return password_hash($password, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536,
        'time_cost' => 4,
        'threads' => 3
    ]);
}

// ============================================
// VALIDATION HELPERS
// ============================================

function isValidEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function isValidRole(string $role): bool
{
    return in_array($role, ['admin', 'organizer', 'spectator'], true);
}

// ============================================
// COMMAND: INIT
// ============================================

function cmd_init(array $args): int
{
    $force = in_array('--force', $args);

    // Check if database already exists
    if (file_exists(DATABASE_PATH)) {
        if (!$force) {
            echo warning("Database already exists at: " . DATABASE_PATH) . "\n";
            echo "Use --force to recreate the database.\n";
            return 1;
        }
        echo info("Removing existing database (--force specified)...\n");
        unlink(DATABASE_PATH);
    }

    // Create database directory if needed
    $dbDir = dirname(DATABASE_PATH);
    if (!is_dir($dbDir)) {
        echo info("Creating database directory: {$dbDir}\n");
        if (!mkdir($dbDir, 0755, true)) {
            echo error("Failed to create database directory: {$dbDir}\n");
            return 1;
        }
    }

    // Check if schema file exists
    if (!file_exists(SCHEMA_PATH)) {
        echo error("Schema file not found: " . SCHEMA_PATH . "\n");
        return 1;
    }

    // Read and execute schema
    try {
        echo info("Loading schema from: " . SCHEMA_PATH . "\n");
        $schema = file_get_contents(SCHEMA_PATH);
        
        Database::getConnection()->exec($schema);
        echo success("Database schema created successfully.\n");
    } catch (Throwable $e) {
        echo error("Failed to create database schema: " . $e->getMessage() . "\n");
        return 1;
    }

    // Generate JWT_SECRET
    $jwtSecret = bin2hex(random_bytes(64));

    // Create .env file
    try {
        $envContent = "JWT_SECRET={$jwtSecret}\n";
        file_put_contents(ENV_PATH, $envContent);
        echo success(".env file created with JWT_SECRET.\n");
    } catch (Throwable $e) {
        echo error("Failed to create .env file: " . $e->getMessage() . "\n");
        return 1;
    }

    echo "\n" . success("Initialization complete!") . "\n\n";
    echo info("Default admin credentials:\n");
    echo "  Email: admin@contest.local\n";
    echo "  Password: admin (PLEASE CHANGE IMMEDIATELY)\n\n";
    echo yellow("IMPORTANT: The default password hash in the database is a placeholder.") . "\n";
    echo yellow("You must update it with a proper bcrypt hash before production use.\n");

    return 0;
}

// ============================================
// COMMAND: USER:CREATE
// ============================================

function cmd_user_create(array $args): int
{
    if (count($args) < 2) {
        echo red('Usage: php cli.php user:create <email> <role>') . PHP_EOL;
        echo 'Roles: admin, organizer, spectator' . PHP_EOL;
        return 1;
    }

    $email = trim($args[0]);
    $role = trim($args[1]);

    if (!isValidEmail($email)) {
        echo error('Invalid email format') . PHP_EOL;
        return 1;
    }

    if (!isValidRole($role)) {
        echo error('Invalid role. Must be one of: admin, organizer, spectator') . PHP_EOL;
        return 1;
    }

    // Check if email already exists
    $existing = Database::fetchOne('SELECT id FROM users WHERE email = ?', [$email]);
    if ($existing !== null) {
        echo error('Email already exists') . PHP_EOL;
        return 1;
    }

    $password = prompt('Password: ', true);
    
    if (strlen($password) < 8) {
        echo error('Password must be at least 8 characters') . PHP_EOL;
        return 1;
    }

    $confirmPassword = prompt('Confirm password: ', true);
    
    if ($password !== $confirmPassword) {
        echo error('Passwords do not match') . PHP_EOL;
        return 1;
    }

    $passwordHash = hashPassword($password);

    try {
        Database::execute(
            'INSERT INTO users (email, password_hash, role) VALUES (?, ?, ?)',
            [$email, $passwordHash, $role]
        );
        
        $userId = Database::lastInsertId();
        echo success("User created with ID {$userId}") . PHP_EOL;
        return 0;
    } catch (Throwable $e) {
        echo error('Failed to create user: ' . $e->getMessage()) . PHP_EOL;
        return 1;
    }
}

// ============================================
// COMMAND: USER:LIST
// ============================================

function cmd_user_list(array $args): int
{
    try {
        $users = Database::query('SELECT id, email, role, created_at FROM users ORDER BY id')->fetchAll();

        if (empty($users)) {
            echo warning('No users found') . PHP_EOL;
            return 0;
        }

        $header = sprintf('| %-3s | %-30s | %-12s | %-20s |', 'ID', 'Email', 'Role', 'Created At');
        $separator = str_repeat('-', strlen($header));

        echo $separator . PHP_EOL;
        echo $header . PHP_EOL;
        echo $separator . PHP_EOL;

        foreach ($users as $user) {
            $createdAt = date('Y-m-d H:i:s', (int) $user['created_at']);
            printf('| %-3d | %-30s | %-12s | %-20s |' . PHP_EOL, 
                (int) $user['id'], 
                $user['email'], 
                $user['role'], 
                $createdAt
            );
        }

        echo $separator . PHP_EOL;
        echo success('Total: ' . count($users) . ' user(s)') . PHP_EOL;
        
        return 0;
    } catch (Throwable $e) {
        echo error('Failed to list users: ' . $e->getMessage()) . PHP_EOL;
        return 1;
    }
}

// ============================================
// COMMAND: USER:DELETE
// ============================================

function cmd_user_delete(array $args): int
{
    if (count($args) < 1) {
        echo red('Usage: php cli.php user:delete <id>') . PHP_EOL;
        return 1;
    }

    $userId = (int) trim($args[0]);

    if ($userId === 1) {
        echo error('Cannot delete the default admin user (ID 1)') . PHP_EOL;
        return 1;
    }

    try {
        $user = Database::fetchOne('SELECT id, email, role, created_at FROM users WHERE id = ?', [$userId]);

        if ($user === null) {
            echo error('User not found') . PHP_EOL;
            return 1;
        }

        echo "Are you sure you want to delete user '{$user['email']}'? (y/n): ";
        $confirm = strtolower(trim(fgets(STDIN)));

        if ($confirm !== 'y' && $confirm !== 'yes') {
            echo warning('Deletion cancelled') . PHP_EOL;
            return 0;
        }

        Database::execute('DELETE FROM users WHERE id = ?', [$userId]);
        
        echo success('User deleted') . PHP_EOL;
        return 0;
    } catch (Throwable $e) {
        echo error('Failed to delete user: ' . $e->getMessage()) . PHP_EOL;
        return 1;
    }
}

// ============================================
// COMMAND: USER:PASSWORD
// ============================================

function cmd_user_password(array $args): int
{
    if (count($args) < 1) {
        echo red('Usage: php cli.php user:password <email>') . PHP_EOL;
        return 1;
    }

    $email = trim($args[0]);

    if (!isValidEmail($email)) {
        echo error('Invalid email format') . PHP_EOL;
        return 1;
    }

    try {
        $user = Database::fetchOne('SELECT id, email, role, created_at FROM users WHERE email = ?', [$email]);

        if ($user === null) {
            echo error('User not found') . PHP_EOL;
            return 1;
        }

        $newPassword = prompt('New password: ', true);
        
        if (strlen($newPassword) < 8) {
            echo error('Password must be at least 8 characters') . PHP_EOL;
            return 1;
        }

        $confirmPassword = prompt('Confirm new password: ', true);
        
        if ($newPassword !== $confirmPassword) {
            echo error('Passwords do not match') . PHP_EOL;
            return 1;
        }

        $passwordHash = hashPassword($newPassword);

        Database::execute('UPDATE users SET password_hash = ? WHERE id = ?', [$passwordHash, $user['id']]);
        
        echo success('Password updated') . PHP_EOL;
        return 0;
    } catch (Throwable $e) {
        echo error('Failed to update password: ' . $e->getMessage()) . PHP_EOL;
        return 1;
    }
}

// ============================================
// COMMAND: USER:ROLE
// ============================================

function cmd_user_role(array $args): int
{
    if (count($args) < 2) {
        echo red('Usage: php cli.php user:role <email> <new-role>') . PHP_EOL;
        echo 'Roles: admin, organizer, spectator' . PHP_EOL;
        return 1;
    }

    $email = trim($args[0]);
    $newRole = trim($args[1]);

    if (!isValidEmail($email)) {
        echo error('Invalid email format') . PHP_EOL;
        return 1;
    }

    if (!isValidRole($newRole)) {
        echo error('Invalid role. Must be one of: admin, organizer, spectator') . PHP_EOL;
        return 1;
    }

    try {
        $user = Database::fetchOne('SELECT id, email, role, created_at FROM users WHERE email = ?', [$email]);

        if ($user === null) {
            echo error('User not found') . PHP_EOL;
            return 1;
        }

        Database::execute('UPDATE users SET role = ? WHERE id = ?', [$newRole, $user['id']]);
        
        echo success("Role updated to '{$newRole}'") . PHP_EOL;
        return 0;
    } catch (Throwable $e) {
        echo error('Failed to update role: ' . $e->getMessage()) . PHP_EOL;
        return 1;
    }
}

// ============================================
// COMMAND: DB:CLEAN
// ============================================

function cmd_db_clean(array $args): int
{
    try {
        $currentTimestamp = time();
        
        // Get count of expired tokens before deletion
        $expiredCount = Database::fetchOne(
            'SELECT COUNT(*) as count FROM token_blacklist WHERE expires_at < ?',
            [$currentTimestamp]
        );
        
        $count = (int) ($expiredCount['count'] ?? 0);
        
        if ($count === 0) {
            echo success('No expired tokens to clean.') . PHP_EOL;
            return 0;
        }
        
        // Delete expired tokens
        Database::execute(
            'DELETE FROM token_blacklist WHERE expires_at < ?',
            [$currentTimestamp]
        );
        
        echo success("Cleaned {$count} expired token(s).") . PHP_EOL;
        
        return 0;
    } catch (Throwable $e) {
        echo error('Failed to clean tokens: ' . $e->getMessage()) . PHP_EOL;
        return 1;
    }
}

// ============================================
// COMMAND: DB:STATS
// ============================================

function cmd_db_stats(array $args): int
{
    try {
        echo blue('╔══════════════════════════════════════════╗') . PHP_EOL;
        echo blue('║         DATABASE STATISTICS               ║') . PHP_EOL;
        echo blue('╚══════════════════════════════════════════╝') . PHP_EOL . PHP_EOL;
        
        // User counts by role
        $adminCount = Database::fetchOne('SELECT COUNT(*) as count FROM users WHERE role = ?', ['admin']);
        $organizerCount = Database::fetchOne('SELECT COUNT(*) as count FROM users WHERE role = ?', ['organizer']);
        $spectatorCount = Database::fetchOne('SELECT COUNT(*) as count FROM users WHERE role = ?', ['spectator']);
        
        echo yellow('USERS:') . PHP_EOL;
        echo '  ' . success('Admin: ') . ($adminCount['count'] ?? 0) . PHP_EOL;
        echo '  ' . success('Organizer: ') . ($organizerCount['count'] ?? 0) . PHP_EOL;
        echo '  ' . success('Spectator: ') . ($spectatorCount['count'] ?? 0) . PHP_EOL;
        $totalUsers = (int)($adminCount['count'] ?? 0) + (int)($organizerCount['count'] ?? 0) + (int)($spectatorCount['count'] ?? 0);
        echo '  ' . blue('Total: ') . $totalUsers . PHP_EOL . PHP_EOL;
        
        // File count
        $fileCount = Database::fetchOne('SELECT COUNT(*) as count FROM files');
        echo yellow('FILES:') . PHP_EOL;
        echo '  ' . success('Total: ') . ($fileCount['count'] ?? 0) . PHP_EOL . PHP_EOL;
        
        // Token blacklist count
        $blacklistCount = Database::fetchOne('SELECT COUNT(*) as count FROM token_blacklist');
        echo yellow('BLACKLISTED TOKENS:') . PHP_EOL;
        echo '  ' . success('Total: ') . ($blacklistCount['count'] ?? 0) . PHP_EOL . PHP_EOL;
        
        // Database file size
        echo yellow('DATABASE:') . PHP_EOL;
        if (file_exists(DATABASE_PATH)) {
            $fileSize = filesize(DATABASE_PATH);
            echo '  ' . success('File: ') . DATABASE_PATH . PHP_EOL;
            echo '  ' . success('Size: ') . formatBytes($fileSize) . PHP_EOL;
            
            // Try to get disk free space
            $freeSpace = @disk_free_space(dirname(DATABASE_PATH));
            if ($freeSpace !== false) {
                echo '  ' . success('Free space: ') . formatBytes($freeSpace) . PHP_EOL;
            }
        } else {
            echo '  ' . error('Database file not found') . PHP_EOL;
        }
        
        echo PHP_EOL;
        
        return 0;
    } catch (Throwable $e) {
        echo error('Failed to get database stats: ' . $e->getMessage()) . PHP_EOL;
        return 1;
    }
}

/**
 * Format bytes to human readable string
 */
function formatBytes(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $unitIndex = 0;
    $size = (float) $bytes;
    
    while ($size >= 1024 && $unitIndex < count($units) - 1) {
        $size /= 1024;
        $unitIndex++;
    }
    
    return round($size, 2) . ' ' . $units[$unitIndex];
}

// ============================================
// COMMAND: DEV:SEED
// ============================================

function cmd_dev_seed(array $args): int
{
    $force = in_array('--force', $args);
    
    // Check if in production (.env exists AND JWT_SECRET is set)
    if (file_exists(ENV_PATH)) {
        $envContent = file_get_contents(ENV_PATH);
        if (preg_match('/^JWT_SECRET=.+/m', $envContent)) {
            if (!$force) {
                echo error('ERROR: This command cannot be run in production mode.') . PHP_EOL;
                echo warning('Use --force to override this safety check.') . PHP_EOL;
                return 1;
            }
            echo warning('WARNING: Running in production mode with --force flag!') . PHP_EOL;
        }
    }
    
    // Define test users
    $testUsers = [
        ['email' => 'dev-admin@test.com', 'role' => 'admin'],
        ['email' => 'dev-organizer@test.com', 'role' => 'organizer'],
        ['email' => 'dev-spectator@test.com', 'role' => 'spectator'],
        ['email' => 'dev-user@test.com', 'role' => 'organizer'],
    ];
    
    $password = 'password123';
    $passwordHash = hashPassword($password);
    
    $createdUsers = [];
    
    try {
        foreach ($testUsers as $user) {
            // Check if user already exists
            $existing = Database::fetchOne('SELECT id FROM users WHERE email = ?', [$user['email']]);
            
            if ($existing !== null) {
                echo warning("Skipping {$user['email']} (already exists)") . PHP_EOL;
                continue;
            }
            
            // Create user
            Database::execute(
                'INSERT INTO users (email, password_hash, role) VALUES (?, ?, ?)',
                [$user['email'], $passwordHash, $user['role']]
            );
            
            $userId = Database::lastInsertId();
            $createdUsers[] = [
                'id' => $userId,
                'email' => $user['email'],
                'role' => $user['role'],
            ];
            
            echo success("Created: {$user['email']} ({$user['role']})") . PHP_EOL;
        }
        
        if (empty($createdUsers)) {
            echo warning('No new users created (all users already exist).') . PHP_EOL;
            return 0;
        }
        
        // Display created users in table format
        echo PHP_EOL;
        echo blue('╔══════════════════════════════════════════╗') . PHP_EOL;
        echo blue('║         CREATED TEST USERS              ║') . PHP_EOL;
        echo blue('╚══════════════════════════════════════════╝') . PHP_EOL . PHP_EOL;
        
        $header = sprintf('| %-3s | %-30s | %-12s |', 'ID', 'Email', 'Role');
        $separator = str_repeat('─', strlen($header));
        
        echo $header . PHP_EOL;
        echo $separator . PHP_EOL;
        
        foreach ($createdUsers as $user) {
            printf('| %-3d | %-30s | %-12s |' . PHP_EOL, 
                $user['id'], 
                $user['email'], 
                $user['role']
            );
        }
        
        echo $separator . PHP_EOL . PHP_EOL;
        echo success('All test users created with password: ') . $password . PHP_EOL;
        
        return 0;
    } catch (Throwable $e) {
        echo error('Failed to seed users: ' . $e->getMessage()) . PHP_EOL;
        return 1;
    }
}

// ============================================
// HELP FUNCTION
// ============================================

function get_help(): void
{
    echo blue("Contest Organizer CLI Admin Tool") . "\n\n";
    echo "Usage: php cli.php <command> [options]\n\n";
    echo "Commands:\n";
    echo "  init [--force]              Initialize the database and generate configuration\n";
    echo "  user:create <email> <role> Create a new user interactively\n";
    echo "  user:list                   List all users in table format\n";
    echo "  user:delete <id>            Delete a user (with confirmation)\n";
    echo "  user:password <email>       Change user password\n";
    echo "  user:role <email> <role>    Change user role\n";
    echo "  db:clean                    Clean expired tokens from blacklist\n";
    echo "  db:stats                    Show database statistics\n";
    echo "  dev:seed [--force]          Seed test users (dev mode only)\n";
    echo "  help                        Show this help message\n\n";
    echo "Options:\n";
    echo "  --force                     Force action (used with init to recreate database)\n\n";
    echo "Roles:\n";
    echo "  admin, organizer, spectator\n\n";
    echo "Examples:\n";
    echo "  php cli.php init\n";
    echo "  php cli.php user:create user@example.com admin\n";
    echo "  php cli.php user:list\n";
    echo "  php cli.php user:delete 2\n";
    echo "  php cli.php user:password user@example.com\n";
    echo "  php cli.php user:role user@example.com organizer\n";
    echo "  php cli.php db:clean\n";
    echo "  php cli.php db:stats\n";
    echo "  php cli.php dev:seed\n";
}

// ============================================
// MAIN ENTRY POINT
// ============================================

function main(array $argv): int
{
    array_shift($argv); // Remove script name

    if (empty($argv) || in_array('--help', $argv)) {
        get_help();
        return 0;
    }

    $command = $argv[0];
    $args = array_slice($argv, 1);

    try {
        switch ($command) {
            case 'init':
                return cmd_init($args);
            case 'user:create':
                return cmd_user_create($args);
            case 'user:list':
                return cmd_user_list($args);
            case 'user:delete':
                return cmd_user_delete($args);
            case 'user:password':
                return cmd_user_password($args);
            case 'user:role':
                return cmd_user_role($args);
            case 'db:clean':
                return cmd_db_clean($args);
            case 'db:stats':
                return cmd_db_stats($args);
            case 'dev:seed':
                return cmd_dev_seed($args);
            case 'help':
                get_help();
                return 0;
            default:
                echo error("Unknown command: {$command}\n\n");
                get_help();
                return 1;
        }
    } catch (Throwable $e) {
        echo error("Exception: " . $e->getMessage() . "\n");
        return 1;
    }
}

exit(main($argv));
