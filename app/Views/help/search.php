<?php use App\Core\View; $e=fn($v)=>View::e($v); $url=fn($p='')=>View::url($p); ?>
<nav style="margin-bottom:24px;font-size:13.5px">
  <a href="<?= $url('help') ?>" style="color:#6366f1;text-decoration:none">Help Center</a>
  <span style="color:#cbd5e1;margin:0 8px">/</span>
  <span style="color:#64748b">Search</span>
</nav>

<h1 style="font-size:26px;font-weight:800;color:#0f172a;margin-bottom:20px">
  <?= $q ? 'Results for "' . $e($q) . '"' : 'Search Help Center' ?>
</h1>

<form method="GET" style="margin-bottom:28px">
  <div style="position:relative;max-width:500px">
    <i class="bi bi-search" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:15px"></i>
    <input type="text" name="q" value="<?= $e($q) ?>" autofocus
           placeholder="Search articles…"
           style="width:100%;padding:12px 16px 12px 44px;border:2px solid #e2e8f0;border-radius:12px;font-size:15px;font-family:inherit;outline:none;transition:border-color .15s"
           onfocus="this.style.borderColor='#6366f1'"
           onblur="this.style.borderColor='#e2e8f0'">
  </div>
</form>

<?php if (!$q): ?>
<p style="color:#94a3b8">Enter a search term above to find help articles.</p>
<?php elseif (empty($results)): ?>
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:48px;text-align:center">
  <i class="bi bi-search" style="font-size:2.5rem;color:#cbd5e1"></i>
  <div style="font-weight:700;font-size:16px;color:#374151;margin-top:12px">No results for "<?= $e($q) ?>"</div>
  <p style="color:#94a3b8;margin-top:6px">Try different keywords or <a href="<?= $url('help') ?>" style="color:#6366f1">browse categories</a>.</p>
</div>
<?php else: ?>
<div style="font-size:13.5px;color:#64748b;margin-bottom:16px"><?= count($results) ?> result<?= count($results)!=1?'s':'' ?> found</div>
<div style="display:flex;flex-direction:column;gap:12px">
  <?php foreach ($results as $r):
    $excerpt = strip_tags($r['excerpt'] ?? '');
    $excerpt = mb_strimwidth($excerpt, 0, 160, '…');
  ?>
  <a href="<?= $url('help/article/' . $r['slug']) ?>"
     style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:18px 20px;text-decoration:none;display:block;transition:box-shadow .15s"
     onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,.07)'"
     onmouseout="this.style.boxShadow=''">
    <div style="font-size:12px;color:#6366f1;font-weight:600;margin-bottom:4px"><?= $e($r['cat_name'] ?? '') ?></div>
    <div style="font-size:16px;font-weight:700;color:#0f172a;margin-bottom:6px"><?= $e($r['title']) ?></div>
    <div style="font-size:13.5px;color:#64748b;line-height:1.6"><?= $e($excerpt) ?></div>
  </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>
