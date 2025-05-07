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

if (!isset($_GET['id'])) {
    $_SESSION['error_message'] = "Employee ID not specified";
    header("Location: employee_list.php");
    exit();
}

$employeeId = (int)$_GET['id'];

// Get comprehensive employee details
$stmt = $db->prepare("
    SELECT 
        e.*, 
        d.department_name, 
        r.role_type, 
        u.username, 
        u.user_id,
        u.created_at as user_created_at,
        u.updated_at as user_updated_at
    FROM employee e
    LEFT JOIN department d ON e.department_id = d.department_id
    LEFT JOIN roles r ON e.id = r.employee_id
    LEFT JOIN users u ON e.person_id = u.person_id
    WHERE e.id = ?
");
$stmt->execute([$employeeId]);
$employee = $stmt->fetch();

if (!$employee) {
    $_SESSION['error_message'] = "Employee not found";
    header("Location: employee_list.php");
    exit();
}

// Get employee's login history (if available)
$loginHistory = [];
$stmt = $db->prepare("SELECT * FROM login_history WHERE user_id = ? ORDER BY login_time DESC LIMIT 5");
$stmt->execute([$employee['user_id']]);
$loginHistory = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4><i class="bi bi-person"></i> Employee Details: <?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?></h4>
        </div>
        <div class="card-body">
            <!-- Personal Information Section -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-secondary text-white">
                            <h5><i class="bi bi-person-badge"></i> Personal Information</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr>
                                    <th width="30%">Employee ID</th>
                                    <td><?= $employee['id'] ?></td>
                                </tr>
                                <tr>
                                    <th>Full Name</th>
                                    <td><?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?></td>
                                </tr>
                                <tr>
                                    <th>Email</th>
                                    <td><?= htmlspecialchars($employee['email']) ?></td>
                                </tr>
                                <tr>
                                    <th>Phone</th>
                                    <td><?= $employee['phone'] ? htmlspecialchars($employee['phone']) : 'N/A' ?></td>
                                </tr>
                                <tr>
                                    <th>Address</th>
                                    <td><?= $employee['address_line'] ? htmlspecialchars($employee['address_line']) : 'N/A' ?></td>
                                </tr>
                                <tr>
                                    <th>City</th>
                                    <td><?= $employee['city'] ? htmlspecialchars($employee['city']) : 'N/A' ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Employment Information Section -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-secondary text-white">
                            <h5><i class="bi bi-briefcase"></i> Employment Information</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr>
                                    <th width="30%">Department</th>
                                    <td><?= $employee['department_name'] ? htmlspecialchars($employee['department_name']) : 'Unassigned' ?></td>
                                </tr>
                                <tr>
                                    <th>Role</th>
                                    <td>
                                        <span class="badge bg-info">
                                            <?= htmlspecialchars($employee['role_type']) ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Hire Date</th>
                                    <td><?= date('M d, Y', strtotime($employee['hire_date'])) ?></td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        <span class="badge bg-<?= $employee['is_active'] ? 'success' : 'danger' ?>">
                                            <?= $employee['is_active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Created At</th>
                                    <td><?= date('M d, Y H:i', strtotime($employee['created_at'])) ?></td>
                                </tr>
                                <tr>
                                    <th>Last Updated</th>
                                    <td><?= date('M d, Y H:i', strtotime($employee['updated_at'])) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- User Account Section -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header bg-secondary text-white">
                            <h5><i class="bi bi-person-circle"></i> User Account Information</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr>
                                    <th width="20%">User ID</th>
                                    <td><?= $employee['user_id'] ?></td>
                                </tr>
                                <tr>
                                    <th>Username</th>
                                    <td><?= htmlspecialchars($employee['username']) ?></td>
                                </tr>
                                <tr>
                                    <th>Account Created</th>
                                    <td><?= date('M d, Y H:i', strtotime($employee['user_created_at'])) ?></td>
                                </tr>
                                <tr>
                                    <th>Last Updated</th>
                                    <td><?= date('M d, Y H:i', strtotime($employee['user_updated_at'])) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Login History Section -->
            <?php if (!empty($loginHistory)): ?>
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header bg-secondary text-white">
                            <h5><i class="bi bi-clock-history"></i> Recent Login Activity</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Login Time</th>
                                            <th>IP Address</th>
                                            <th>User Agent</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($loginHistory as $login): ?>
                                        <tr>
                                            <td><?= date('M d, Y H:i', strtotime($login['login_time'])) ?></td>
                                            <td><?= htmlspecialchars($login['ip_address']) ?></td>
                                            <td><?= htmlspecialchars(substr($login['user_agent'], 0, 50)) ?>...</td>
                                            <td>
                                                <span class="badge bg-<?= $login['success'] ? 'success' : 'danger' ?>">
                                                    <?= $login['success'] ? 'Success' : 'Failed' ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Action Buttons -->
            <div class="d-flex justify-content-between">
                <a href="employee_list.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to List
                </a>
                <div class="btn-group">
                    <a href="update_employee.php?id=<?= $employee['id'] ?>" class="btn btn-primary">
                        <i class="bi bi-pencil"></i> Edit Employee
                    </a>
                    <?php if ($employee['role_type'] !== 'owner'): ?>
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteEmployeeModal">
                            <i class="bi bi-trash"></i> Delete Employee
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteEmployeeModal" tabindex="-1" aria-labelledby="deleteEmployeeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Delete Employee</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="delete_employee.php" method="POST">
                <div class="modal-body">
                    <p>Are you sure you want to permanently delete <strong><?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?></strong>?</p>
                    <p class="text-danger"><strong>Warning:</strong> This will permanently remove all related records!</p>
                    
                    <div class="mb-3">
                        <label for="hrPassword" class="form-label">Enter your password to confirm:</label>
                        <input type="password" class="form-control" name="hr_password" required>
                    </div>
                    <input type="hidden" name="employee_id" value="<?= $employee['id'] ?>">
                    <input type="hidden" name="user_id" value="<?= $employee['user_id'] ?>">
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