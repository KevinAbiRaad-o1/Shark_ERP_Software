<?php
ob_start(); // Add this line
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/header.php';

$db = DatabaseConnection::getInstance();

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db->beginTransaction();
    try {
        $newStatus = $_POST['action'] === 'approved' ? 'approved' : 'cancelled';
        
        // Only require supplier for approvals, not rejections
        if ($newStatus === 'approved' && empty($_POST['supplier_id'])) {
            throw new Exception("You must select a supplier when approving");
        }

        // Update the PO with approval info
        $stmt = $db->prepare("
            UPDATE warehouse_item_request SET
                status = ?,
                supplier_id = ?,
                approved_by = ?,
                approved_at = NOW(),
                payment_terms = ?,
                shipping_method = ?,
                updated_at = NOW()
            WHERE id = ?
        ");

        $stmt->execute([
            $newStatus,
            // Only set supplier for approvals, null for rejections
            $newStatus === 'approved' ? $_POST['supplier_id'] : null,
            $_SESSION['employee_id'],
            // Only set payment terms and shipping for approvals
            $newStatus === 'approved' ? $_POST['payment_terms'] : null,
            $newStatus === 'approved' ? $_POST['shipping_method'] : null,
            $_POST['po_id']
        ]);

        // Only log approval in logistics_po_approval table
        if ($newStatus === 'approved') {
            $approvalStmt = $db->prepare("
                INSERT INTO logistics_po_approval (
                    warehouse_request_id,
                    supplier_id,
                    approved_by
                ) VALUES (?, ?, ?)
            ");
            $approvalStmt->execute([
                $_POST['po_id'],
                $_POST['supplier_id'],
                $_SESSION['employee_id']
            ]);
        }
        
     $db->commit();
$_SESSION['success'] = "PO #{$_POST['po_number']} has been " . $newStatus;
ob_end_clean(); // Clean the output buffer
header("Location: approve_po.php");
exit();
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['error'] = $e->getMessage();
        header("Location: approve_po.php?id=" . $_POST['po_id']);
        exit();
    }
}

// Get pending POs (exclude already approved/cancelled)
$pendingPOs = $db->query("
    SELECT 
        w.*, 
        e.first_name, 
        e.last_name,
        e.email,
        COUNT(poi.id) as item_count,
        SUM(poi.quantity) as total_items
    FROM warehouse_item_request w
    JOIN employee e ON w.created_by = e.id
    LEFT JOIN purchase_order_item poi ON w.id = poi.po_id
    WHERE w.status = 'draft'
    GROUP BY w.id
    ORDER BY w.created_at
")->fetchAll();

// Get all active suppliers for dropdown
$suppliers = $db->query("SELECT id, name, supplier_code FROM supplier WHERE is_active = 1 ORDER BY name")->fetchAll();

// Get specific PO if ID is provided in URL
$currentPO = null;
if (isset($_GET['id'])) {
    $poStmt = $db->prepare("
        SELECT w.*, e.first_name, e.last_name, e.email
        FROM warehouse_item_request w
        JOIN employee e ON w.created_by = e.id
        WHERE w.id = ? AND w.status = 'draft'
    ");
    $poStmt->execute([$_GET['id']]);
    $currentPO = $poStmt->fetch();
    
    if ($currentPO) {
        $itemsStmt = $db->prepare("
            SELECT poi.*, i.sku, i.name, i.description
            FROM purchase_order_item poi
            JOIN item i ON poi.item_id = i.id
            WHERE poi.po_id = ?
        ");
        $itemsStmt->execute([$_GET['id']]);
        $currentPO['items'] = $itemsStmt->fetchAll();
    }
}
?>

<div class="container">
    <h2 class="mb-4"><i class="bi bi-file-earmark-check"></i> Pending Purchase Order Approvals</h2>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (empty($pendingPOs) && !isset($currentPO)): ?>
        <div class="alert alert-info">No pending purchase orders to approve</div>
    <?php else: ?>
        <div class="list-group">
            <?php foreach ($pendingPOs as $po): ?>
            <div class="list-group-item mb-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="me-3">
                        <h5 class="mb-1">PO #<?= htmlspecialchars($po['po_number']) ?></h5>
                        <small class="text-muted">
                            Requested by <?= htmlspecialchars($po['first_name'] . ' ' . $po['last_name']) ?>
                            (<?= htmlspecialchars($po['email']) ?>)
                        </small>
                        <div class="mt-2">
                            <span class="badge bg-secondary me-2">
                                <?= $po['item_count'] ?> items
                            </span>
                            <span class="badge bg-secondary">
                                <?= $po['total_items'] ?> units total
                            </span>
                        </div>
                        <?php if ($po['expected_delivery_date']): ?>
                        <div class="mt-1">
                            <i class="bi bi-calendar"></i> 
                            Expected: <?= date('M d, Y', strtotime($po['expected_delivery_date'])) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" 
                            data-bs-target="#approveModal<?= $po['id'] ?>">
                        <i class="bi bi-pencil-square"></i> Review
                    </button>
                </div>
                
                <!-- Approval Modal - Updated Version -->
                <div class="modal fade" id="approveModal<?= $po['id'] ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <form method="POST" id="poForm<?= $po['id'] ?>">
                                <input type="hidden" name="po_id" value="<?= $po['id'] ?>">
                                <input type="hidden" name="po_number" value="<?= $po['po_number'] ?>">
                                
                                <div class="modal-header">
                                    <h5 class="modal-title">Process PO #<?= $po['po_number'] ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div id="approvalFields<?= $po['id'] ?>">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Supplier <span class="text-danger">*</span></label>
                                                <select name="supplier_id" class="form-select" id="supplierSelect<?= $po['id'] ?>">
                                                    <option value="">Select a supplier</option>
                                                    <?php foreach ($suppliers as $supplier): ?>
                                                    <option value="<?= $supplier['id'] ?>">
                                                        <?= htmlspecialchars($supplier['name']) ?> (<?= $supplier['supplier_code'] ?>)
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Payment Terms <span class="text-danger">*</span></label>
                                                <select name="payment_terms" class="form-select" id="paymentTerms<?= $po['id'] ?>">
                                                    <option value="">Select payment terms</option>
                                                    <option value="NET30">NET 30</option>
                                                    <option value="NET60">NET 60</option>
                                                    <option value="Upon Delivery">Upon Delivery</option>
                                                    <option value="Advance Payment">Advance Payment</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Shipping Method <span class="text-danger">*</span></label>
                                                <select name="shipping_method" class="form-select" id="shippingMethod<?= $po['id'] ?>">
                                                    <option value="">Select shipping method</option>
                                                    <option value="Ground">Ground</option>
                                                    <option value="Air">Air</option>
                                                    <option value="Sea">Sea</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <h6>Items in this order:</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>SKU</th>
                                                    <th>Item</th>
                                                    <th>Description</th>
                                                    <th>Qty</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $items = $db->prepare("
                                                    SELECT i.sku, i.name, i.description, poi.quantity
                                                    FROM purchase_order_item poi
                                                    JOIN item i ON poi.item_id = i.id
                                                    WHERE poi.po_id = ?
                                                ");
                                                $items->execute([$po['id']]);
                                                while ($item = $items->fetch()): 
                                                ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($item['sku']) ?></td>
                                                    <td><?= htmlspecialchars($item['name']) ?></td>
                                                    <td><?= htmlspecialchars($item['description']) ?></td>
                                                    <td><?= $item['quantity'] ?></td>
                                                </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <?php if ($po['notes']): ?>
                                    <div class="alert alert-light mt-3">
                                        <strong>Requester Notes:</strong>
                                        <?= nl2br(htmlspecialchars($po['notes'])) ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" name="action" value="cancelled" class="btn btn-danger" 
                                        onclick="prepareRejection('<?= $po['id'] ?>')">
                                        <i class="bi bi-x-circle"></i> Reject
                                    </button>
                                    <button type="submit" name="action" value="approved" class="btn btn-success"
                                        onclick="return validateApproval('<?= $po['id'] ?>')">
                                        <i class="bi bi-check-circle"></i> Approve
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (isset($currentPO)): ?>
            <!-- Display single PO if accessed directly via ID -->
            <div class="card mt-4">
                <div class="card-header">
                    <h4>PO #<?= htmlspecialchars($currentPO['po_number']) ?></h4>
                </div>
                <div class="card-body">
                    <form method="POST" id="singlePoForm">
                        <input type="hidden" name="po_id" value="<?= $currentPO['id'] ?>">
                        <input type="hidden" name="po_number" value="<?= $currentPO['po_number'] ?>">
                        
                        <div id="approvalFields">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Supplier <span class="text-danger">*</span></label>
                                    <select name="supplier_id" class="form-select" id="singleSupplierSelect">
                                        <option value="">Select a supplier</option>
                                        <?php foreach ($suppliers as $supplier): ?>
                                        <option value="<?= $supplier['id'] ?>">
                                            <?= htmlspecialchars($supplier['name']) ?> (<?= $supplier['supplier_code'] ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Payment Terms <span class="text-danger">*</span></label>
                                    <select name="payment_terms" class="form-select" id="singlePaymentTerms">
                                        <option value="">Select payment terms</option>
                                        <option value="NET30">NET 30</option>
                                        <option value="NET60">NET 60</option>
                                        <option value="Upon Delivery">Upon Delivery</option>
                                        <option value="Advance Payment">Advance Payment</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Shipping Method <span class="text-danger">*</span></label>
                                    <select name="shipping_method" class="form-select" id="singleShippingMethod">
                                        <option value="">Select shipping method</option>
                                        <option value="Ground">Ground</option>
                                        <option value="Air">Air</option>
                                        <option value="Sea">Sea</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <h5>Items in this order:</h5>
                        <div class="table-responsive mb-4">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>SKU</th>
                                        <th>Item</th>
                                        <th>Description</th>
                                        <th>Qty</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($currentPO['items'] as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['sku']) ?></td>
                                        <td><?= htmlspecialchars($item['name']) ?></td>
                                        <td><?= htmlspecialchars($item['description']) ?></td>
                                        <td><?= $item['quantity'] ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if ($currentPO['notes']): ?>
                        <div class="alert alert-light mb-4">
                            <strong>Requester Notes:</strong>
                            <?= nl2br(htmlspecialchars($currentPO['notes'])) ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="d-flex justify-content-end">
                            <button type="submit" name="action" value="cancelled" class="btn btn-danger me-2"
                                onclick="prepareSingleRejection()">
                                <i class="bi bi-x-circle"></i> Reject
                            </button>
                            <button type="submit" name="action" value="approved" class="btn btn-success"
                                onclick="return validateSingleApproval()">
                                <i class="bi bi-check-circle"></i> Approve
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
// Handle modal forms
function prepareRejection(poId) {
    // Clear validation for rejection
    const form = document.getElementById('poForm' + poId);
    form.querySelectorAll('[required]').forEach(field => {
        field.removeAttribute('required');
    });
    return true;
}

function validateApproval(poId) {
    const supplier = document.getElementById('supplierSelect' + poId);
    const paymentTerms = document.getElementById('paymentTerms' + poId);
    const shippingMethod = document.getElementById('shippingMethod' + poId);
    
    if (!supplier.value) {
        alert('You must select a supplier before approving');
        supplier.focus();
        return false;
    }
    if (!paymentTerms.value) {
        alert('You must select payment terms before approving');
        paymentTerms.focus();
        return false;
    }
    if (!shippingMethod.value) {
        alert('You must select a shipping method before approving');
        shippingMethod.focus();
        return false;
    }
    return true;
}

// Handle single PO form (when accessed directly via ID)
function prepareSingleRejection() {
    const form = document.getElementById('singlePoForm');
    form.querySelectorAll('[required]').forEach(field => {
        field.removeAttribute('required');
    });
    return true;
}

function validateSingleApproval() {
    const supplier = document.getElementById('singleSupplierSelect');
    const paymentTerms = document.getElementById('singlePaymentTerms');
    const shippingMethod = document.getElementById('singleShippingMethod');
    
    if (!supplier.value) {
        alert('You must select a supplier before approving');
        supplier.focus();
        return false;
    }
    if (!paymentTerms.value) {
        alert('You must select payment terms before approving');
        paymentTerms.focus();
        return false;
    }
    if (!shippingMethod.value) {
        alert('You must select a shipping method before approving');
        shippingMethod.focus();
        return false;
    }
    return true;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>