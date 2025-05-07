<?php
require_once __DIR__ . '/includes/auth_check.php';
$db = DatabaseConnection::getInstance();

// Verify HR role
$stmt = $db->prepare("SELECT role_type FROM roles WHERE employee_id = ?");
$stmt->execute([$_SESSION['employee_id']]);
$role = $stmt->fetchColumn();

if ($role !== 'hr') {
    $_SESSION['error_message'] = "Unauthorized access";
    header("Location: ../../unauthorized.php");
    exit();
}

// Verify required fields
if (!isset($_POST['employee_id'], $_POST['user_id'], $_POST['hr_password'])) {
    $_SESSION['error_message'] = "Missing required fields";
    header("Location: employee_list.php");
    exit();
}

// Get employee details
$stmt = $db->prepare("
    SELECT e.id, e.first_name, e.last_name, r.role_type
    FROM employee e
    JOIN roles r ON e.id = r.employee_id
    WHERE e.id = ?
");
$stmt->execute([$_POST['employee_id']]);
$employee = $stmt->fetch();

// Prevent deleting owner or invalid employee
if (!$employee || $employee['role_type'] === 'owner') {
    $_SESSION['error_message'] = "Cannot delete this employee";
    header("Location: employee_list.php");
    exit();
}

// Verify HR password (plain text comparison - not secure, but matches your current setup)
$stmt = $db->prepare("SELECT password FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$hrUser = $stmt->fetch();

if (!$hrUser || $hrUser['password'] !== $_POST['hr_password']) {
    $_SESSION['error_message'] = "Incorrect password";
    header("Location: employee_list.php");
    exit();
}

// Delete employee (with transaction)
try {
    $db->beginTransaction();
    
    // Delete from roles
    $stmt = $db->prepare("DELETE FROM roles WHERE employee_id = ?");
    $stmt->execute([$_POST['employee_id']]);
    
    // Delete from users
    $stmt = $db->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->execute([$_POST['user_id']]);
    
    // Delete from employee
    $stmt = $db->prepare("DELETE FROM employee WHERE id = ?");
    $stmt->execute([$_POST['employee_id']]);
    
    $db->commit();
    
    $_SESSION['success_message'] = "Employee deleted successfully";
    header("Location: employee_list.php");
    exit();
    
} catch (PDOException $e) {
    $db->rollBack();
    $_SESSION['error_message'] = "Error deleting employee: " . $e->getMessage();
    header("Location: employee_list.php");
    exit();
}