<?php
use App\Core\View;
$e   = fn(mixed $v): string => View::e($v);
$url = fn(string $p = ''): string => View::url($p);
$a   = $article ?? [];
$isEdit = !empty($a['uuid']);
$action = $isEdit ? $url('admin/knowledge-base/'.$a['uuid'].'/edit') : $url('admin/knowledge-base/create');
?>
<form action="<?= $action ?>" method="POST">
  <input type="hidden" name="csrf_token" value="<?= $e($csrf_token) ?>">
  <div class="row g-4">
    <div class="col-12 col-lg-8">
      <div class="card lms-card mb-4">
        <div class="card-body p-4">
          <div class="mb-3">
            <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="title"
                   value="<?= $e($a['title'] ?? '') ?>" required maxlength="255" placeholder="Article title…">
          </div>
          <div>
            <label class="form-label fw-semibold">Body</label>
            <div id="kbEditor"><?= $a['body'] ?? '' ?></div>
            <textarea name="body" id="kbBodyHidden" class="d-none"><?= $e($a['body'] ?? '') ?></textarea>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-4">
      <div class="card lms-card mb-4">
        <div class="card-header lms-card-header"><h6 class="mb-0">Settings</h6></div>
        <div class="card-body p-4">
          <div class="mb-3">
            <label class="form-label fw-semibold">Status</label>
            <select class="form-select" name="status">
              <option value="draft"     <?= ($a['status']??'draft')==='draft'     ?'selected':'' ?>>Draft</option>
              <option value="published" <?= ($a['status']??'')==='published' ?'selected':'' ?>>Published</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Category</label>
            <select class="form-select" name="category_id">
              <option value="">— None —</option>
              <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= ($a['category_id']??0)==$cat['id']?'selected':'' ?>>
                <?= $e($cat['name']) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php if ($isEdit): ?>
          <div class="text-muted small">
            <i class="bi bi-eye me-1"></i><?= number_format((int)$a['views']) ?> views<br>
            <i class="bi bi-calendar me-1"></i>Created <?= date('d M Y', strtotime($a['created_at'])) ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary flex-grow-1">
          <i class="bi bi-check-circle me-1"></i> <?= $isEdit ? 'Save Changes' : 'Create Article' ?>
        </button>
        <a href="<?= $url('admin/knowledge-base') ?>" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </div>
  </div>
</form>
<script>
(function(){
  function init(){
    if(typeof Quill==='undefined'){
      const css=document.createElement('link');css.rel='stylesheet';css.href='https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css';document.head.appendChild(css);
      const s=document.createElement('script');s.src='https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js';s.onload=init;document.head.appendChild(s);return;
    }
    const q=new Quill('#kbEditor',{theme:'snow',modules:{toolbar:[[{header:[1,2,3,false]}],['bold','italic','underline'],[{list:'ordered'},{list:'bullet'}],['link','blockquote','code-block'],['clean']]}});
    q.on('text-change',()=>document.getElementById('kbBodyHidden').value=q.root.innerHTML);
    document.querySelector('form').addEventListener('submit',()=>document.getElementById('kbBodyHidden').value=q.root.innerHTML,{once:false});
  }
  init();
})();
</script>
