<?php use App\Core\View; $e=fn($v)=>View::e($v); $url=fn($p='')=>View::url($p);
$t = $tenant; $isEdit = $t !== null;
$v = fn($k,$d='') => $t[$k] ?? $d;
?>
<div class="adm-page-header mb-4">
  <h2 class="adm-page-title"><?=$isEdit?'✏️ Edit Tenant: '.$e($v('name')):'🏢 New Tenant'?></h2>
</div>
<?php if($flash): ?><div class="alert alert-<?=$flash['type']==='success'?'success':'danger'?> mb-4"><?=$e($flash['message'])?></div><?php endif; ?>

<?php if($isEdit && isset($stats)): ?>
<div class="row g-3 mb-4">
  <?php foreach([['bi-people','Users',$stats['users']],['bi-journal','Courses',$stats['courses']],['bi-person-check','Enrollments',$stats['enrollments']]] as [$ico,$lbl,$val]): ?>
  <div class="col-4">
    <div class="card border-0 shadow-sm text-center py-3">
      <i class="bi <?=$ico?>" style="font-size:24px;color:var(--bs-primary)"></i>
      <div style="font-size:22px;font-weight:800"><?=number_format((int)$val)?></div>
      <div class="text-muted" style="font-size:12px"><?=$lbl?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-lg-8">
    <form method="POST" action="<?=$isEdit?$url('admin/tenants/'.$v('uuid').'/update'):$url('admin/tenants')?>">
      <input type="hidden" name="csrf_token" value="<?=$e($csrf_token)?>">

      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent fw-bold py-3"><i class="bi bi-building me-2"></i>Identity</div>
        <div class="card-body row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Tenant Name *</label>
            <input type="text" class="form-control" name="name" value="<?=$e($v('name'))?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Slug * <small class="text-muted">(subdomain)</small></label>
            <div class="input-group">
              <input type="text" class="form-control" name="slug" id="slugInput" value="<?=$e($v('slug'))?>" required pattern="[a-z0-9-]+">
              <span class="input-group-text">.lmsadvisor.com</span>
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Custom Domain</label>
            <input type="text" class="form-control" name="custom_domain" value="<?=$e($v('custom_domain'))?>" placeholder="learn.client.com">
          </div>
          <div class="col-md-3">
            <label class="form-label fw-semibold">Plan</label>
            <select class="form-select" name="plan">
              <?php foreach(['trial','starter','pro','enterprise'] as $pl): ?>
              <option value="<?=$pl?>" <?=$v('plan')===$pl?'selected':''?>><?=ucfirst($pl)?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php if($isEdit): ?>
          <div class="col-md-3">
            <label class="form-label fw-semibold">Status</label>
            <select class="form-select" name="status">
              <?php foreach(['active','trial','suspended'] as $st): ?>
              <option value="<?=$st?>" <?=$v('status')===$st?'selected':''?>><?=ucfirst($st)?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php endif; ?>
          <div class="col-md-3">
            <label class="form-label fw-semibold">Seat Limit</label>
            <input type="number" class="form-control" name="seat_limit" value="<?=$e((string)$v('seat_limit',100))?>" min="1">
          </div>
          <?php if(!$isEdit): ?>
          <div class="col-md-3">
            <label class="form-label fw-semibold">Trial Ends</label>
            <input type="date" class="form-control" name="trial_ends_at" value="<?=date('Y-m-d', strtotime('+30 days'))?>">
          </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent fw-bold py-3"><i class="bi bi-palette me-2"></i>Branding</div>
        <div class="card-body row g-3">
          <div class="col-md-4">
            <label class="form-label fw-semibold">Primary Color</label>
            <div class="d-flex gap-2">
              <input type="color" class="form-control form-control-color" name="primary_color" value="<?=$e($v('primary_color','#5b5ef6'))?>">
              <input type="text" class="form-control" id="primaryHex" value="<?=$e($v('primary_color','#5b5ef6'))?>">
            </div>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Accent Color</label>
            <div class="d-flex gap-2">
              <input type="color" class="form-control form-control-color" name="accent_color" value="<?=$e($v('accent_color','#3b82f6'))?>">
              <input type="text" class="form-control" id="accentHex" value="<?=$e($v('accent_color','#3b82f6'))?>">
            </div>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Email From Name</label>
            <input type="text" class="form-control" name="email_name" value="<?=$e($v('email_name'))?>">
          </div>
          <div class="col-12">
            <label class="form-label fw-semibold">Email From Address</label>
            <input type="email" class="form-control" name="email_from" value="<?=$e($v('email_from'))?>">
          </div>
          <?php if($isEdit): ?>
          <div class="col-12">
            <label class="form-label fw-semibold">Custom CSS</label>
            <textarea class="form-control font-monospace" name="custom_css" rows="4" style="font-size:12px"><?=$e($v('custom_css'))?></textarea>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary px-5"><?=$isEdit?'Save Changes':'Create Tenant'?></button>
        <a href="<?=$url('admin/tenants')?>" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>
  </div>

  <!-- Live preview -->
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm sticky-top" style="top:80px">
      <div class="card-header bg-transparent fw-bold py-3"><i class="bi bi-eye me-2"></i>Brand Preview</div>
      <div class="card-body" id="brandPreview">
        <div id="previewBar" style="background:#5b5ef6;height:6px;border-radius:3px;margin-bottom:16px;transition:background .2s"></div>
        <div class="d-flex align-items-center gap-2 mb-3">
          <div id="previewLogo" style="width:36px;height:36px;border-radius:10px;background:#5b5ef6;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:14px;transition:background .2s">T</div>
          <span id="previewName" style="font-weight:700;font-size:15px"><?=$e($v('name','Your Tenant'))?></span>
        </div>
        <div style="background:#f8fafc;border-radius:10px;padding:14px">
          <div style="height:10px;background:#e2e8f0;border-radius:5px;margin-bottom:8px;width:80%"></div>
          <div style="height:10px;background:#e2e8f0;border-radius:5px;margin-bottom:8px;width:60%"></div>
          <div id="previewBtn" style="background:#5b5ef6;color:#fff;border-radius:8px;padding:8px 16px;font-size:13px;font-weight:600;display:inline-block;margin-top:6px;transition:background .2s">Start Learning</div>
        </div>
        <div class="mt-3 text-muted" style="font-size:12px">
          <strong>URL:</strong> <code id="previewUrl"><?=$e($v('slug','your-tenant'))?>.lmsadvisor.com</code>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Live preview
function updatePreview() {
  var name    = document.querySelector('[name=name]').value || 'Your Tenant';
  var slug    = document.querySelector('[name=slug]').value || 'your-tenant';
  var primary = document.querySelector('[name=primary_color]').value;
  var accent  = document.querySelector('[name=accent_color]').value;
  document.getElementById('previewBar').style.background  = primary;
  document.getElementById('previewLogo').style.background = primary;
  document.getElementById('previewBtn').style.background  = primary;
  document.getElementById('previewName').textContent = name;
  document.getElementById('previewUrl').textContent  = slug + '.lmsadvisor.com';
  document.getElementById('primaryHex').value = primary;
  document.getElementById('accentHex').value  = accent;
}
document.querySelectorAll('input[type=color],input[type=text],select').forEach(function(el) {
  el.addEventListener('input', updatePreview);
});
// Auto-slug from name
document.querySelector('[name=name]').addEventListener('input', function() {
  if (!<?=$isEdit?'true':'false'?>) {
    document.getElementById('slugInput').value = this.value.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');
  }
  updatePreview();
});
</script>
