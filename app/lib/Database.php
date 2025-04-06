<?php

namespace App\Lib;

use PDOException;
use PDO;
use Exception;

/**
 * Database Class - PDO Wrapper for Canabidiol Commerce Platform
 * 
 * Provides secure database connectivity and query methods with:
 * - Prepared statements protection against SQL injection
 * - Transaction support
 * - Error handling
 * - Query logging
 */
class Database {
    private $host;
    private $user;
    private $pass;
    private $name;
    private $port;
    private $charset;
    
    private $pdo;
    private $stmt;
    private $error;
    private $connected = false;
     
    
    public function __construct() {
        // Load database configuration
        $this->host = $_ENV['DB_HOST'] ?? 'localhost';
        $this->user = $_ENV['DB_USER'] ?? 'root';
        $this->pass = $_ENV['DB_PASS'] ?? '';
        $this->name = $_ENV['DB_NAME'] ?? 'canabidiol_commerce';
        $this->port = $_ENV['DB_PORT'] ?? '3306';
        $this->charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';
        
        // Set DSN
        $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->name};charset={$this->charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => true
        ];
        
        try {
            $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
            $this->connected = true;
            
            // Set timezone for database connection
            $this->query("SET time_zone = '+00:00'");
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            $this->logError($e);
            throw new Exception("Database connection failed: " . $this->error);
        }
    }
    
    /**
     * Prepare a SQL query
     */
    public function query($sql) {
        $this->stmt = $this->pdo->prepare($sql);
    }
    
    /**
     * Bind parameters to prepared statement
     */
    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        
        $this->stmt->bindValue($param, $value, $type);
    }
    
    /**
     * Execute the prepared statement
     */
    public function execute() {
        try {
            return $this->stmt->execute();
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            $this->logError($e);
            throw new Exception("Query execution failed: " . $this->error);
        }
    }
    
    /**
     * Get result set as array of objects
     */
    public function resultSet() {
        $this->execute();
        return $this->stmt->fetchAll();
    }
    
    /**
     * Get single record as object
     */
    public function single() {
        $this->execute();
        return $this->stmt->fetch();
    }
    
    /**
     * Get row count
     */
    public function rowCount() {
        return $this->stmt->rowCount();
    }
    
    /**
     * Get last inserted ID
     */
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->pdo->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollBack() {
        return $this->pdo->rollBack();
    }
    
    /**
     * Debug SQL query
     */
    public function debugDumpParams() {
        return $this->stmt->debugDumpParams();
    }
    
    /**
     * Check if connected to database
     */
    public function isConnected() {
        return $this->connected;
    }
    
    /**
     * Get database error
     */
    public function getError() {
        return $this->error;
    }
    
    /**
     * Log database errors
     */
    private function logError(PDOException $e) {
        $logMessage = sprintf(
            "[%s] Database Error: %s in %s on line %d\nStack Trace:\n%s\n\n",
            date('Y-m-d H:i:s'),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );
        
        $logFile = __DIR__ . '/../../storage/logs/database-errors.log';
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
    
    /**
     * Sanitize input data
     */
    public function sanitize($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitize'], $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Get PDO instance (for advanced operations)
     */
    public function getPdo() {
        return $this->pdo;
    }
    
    /**
     * Close the database connection
     */
    public function close() {
        $this->pdo = null;
        $this->connected = false;
    }
    
    public function __destruct() {
        $this->close();
    }
}