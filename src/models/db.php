<?php

declare(strict_types=1);

if (!defined('PROJECT_ROOT_PATH')) {
    require_once(__DIR__ . '/../utils/404.php');
}

class DB
{
    private static ?DB $instance = null;
    private PDO $connection;
    private string $dbPath;

    private function __construct()
    {
        $this->dbPath = PROJECT_ROOT_PATH . 'database/contest.db';
        $this->initializeConnection();
    }

    private function initializeConnection(): void
    {
        $dbDir = dirname($this->dbPath);
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }

        $dsn = 'sqlite:' . $this->dbPath;
        $this->connection = new PDO($dsn, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $this->connection->exec('PRAGMA foreign_keys = ON');

        $this->initializeDatabase();
    }

    public static function getInstance(): DB
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function initializeDatabase(): void
    {
        $instance = self::getInstance();
        $schemaPath = PROJECT_ROOT_PATH . 'database/auth_schema.sql';

        if (!file_exists($schemaPath)) {
            throw new RuntimeException('Schema file not found: ' . $schemaPath);
        }

        $schema = file_get_contents($schemaPath);
        $statements = array_filter(
            array_map('trim', explode(';', $schema)),
            fn(string $s) => !empty($s) && strpos($s, '--') !== 0
        );

        foreach ($statements as $statement) {
            $trimmed = trim($statement);
            if (!empty($trimmed) && strpos($trimmed, '--') !== 0) {
                $instance->connection->exec($statement);
            }
        }
    }

    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }

    public function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result === false ? null : $result;
    }

    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->connection->commit();
    }

    public function rollback(): bool
    {
        return $this->connection->rollBack();
    }

    private function __clone() {}

    public function __wakeup()
    {
        throw new RuntimeException('Cannot unserialize singleton');
    }
}
