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

$departments = $db->query("SELECT department_id, department_name FROM department ORDER BY department_name")->fetchAll();
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h4><i class="bi bi-person-plus"></i> Add New Employee</h4>
        </div>
        <div class="card-body">
            <form id="addEmployeeForm" method="POST" action="process_employee.php">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="firstName" class="form-label">First Name *</label>
                        <input type="text" class="form-control" id="firstName" name="first_name" required maxlength="25">
                    </div>
                    <div class="col-md-6">
                        <label for="lastName" class="form-label">Last Name *</label>
                        <input type="text" class="form-control" id="lastName" name="last_name" required maxlength="25">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" required maxlength="50">
                    </div>
                    <div class="col-md-6">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" id="phone" name="phone" maxlength="20">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="department" class="form-label">Department</label>
                        <select class="form-select" id="department" name="department_id">
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept['department_id'] ?>"><?= htmlspecialchars($dept['department_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="hireDate" class="form-label">Hire Date *</label>
                        <input type="date" class="form-control" id="hireDate" name="hire_date" required>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="address" class="form-label">Address</label>
                        <input type="text" class="form-control" id="address" name="address_line" maxlength="100">
                    </div>
                    <div class="col-md-6">
                        <label for="city" class="form-label">City</label>
                        <input type="text" class="form-control" id="city" name="city" maxlength="50">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="username" class="form-label">Username *</label>
                        <input type="text" class="form-control" id="username" name="username" required maxlength="25">
                    </div>
                    <div class="col-md-6">
                        <label for="password" class="form-label">Password *</label>
                        <input type="password" class="form-control" id="password" name="password" required minlength="8">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="role" class="form-label">Role *</label>
                        <select class="form-select" id="role" name="role_type" required>
                            <option value="">Select Role</option>
                            <option value="hr">HR</option>
                            <option value="accounting">Accounting</option>
                            <option value="warehouse">Warehouse</option>
                            <option value="logistics">Logistics</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="isActive" class="form-label">Status</label>
                        <select class="form-select" id="isActive" name="is_active">
                            <option value="1" selected>Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                
                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Save Employee
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>