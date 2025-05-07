<?php
// will run the jsonCONFIG.php to Customize the category and location specificaly for the user of the software.
require_once __DIR__ . '/jsonCONFIG.php'; 
class DatabaseConnection {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $this->connection = new PDO(
                'mysql:host=localhost;dbname=erp-database;charset=utf8mb4',
                'root', // Default username - CHANGE THIS
                '',     // Default password - CHANGE THIS
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            // Log error details for debugging
            error_log('Database connection error: ' . $e->getMessage());
            
            // User-friendly message
            die('System temporarily unavailable. Please contact IT support.');
        }
    }
    
    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new DatabaseConnection();
        }
        return self::$instance->connection;
    }
    
    // Prevent cloning and serialization
    private function __clone() {}
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Usage example in other files:
// $db = DatabaseConnection::getInstance();
?>