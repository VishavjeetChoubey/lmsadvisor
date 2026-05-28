<?php use App\Core\View; $e=fn($v)=>View::e($v); $url=fn($p='')=>View::url($p); ?>
<!-- Breadcrumb -->
<nav style="margin-bottom:24px;font-size:13.5px">
  <a href="<?= $url('help') ?>" style="color:#6366f1;text-decoration:none">Help Center</a>
  <span style="color:#cbd5e1;margin:0 8px">/</span>
  <span style="color:#64748b"><?= $e($cat['name']) ?></span>
</nav>

<h1 style="font-size:26px;font-weight:800;color:#0f172a;margin-bottom:6px"><?= $e($cat['name']) ?></h1>
<p style="color:#64748b;margin-bottom:28px"><?= count($articles) ?> article<?= count($articles)!=1?'s':'' ?></p>

<?php if (empty($articles)): ?>
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:48px;text-align:center;color:#94a3b8">
  No articles in this category yet.
</div>
<?php else: ?>
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden">
  <?php foreach ($articles as $i => $a): ?>
  <a href="<?= $url('help/article/' . $a['slug']) ?>"
     style="display:flex;align-items:center;gap:14px;padding:16px 20px;text-decoration:none;border-bottom:1px solid #f1f5f9;transition:background .1s<?= $i===count($articles)-1?';border-bottom:none':'' ?>"
     onmouseover="this.style.background='#f8fafc'"
     onmouseout="this.style.background=''">
    <i class="bi bi-file-text-fill" style="color:#6366f1;font-size:16px;flex-shrink:0"></i>
    <div style="flex:1;min-width:0">
      <div style="font-size:14.5px;font-weight:600;color:#0f172a"><?= $e($a['title']) ?></div>
      <div style="font-size:12px;color:#94a3b8;margin-top:2px">
        Updated <?= $a['updated_at'] ? date('d M Y', strtotime($a['updated_at'])) : '' ?> · <?= number_format((int)$a['views']) ?> views
      </div>
    </div>
    <i class="bi bi-chevron-right" style="color:#cbd5e1;font-size:12px"></i>
  </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>
