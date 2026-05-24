<?php
use App\Core\View;
$e     = fn(mixed $v): string => View::e($v);
$asset = fn(string $p): string => View::asset($p);
$url   = fn(string $p = ''): string => View::url($p);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $e($title ?? 'LMSAdvisor') ?></title>
  <link rel="icon" type="image/png" href="<?= $asset('icons/favicon.png') ?>">

  <!-- Bootstrap 5.3 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <!-- Font Awesome 6 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <!-- Inter Font -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">

  <!-- Admin CSS -->
  <link rel="stylesheet" href="<?= $asset('css/admin.css') ?>">
</head>
<body class="admin-body">

<!-- Sidebar -->
<?php require VIEW_PATH . '/partials/admin/sidebar.php'; ?>

<!-- Page wrapper -->
<div class="admin-main" id="adminMain">

  <!-- Topbar -->
  <?php require VIEW_PATH . '/partials/admin/topbar.php'; ?>

  <!-- Impersonation banner -->
  <?php require VIEW_PATH . '/partials/admin/impersonation_banner.php'; ?>

  <!-- Breadcrumb -->
  <?php if (!empty($breadcrumbs)): ?>
    <?php require VIEW_PATH . '/partials/admin/breadcrumb.php'; ?>
  <?php endif; ?>

  <!-- Main content -->
  <main class="admin-content">
    <?php require VIEW_PATH . '/partials/admin/toast.php'; ?>
    <?= $content ?>
  </main>

  <!-- Footer -->
  <?php require VIEW_PATH . '/partials/admin/footer.php'; ?>
</div>

<!-- jQuery -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<!-- Bootstrap Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Admin JS -->
<script src="<?= $asset('js/admin.js') ?>"></script>
</body>
</html>
