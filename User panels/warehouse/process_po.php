<?php
require_once __DIR__ . '/includes/auth_check.php';

$db = DatabaseConnection::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db->beginTransaction();
    try {
        // 1. Create the warehouse item request
        $stmt = $db->prepare("
            INSERT INTO warehouse_item_request (
                po_number, 
                order_date, 
                expected_delivery_date, 
                status, 
                notes, 
                created_by
            ) VALUES (
                :po_number,
                NOW(),
                :expected_delivery_date,
                'draft',
                :notes,
                :created_by
            )
        ");
        
        // Generate PO number
        $poNumber = 'PO-' . date('Ymd-His');
        
        $stmt->execute([
            ':po_number' => $poNumber,
            ':expected_delivery_date' => $_POST['expected_delivery_date'],
            ':notes' => $_POST['notes'],
            ':created_by' => $_SESSION['employee_id']
        ]);
        
        $poId = $db->lastInsertId();
        
        // 2. Add items to purchase_order_item table
        if (!empty($_POST['items'])) {
            $itemStmt = $db->prepare("
                INSERT INTO purchase_order_item (
                    po_id, 
                    item_id, 
                    quantity
                ) VALUES (
                    :po_id,
                    :item_id,
                    :quantity
                )
            ");
            
            foreach ($_POST['items'] as $itemId => $itemData) {
                if (isset($itemData['include']) && $itemData['include'] === 'on') {
                    $itemStmt->execute([
                        ':po_id' => $poId,
                        ':item_id' => $itemId,
                        ':quantity' => $itemData['qty']
                    ]);
                }
            }
        }
        
        $db->commit();
        $_SESSION['success'] = "Purchase request #$poNumber created successfully!";
        header("Location: create_po.php");
        exit();
        
    } catch (PDOException $e) {
        $db->rollBack();
        $_SESSION['error'] = "Error creating purchase request: " . $e->getMessage();
        header("Location: create_po.php");
        exit();
    }
} else {
    header("Location: create_po.php");
    exit();
}