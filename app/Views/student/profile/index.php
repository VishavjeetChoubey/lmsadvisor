<?php
use App\Core\View;
$e   = fn($v) => View::e($v);
$url = fn($p='') => View::url($p);
$u   = $full_user;
$initials = strtoupper(substr($u['first_name']??'',0,1) . substr($u['last_name']??'',0,1));
?>
<style>
.pf-wrap { max-width:860px; margin:0 auto; padding:24px 16px; }
.pf-header { display:flex; align-items:center; gap:24px; background:var(--content-bg); border:1px solid var(--border-color); border-radius:16px; padding:24px; margin-bottom:24px; flex-wrap:wrap; }
.pf-avatar-wrap { position:relative; flex-shrink:0; }
.pf-avatar { width:88px; height:88px; border-radius:50%; object-fit:cover; border:3px solid var(--border-color); }
.pf-avatar-init { width:88px; height:88px; border-radius:50%; background:linear-gradient(135deg,#6366f1,#3b82f6); display:flex; align-items:center; justify-content:center; font-size:28px; font-weight:700; color:#fff; border:3px solid rgba(99,102,241,.2); }
.pf-avatar-edit { position:absolute; bottom:0; right:0; width:28px; height:28px; background:#6366f1; border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer; border:2px solid var(--content-bg); }
.pf-avatar-edit i { color:#fff; font-size:12px; }
.pf-name { font-size:22px; font-weight:800; color:var(--text-primary); margin-bottom:4px; }
.pf-email { font-size:14px; color:var(--text-muted); margin-bottom:12px; }
.pf-stat-row { display:flex; gap:20px; flex-wrap:wrap; }
.pf-stat { text-align:center; }
.pf-stat-val { font-size:20px; font-weight:800; color:var(--primary); line-height:1; }
.pf-stat-lbl { font-size:12px; color:var(--text-muted); margin-top:2px; }
.pf-card { background:var(--content-bg); border:1px solid var(--border-color); border-radius:14px; padding:22px; margin-bottom:20px; }
.pf-card h5 { font-size:15px; font-weight:700; color:var(--text-primary); margin-bottom:16px; padding-bottom:10px; border-bottom:1px solid var(--border-color); display:flex; align-items:center; gap:8px; }
.pf-form-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
@media(max-width:600px){ .pf-form-grid{grid-template-columns:1fr;} .pf-stat-row{gap:12px;} }
.pf-form-group { display:flex; flex-direction:column; gap:6px; }
.pf-form-group label { font-size:13px; font-weight:600; color:var(--text-primary); }
.pf-input { background:var(--card-bg); border:1px solid var(--border-color); border-radius:9px; padding:9px 13px; font-size:13.5px; color:var(--text-primary); font-family:inherit; outline:none; transition:border-color .15s; width:100%; }
.pf-input:focus { border-color:#6366f1; }
.pf-btn { background:#6366f1; color:#fff; border:none; border-radius:9px; padding:10px 24px; font-size:14px; font-weight:600; cursor:pointer; transition:background .15s; }
.pf-btn:hover { background:#4f46e5; }
.pf-btn-outline { background:transparent; color:var(--text-primary); border:1px solid var(--border-color); }
.pf-btn-outline:hover { background:var(--card-bg); }
.pf-activity-row { display:flex; align-items:center; gap:12px; padding:10px 0; border-bottom:1px solid var(--border-color); }
.pf-activity-row:last-child { border-bottom:none; }
.pf-activity-icon { width:36px; height:36px; border-radius:10px; background:#ededff; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.pf-activity-icon i { color:#6366f1; font-size:15px; }
</style>

<div class="pf-wrap">

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type']==='success'?'success':'danger' ?> mb-3"><?= $e($flash['message']) ?></div>
<?php endif; ?>

<!-- Profile header -->
<div class="pf-header">
  <div class="pf-avatar-wrap">
    <?php if (!empty($u['profile_photo'])): ?>
      <img src="<?= $e(APP_URL . '/storage/uploads/' . $u['profile_photo']) ?>"
           alt="<?= $e($u['first_name']) ?>" class="pf-avatar">
    <?php else: ?>
      <div class="pf-avatar-init"><?= $e($initials) ?></div>
    <?php endif; ?>
    <label class="pf-avatar-edit" for="photoInput" title="Change photo">
      <i class="bi bi-camera-fill"></i>
    </label>
  </div>
  <div style="flex:1;min-width:0">
    <div class="pf-name"><?= $e($u['first_name'] . ' ' . $u['last_name']) ?></div>
    <div class="pf-email"><?= $e($u['email']) ?></div>
    <div class="pf-stat-row">
      <div class="pf-stat"><div class="pf-stat-val"><?= $stats['enrollments'] ?></div><div class="pf-stat-lbl">Enrolled</div></div>
      <div class="pf-stat"><div class="pf-stat-val"><?= $stats['completions'] ?></div><div class="pf-stat-lbl">Completed</div></div>
      <div class="pf-stat"><div class="pf-stat-val"><?= $stats['certificates'] ?></div><div class="pf-stat-lbl">Certificates</div></div>
    </div>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-7">

    <!-- Edit profile form -->
    <div class="pf-card">
      <h5><i class="bi bi-person-fill"></i> Edit Profile</h5>
      <form method="POST" action="<?= $url('learn/profile/update') ?>" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $e($csrf_token) ?>">
        <input type="file" id="photoInput" name="profile_photo" accept="image/*" style="display:none"
               onchange="previewPhoto(this)">

        <div class="pf-form-grid mb-3">
          <div class="pf-form-group">
            <label>First Name</label>
            <input type="text" class="pf-input" name="first_name" value="<?= $e($u['first_name']??'') ?>" required>
          </div>
          <div class="pf-form-group">
            <label>Last Name</label>
            <input type="text" class="pf-input" name="last_name" value="<?= $e($u['last_name']??'') ?>" required>
          </div>
        </div>
        <div class="pf-form-group mb-3">
          <label>Bio</label>
          <textarea class="pf-input" name="bio" rows="3" style="resize:vertical"
                    placeholder="Tell us a bit about yourself..."><?= $e($u['bio']??'') ?></textarea>
        </div>
        <div class="pf-form-grid mb-4">
          <div class="pf-form-group">
            <label>Phone</label>
            <input type="tel" class="pf-input" name="phone" value="<?= $e($u['phone']??'') ?>">
          </div>
          <div class="pf-form-group">
            <label>Timezone</label>
            <select class="pf-input" name="timezone">
              <?php $tzones = ['UTC','Asia/Kolkata','Asia/Dubai','Europe/London','Europe/Paris','America/New_York','America/Los_Angeles','Asia/Singapore','Asia/Tokyo'];
              foreach ($tzones as $tz): ?>
              <option value="<?= $e($tz) ?>" <?= ($u['timezone']??'UTC')===$tz?'selected':'' ?>><?= $e($tz) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <button type="submit" class="pf-btn">Save Profile</button>
      </form>
    </div>

    <!-- Change password -->
    <div class="pf-card">
      <h5><i class="bi bi-shield-lock-fill"></i> Change Password</h5>
      <form method="POST" action="<?= $url('learn/profile/password') ?>">
        <input type="hidden" name="csrf_token" value="<?= $e($csrf_token) ?>">
        <div class="pf-form-group mb-3">
          <label>Current Password</label>
          <input type="password" class="pf-input" name="current_password" required>
        </div>
        <div class="pf-form-grid mb-4">
          <div class="pf-form-group">
            <label>New Password</label>
            <input type="password" class="pf-input" name="new_password" minlength="8" required>
          </div>
          <div class="pf-form-group">
            <label>Confirm New Password</label>
            <input type="password" class="pf-input" name="confirm_password" required>
          </div>
        </div>
        <button type="submit" class="pf-btn">Change Password</button>
      </form>
    </div>

  </div>
  <div class="col-lg-5">

    <!-- Recent activity -->
    <div class="pf-card">
      <h5><i class="bi bi-clock-history"></i> Recent Activity</h5>
      <?php if (empty($activity)): ?>
        <p style="font-size:13.5px;color:var(--text-muted)">No activity yet. Start learning!</p>
      <?php else: foreach ($activity as $a): ?>
      <div class="pf-activity-row">
        <div class="pf-activity-icon">
          <i class="bi bi-<?= $a['status']==='completed'?'check-circle-fill':'play-circle-fill' ?>"></i>
        </div>
        <div style="flex:1;min-width:0">
          <div style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= $e($a['lesson_title']) ?></div>
          <div style="font-size:12px;color:var(--text-muted)"><?= $e($a['course_title']) ?></div>
        </div>
        <div style="font-size:11.5px;color:var(--text-muted);white-space:nowrap">
          <?= $a['last_accessed'] ? date('d M', strtotime($a['last_accessed'])) : '' ?>
        </div>
      </div>
      <?php endforeach; endif; ?>
    </div>

    <!-- Account info -->
    <div class="pf-card">
      <h5><i class="bi bi-info-circle-fill"></i> Account Info</h5>
      <?php $info = [
        ['bi-envelope','Email', $u['email']??''],
        ['bi-calendar','Joined', $u['created_at'] ? date('d M Y', strtotime($u['created_at'])) : '—'],
        ['bi-person-badge','Role', ucfirst($auth_user['role_name']??'Student')],
        ['bi-clock','Last Login', $u['last_login_at'] ? date('d M Y H:i', strtotime($u['last_login_at'])) : 'First visit'],
      ];
      foreach ($info as [$ico,$lbl,$val]): ?>
      <div style="display:flex;align-items:center;gap:12px;padding:9px 0;border-bottom:1px solid var(--border-color);font-size:13.5px">
        <i class="bi <?= $ico ?>" style="color:var(--primary);width:18px;flex-shrink:0"></i>
        <span style="color:var(--text-muted);flex:1"><?= $e($lbl) ?></span>
        <span style="font-weight:600"><?= $e($val) ?></span>
      </div>
      <?php endforeach; ?>
    </div>

  </div>
</div>
</div>

<script>
function previewPhoto(input) {
  if (input.files && input.files[0]) {
    var reader = new FileReader();
    reader.onload = function(e) {
      var wrap = document.querySelector('.pf-avatar-wrap');
      var existing = wrap.querySelector('.pf-avatar') || wrap.querySelector('.pf-avatar-init');
      if (existing) {
        var img = document.createElement('img');
        img.className = 'pf-avatar';
        img.src = e.target.result;
        existing.replaceWith(img);
      }
    };
    reader.readAsDataURL(input.files[0]);
    // Auto-submit the form after selecting
    input.closest('form') || document.querySelector('form[action*="profile/update"]').submit();
  }
}
</script>
