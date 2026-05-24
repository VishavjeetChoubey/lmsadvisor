<?php
use App\Core\View;
$e    = fn(mixed $v): string => View::e($v);
$c    = $course ?? [];
$get  = fn(string $k, mixed $d = '') => $c[$k] ?? $d;
$asset= fn(string $p): string => View::asset($p);
?>
<div class="row g-4">
  <div class="col-md-6">
    <label class="form-label fw-semibold">Course Thumbnail</label>
    <?php if ($get('thumbnail')): ?>
      <div class="mb-2">
        <img src="<?=$e(APP_URL.'/storage/uploads/'.$get('thumbnail'))?>" alt="" style="max-width:240px;border-radius:10px;border:1px solid var(--border-color)">
      </div>
    <?php endif; ?>
    <input type="file" class="form-control" name="thumbnail" accept="image/*">
    <div class="form-text">Recommended: 1280×720 (16:9). Max 5MB.</div>
  </div>
  <div class="col-md-6">
    <label class="form-label fw-semibold">Preview Video URL</label>
    <input type="url" class="form-control" name="preview_video" value="<?=$e($get('preview_video'))?>" placeholder="https://youtube.com/watch?v=…">
    <div class="form-text">Shown to non-enrolled visitors as a teaser.</div>
  </div>
</div>
