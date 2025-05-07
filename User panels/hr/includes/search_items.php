<?php
// User panels/warehouse/includes/search_items.php
require_once __DIR__ . '/../../DataBaseconnection/config.php';

$db = DatabaseConnection::getInstance();
$searchTerm = $_GET['q'] ?? '';

$stmt = $db->prepare("
    SELECT i.id, i.sku, i.name, c.name as category, 
           i.min_stock_level, i.is_active
    FROM item i
    JOIN category c ON i.category_id = c.id
    WHERE i.is_active = TRUE
    AND (
        i.sku REGEXP :search OR
        i.name REGEXP :search OR
        c.name REGEXP :search
    )
    ORDER BY i.sku
");
$stmt->execute([':search' => $searchTerm]);
header('Content-Type: application/json');
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));