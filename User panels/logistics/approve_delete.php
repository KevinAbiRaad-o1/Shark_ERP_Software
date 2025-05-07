<?php
ob_start();
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/header.php';

$db = DatabaseConnection::getInstance();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['bulk_action']) && !empty($_POST['ids'])) {
            // Bulk actions
            $ids = $_POST['ids'];
            $action = $_POST['bulk_action'];
            $reason = $_POST['reason'] ?? 'Bulk action';

            $db->beginTransaction();

            if ($action === 'approve') {
                // Delete items
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $db->prepare("DELETE FROM item WHERE id IN ($placeholders)");
                $stmt->execute($ids);

                // Insert logs
                $logStmt = $db->prepare("INSERT INTO item_deletion_log 
                    (item_id, action, user_id, reason) 
                    VALUES (?, 'approved', ?, ?)");
                
                foreach ($ids as $id) {
                    $logStmt->execute([$id, $_SESSION['user_id'], $reason]);
                }

                $_SESSION['success_message'] = "Deleted " . count($ids) . " items permanently";
            } 
            elseif ($action === 'reject') {
                // Reactivate items
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $db->prepare("UPDATE item SET is_active = 1 WHERE id IN ($placeholders)");
                $stmt->execute($ids);

                $_SESSION['success_message'] = "Reactivated " . count($ids) . " items";
            }

            $db->commit();
        }
        elseif (isset($_POST['id'])) {
            // Single item action
            $id = $_POST['id'];
            $reason = $_POST['reason'] ?? '';

            $db->beginTransaction();

            if (isset($_POST['approve'])) {
                // Check dependencies
                $stmt = $db->prepare("
                    SELECT EXISTS(SELECT 1 FROM inventory WHERE item_id = ?) OR
                           EXISTS(SELECT 1 FROM purchase_order_item WHERE item_id = ?)
                ");
                $stmt->execute([$id, $id]);
                $hasDependencies = $stmt->fetchColumn();

                if ($hasDependencies) {
                    throw new Exception("Item cannot be deleted - exists in inventory or purchase orders");
                }

                // Delete item
                $stmt = $db->prepare("DELETE FROM item WHERE id = ?");
                $stmt->execute([$id]);

                if ($stmt->rowCount() === 0) {
                    throw new Exception("Item not found or already deleted");
                }

                // Log deletion
                $stmt = $db->prepare("INSERT INTO item_deletion_log 
                    (item_id, action, user_id, reason) 
                    VALUES (?, 'approved', ?, ?)");
                $stmt->execute([$id, $_SESSION['user_id'], $reason]);

                $_SESSION['success_message'] = "Item deleted permanently";
            } 
            elseif (isset($_POST['reject'])) {
                // Reactivate item
                $stmt = $db->prepare("UPDATE item SET is_active = 1 WHERE id = ?");
                $stmt->execute([$id]);

                // Log rejection
                $stmt = $db->prepare("INSERT INTO item_deletion_log 
                    (item_id, action, user_id, reason) 
                    VALUES (?, 'rejected', ?, ?)");
                $stmt->execute([$id, $_SESSION['user_id'], $reason]);

                $_SESSION['success_message'] = "Item reactivated successfully";
            }

            $db->commit();
        }
    } catch (PDOException $e) {
        $db->rollBack();
        $_SESSION['error_message'] = "Database error: " . $e->getMessage();
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['error_message'] = $e->getMessage();
    }
    ob_end_clean(); // Clean the output buffer
    sleep(1); // Added delay before redirect
    header("Location: approve_delete.php");
    exit();
}

// Get pending deletions
$pendingDeletions = $db->query("
    SELECT i.*, c.name as category_name,
           (SELECT COUNT(*) FROM inventory WHERE item_id = i.id) as inventory_count,
           (SELECT COUNT(*) FROM purchase_order_item WHERE item_id = i.id) as po_count
    FROM item i
    JOIN category c ON i.category_id = c.id
    WHERE i.is_active = 0
    ORDER BY i.updated_at DESC
")->fetchAll();
?>

<div class="container-fluid">
    <?php include __DIR__ . '/includes/alerts.php'; ?>

    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-trash3-fill"></i> Pending Deletion Approvals</h5>
        </div>
        
        <div class="card-body">
            <?php if (empty($pendingDeletions)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-check2-circle"></i> No pending deletion requests found.
                </div>
            <?php else: ?>
                <form method="POST" id="bulkForm">
                    <div class="mb-3">
                        <div class="btn-group">
                            <button type="button" class="btn btn-success" 
                                onclick="confirmBulkAction('approve')">
                                <i class="bi bi-check2-all"></i> Approve Selected
                            </button>
                            <button type="button" class="btn btn-danger" 
                                onclick="confirmBulkAction('reject')">
                                <i class="bi bi-x-circle"></i> Reject Selected
                            </button>
                        </div>
                        <span class="ms-3 text-muted" id="selectedCount">0 items selected</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 30px;">
                                        <input type="checkbox" id="selectAll">
                                    </th>
                                    <th>Item Name</th>
                                    <th>Category</th>
                                    <th>Inventory</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingDeletions as $item): ?>
                                <tr class="<?= ($item['inventory_count'] > 0) ? 'table-warning' : '' ?>">
                                    <td>
                                        <input type="checkbox" name="ids[]" 
                                            value="<?= $item['id'] ?>" 
                                            class="item-checkbox">
                                    </td>
                                    <td><?= htmlspecialchars($item['name']) ?></td>
                                    <td><?= htmlspecialchars($item['category_name']) ?></td>
                                    <td>
                                        <?= $item['inventory_count'] ?>
                                        <?php if ($item['inventory_count'] > 0): ?>
                                            <i class="bi bi-exclamation-triangle text-danger"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-success"
                                                data-bs-toggle="modal" data-bs-target="#actionModal"
                                                data-id="<?= $item['id'] ?>"
                                                data-action="approve"
                                                data-name="<?= htmlspecialchars($item['name']) ?>"
                                                data-inventory="<?= $item['inventory_count'] ?>">
                                                <i class="bi bi-check-circle"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger"
                                                data-bs-toggle="modal" data-bs-target="#actionModal"
                                                data-id="<?= $item['id'] ?>"
                                                data-action="reject"
                                                data-name="<?= htmlspecialchars($item['name']) ?>">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <input type="hidden" name="bulk_action" id="bulkAction">
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Action Modal -->
<div class="modal fade" id="actionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="itemActionForm">
                <div class="modal-body">
                    <input type="hidden" name="id" id="modalItemId">
                    
                    <div id="dependenciesWarning" class="alert alert-warning d-none">
                        <i class="bi bi-exclamation-triangle"></i> 
                        This item has inventory or purchase order references!
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Reason (optional for rejection)</label>
                        <textarea name="reason" class="form-control" rows="3" 
                                  placeholder="Enter reason for this action..." id="reasonField"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="approve" class="btn btn-danger d-none" id="approveBtn">Confirm Deletion</button>
                    <button type="submit" name="reject" class="btn btn-primary d-none" id="rejectBtn">Confirm Rejection</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Bulk Actions
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.item-checkbox');
    const selectAll = document.getElementById('selectAll');
    const selectedCount = document.getElementById('selectedCount');

    function updateSelection() {
        const checked = document.querySelectorAll('.item-checkbox:checked');
        selectedCount.textContent = `${checked.length} items selected`;
    }

    selectAll.addEventListener('change', function() {
        checkboxes.forEach(cb => cb.checked = this.checked);
        updateSelection();
    });

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateSelection);
    });
});

function confirmBulkAction(action) {
    const form = document.getElementById('bulkForm');
    const checked = document.querySelectorAll('.item-checkbox:checked');
    
    if (checked.length === 0) {
        alert('Please select at least one item');
        return;
    }
    
    if (action === 'approve' && !confirm(`Permanently delete ${checked.length} items?`)) {
        return;
    }
    
    document.getElementById('bulkAction').value = action;
    form.submit();
}

// Single Action Modal
const actionModal = document.getElementById('actionModal');
const itemActionForm = document.getElementById('itemActionForm');
const reasonField = document.getElementById('reasonField');

actionModal.addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    const id = button.getAttribute('data-id');
    const action = button.getAttribute('data-action');
    const name = button.getAttribute('data-name');
    const inventory = button.getAttribute('data-inventory');

    const modal = this;
    modal.querySelector('#modalItemId').value = id;
    
    const title = action === 'approve' 
        ? `Approve Deletion: ${name}` 
        : `Reject Deletion: ${name}`;
        
    modal.querySelector('#modalTitle').textContent = title;
    
    // Show appropriate button
    const approveBtn = modal.querySelector('#approveBtn');
    const rejectBtn = modal.querySelector('#rejectBtn');
    approveBtn.classList.toggle('d-none', action !== 'approve');
    rejectBtn.classList.toggle('d-none', action !== 'reject');
    
    // Show warning if inventory exists
    const warning = modal.querySelector('#dependenciesWarning');
    warning.classList.toggle('d-none', inventory <= 0 || action !== 'approve');
    
    // Make reason optional for rejection
    reasonField.required = action === 'approve';
});

// Handle form submission
itemActionForm.addEventListener('submit', function(e) {
    const approveBtn = document.getElementById('approveBtn');
    if (!approveBtn.classList.contains('d-none') && !reasonField.value) {
        e.preventDefault();
        alert('Please enter a reason for approval');
        reasonField.focus();
    }
    // Rejection can proceed without reason
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>