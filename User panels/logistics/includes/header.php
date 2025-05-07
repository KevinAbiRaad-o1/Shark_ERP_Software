<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logistics Panel | Shark ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        /* Main color scheme */
        .bg-primary {
            background-color: #4b6cb7 !important;
        }
        
        .navbar {
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            background-color: #4b6cb7 !important;
        }
        
        /* Navigation links */
        .nav-link {
            transition: all 0.3s ease;
            padding: 8px 12px;
            margin: 0 2px;
            color: rgba(255,255,255,0.85) !important;
        }
        
        .nav-link:hover {
            background-color: rgba(255,255,255,0.15) !important;
            color: white !important;
        }
        
        /* Active link styling */
        .nav-link.active {
            font-weight: 600;
            background-color: rgba(255,255,255,0.25) !important;
            border-radius: 4px;
            color: white !important;
        }
        
        /* Logout button */
        .logout-link {
            color: rgba(255,255,255,0.85) !important;
        }
        .logout-link:hover {
            background-color: rgba(220,53,69,0.3) !important;
            color: white !important;
        }
        
        /* Brand styling */
        .navbar-brand {
            color: white !important;
            font-weight: 500;
        }
        
        /* Mobile responsiveness */
        @media (max-width: 992px) {
            .navbar-collapse {
                padding-top: 10px;
                background-color: #4b6cb7;
            }
            .nav-link {
                margin: 2px 0;
                padding: 10px 15px;
            }
        }
        
        /* Toggle button */
        .navbar-toggler {
            border-color: rgba(255,255,255,0.5);
        }
        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.85%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="bi bi-truck me-2"></i>Shark ERP - Logistics
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#logisticsNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="logisticsNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>" 
                           href="index.php">
                           <i class="bi bi-speedometer2 me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'approve_delete.php' ? 'active' : '' ?>" 
                          href="approve_delete.php">
                          <i class="bi bi-trash3-fill me-1"></i> Approve Deletions
                       </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'approve_po.php' ? 'active' : '' ?>" 
                           href="approve_po.php">
                           <i class="bi bi-file-earmark-check me-1"></i> Approve POs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link logout-link" href="../../logout.php">
                           <i class="bi bi-box-arrow-right me-1"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-4">