<?php
require_once __DIR__ . '/../../../DataBaseconnection/config.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

// Verify logistics role
$db = DatabaseConnection::getInstance();
$stmt = $db->prepare("SELECT role_type FROM roles WHERE employee_id = ?");
$stmt->execute([$_SESSION['employee_id']]);
$role = $stmt->fetchColumn();

if ($role !== 'logistics') {
    header("Location: ../../unauthorized.php");
    exit();
}
?>