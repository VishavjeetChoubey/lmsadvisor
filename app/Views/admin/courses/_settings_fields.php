<?php
use App\Core\View;
$e   = fn(mixed $v): string => View::e($v);
$c   = $course ?? [];
$get = fn(string $k, mixed $d = '') => $c[$k] ?? $d;
?>
<div class="row g-3">
  <div class="col-md-4">
    <label class="form-label fw-semibold">Status</label>
    <select class="form-select" name="status">
      <?php foreach(['draft'=>'Draft','published'=>'Published','archived'=>'Archived'] as $v=>$l): ?>
      <option value="<?=$v?>" <?=$get('status','draft')===$v?'selected':''?>><?=$l?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-4">
    <label class="form-label fw-semibold">Visibility</label>
    <select class="form-select" name="visibility" id="visSelSettings">
      <?php foreach(['public'=>'Public','private'=>'Private','password'=>'Password Protected'] as $v=>$l): ?>
      <option value="<?=$v?>" <?=$get('visibility','public')===$v?'selected':''?>><?=$l?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-4" id="pwWrapSettings" style="<?=$get('visibility')==='password'?'':'display:none'?>">
    <label class="form-label fw-semibold">Course Password</label>
    <input type="text" class="form-control" name="course_password" value="<?=$e($get('password'))?>">
  </div>
  <div class="col-md-3">
    <label class="form-label fw-semibold">Pass %</label>
    <input type="number" class="form-control" name="pass_percentage" value="<?=$e($get('pass_percentage',80))?>" min="1" max="100">
  </div>
  <div class="col-md-3">
    <label class="form-label fw-semibold">Grade Points</label>
    <input type="number" class="form-control" name="grade_points" value="<?=$e($get('grade_points',0))?>" min="0">
    <div class="form-text">Awarded on completion</div>
  </div>
  <div class="col-md-3">
    <label class="form-label fw-semibold">Duration (hours)</label>
    <input type="number" class="form-control" name="duration_hours" step="0.5" value="<?=$e($get('duration_hours'))?>">
  </div>
  <div class="col-md-3">
    <label class="form-label fw-semibold">End Date</label>
    <input type="date" class="form-control" name="end_date" value="<?=$e($get('end_date'))?>">
  </div>
  <div class="col-12">
    <div class="row g-3 mt-1">
      <?php foreach([
        ['certificate_enabled','Certificate Enabled',1],
        ['forum_enabled','Enable Forum',0],
        ['forum_enrolled_only','Forum: Enrolled Only',1],
        ['drip_enabled','Drip Content',0],
        ['is_rtl','RTL Layout',0],
      ] as [$name,$label,$default]):
        $checked = isset($c['id']) ? (bool)(int)($c[$name]??$default) : (bool)$default;
      ?>
      <div class="col-md-4 col-lg-3">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" name="<?=$name?>" id="tog_<?=$name?>" value="1" <?=$checked?'checked':''?>>
          <label class="form-check-label fw-semibold" for="tog_<?=$name?>"><?=$e($label)?></label>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<script>
document.getElementById('visSelSettings')?.addEventListener('change', function() {
  document.getElementById('pwWrapSettings').style.display = this.value==='password' ? '' : 'none';
});
</script>
