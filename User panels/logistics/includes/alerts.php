<?php if (isset($_SESSION['success_message'])): ?>
<div class="alert alert-success alert-dismissible fade show mt-3">
    <?= $_SESSION['success_message'] ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['success_message']); endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
<div class="alert alert-danger alert-dismissible fade show mt-3">
    <?= $_SESSION['error_message'] ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['error_message']); endif; ?>