<?php
require_once __DIR__ . '/includes/auth_check.php';
$db = DatabaseConnection::getInstance();

// Verify HR role
$stmt = $db->prepare("SELECT role_type FROM roles WHERE employee_id = ?");
$stmt->execute([$_SESSION['employee_id']]);
$role = $stmt->fetchColumn();

if ($role !== 'hr') {
    header("Location: ../../unauthorized.php");
    exit();
}

// Verify required fields
if (!isset($_POST['employee_id'], $_POST['new_status'], $_POST['hr_password'])) {
    $_SESSION['error_message'] = "Missing required fields";
    header("Location: employee_list.php");
    exit();
}

// Get employee details
$stmt = $db->prepare("
    SELECT r.role_type 
    FROM employee e
    JOIN roles r ON e.id = r.employee_id
    WHERE e.id = ?
");
$stmt->execute([$_POST['employee_id']]);
$targetEmployee = $stmt->fetch();

// Prevent deactivating owner
if ($targetEmployee['role_type'] === 'owner') {
    $_SESSION['error_message'] = "Cannot deactivate owner account";
    header("Location: employee_list.php");
    exit();
}

// Verify HR password
$stmt = $db->prepare("SELECT password FROM users WHERE person_id = ?");
$stmt->execute([$_SESSION['person_id']]);
$hrUser = $stmt->fetch();

if (!$hrUser || $hrUser['password'] !== $_POST['hr_password']) {
    $_SESSION['error_message'] = "Incorrect password";
    header("Location: view_employee.php?id=" . $_POST['employee_id']);
    exit();
}

// Update employee status
try {
    $db->beginTransaction();
    
    $stmt = $db->prepare("UPDATE employee SET is_active = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$_POST['new_status'], $_POST['employee_id']]);
    
    $db->commit();
    
    $_SESSION['success_message'] = "Employee status updated successfully";
    header("Location: view_employee.php?id=" . $_POST['employee_id']);
    exit();
    
} catch (PDOException $e) {
    $db->rollBack();
    $_SESSION['error_message'] = "Error updating employee status: " . $e->getMessage();
    header("Location: view_employee.php?id=" . $_POST['employee_id']);
    exit();
}