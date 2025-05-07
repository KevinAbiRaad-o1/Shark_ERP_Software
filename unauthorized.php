<?php
// Shark-erp/unauthorized.php
require_once 'DataBaseconnection/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Access Denied</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container text-center mt-5">
        <h1 class="text-danger"><i class="bi bi-exclamation-octagon"></i> 401 Unauthorized</h1>
        <p class="lead">You don't have permission to access this page.</p>
        <a href="login.php" class="btn btn-primary">Return to Login</a>
    </div>
</body>
</html>