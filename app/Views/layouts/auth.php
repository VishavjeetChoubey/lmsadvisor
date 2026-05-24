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

  <!-- Bootstrap 5.3 — CDN direct (no local fallback needed for dev) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <!-- Inter font -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">

  <!-- Admin CSS -->
  <link rel="stylesheet" href="<?= $asset('css/admin.css') ?>">

  <style>
    body { background: linear-gradient(135deg,#1e293b 0%,#0f172a 100%); min-height:100vh; }
  </style>
</head>
<body class="auth-body d-flex align-items-center justify-content-center min-vh-100">
  <?= $content ?>

  <footer class="position-fixed bottom-0 w-100 text-center py-2" style="color:rgba(255,255,255,.35);font-size:.75rem">
    Proudly developed by LMS Advisor
  </footer>

  <!-- jQuery -->
  <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
  <!-- Bootstrap Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
