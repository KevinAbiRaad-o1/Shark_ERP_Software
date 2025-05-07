<?php
// Shark-erp/DataBaseconnection/jsonCONFIG.php
require_once 'config.php';

class JsonConfig {
    private static $config;
    private static $configFile = __DIR__ . '/jsonConfig.json';

    public static function init() {
        try {
            // Load JSON config
            if (!file_exists(self::$configFile)) {
                throw new Exception("Config file not found: " . self::$configFile);
            }
            
            $jsonContent = file_get_contents(self::$configFile);
            self::$config = json_decode($jsonContent, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Invalid JSON config: " . json_last_error_msg());
            }
            
            // Validate supplier emails before sync
            self::validateSupplierEmails();
            
            // Sync with database
            self::syncWithDatabase();
            
        } catch (Exception $e) {
            error_log("JsonConfig Error: " . $e->getMessage());
            throw $e;
        }
    }

    private static function validateSupplierEmails() {
        if (!isset(self::$config['suppliers'])) {
            return;
        }

        $emailMap = [];
        foreach (self::$config['suppliers'] as $index => $supplier) {
            $email = strtolower(trim($supplier['email']));
            
            if (isset($emailMap[$email])) {
                throw new Exception(
                    "Duplicate supplier email detected: " . $email . 
                    " in suppliers " . $emailMap[$email] . " and " . $supplier['name']
                );
            }
            
            $emailMap[$email] = $supplier['name'];
        }
    }

    private static function syncWithDatabase() {
        try {
            $db = DatabaseConnection::getInstance();
    
            // ---- Categories ----
            if (isset(self::$config['categories'])) {
                $categories = array_unique(array_map('trim', self::$config['categories']));
    
                $stmt = $db->query("SELECT name FROM category");
                $existingCategories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
                foreach ($categories as $categoryName) {
                    if (!in_array($categoryName, $existingCategories)) {
                        $db->prepare("INSERT INTO category (name) VALUES (?)")
                           ->execute([$categoryName]);
                    }
                }
    
                foreach ($existingCategories as $existingName) {
                    if (!in_array($existingName, $categories)) {
                        $db->prepare("DELETE FROM category WHERE name = ?")
                           ->execute([$existingName]);
                    }
                }
            }
    
            // ---- Locations ----
            if (isset(self::$config['locations'])) {
                $locations = array_unique(array_map('trim', self::$config['locations']));
    
                $stmt = $db->query("SELECT name FROM location");
                $existingLocations = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
                foreach ($locations as $locationName) {
                    if (!in_array($locationName, $existingLocations)) {
                        $db->prepare("INSERT INTO location (warehouse_id, name) VALUES (1, ?)")
                           ->execute([$locationName]);
                    }
                }
    
                foreach ($existingLocations as $existingName) {
                    if (!in_array($existingName, $locations)) {
                        $db->prepare("DELETE FROM location WHERE name = ?")
                           ->execute([$existingName]);
                    }
                }
            }
    
            // ---- Departments ----
            if (isset(self::$config['departments'])) {
                $departments = array_unique(array_map('trim', self::$config['departments']));
    
                $stmt = $db->query("SELECT department_name FROM department");
                $existingDepartments = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
                foreach ($departments as $departmentName) {
                    if (!in_array($departmentName, $existingDepartments)) {
                        $db->prepare("INSERT INTO department (department_name) VALUES (?)")
                           ->execute([$departmentName]);
                    }
                }
    
                foreach ($existingDepartments as $existingName) {
                    if (!in_array($existingName, $departments)) {
                        $db->prepare("DELETE FROM department WHERE department_name = ?")
                           ->execute([$existingName]);
                    }
                }
            }
            
            // ---- Suppliers ----
            if (isset(self::$config['suppliers'])) {
                $suppliers = self::$config['suppliers'];
                $existingSuppliers = $db->query("SELECT supplier_code, email FROM supplier")
                                      ->fetchAll(PDO::FETCH_ASSOC);
                
                // Build lookup maps
                $existingCodes = array_column($existingSuppliers, 'supplier_code');
                $existingEmails = array_column($existingSuppliers, 'email');
                
                foreach ($suppliers as $supplier) {
                    $supplier = array_map('trim', $supplier);
                    $email = strtolower($supplier['email']);
                    
                    // Check if email exists with different supplier code
                    $emailOwner = $db->prepare(
                        "SELECT supplier_code FROM supplier WHERE email = ? AND supplier_code != ?"
                    );
                    $emailOwner->execute([$email, $supplier['code']]);
                    
                    if ($emailOwner->rowCount() > 0) {
                        $conflict = $emailOwner->fetch(PDO::FETCH_ASSOC);
                        error_log("Skipping supplier {$supplier['name']} - email {$email} already used by {$conflict['supplier_code']}");
                        continue;
                    }
                    
                    if (in_array($supplier['code'], $existingCodes)) {
                        // Update existing supplier
                        $stmt = $db->prepare("
                            UPDATE supplier SET 
                                name = ?,
                                contact_person = ?,
                                email = ?,
                                phone = ?,
                                is_active = 1,
                                updated_at = NOW()
                            WHERE supplier_code = ?
                        ");
                        $stmt->execute([
                            $supplier['name'],
                            $supplier['contact'],
                            $email,
                            $supplier['phone'],
                            $supplier['code']
                        ]);
                    } else {
                        // Insert new supplier
                        $stmt = $db->prepare("
                            INSERT INTO supplier (
                                name, 
                                supplier_code, 
                                contact_person, 
                                email, 
                                phone,
                                is_active,
                                created_at
                            ) VALUES (?, ?, ?, ?, ?, 1, NOW())
                        ");
                        $stmt->execute([
                            $supplier['name'],
                            $supplier['code'],
                            $supplier['contact'],
                            $email,
                            $supplier['phone']
                        ]);
                    }
                }
                
                // Deactivate suppliers not in config
                $configCodes = array_column($suppliers, 'code');
                $inactiveCodes = array_diff($existingCodes, $configCodes);
                
                if (!empty($inactiveCodes)) {
                    $placeholders = implode(',', array_fill(0, count($inactiveCodes), '?'));
                    $stmt = $db->prepare("
                        UPDATE supplier 
                        SET is_active = 0, updated_at = NOW()
                        WHERE supplier_code IN ($placeholders)
                    ");
                    $stmt->execute(array_values($inactiveCodes));
                }
            }
    
        } catch (PDOException $e) {
            error_log("Database sync error: " . $e->getMessage());
            throw $e;
        }
    }
    
    // ... rest of your class methods (getConfig, addSupplier, updateSupplier, removeSupplier)
}

// Initialize on include
JsonConfig::init();