<?php
/**
 * Database Configuration for DecorVista
 * Using MySQLi with OOP wrapper
 */
class Database {
    private $host = 'localhost';
    private $username = 'root';
    private $password = '';
    private $database = 'decorvista';
    private $connection;
    
    public function __construct() {
        $this->connect();
    }
    
    private function connect() {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // Throw exceptions on error
        try {
            $this->connection = new mysqli($this->host, $this->username, $this->password, $this->database);
            $this->connection->set_charset("utf8mb4"); // Full UTF-8 support
        } catch (mysqli_sql_exception $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function close() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
    
    // Safe prepared statement
    public function prepare($query) {
        try {
            return $this->connection->prepare($query);
        } catch (mysqli_sql_exception $e) {
            error_log("Prepare failed: " . $e->getMessage());
            return false;
        }
    }
    
    // Safe query
    public function query($query) {
        try {
            return $this->connection->query($query);
        } catch (mysqli_sql_exception $e) {
            error_log("Query failed: " . $e->getMessage() . " | SQL: " . $query);
            return false;
        }
    }
    
    public function getLastInsertId() {
        return $this->connection->insert_id;
    }
    
    public function escapeString($string) {
        return $this->connection->real_escape_string($string);
    }
    
    public function ping() {
        return $this->connection->ping();
    }
}

// Global database instance
$db = new Database();
?>
