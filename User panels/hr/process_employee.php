<?php
require_once __DIR__ . '/includes/auth_check.php';

// Verify HR role
$db = DatabaseConnection::getInstance();
$stmt = $db->prepare("SELECT role_type FROM roles WHERE employee_id = ?");
$stmt->execute([$_SESSION['employee_id']]);
$role = $stmt->fetchColumn();

if ($role !== 'hr') {
    header("Location: ../../unauthorized.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();

        // First, insert into employee table
        $stmt = $db->prepare("
            INSERT INTO employee (
                first_name, 
                last_name, 
                email, 
                phone, 
                address_line, 
                city, 
                hire_date, 
                department_id, 
                is_active
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['email'],
            $_POST['phone'] ?: null,
            $_POST['address_line'] ?: null,
            $_POST['city'] ?: null,
            $_POST['hire_date'],
            $_POST['department_id'] ?: null,
            $_POST['is_active'] ?? 1
        ]);
        
        $employeeId = $db->lastInsertId();
        $personId = $employeeId;
        
        // Hash the password
        $hashedPassword =$_POST['password'];
        
        // Insert into users table
        $stmt = $db->prepare("
            INSERT INTO users (
                person_id, 
                username, 
                password
            ) VALUES (?, ?, ?)
        ");
        $stmt->execute([
            $personId,
            $_POST['username'],
            $hashedPassword
        ]);
        
        // Insert into roles table
        $stmt = $db->prepare("
            INSERT INTO roles (
                employee_id, 
                role_type
            ) VALUES (?, ?)
        ");
        $stmt->execute([
            $employeeId,
            $_POST['role_type']
        ]);
        
        $db->commit();
        
        $_SESSION['success_message'] = "Employee added successfully!";
        header("Location: employee_list.php");
        exit();
        
    } catch (PDOException $e) {
        $db->rollBack();
        
        // Check for duplicate entry error
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            if (strpos($e->getMessage(), 'email') !== false) {
                $_SESSION['error_message'] = "Email already exists in the system.";
            } elseif (strpos($e->getMessage(), 'username') !== false) {
                $_SESSION['error_message'] = "Username already exists in the system.";
            } else {
                $_SESSION['error_message'] = "Duplicate entry error occurred.";
            }
        } else {
            $_SESSION['error_message'] = "Error adding employee: " . $e->getMessage();
        }
        
        header("Location: add_employee.php");
        exit();
    }
} else {
    header("Location: add_employee.php");
    exit();
}