<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/header.php';

$db = DatabaseConnection::getInstance();

// Check if user is HR
$stmt = $db->prepare("SELECT role_type FROM roles WHERE employee_id = ?");
$stmt->execute([$_SESSION['employee_id']]);
$role = $stmt->fetchColumn();

if ($role !== 'hr') {
    header("Location: ../../unauthorized.php");
    exit();
}

// Filters
$allowedFilters = ['all', 'active', 'inactive', 'unassigned'];
$filter = isset($_GET['filter']) && in_array($_GET['filter'], $allowedFilters) ? $_GET['filter'] : 'all';
$search = $_GET['search'] ?? '';

// Build SQL query
$query = "
SELECT e.*, d.department_name, r.role_type, u.username, u.user_id ,u.password
FROM employee e
LEFT JOIN department d ON e.department_id = d.department_id
LEFT JOIN roles r ON e.id = r.employee_id
LEFT JOIN users u ON e.person_id = u.person_id
WHERE 1=1
";

if ($filter === 'active') {
    $query .= " AND e.is_active = 1";
} elseif ($filter === 'inactive') {
    $query .= " AND e.is_active = 0";
} elseif ($filter === 'unassigned') {
    $query .= " AND e.department_id IS NULL";
}


if (!empty($search)) {
    $query .= " AND (e.first_name LIKE ? OR e.last_name LIKE ? OR e.email LIKE ?)";
    $params = array_fill(0, 3, "%$search%");
}

    $params=[

    ];

$query .= " ORDER BY e.last_name, e.first_name";
$employees = $db->prepare($query);
$employees->execute($params);
$employees = $employees->fetchAll();

?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
            <h4 class="mb-0"><i class="bi bi-people"></i> Employee Directory</h4>
            <div>
                <form class="d-inline me-2" method="GET">
                    <div class="input-group">
                        <select class="form-select" name="filter">
                            <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All Employees</option>
                            <option value="active" <?= $filter === 'active' ? 'selected' : '' ?>>Active Only</option>
                            <option value="inactive" <?= $filter === 'inactive' ? 'selected' : '' ?>>Inactive Only</option>
                            <option value="unassigned" <?= $filter === 'unassigned' ? 'selected' : '' ?>>Unassigned</option>
                        </select>
                        <input type="text" class="form-control" name="search" placeholder="Search employees..." value="<?= htmlspecialchars($search) ?>">
                        <button class="btn btn-outline-light" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card-body">
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success_message']) ?></div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error_message']) ?></div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>
            
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="w-20">Name</th>
                            <th class="w-15">Department</th>
                            <th class="w-15">Role</th>
                            <th class="w-10">Status</th>
                            <th class="w-15">Email</th>
                            <th class="w-10">Phone</th>
                            <th class="w-10">Hire Date</th>
                            <th class="w-5">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
<?php foreach ($employees as $employee): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0 me-2">
                                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" 
                                             style="width: 40px; height: 40px;">
                                            <i class="bi bi-person-fill" style="font-size: 1.2rem; color: #6c757d;"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <strong><?= htmlspecialchars($employee['first_name'] ?? '') ?> <?= htmlspecialchars($employee['last_name'] ?? '') ?></strong>
                                    </div>
                                </div>
                            </td>
                            <td><?= isset($employee['department_name']) ? htmlspecialchars($employee['department_name']) : '<span class="text-warning">Unassigned</span>' ?></td>
                            <td>
                                <span class="badge bg-info text-dark">
                                    <?= isset($employee['role_type']) ? htmlspecialchars($employee['role_type']) : 'N/A' ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?= $employee['is_active'] ? 'success' : 'danger' ?>">
                                    <?= $employee['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td><?= isset($employee['email']) ? htmlspecialchars($employee['email']) : 'N/A' ?></td>
                            <td><?= isset($employee['phone']) ? htmlspecialchars($employee['phone']) : 'N/A' ?></td>
                            <td><?= isset($employee['hire_date']) ? date('M d, Y', strtotime($employee['hire_date'])) : 'N/A' ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="view_employee.php?id=<?= $employee['id'] ?? '' ?>" class="btn btn-outline-primary" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-secondary" title="Edit" data-bs-toggle="modal" data-bs-target="#editEmployeeModal<?= $employee['id'] ?? '' ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <?php if (($employee['role_type'] ?? '') !== 'owner'): ?>
                                        <button type="button" class="btn btn-outline-danger" title="Delete" data-bs-toggle="modal" data-bs-target="#deleteEmployeeModal<?= $employee['id'] ?? '' ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
<?php endif; ?>
                                </div>

                                <!-- Edit Modal -->
<!-- Edit Modal -->
<?php if (isset($employee['id'])): ?>
<div class="modal fade" id="editEmployeeModal<?= $employee['id'] ?>" tabindex="-1" aria-labelledby="editEmployeeModalLabel<?= $employee['id'] ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Edit Employee: <?= htmlspecialchars($employee['first_name'] ?? '') ?> <?= htmlspecialchars($employee['last_name'] ?? '') ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="update_employee.php" method="POST">
                <input type="hidden" name="employee_id" value="<?= $employee['id'] ?>">
                
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">First Name *</label>
                            <input type="text" class="form-control" name="first_name" value="<?= htmlspecialchars($employee['first_name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name *</label>
                            <input type="text" class="form-control" name="last_name" value="<?= htmlspecialchars($employee['last_name'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($employee['email'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone" value="<?= htmlspecialchars($employee['phone'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Department</label>
                            <select class="form-select" name="department_id">
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['department_id'] ?>" <?= ($employee['department_id'] ?? '') == $dept['department_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dept['department_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Hire Date *</label>
                            <input type="date" class="form-control" name="hire_date" value="<?= htmlspecialchars($employee['hire_date'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-control" name="address_line" value="<?= htmlspecialchars($employee['address_line'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">City</label>
                            <input type="text" class="form-control" name="city" value="<?= htmlspecialchars($employee['city'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Role *</label>
                            <select class="form-select" name="role_type" required>
                                <option value="">Select Role</option>
                                <option value="hr" <?= ($employee['role_type'] ?? '') === 'hr' ? 'selected' : '' ?>>HR</option>
                                <option value="accounting" <?= ($employee['role_type'] ?? '') === 'accounting' ? 'selected' : '' ?>>Accounting</option>
                                <option value="warehouse" <?= ($employee['role_type'] ?? '') === 'warehouse' ? 'selected' : '' ?>>Warehouse</option>
                                <option value="logistics" <?= ($employee['role_type'] ?? '') === 'logistics' ? 'selected' : '' ?>>Logistics</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="is_active">
                                <option value="1" <?= ($employee['is_active'] ?? 0) ? 'selected' : '' ?>>Active</option>
                                <option value="0" <?= !($employee['is_active'] ?? 0) ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">New Password (leave blank to keep current)</label>
                            <input type="password" class="form-control" name="password">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

                                <!-- Delete Modal -->
                                <?php if (isset($employee['id'])): ?>
                                <div class="modal fade" id="deleteEmployeeModal<?= $employee['id'] ?>" tabindex="-1" aria-labelledby="deleteEmployeeModalLabel<?= $employee['id'] ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title">Delete Employee</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form action="delete_employee.php" method="POST">
                                                <div class="modal-body">
                                                    <p>Are you sure you want to permanently delete <strong><?= htmlspecialchars($employee['first_name'] ?? '') ?> <?= htmlspecialchars($employee['last_name'] ?? '') ?></strong>?</p>
                                                    <p class="text-danger"><strong>Warning:</strong> This will permanently remove all related records!</p>
                                                    
                                                    <div class="mb-3">
                                                        <label for="hrPassword" class="form-label">Enter your password to confirm:</label>
                                                        <input type="password" class="form-control" name="hr_password" required>
                                                    </div>
                                                    <input type="hidden" name="employee_id" value="<?= $employee['id'] ?>">
                                                    <input type="hidden" name="user_id" value="<?= $employee['user_id'] ?? '' ?>">
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-danger">
                                                        <i class="bi bi-trash"></i> Confirm Delete
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>