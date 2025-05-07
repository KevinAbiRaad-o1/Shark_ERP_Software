<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/header.php';

$db = DatabaseConnection::getInstance();
$userID = $_SESSION['user_id'];

// Improved query to check for existing requests by this user
$items = $db->prepare("
    SELECT 
        i.id, 
        i.sku, 
        i.name, 
        COALESCE(SUM(inv.quantity), 0) as current_stock,
        i.min_stock_level,
        i.is_active,
        EXISTS (
            SELECT 1 FROM purchase_order_item poi
            JOIN warehouse_item_request wir ON poi.po_id = wir.id
            WHERE poi.item_id = i.id 
            AND wir.created_by = ?
            AND wir.status NOT IN ('rejected', 'cancelled', 'completed')
        ) as has_active_request
    FROM item i
    LEFT JOIN inventory inv ON i.id = inv.item_id
    GROUP BY i.id
    HAVING current_stock < i.min_stock_level 
       AND has_active_request = 0
    ORDER BY i.sku
");
$items->execute([$userID]);
$items = $items->fetchAll();

?>

<div class="container mt-4">
    <h2><i class="bi bi-file-earmark-plus"></i> Create Purchase Request</h2>
    
    <form method="POST" action="process_po.php" id="poForm">
        <div class="card mt-4">
            <div class="card-header bg-primary text-white">
                Items Needed
            </div>
            <div class="card-body">
                <?php if (empty($items)): ?>
                    <div class="alert alert-info">
                        No items currently need reordering or you have already created requests for all low-stock items.
                    </div>
                <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Include</th>
                            <th>SKU</th>
                            <th>Item Name</th>
                            <th>Current Stock</th>
                            <th>Min Level</th>
                            <th>Order Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <?php if ($item['is_active'] == true): ?>
                        <tr class="<?= 
                            ($item['min_stock_level'] === null) ? 'table-warning' : 
                            (($item['current_stock'] < $item['min_stock_level']) ? 'table-danger' : '') 
                        ?>">
                            <td>
                                <input type="checkbox" name="items[<?= $item['id'] ?>][include]" 
                                       class="form-check-input">
                            </td>
                            <td><?= htmlspecialchars($item['sku']) ?></td>
                            <td><?= htmlspecialchars($item['name']) ?></td>
                            <td><?= $item['current_stock'] ?></td>
                            <td><?= $item['min_stock_level'] ?? 'Not set' ?></td>
                            <td>
                                <input type="number" name="items[<?= $item['id'] ?>][qty]" 
                                       min="1" class="form-control" 
                                       value="<?= max(1, ($item['min_stock_level'] ?? 1) - $item['current_stock']) ?>" 
                                       disabled>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($items)): ?>
        <div class="card mt-3">
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Expected Delivery Date</label>
                    <input type="date" name="expected_delivery_date" class="form-control" 
                           min="<?= date('Y-m-d') ?>" 
                           value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-send"></i> Submit for Approval
                </button>
            </div>
        </div>
        <?php endif; ?>
    </form>
</div>

<script>
// Enable quantity fields when checkbox is checked
document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const qtyField = this.closest('tr').querySelector('input[type="number"]');
        qtyField.disabled = !this.checked;
        if (!this.checked) qtyField.value = '';
    });
});

// Validate form before submission
document.getElementById('poForm').addEventListener('submit', function(e) {
    const checkedItems = document.querySelectorAll('input[type="checkbox"]:checked');
    if (checkedItems.length === 0) {
        e.preventDefault();
        alert('Please select at least one item to order');
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>