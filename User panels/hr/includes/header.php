<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Panel | Shark ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    <style>
        /* Main header color */
        .app-header,
        .card-header {
            background-color: #4b6cb7;
            color: white;
        }

        /* Hover effects */
        .nav-link:hover {
            background-color: #3a5a9b !important;
        }

        /* Active tab styling */
        .nav-pills .nav-link.active,
        .nav-link.active {
            background-color: #4b6cb7 !important;
            font-weight: 600;
        }

        /* Button styling */
        .btn-primary {
            background-color: #4b6cb7;
            border-color: #4b6cb7;
        }

        /* Original HR Panel styles */
        .navbar {
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            background-color: #4b6cb7 !important;
        }
        .nav-link {
            transition: all 0.3s ease;
            padding: 8px 12px;
            margin: 0 2px;
            color: rgba(255,255,255,0.85);
        }
        .logout-link:hover {
            background-color: rgba(220,53,69,0.2) !important;
        }
        @media (max-width: 992px) {
            .navbar-collapse {
                padding-top: 10px;
            }
            .nav-link {
                margin: 2px 0;
            }
        }
        .hr-badge {
            background-color: #ff6b6b;
            color: white;
            font-size: 0.7rem;
            vertical-align: super;
        }
        
        /* Additional styling for consistency */
        .navbar-brand {
            color: white !important;
        }
        .nav-link.active {
            color: white !important;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="bi bi-people-fill me-2"></i>Shark ERP - HR Panel
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#hrNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="hrNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>" 
                           href="index.php">
                           <i class="bi bi-speedometer2 me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'employee_list.php' ? 'active' : '' ?>" 
                           href="employee_list.php">
                           <i class="bi bi-person-lines-fill me-1"></i> Employees
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'add_employee.php' ? 'active' : '' ?>" 
                           href="add_employee.php">
                           <i class="bi bi-person-plus me-1"></i> Add Employee
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-danger logout-link" href="../../logout.php">
                           <i class="bi bi-box-arrow-right me-1"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-4">
        <!-- Page content will go here -->
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>