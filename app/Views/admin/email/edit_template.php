<?php use App\Core\View; $e=fn($v)=>View::e($v); $url=fn($p='')=>View::url($p);
$vars=json_decode($template['variables']??'[]',true); ?>
<form method="POST" action="<?=$url('admin/email/templates/'.$template['slug'].'/save')?>">
<input type="hidden" name="csrf_token" value="<?=$e($csrf_token)?>">
<div class="row g-4">
  <div class="col-12 col-lg-8">
    <div class="card lms-card">
      <div class="card-body p-4">
        <div class="mb-3"><label class="form-label fw-semibold">Template Name</label><input name="name" class="form-control" value="<?=$e($template['name'])?>"></div>
        <div class="mb-3"><label class="form-label fw-semibold">Subject Line</label><input name="subject" class="form-control" value="<?=$e($template['subject'])?>"></div>
        <div class="mb-3">
          <label class="form-label fw-semibold">HTML Body</label>
          <div class="form-text mb-2">Use <code>{{variable}}</code> placeholders. The template supports full HTML.</div>
          <textarea name="body_html" rows="22" class="form-control" style="font-family:monospace;font-size:13px"><?=$e($template['body_html'])?></textarea>
        </div>
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" name="is_enabled" value="1" <?=$template['is_enabled']?'checked':''?>>
          <label class="form-check-label fw-semibold">Enable this template</label>
        </div>
      </div>
      <div class="card-footer d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Template</button>
        <a href="<?=$url('admin/email')?>" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </div>
  </div>
  <div class="col-12 col-lg-4">
    <div class="card lms-card">
      <div class="card-header lms-card-header"><h6 class="mb-0">Available Variables</h6></div>
      <div class="card-body p-3">
        <p class="text-muted" style="font-size:12.5px">Click to copy to clipboard</p>
        <?php foreach($vars as $v):?>
        <code class="d-block mb-2 p-2 rounded" style="background:var(--content-bg);cursor:pointer;font-size:13px" onclick="navigator.clipboard.writeText('{{<?=$e($v)?>}}');LMS.toast('success','Copied!')">{{<?=$e($v)?>}}</code>
        <?php endforeach;?>
      </div>
    </div>
    <div class="card lms-card mt-3">
      <div class="card-header lms-card-header"><h6 class="mb-0">Preview</h6></div>
      <div class="card-body p-3">
        <button type="button" class="btn btn-sm btn-outline-primary w-100" onclick="document.getElementById('previewFrame').srcdoc=document.querySelector('[name=body_html]').value">
          <i class="bi bi-eye me-1"></i>Preview HTML
        </button>
        <iframe id="previewFrame" style="width:100%;height:300px;border:1px solid var(--border-color);border-radius:8px;margin-top:10px"></iframe>
      </div>
    </div>
  </div>
</div>
</form>
