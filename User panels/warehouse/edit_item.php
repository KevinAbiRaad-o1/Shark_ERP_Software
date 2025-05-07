<?php
ob_start(); 
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/header.php'; 
$db = DatabaseConnection::getInstance();

// Search functionality
if (isset($_GET['search'])) {
    $stmt = $db->prepare("
        SELECT i.*, c.name as category_name
        FROM item i
        JOIN category c ON i.category_id = c.id
        WHERE i.is_active = TRUE
        AND (
            i.sku LIKE CONCAT('%', ?, '%') OR
            i.name LIKE CONCAT('%', ?, '%') OR
            c.name LIKE CONCAT('%', ?, '%')
        )
        ORDER BY i.sku
    ");
    $stmt->execute([$_GET['search'], $_GET['search'], $_GET['search']]);
    $items = $stmt->fetchAll();
} else {
    $items = $db->query("
        SELECT i.*, c.name as category_name 
        FROM item i
        JOIN category c ON i.category_id = c.id
        ORDER BY i.sku
    ")->fetchAll();
}

// Handle item deletion/editing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        if (isset($_POST['delete_item'])) {
            // Soft delete
            $stmt = $db->prepare("UPDATE item SET is_active = FALSE WHERE sku = ?");
            $stmt->execute([$_POST['sku']]);
            $_SESSION['success'] = "Item {$_POST['sku']} deactivated";
            
        } else {
            // Update item
            $stmt = $db->prepare("
                UPDATE item SET
                    sku = ?,
                    name = ?,
                    category_id = ?,
                    description = ?,
                    weight = ?,
                    weight_unit = ?,
                    dimensions = ?,
                    color = ?,
                    min_stock_level = ?,
                    max_stock_level = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['sku'],
                $_POST['name'],
                $_POST['category_id'],
                $_POST['description'],
                $_POST['weight'],
                $_POST['weight_unit'],
                $_POST['dimensions'],
                $_POST['color'],
                $_POST['min_stock_level'],
                $_POST['max_stock_level'],
                $_POST['item_id']
            ]);
            $_SESSION['success'] = "Item {$_POST['sku']} updated";
        }
        
        $db->commit();
        ob_end_clean(); // Clean the output buffer
        header("Location: edit_item.php");
        exit();
        
    } catch (PDOException $e) {
        $db->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}
?>

<div class="container">
    <h2><i class="bi bi-pencil-square"></i> Manage Inventory Items</h2>
    
    <!-- Search Bar -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-9">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search by SKU, Name or Category..." 
                           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Search
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <!-- Items Table -->
    <div class="table-responsive">
        <table class="table table-hover">
            <thead class="table-dark">
                <tr>
                    <th>SKU</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Min Stock</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['sku']) ?></td>
                    <td><?= htmlspecialchars($item['name']) ?></td>
                    <td><?= htmlspecialchars($item['category_name']) ?></td>
                    <td><?= $item['min_stock_level'] ?></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary edit-btn"
                                data-item='<?= htmlspecialchars(json_encode($item)) ?>'>
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="sku" value="<?= $item['sku'] ?>">
                            <button type="submit" name="delete_item" class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('ARE YOU SURE YOU WANT TO SEND IT TO LOGISTICS <?= $item['sku'] ?>?')">
                                <i class="bi bi-trash"></i> Remove
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Item</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="modal-body-content">
                        <!-- Dynamic content loaded via JS -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Edit button handler
document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const item = JSON.parse(this.dataset.item);
        const modal = new bootstrap.Modal(document.getElementById('editModal'));
        
        // Generate form HTML
        document.getElementById('modal-body-content').innerHTML = `
            <input type="hidden" name="item_id" value="${item.id}">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">SKU</label>
                    <input type="text" name="sku" class="form-control" value="${escapeHtml(item.sku)}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" value="${escapeHtml(item.name)}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-select" required>
                        <?php 
                        $categories = $db->query("SELECT id, name FROM category")->fetchAll();
                        foreach ($categories as $cat): 
                        ?>
                        <option value="<?= $cat['id'] ?>" ${item.category_id == <?= $cat['id'] ?> ? 'selected' : ''}>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control">${escapeHtml(item.description || '')}</textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Weight</label>
                    <input type="number" step="0.01" name="weight" class="form-control" value="${item.weight || ''}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Unit</label>
                    <select name="weight_unit" class="form-select">
                        <option value="kg" ${item.weight_unit == 'kg' ? 'selected' : ''}>kg</option>
                        <option value="g" ${item.weight_unit == 'g' ? 'selected' : ''}>g</option>
                        <option value="lb" ${item.weight_unit == 'lb' ? 'selected' : ''}>lb</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Dimensions</label>
                    <input type="text" name="dimensions" class="form-control" value="${escapeHtml(item.dimensions || '')}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Color</label>
                    <input type="text" name="color" class="form-control" value="${escapeHtml(item.color || '')}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Min Stock</label>
                    <input type="number" name="min_stock_level" class="form-control" value="${item.min_stock_level || ''}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Max Stock</label>
                    <input type="number" name="max_stock_level" class="form-control" value="${item.max_stock_level || ''}">
                </div>
            </div>
        `;
        
        modal.show();
    });
});

function escapeHtml(unsafe) {
    return unsafe?.toString()?.replace(/&/g, "&amp;")
                     .replace(/</g, "&lt;")
                     .replace(/>/g, "&gt;")
                     .replace(/"/g, "&quot;")
                     .replace(/'/g, "&#039;") || '';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>