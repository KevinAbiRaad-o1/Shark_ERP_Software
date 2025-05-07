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

        // Check for duplicate email first
        $stmt = $db->prepare("SELECT COUNT(*) FROM employee WHERE email = ?");
        $stmt->execute([$_POST['email']]);
        if ($stmt->fetchColumn() > 0) {
            throw new PDOException("Email already exists", 23000);
        }

        // Check for duplicate username
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$_POST['username']]);
        if ($stmt->fetchColumn() > 0) {
            throw new PDOException("Username already exists", 23000);
        }

        // Generate a new person_id
        $stmt = $db->query("SELECT IFNULL(MAX(person_id), 0) + 1 FROM employee");
        $personId = $stmt->fetchColumn();

        // Insert into employee table
        $stmt = $db->prepare("
            INSERT INTO employee (
                person_id,
                first_name, 
                last_name, 
                email, 
                phone, 
                address_line, 
                city, 
                hire_date, 
                department_id, 
                is_active
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $personId,
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
        
        // Hash the password
        $hashedPassword = $_POST['password'];
        
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
        
        $errorMessage = "Error adding employee: ";
        
        // Handle specific error cases
        if ($e->getCode() == 23000) { // Integrity constraint violation
            if (strpos($e->getMessage(), 'email') !== false || $e->getMessage() === "Email already exists") {
                $errorMessage = "The email address is already registered in our system.";
            } 
            elseif (strpos($e->getMessage(), 'username') !== false || $e->getMessage() === "Username already exists") {
                $errorMessage = "The username is already taken. Please choose another one.";
            }
            elseif (strpos($e->getMessage(), 'person_id') !== false) {
                $errorMessage = "A system error occurred (duplicate ID). Please try again.";
            }
            else {
                $errorMessage = "A duplicate entry error occurred. Please check your input.";
            }
        } else {
            $errorMessage .= $e->getMessage();
        }
        
        $_SESSION['error_message'] = $errorMessage;
        $_SESSION['form_data'] = $_POST; // Preserve form input
        header("Location: add_employee.php");
        exit();
    }
} else {
    header("Location: add_employee.php");
    exit();
}