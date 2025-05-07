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

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: employee_list.php");
    exit();
}

// Validate required fields
$requiredFields = [
    'employee_id', 
    'first_name', 
    'last_name', 
    'email', 
    'hire_date',
    'role_type'
];

foreach ($requiredFields as $field) {
    if (empty($_POST[$field])) {
        $_SESSION['error_message'] = "Missing required field: " . $field;
        header("Location: employee_list.php");
        exit();
    }
}

try {
    $db->beginTransaction();
    
    // Update employee table
    $stmt = $db->prepare("
        UPDATE employee 
        SET first_name = ?, 
            last_name = ?, 
            email = ?, 
            phone = ?, 
            address_line = ?, 
            city = ?, 
            hire_date = ?,
            department_id = ?, 
            is_active = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    
    $stmt->execute([
        trim($_POST['first_name']),
        trim($_POST['last_name']),
        trim($_POST['email']),
        !empty($_POST['phone']) ? trim($_POST['phone']) : null,
        !empty($_POST['address_line']) ? trim($_POST['address_line']) : null,
        !empty($_POST['city']) ? trim($_POST['city']) : null,
        trim($_POST['hire_date']),
        !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null,
        $_POST['is_active'] ?? 1,
        (int)$_POST['employee_id']
    ]);
    
    // Update users table
    if (!empty($_POST['password'])) {
        $hashedPassword = $_POST['password'];
        $stmt = $db->prepare("
            UPDATE users 
            SET password = ? 
            WHERE person_id = ?
        ");
        $stmt->execute([
            $hashedPassword,
            (int)$_POST['employee_id']
        ]);
    }
    
    // Update roles table
    $stmt = $db->prepare("
        UPDATE roles 
        SET role_type = ? 
        WHERE employee_id = ?
    ");
    $stmt->execute([
        trim($_POST['role_type']),
        (int)$_POST['employee_id']
    ]);
    
    $db->commit();
    
    $_SESSION['success_message'] = "Employee updated successfully!";
    header("Location: employee_list.php");
    exit();

} catch (PDOException $e) {
    $db->rollBack();
    $_SESSION['error_message'] = "Error updating employee: " . $e->getMessage();
    header("Location: employee_list.php");
    exit();
}