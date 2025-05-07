<?php
// Shark-erp/User panels/warehouse/includes/auth_check.php
require_once __DIR__ . '/../../../DataBaseconnection/config.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$db = DatabaseConnection::getInstance();
$stmt = $db->prepare("SELECT r.role_type FROM roles r JOIN users u ON r.employee_id = u.person_id WHERE u.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$role = $stmt->fetchColumn();

if ($role !== 'hr') {
    header("Location: ../../unauthorized.php");
    exit();
}
?>