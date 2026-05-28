<?php
use App\Core\View;
use App\Models\Setting;
$e        = fn($v) => View::e($v);
$url      = fn($p='') => View::url($p);
$siteName = Setting::get('site_name','LMSAdvisor');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= $e($title ?? 'Help Center') ?></title>
  <link rel="preconnect" href="https://cdn.jsdelivr.net">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <style>html{visibility:hidden}</style>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" media="print" onload="this.media='all'">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" media="print" onload="this.media='all'">
  <script>document.documentElement.style.visibility='visible'</script>
  <style>
  :root { --primary:#6366f1; --primary-light:#ededff; }
  * { box-sizing:border-box; }
  body { font-family:'Inter',system-ui,sans-serif; background:#f8fafc; color:#1e293b; }
  .help-nav { background:#fff; border-bottom:1px solid #e2e8f0; padding:0 24px; }
  .help-nav-inner { max-width:1100px; margin:0 auto; display:flex; align-items:center; justify-content:space-between; height:60px; }
  .help-brand { font-size:16px; font-weight:700; color:#1e293b; text-decoration:none; display:flex; align-items:center; gap:8px; }
  .help-brand i { color:var(--primary); font-size:20px; }
  .help-nav-links { display:flex; gap:20px; align-items:center; }
  .help-nav-links a { font-size:13.5px; color:#64748b; text-decoration:none; font-weight:500; }
  .help-nav-links a:hover { color:var(--primary); }
  .help-content { max-width:1100px; margin:0 auto; padding:32px 24px 60px; }
  .help-footer { background:#1e293b; color:#94a3b8; padding:24px; text-align:center; font-size:13px; margin-top:auto; }
  </style>
</head>
<body>
<nav class="help-nav">
  <div class="help-nav-inner">
    <a href="<?= $url('help') ?>" class="help-brand">
      <i class="bi bi-stars"></i>
      <?= $e($siteName) ?> Help Center
    </a>
    <div class="help-nav-links">
      <a href="<?= $url('help') ?>"><i class="bi bi-house me-1"></i>Home</a>
      <a href="<?= $url('help/search') ?>"><i class="bi bi-search me-1"></i>Search</a>
      <a href="<?= $url('learn/dashboard') ?>"><i class="bi bi-arrow-left me-1"></i>Back to LMS</a>
    </div>
  </div>
</nav>
<main>
  <div class="help-content">
    <?= $content ?>
  </div>
</main>
<footer class="help-footer">
  <?= $e($siteName) ?> Help Center · <a href="<?= $url('learn/dashboard') ?>" style="color:#94a3b8">Go to LMS</a>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
