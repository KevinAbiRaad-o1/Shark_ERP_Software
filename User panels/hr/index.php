<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/header.php';

// Verify HR role
$db = DatabaseConnection::getInstance();
$stmt = $db->prepare("SELECT role_type FROM roles WHERE employee_id = ?");
$stmt->execute([$_SESSION['employee_id']]);
$role = $stmt->fetchColumn();

if ($role !== 'hr') {
    header("Location: ../../unauthorized.php");
    exit();
}

// Get HR-specific stats
$stats = $db->query("
    SELECT 
        COUNT(*) as total_employees,
        SUM(CASE WHEN department_id IS NULL THEN 1 ELSE 0 END) as unassigned_employees,
        (SELECT COUNT(*) FROM department) as total_departments
    FROM employee
    WHERE is_active = 1
")->fetch();

// Get recent employee changes
$recentChanges = $db->query("
    SELECT 
        e.id, e.first_name, e.last_name, e.email,
        d.department_name,
        e.hire_date,
        e.updated_at,
        u.username
    FROM employee e
    LEFT JOIN department d ON e.department_id = d.department_id
    LEFT JOIN users u ON e.person_id = u.person_id
    ORDER BY e.updated_at DESC
    LIMIT 10
")->fetchAll();
?>

<div class="container-fluid">
    <!-- HR Dashboard Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="bi bi-people-fill text-primary"></i> HR Panel
        </h1>
    </div>

    <!-- Stats Cards Row -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card text-white bg-primary h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title">Total Employees</h5>
                            <h2 class="card-text mb-0"><?= $stats['total_employees'] ?></h2>
                        </div>
                        <i class="bi bi-people-fill fs-1 opacity-50"></i>
                    </div>
                    <a href="employee_list.php" class="stretched-link"></a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card text-white bg-warning h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title">Unassigned</h5>
                            <h2 class="card-text mb-0"><?= $stats['unassigned_employees'] ?></h2>
                            <small class="opacity-75">Employees without department</small>
                        </div>
                        <i class="bi bi-exclamation-triangle-fill fs-1 opacity-50"></i>
                    </div>
                    <a href="employee_list.php?filter=unassigned" class="stretched-link"></a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card text-white bg-success h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title">Departments</h5>
                            <h2 class="card-text mb-0"><?= $stats['total_departments'] ?></h2>
                        </div>
                        <i class="bi bi-building fs-1 opacity-50"></i>
                    </div>
                    <a href="index.php" class="stretched-link"></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity Section -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Employee Changes</h5>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown">
                    <i class="bi bi-funnel"></i> Filter
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="?filter=all">All Changes</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="?filter=recent">Last 7 Days</a></li>
                    <li><a class="dropdown-item" href="?filter=department">By Department</a></li>
                </ul>
            </div>
        </div>
        
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Hire Date</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentChanges as $employee): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?></strong>
                                <div class="text-muted small"><?= htmlspecialchars($employee['email']) ?></div>
                            </td>
                            <td><?= $employee['department_name'] ? htmlspecialchars($employee['department_name']) : '<span class="text-warning">Unassigned</span>' ?></td>
                            <td><?= $employee['username'] ? htmlspecialchars($employee['username']) : 'N/A' ?></td>
                            <td><?= date('M d, Y', strtotime($employee['hire_date'])) ?></td>
                            <td>
                                <?= date('M d, Y', strtotime($employee['updated_at'])) ?>
                                <div class="text-muted small"><?= date('h:i A', strtotime($employee['updated_at'])) ?></div>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="view_employee.php?id=<?= $employee['id'] ?>" class="btn btn-outline-primary" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="update_employee.php?id=<?= $employee['id'] ?>" class="btn btn-outline-secondary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="card-footer text-end">
            <a href="employee_list.php" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-list-ul"></i> View All Employees
            </a>
        </div>
    </div>

    <!-- Upcoming Events Section -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-calendar-event"></i> Upcoming HR Events</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> This section will display upcoming events like birthdays, anniversaries, etc.
            </div>
            <!-- Will be populated with actual events in future updates -->
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>