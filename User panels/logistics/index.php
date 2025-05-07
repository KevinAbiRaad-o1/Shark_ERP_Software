<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/header.php';

$db = DatabaseConnection::getInstance();

// Get filter from URL or default to 'all'
$filter = $_GET['filter'] ?? 'all';

// Get counts for dashboard cards
$stats = $db->query("
    SELECT 
        SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count,
        SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent_count,
        SUM(CASE WHEN status = 'received' THEN 1 ELSE 0 END) as received_count,
        COUNT(*) as total_count
    FROM warehouse_item_request
    WHERE is_deleted = 0
")->fetch();

// Get recent activity with filtering
$query = "
    SELECT 
        w.id, 
        w.po_number, 
        w.status,
        e.first_name,
        e.last_name,
        e.email,
        w.created_at,
        w.updated_at,
        a.first_name as approver_first,
        a.last_name as approver_last,
        a.email as approver_email,
        w.expected_delivery_date,
        s.name as supplier_name,
        (SELECT COUNT(*) FROM purchase_order_item WHERE po_id = w.id) as item_count
    FROM warehouse_item_request w
    JOIN employee e ON w.created_by = e.id
    LEFT JOIN employee a ON w.approved_by = a.id
    LEFT JOIN supplier s ON w.supplier_id = s.id
    WHERE w.is_deleted = 0
";

// Apply filter if not 'all'
if ($filter !== 'all') {
    $query .= " AND w.status = ?";
    $params = [$filter];
} else {
    $params = [];
}

$query .= " ORDER BY w.updated_at DESC LIMIT 10";

$recentActivity = $db->prepare($query);
$recentActivity->execute($params);
$recentActivity = $recentActivity->fetchAll();

// Get quick stats for the last 30 days
$quickStats = $db->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m-%d') as day,
        COUNT(*) as count,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved
    FROM warehouse_item_request
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY day
    ORDER BY day ASC
")->fetchAll();
?>

<div class="container-fluid">
    <!-- Status Cards Row -->
    <div class="row mb-4">
        <div class="col-md-2 col-6 mb-3">
            <div class="card text-white bg-warning h-100">
                <div class="card-body text-center">
                    <h5 class="card-title"><i class="bi bi-hourglass"></i> Pending</h5>
                    <h2 class="card-text"><?= $stats['pending_count'] ?></h2>
                    <a href="approve_po.php" class="stretched-link text-white-50 small">View</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-2 col-6 mb-3">
            <div class="card text-white bg-success h-100">
                <div class="card-body text-center">
                    <h5 class="card-title"><i class="bi bi-check-circle"></i> Approved</h5>
                    <h2 class="card-text"><?= $stats['approved_count'] ?></h2>
                    <a href="po_list.php?status=approved" class="stretched-link text-white-50 small">View</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-2 col-6 mb-3">
            <div class="card text-white bg-danger h-100">
                <div class="card-body text-center">
                    <h5 class="card-title"><i class="bi bi-x-circle"></i> Cancelled</h5>
                    <h2 class="card-text"><?= $stats['cancelled_count'] ?></h2>
                    <a href="po_list.php?status=cancelled" class="stretched-link text-white-50 small">View</a>
                </div>
            </div>
        </div>       
        <div class="col-md-2 col-6 mb-3">
            <div class="card text-white bg-secondary h-100">
                <div class="card-body text-center">
                    <h5 class="card-title"><i class="bi bi-collection"></i> Total</h5>
                    <h2 class="card-text"><?= $stats['total_count'] ?></h2>
                    <a href="po_list.php" class="stretched-link text-white-50 small">View All</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats Row -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-bar-chart"></i> PO Activity (Last 30 Days)</h5>
                </div>
                <div class="card-body">
                    <canvas id="poActivityChart" height="250"></canvas>
                </div>
            </div>
        </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity Section -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Activity</h5>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown">
                    <i class="bi bi-funnel"></i> Filter: <?= ucfirst($filter) ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="?filter=all">All Requests</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="?filter=draft">Pending Only</a></li>
                    <li><a class="dropdown-item" href="?filter=approved">Approved Only</a></li>
                    <li><a class="dropdown-item" href="?filter=sent">Sent Only</a></li>
                    <li><a class="dropdown-item" href="?filter=received">Received Only</a></li>
                    <li><a class="dropdown-item" href="?filter=cancelled">Cancelled Only</a></li>
                </ul>
            </div>
        </div>
        
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>PO #</th>
                            <th>Status</th>
                            <th>Requester</th>
                            <th>Supplier</th>
                            <th>Items</th>
                            <th>Updated</th>
                            <th>Expected Delivery</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentActivity as $activity): ?>
                        <tr>
                            <td>
                                <a href="view_po.php?id=<?= $activity['id'] ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($activity['po_number']) ?>
                                </a>
                            </td>
                            <td>
                                <span class="badge bg-<?= 
                                    $activity['status'] === 'approved' ? 'success' : 
                                    ($activity['status'] === 'cancelled' ? 'danger' : 
                                    ($activity['status'] === 'sent' ? 'info' : 
                                    ($activity['status'] === 'received' ? 'primary' : 'warning'))) 
                                ?>">
                                    <?= ucfirst($activity['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?= htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']) ?>
                                <small class="text-muted d-block"><?= htmlspecialchars($activity['email']) ?></small>
                            </td>
                            <td><?= $activity['supplier_name'] ? htmlspecialchars($activity['supplier_name']) : 'N/A' ?></td>
                            <td><?= $activity['item_count'] ?></td>
                            <td>
                                <?= date('M d, Y', strtotime($activity['updated_at'])) ?>
                                <small class="text-muted d-block"><?= date('h:i A', strtotime($activity['updated_at'])) ?></small>
                            </td>
                            <td>
                                <?= $activity['expected_delivery_date'] ? 
                                    date('M d, Y', strtotime($activity['expected_delivery_date'])) : 
                                    '<span class="text-muted">Not set</span>' ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="view_po.php?id=<?= $activity['id'] ?>" 
                                       class="btn btn-outline-primary" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if ($activity['status'] === 'draft'):?>
                                    <a href="approve_po.php?id=<?= $activity['id'] ?>" 
                                       class="btn btn-outline-success" title="Process">
                                        <i class="bi bi-check-circle"></i>
                                    </a>
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
            <a href="po_list.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-list-ul"></i> View All Requests
            </a>
        </div>
    </div>
</div>

<!-- Chart.js for PO Activity -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // PO Activity Chart
    const ctx = document.getElementById('poActivityChart').getContext('2d');
    const poActivityChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($quickStats, 'day')) ?>,
            datasets: [
                {
                    label: 'Total POs',
                    data: <?= json_encode(array_column($quickStats, 'count')) ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Approved POs',
                    data: <?= json_encode(array_column($quickStats, 'approved')) ?>,
                    backgroundColor: 'rgba(75, 192, 192, 0.7)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.raw;
                        }
                    }
                }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>