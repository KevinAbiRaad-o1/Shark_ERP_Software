<?php
// Shark-erp/User panels/warehouse/add_item.php
ob_start(); // Add this line
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/header.php';

$db = DatabaseConnection::getInstance();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $db->prepare("INSERT INTO item 
            (sku, category_id, name, description, weight, weight_unit, dimensions, color, min_stock_level, max_stock_level) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            $_POST['sku'],
            $_POST['category_id'],
            $_POST['name'],
            $_POST['description'],
            $_POST['weight'] ?? null,
            $_POST['weight_unit'] ?? 'kg',
            $_POST['dimensions'] ?? null,
            $_POST['color'] ?? null,
            $_POST['min_stock_level'],
            $_POST['max_stock_level']
        ]);

        $itemId = $db->lastInsertId();

        $inventoryStmt = $db->prepare("INSERT INTO inventory (item_id, location_id, quantity) VALUES (?, ?, ?)");
        $inventoryStmt->execute([
            $itemId,
            $_POST['location_id'],
            $_POST['initial_quantity'] ?? 0
        ]);
        ob_end_clean(); // Clean the output buffer
        $_SESSION['success'] = "Item added successfully!";
        header("Location: view_items.php");
        exit();

    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

$categories = $db->query("SELECT id, name FROM category")->fetchAll();
$locations = $db->query("SELECT id, name FROM location")->fetchAll();
?>

<div class="container">
    <h2 class="my-4"><i class="bi bi-plus-circle"></i> Add New Item to Inventory</h2>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" class="needs-validation" novalidate>
        <div class="row g-4">
            <!-- Left Column -->
            <div class="col-md-6 d-flex">
                <div class="card flex-fill">
                    <div class="card-header bg-primary text-white">Item Details</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">SKU*</label>
                            <input type="text" class="form-control" name="sku" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category*</label>
                            <select class="form-select" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Item Name*</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-md-6 d-flex">
                <div class="card flex-fill">
                    <div class="card-header bg-primary text-white">Physical Attributes</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Weight</label>
                                <input type="number" step="0.01" class="form-control" name="weight">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Unit</label>
                                <select class="form-select" name="weight_unit">
                                    <option value="kg">kg</option>
                                    <option value="g">g</option>
                                    <option value="lb">lb</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Dimensions (L×W×H)</label>
                                <input type="text" class="form-control" name="dimensions" placeholder="e.g., 10×5×2 cm">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Color</label>
                                <input type="text" class="form-control" name="color">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stock Information Full Row -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">Stock Information</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Initial Quantity*</label>
                                <input type="number" class="form-control" name="initial_quantity" min="0" value="0" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Min Stock Level*</label>
                                <input type="number" class="form-control" name="min_stock_level" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Max Stock Level*</label>
                                <input type="number" class="form-control" name="max_stock_level" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Storage Location*</label>
                            <select class="form-select" name="location_id" required>
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?= $loc['id'] ?>"><?= htmlspecialchars($loc['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="view_items.php" class="btn btn-secondary">
                <i class="bi bi-x-circle"></i> Cancel
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> Add Item
            </button>
        </div>
    </form>
</div>

<script>
// Bootstrap form validation
(() => {
    'use strict';
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
