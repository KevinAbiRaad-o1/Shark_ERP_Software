<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

$db = DatabaseConnection::getInstance();

// Get stats
$lowStock = $db->query("
    SELECT COUNT(*) as count 
    FROM item i 
    JOIN inventory inv ON i.id = inv.item_id 
    WHERE inv.quantity < i.min_stock_level
")->fetchColumn();

$totalItems = $db->query("SELECT COUNT(*) FROM item")->fetchColumn();
$totalCategories = $db->query("SELECT COUNT(*) FROM category")->fetchColumn();
?>

<h2><i class="bi bi-speedometer2"></i> Warehouse Dashboard</h2>

<div class="row mt-4">
    <div class="col-md-4 mb-4">
        <div class="card text-white bg-primary h-100">
            <div class="card-body">
                <h5 class="card-title">Total Items</h5>
                <h2 class="card-text"><?= $totalItems ?></h2>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card text-white bg-success h-100">
            <div class="card-body">
                <h5 class="card-title">Categories</h5>
                <h2 class="card-text"><?= $totalCategories ?></h2>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card text-white bg-warning h-100">
            <div class="card-body">
                <h5 class="card-title">Low Stock Items</h5>
                <h2 class="card-text"><?= $lowStock ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h5>Quick Actions</h5>
    </div>
    <div class="card-body">
        <div class="d-grid gap-2 d-md-flex">
            <a href="add_item.php" class="btn btn-primary me-md-2">
                <i class="bi bi-plus-lg"></i> Add New Item
            </a>
            <a href="view_items.php" class="btn btn-secondary">
                <i class="bi bi-boxes"></i> View All Inventory
            </a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>