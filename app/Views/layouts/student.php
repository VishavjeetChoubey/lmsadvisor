<?php
use App\Core\View;
$e     = fn(mixed $v): string => View::e($v);
$asset = fn(string $p): string => View::asset($p);
$url   = fn(string $p = ''): string => View::url($p);
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#6366f1">
  <title><?= $e($title ?? 'LMSAdvisor') ?></title>

  <link rel="manifest" href="<?= APP_URL ?>/manifest.json">
  <link rel="icon" type="image/png" href="<?= $asset('icons/favicon.png') ?>">

  <!-- Bootstrap 5.3 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <!-- Inter Font -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">

  <!-- Student CSS -->
  <link rel="stylesheet" href="<?= $asset('css/app.css') ?>">
</head>
<body class="student-body">

<!-- Sidebar -->
<?php require VIEW_PATH . '/partials/student/sidebar.php'; ?>

<!-- Page wrapper -->
<div class="student-main" id="studentMain">
  <!-- Topbar -->
  <?php require VIEW_PATH . '/partials/student/topbar.php'; ?>
  <!-- Main content -->
  <main class="student-content">
    <?= $content ?>
  </main>
  <!-- Footer -->
  <?php require VIEW_PATH . '/partials/student/footer.php'; ?>
</div>

<!-- jQuery -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<!-- Bootstrap Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Student JS -->
<script src="<?= $asset('js/app.js') ?>"></script>
<script>
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('<?= APP_URL ?>/sw.js');
  }
</script>
</body>
</html>
