<?php
ob_start();
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/header.php';

$db = DatabaseConnection::getInstance();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db->beginTransaction();
    try {
        if (isset($_POST['reject_po'])) {
            // Reject PO
            $stmt = $db->prepare("
                UPDATE warehouse_item_request 
                SET status = 'cancelled',
                    approved_by = ?,
                    approved_at = NOW(),
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$_SESSION['employee_id'], $_POST['po_id']]);
            $message = "PO #{$_POST['po_number']} has been cancelled";
            
        } elseif (isset($_POST['delete_po'])) {
            // Delete PO (soft delete)
            $stmt = $db->prepare("
                UPDATE warehouse_item_request 
                SET is_deleted = 1,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$_POST['po_id']]);
            $message = "PO #{$_POST['po_number']} has been deleted";
            
        } elseif (isset($_POST['clear_history'])) {
            // Clear cancelled/deleted POs
            $stmt = $db->prepare("
                DELETE FROM warehouse_item_request
                WHERE (status = 'cancelled' OR is_deleted = 1)
                AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stmt->execute();
            $count = $stmt->rowCount();
            $message = "Cleared $count old cancelled/deleted POs";
        }
        
        $db->commit();
        $_SESSION['success'] = $message;
        ob_end_clean(); // Clean the output buffer
        header("Location: po_list.php");
        exit();
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['error'] = "Error processing request: " . $e->getMessage();
        header("Location: po_list.php");
        exit();
    }
}

// Get all POs with filtering options
$statusFilter = $_GET['status'] ?? 'all';
$showDeleted = isset($_GET['show_deleted']);

$baseQuery = "
    SELECT 
        w.*, 
        e.first_name as requester_first, 
        e.last_name as requester_last,
        s.name as supplier_name,
        a.first_name as approver_first,
        a.last_name as approver_last
    FROM warehouse_item_request w
    JOIN employee e ON w.created_by = e.id
    LEFT JOIN supplier s ON w.supplier_id = s.id
    LEFT JOIN employee a ON w.approved_by = a.id
    WHERE (w.is_deleted = 0 OR w.is_deleted = ?)
";

// Apply status filter
$params = [$showDeleted ? 1 : 0];
if ($statusFilter !== 'all') {
    $baseQuery .= " AND w.status = ?";
    $params[] = $statusFilter;
}

$query = $baseQuery . " ORDER BY w.created_at DESC";
$pos = $db->prepare($query);
$pos->execute($params);
$pos = $pos->fetchAll();

// Get item counts for each PO
foreach ($pos as &$po) {
    $items = $db->prepare("
        SELECT COUNT(id) as item_count, SUM(quantity) as total_items 
        FROM purchase_order_item 
        WHERE po_id = ?
    ");
    $items->execute([$po['id']]);
    $itemData = $items->fetch();
    $po['item_count'] = $itemData['item_count'];
    $po['total_items'] = $itemData['total_items'];
}
unset($po); // Break the reference

// Check if there are cancellations/deletions to clear
$hasClearableItems = $db->query("
    SELECT COUNT(*) 
    FROM warehouse_item_request
    WHERE (status = 'cancelled' OR is_deleted = 1)
    AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
")->fetchColumn();
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4><i class="bi bi-list-ul"></i> All Purchase Requests</h4>
            <div>
                <?php if ($hasClearableItems): ?>
                <form method="POST" class="d-inline me-2">
                    <button type="submit" name="clear_history" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-trash"></i> Clear Old History
                    </button>
                </form>
                <?php endif; ?>
                
                <div class="dropdown d-inline">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown">
                        <i class="bi bi-funnel"></i> Filter: <?= ucfirst($statusFilter) ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="?status=all">All Requests</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="?status=draft">Draft</a></li>
                        <li><a class="dropdown-item" href="?status=approved">Approved</a></li>
                        <li><a class="dropdown-item" href="?status=sent">Sent</a></li>
                        <li><a class="dropdown-item" href="?status=received">Received</a></li>
                        <li><a class="dropdown-item" href="?status=cancelled">Cancelled</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="?status=all&show_deleted=1">
                                <i class="bi bi-trash"></i> Show Deleted
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="card-body">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>PO #</th>
                            <th>Status</th>
                            <th>Requester</th>
                            <th>Supplier</th>
                            <th>Items</th>
                            <th>Total Qty</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pos as $po): ?>
                        <tr class="<?= $po['is_deleted'] ? 'text-muted' : '' ?>">
                            <td>
                                <?php if (!$po['is_deleted']): ?>
                                <a href="view_po.php?id=<?= $po['id'] ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($po['po_number']) ?>
                                </a>
                                <?php else: ?>
                                <s><?= htmlspecialchars($po['po_number']) ?></s>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= 
                                    $po['is_deleted'] ? 'secondary' :
                                    ($po['status'] === 'approved' ? 'success' : 
                                    ($po['status'] === 'cancelled' ? 'danger' : 
                                    ($po['status'] === 'sent' ? 'info' : 
                                    ($po['status'] === 'received' ? 'primary' : 'warning')))) 
                                ?>">
                                    <?= $po['is_deleted'] ? 'Deleted' : ucfirst($po['status']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($po['requester_first'] . ' ' . $po['requester_last']) ?></td>
                            <td><?= $po['supplier_name'] ? htmlspecialchars($po['supplier_name']) : 'N/A' ?></td>
                            <td><?= $po['item_count'] ?></td>
                            <td><?= $po['total_items'] ?></td>
                            <td><?= date('M d, Y', strtotime($po['created_at'])) ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <?php if (!$po['is_deleted']): ?>
                                        <a href="view_po.php?id=<?= $po['id'] ?>" class="btn btn-outline-primary" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        
                                        <?php if ($po['status'] === 'draft'): ?>
                                            <button type="button" 
                                                    class="btn btn-outline-danger reject-btn" 
                                                    title="Reject"
                                                    data-poid="<?= $po['id'] ?>"
                                                    data-ponumber="<?= htmlspecialchars($po['po_number']) ?>">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($po['status'] !== 'draft' || $po['is_deleted']): ?>
                                            <button type="button" 
                                                    class="btn btn-outline-secondary delete-btn" 
                                                    title="Delete"
                                                    data-poid="<?= $po['id'] ?>"
                                                    data-ponumber="<?= htmlspecialchars($po['po_number']) ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="card-footer text-end">
            <a href="index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>

<!-- Rejection Confirmation Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="reject_po" value="1">
                <input type="hidden" name="po_id" id="reject_po_id">
                <input type="hidden" name="po_number" id="reject_po_number">
                
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Rejection</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to reject PO #<span id="reject_po_number_display"></span>?</p>
                    <p class="text-danger">This will cancel the purchase order.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Rejection</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="delete_po" value="1">
                <input type="hidden" name="po_id" id="delete_po_id">
                <input type="hidden" name="po_number" id="delete_po_number">
                
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete PO #<span id="delete_po_number_display"></span>?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Deletion</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle reject button clicks
    document.querySelectorAll('.reject-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('reject_po_id').value = this.dataset.poid;
            document.getElementById('reject_po_number').value = this.dataset.ponumber;
            document.getElementById('reject_po_number_display').textContent = this.dataset.ponumber;
            
            const modal = new bootstrap.Modal(document.getElementById('rejectModal'));
            modal.show();
        });
    });
    
    // Handle delete button clicks
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('delete_po_id').value = this.dataset.poid;
            document.getElementById('delete_po_number').value = this.dataset.ponumber;
            document.getElementById('delete_po_number_display').textContent = this.dataset.ponumber;
            
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>