<?php use App\Core\View; $e=fn($v)=>View::e($v); $url=fn($p='')=>View::url($p); ?>
<div class="row g-5">
  <!-- Main article -->
  <div class="col-lg-8">
    <!-- Breadcrumb -->
    <nav style="margin-bottom:24px;font-size:13.5px">
      <a href="<?= $url('help') ?>" style="color:#6366f1;text-decoration:none">Help Center</a>
      <span style="color:#cbd5e1;margin:0 8px">/</span>
      <?php if (!empty($article['cat_slug'])): ?>
      <a href="<?= $url('help/category/' . $article['cat_slug']) ?>" style="color:#6366f1;text-decoration:none"><?= $e($article['cat_name'] ?? '') ?></a>
      <span style="color:#cbd5e1;margin:0 8px">/</span>
      <?php endif; ?>
      <span style="color:#64748b"><?= $e($article['title']) ?></span>
    </nav>

    <h1 style="font-size:28px;font-weight:800;color:#0f172a;margin-bottom:8px;line-height:1.3"><?= $e($article['title']) ?></h1>
    <div style="font-size:13px;color:#94a3b8;margin-bottom:28px">
      Last updated <?= $article['updated_at'] ? date('d M Y', strtotime($article['updated_at'])) : '' ?>
      · <?= number_format((int)$article['views']) ?> views
    </div>

    <!-- Article body -->
    <div class="help-article-body">
      <?= $article['body'] ?>
    </div>

    <!-- Was this helpful? -->
    <div style="margin-top:40px;padding:20px;background:#f8fafc;border-radius:12px;text-align:center">
      <div style="font-size:14px;font-weight:600;color:#374151;margin-bottom:12px">Was this article helpful?</div>
      <div style="display:flex;gap:10px;justify-content:center">
        <button onclick="this.textContent='👍 Thanks!';this.disabled=true"
                style="background:#ecfdf5;color:#059669;border:1px solid #a7f3d0;border-radius:8px;padding:8px 20px;font-weight:600;cursor:pointer;font-size:13.5px">
          👍 Yes
        </button>
        <button onclick="window.location='<?= $url('help/search') ?>'"
                style="background:#fef2f2;color:#dc2626;border:1px solid #fca5a5;border-radius:8px;padding:8px 20px;font-weight:600;cursor:pointer;font-size:13.5px">
          👎 No — help me find what I need
        </button>
      </div>
    </div>
  </div>

  <!-- Sidebar -->
  <div class="col-lg-4">
    <!-- Search -->
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:16px;margin-bottom:20px">
      <form method="GET" action="<?= $url('help/search') ?>">
        <div style="position:relative">
          <i class="bi bi-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:13px"></i>
          <input type="text" name="q" placeholder="Search articles…"
                 style="width:100%;padding:9px 12px 9px 32px;border:1px solid #e2e8f0;border-radius:9px;font-size:13px;font-family:inherit;outline:none">
        </div>
      </form>
    </div>

    <!-- Related -->
    <?php if (!empty($related)): ?>
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden">
      <div style="padding:14px 16px;font-size:13px;font-weight:700;color:#374151;border-bottom:1px solid #f1f5f9">Related Articles</div>
      <?php foreach ($related as $r): ?>
      <a href="<?= $url('help/article/' . $r['slug']) ?>"
         style="display:flex;align-items:center;gap:10px;padding:12px 16px;text-decoration:none;border-bottom:1px solid #f1f5f9;transition:background .1s"
         onmouseover="this.style.background='#f8fafc'"
         onmouseout="this.style.background=''">
        <i class="bi bi-file-text" style="color:#6366f1;font-size:14px;flex-shrink:0"></i>
        <span style="font-size:13px;font-weight:500;color:#374151"><?= $e($r['title']) ?></span>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<style>
.help-article-body { font-size:15px; line-height:1.8; color:#374151; }
.help-article-body h2 { font-size:20px; font-weight:700; color:#0f172a; margin:28px 0 12px; }
.help-article-body h3 { font-size:16px; font-weight:700; color:#0f172a; margin:22px 0 10px; }
.help-article-body p  { margin-bottom:14px; }
.help-article-body ul, .help-article-body ol { margin:0 0 14px 20px; }
.help-article-body li { margin-bottom:6px; }
.help-article-body code { background:#f1f5f9; padding:2px 6px; border-radius:5px; font-size:13px; color:#5b5ef6; font-family:monospace; }
.help-article-body pre { background:#0f172a; color:#e2e8f0; padding:16px 20px; border-radius:10px; font-size:13px; overflow-x:auto; margin:14px 0; }
.help-article-body table { width:100%; border-collapse:collapse; margin:16px 0; font-size:14px; }
.help-article-body th { background:#f1f5f9; padding:10px 14px; text-align:left; font-weight:600; border:1px solid #e2e8f0; }
.help-article-body td { padding:10px 14px; border:1px solid #e2e8f0; }
.help-article-body tr:nth-child(even) td { background:#f8fafc; }
</style>
