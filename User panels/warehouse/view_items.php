<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

$db = DatabaseConnection::getInstance();
$stmt = $db->query("
    SELECT i.*, c.name as category_name, 
           (SELECT SUM(quantity) FROM inventory WHERE item_id = i.id) as total_quantity
    FROM item i 
    JOIN category c ON i.category_id = c.id
");
$items = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between mb-4">
    <h2><i class="bi bi-boxes"></i> Inventory Items</h2>
    <a href="add_item.php" class="btn btn-success">
        <i class="bi bi-plus-lg"></i> Add New Item
    </a>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-hover">
        <thead class="table-dark">
            <tr>
                <th>SKU</th>
                <th>Item Name</th>
                <th>Category</th>
                <th>Stock</th>
                <th>Min/Max</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><span class="badge bg-secondary"><?= htmlspecialchars($item['sku']) ?></span></td>
                <td><?= htmlspecialchars($item['name']) ?></td>
                <td><?= htmlspecialchars($item['category_name']) ?></td>
                <td>
                    <span class="badge <?= $item['total_quantity'] < $item['min_stock_level'] ? 'bg-warning' : 'bg-success' ?>">
                        <?= $item['total_quantity'] ?? 0 ?>
                    </span>
                </td>
                <td>
                    <small><?= $item['min_stock_level'] ?> / <?= $item['max_stock_level'] ?></small>
                </td>
                <td>
                    <a href="edit_item.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-pencil-square"></i> Edit
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>