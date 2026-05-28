<?php
use App\Core\View;
use App\Models\Setting;
$e     = fn(mixed $v): string => View::e($v);
$asset = fn(string $p): string => View::asset($p);
$url   = fn(string $p = ''): string => View::url($p);

// Dynamic branding from settings
$siteName    = Setting::get('site_name', 'LMSAdvisor');
$siteFavicon = Setting::get('site_favicon', '');
$siteLogoVal = Setting::get('site_logo', '');
$faviconUrl  = $siteFavicon ? $asset($siteFavicon) : $asset('icons/favicon.png');
$logoUrl     = $siteLogoVal ? $asset($siteLogoVal) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $e($title ?? $siteName) ?></title>
  <link rel="icon" type="image/x-icon" href="<?= $faviconUrl ?>">
  <link rel="shortcut icon" href="<?= $faviconUrl ?>">

  <!-- Preconnect to external origins -->
  <link rel="preconnect" href="https://cdn.jsdelivr.net">
  <link rel="preconnect" href="https://cdnjs.cloudflare.com">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

  <!-- Hide until CSS ready — prevents FOUC -->
  <style>html { visibility: hidden; overflow-y: scroll; }</style>

  <!-- Critical CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="<?= $asset('css/admin.css') ?>">

  <!-- Non-critical CSS async -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        media="print" onload="this.media='all'">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
        media="print" onload="this.media='all'">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        media="print" onload="this.media='all'">
  <noscript>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
  </noscript>

  <!-- Reveal page -->
  <script>document.documentElement.style.visibility = 'visible';</script>
<?php
  // ── Custom Code injection (highest priority — after all platform files) ──
  $customCss    = Setting::get('custom_css', '');
  $customJsHead = Setting::get('custom_js_head', '');
?>
<?php if ($customCss):    ?><style id="custom-css"><?= $customCss ?></style><?php endif; ?>
<!-- Global BASE — must be in <head> so every inline page script has it -->
<script>window.LMS = window.LMS || {}; window.LMS.BASE = '<?= rtrim(APP_URL, '/') ?>';</script>
<?php if ($customJsHead): ?><script id="custom-js-head"><?= $customJsHead ?></script><?php endif; ?>
</head>
<body class="admin-body">
<?php $customJsBody = Setting::get('custom_js_body', ''); ?>
<?php if ($customJsBody): ?><script id="custom-js-body"><?= $customJsBody ?></script><?php endif; ?>

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
<?php $customJsFooter = Setting::get('custom_js_footer', ''); ?>
<?php if ($customJsFooter): ?><script id="custom-js-footer"><?= $customJsFooter ?></script><?php endif; ?>
</body>
</html>
