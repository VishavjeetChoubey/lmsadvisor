<?php use App\Core\View; $e=fn($v)=>View::e($v); $url=fn($p='')=>View::url($p); ?>

<!-- Hero -->
<div style="text-align:center;padding:48px 0 40px;max-width:640px;margin:0 auto">
  <div style="font-size:13px;font-weight:600;color:#6366f1;letter-spacing:.08em;text-transform:uppercase;margin-bottom:12px">Help Center</div>
  <h1 style="font-size:36px;font-weight:800;color:#0f172a;margin-bottom:12px;line-height:1.2">How can we help?</h1>
  <p style="font-size:16px;color:#64748b;margin-bottom:28px">Search our documentation or browse by category below.</p>
  <!-- Search -->
  <form method="GET" action="<?= $url('help/search') ?>">
    <div style="position:relative;max-width:480px;margin:0 auto">
      <i class="bi bi-search" style="position:absolute;left:16px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:16px"></i>
      <input type="text" name="q" placeholder="Search articles…"
             style="width:100%;padding:14px 16px 14px 46px;border:2px solid #e2e8f0;border-radius:14px;font-size:15px;font-family:inherit;outline:none;transition:border-color .15s;background:#fff"
             onfocus="this.style.borderColor='#6366f1'"
             onblur="this.style.borderColor='#e2e8f0'">
    </div>
  </form>
</div>

<!-- Categories grid -->
<?php
$catIcons = [
  'getting-started'    => ['bi-rocket-takeoff-fill','#6366f1','#ededff'],
  'course-management'  => ['bi-journal-bookmark-fill','#0891b2','#ecfeff'],
  'student-experience' => ['bi-mortarboard-fill','#059669','#ecfdf5'],
  'quizzes-assessments'=> ['bi-patch-question-fill','#d97706','#fffbeb'],
  'ai-features'        => ['bi-stars','#7c3aed','#f5f3ff'],
  'woocommerce'        => ['bi-bag-heart-fill','#be185d','#fdf2f8'],
  'admin-settings'     => ['bi-gear-fill','#374151','#f3f4f6'],
  'enterprise'         => ['bi-building-fill','#0f766e','#f0fdfa'],
  'troubleshooting'    => ['bi-tools','#dc2626','#fef2f2'],
];
?>
<h2 style="font-size:18px;font-weight:700;margin-bottom:20px">Browse by Category</h2>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px;margin-bottom:48px">
  <?php foreach ($categories as $cat):
    [$ico, $clr, $bg] = $catIcons[$cat['slug']] ?? ['bi-folder-fill','#6366f1','#ededff'];
  ?>
  <a href="<?= $url('help/category/' . $cat['slug']) ?>"
     style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:20px;text-decoration:none;transition:box-shadow .15s,transform .15s;display:flex;flex-direction:column;gap:10px"
     onmouseover="this.style.boxShadow='0 6px 20px rgba(0,0,0,.08)';this.style.transform='translateY(-2px)'"
     onmouseout="this.style.boxShadow='';this.style.transform=''">
    <div style="width:42px;height:42px;border-radius:12px;background:<?= $bg ?>;display:flex;align-items:center;justify-content:center">
      <i class="bi <?= $ico ?>" style="font-size:20px;color:<?= $clr ?>"></i>
    </div>
    <div>
      <div style="font-size:14px;font-weight:700;color:#0f172a;margin-bottom:3px"><?= $e($cat['name']) ?></div>
      <div style="font-size:12.5px;color:#94a3b8"><?= (int)$cat['article_count'] ?> article<?= $cat['article_count']!=1?'s':'' ?></div>
    </div>
  </a>
  <?php endforeach; ?>
</div>

<!-- Popular articles -->
<?php if (!empty($popular)): ?>
<h2 style="font-size:18px;font-weight:700;margin-bottom:16px">Popular Articles</h2>
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden">
  <?php foreach ($popular as $i => $a): ?>
  <a href="<?= $url('help/article/' . $a['slug']) ?>"
     style="display:flex;align-items:center;gap:14px;padding:14px 20px;text-decoration:none;border-bottom:1px solid #f1f5f9;transition:background .1s<?= $i===count($popular)-1?';border-bottom:none':'' ?>"
     onmouseover="this.style.background='#f8fafc'"
     onmouseout="this.style.background=''">
    <div style="width:28px;height:28px;border-radius:8px;background:#ededff;display:flex;align-items:center;justify-content:center;flex-shrink:0">
      <i class="bi bi-file-text-fill" style="font-size:13px;color:#6366f1"></i>
    </div>
    <div style="flex:1;min-width:0">
      <div style="font-size:14px;font-weight:600;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= $e($a['title']) ?></div>
      <div style="font-size:12px;color:#94a3b8"><?= $e($a['cat_name'] ?? '') ?></div>
    </div>
    <div style="font-size:12px;color:#cbd5e1;white-space:nowrap"><?= number_format((int)$a['views']) ?> views</div>
    <i class="bi bi-chevron-right" style="color:#cbd5e1;font-size:12px"></i>
  </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>
