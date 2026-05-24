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
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
  <link rel="stylesheet" href="<?= $asset('css/admin.css') ?>">
  <style>
    * { box-sizing: border-box; }
    body { margin: 0; padding: 0; font-family: var(--font); background: #fff; }
  </style>
</head>
<body class="auth-body">
  <?= $content ?>
  <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
