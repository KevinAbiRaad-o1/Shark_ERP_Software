<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/header.php';

$db = DatabaseConnection::getInstance();
$poId = $_GET['id'] ?? 0;

// Get PO details with requester info
$po = $db->prepare("
    SELECT w.*, 
           e.first_name as requester_first, 
           e.last_name as requester_last,
           e.email as requester_email
    FROM warehouse_item_request w
    JOIN employee e ON w.created_by = e.id
    WHERE w.id = ?
");
$po->execute([$poId]);
$poData = $po->fetch();

if (!$poData) {
    $_SESSION['error'] = "Purchase request not found";
    header("Location: index.php");
    exit();
}

// Get PO items with extended inventory info
$items = $db->prepare("
    SELECT 
        i.id,
        i.sku, 
        i.name,
        i.description,
        poi.quantity as requested_qty,
        i.min_stock_level,
        i.max_stock_level,
        i.weight,
        i.weight_unit,
        i.dimensions,
        i.color,
        i.is_active,
        COALESCE((
            SELECT SUM(inv.quantity) 
            FROM inventory inv 
            WHERE inv.item_id = i.id
        ), 0) as current_stock,
        (
            SELECT GROUP_CONCAT(DISTINCT l.name SEPARATOR ', ') 
            FROM inventory inv 
            JOIN location l ON inv.location_id = l.id 
            WHERE inv.item_id = i.id
        ) as locations
    FROM warehouse_item_request wir
    JOIN purchase_order_item poi ON wir.id = poi.po_id
    JOIN item i ON poi.item_id = i.id
    WHERE wir.id = ?
");
$items->execute([$poId]);
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0">Purchase Request #<?= htmlspecialchars($poData['po_number']) ?></h4>
                <small class="text-muted">Created: <?= date('M d, Y h:i A', strtotime($poData['created_at'])) ?></small>
            </div>
            <span class="badge bg-<?= 
                ($poData['status'] == 'approved') ? 'success' : 
                (($poData['status'] == 'rejected') ? 'danger' : 'warning') 
            ?>">
                <?= strtoupper(htmlspecialchars($poData['status'])) ?>
            </span>
        </div>
        
        <div class="card-body">
            <!-- Request Info Section -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5>Request Details</h5>
                    <div class="mb-3">
                        <p><strong>Requested By:</strong> 
                            <?= htmlspecialchars($poData['requester_first'] . ' ' . $poData['requester_last']) ?>
                            (<?= htmlspecialchars($poData['requester_email']) ?>)
                        </p>
                        <p><strong>Expected Delivery:</strong> 
                            <?= $poData['expected_delivery_date'] ? 
                                date('M d, Y', strtotime($poData['expected_delivery_date'])) : 
                                'Not specified' ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Items Table -->
            <h5 class="mb-3">Requested Items</h5>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>SKU</th>
                            <th>Item Name</th>
                            <th>Description</th>
                            <th>Requested</th>
                            <th>In Stock</th>
                            <th>Min Level</th>
                            <th>Max Level</th>
                            <th>Locations</th>
                            <th>Details</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($item = $items->fetch()): ?>
                        <tr class="<?= ($item['current_stock'] < $item['min_stock_level']) ? 'table-warning' : '' ?>">
                            <td><?= htmlspecialchars($item['sku']) ?></td>
                            <td><?= htmlspecialchars($item['name']) ?></td>
                            <td><?= htmlspecialchars($item['description']) ?></td>
                            <td><?= $item['requested_qty'] ?></td>
                            <td><?= $item['current_stock'] ?></td>
                            <td><?= $item['min_stock_level'] ?? 'N/A' ?></td>
                            <td><?= $item['max_stock_level'] ?? 'N/A' ?></td>
                            <td><?= $item['locations'] ? htmlspecialchars($item['locations']) : 'N/A' ?></td>
                            <td>
                                <?php if ($item['weight']): ?>
                                    <?= htmlspecialchars($item['weight']) ?> <?= htmlspecialchars($item['weight_unit']) ?><br>
                                <?php endif; ?>
                                <?php if ($item['dimensions']): ?>
                                    <?= htmlspecialchars($item['dimensions']) ?><br>
                                <?php endif; ?>
                                <?php if ($item['color']): ?>
                                    <?= htmlspecialchars($item['color']) ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= $item['is_active'] ? 'success' : 'secondary' ?>">
                                    <?= $item['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Notes Section -->
            <?php if (!empty($poData['notes'])): ?>
            <div class="mt-4">
                <h5>Additional Notes</h5>
                <div class="card">
                    <div class="card-body">
                        <?= nl2br(htmlspecialchars($poData['notes'])) ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="card-footer">
            <a href="index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>