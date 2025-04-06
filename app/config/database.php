<?php
namespace App\Config;

use PDO;
use PDOException;

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        try {
            // Load environment variables
            $host = getenv('DB_HOST') ?? '127.0.0.1';
            $port = getenv('DB_PORT') ?? '3306';
            $database = getenv('DB_DATABASE') ?? 'ecommerce_canabidiol';
            $username = getenv('DB_USERNAME') ?? 'root';
            $password = getenv('DB_PASSWORD') ?? '';

            // Create PDO connection
            $this->connection = new PDO(
                "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_STRINGIFY_FETCHES => false
                ]
            );

            // Set timezone for database connection
            $this->connection->exec("SET time_zone = '".date('P')."'");
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Failed to connect to database: " . $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance->connection;
    }

    // Prevent cloning and serialization
    private function __clone() {}
    public function __wakeup() {
        throw new \RuntimeException("Cannot unserialize database connection");
    }
}