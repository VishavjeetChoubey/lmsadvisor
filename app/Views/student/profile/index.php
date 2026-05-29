<?php
use App\Core\View;
$e   = fn($v) => View::e($v);
$url = fn($p='') => View::url($p);
$u   = $full_user;
$initials = strtoupper(
    substr($u['first_name'] ?? '', 0, 1) .
    substr($u['last_name']  ?? '', 0, 1)
);
$roleName    = $u['role_name']    ?? $auth_user['role']    ?? 'student';
$roleDisplay = $u['role_display'] ?? $auth_user['role_display'] ?? ucfirst($roleName);
?>

<style>
.pf-wrap        { max-width:900px; margin:0 auto; padding:24px 16px 48px; }

/* ── Header card ── */
.pf-header      { background:var(--content-bg); border:1px solid var(--border-color); border-radius:16px; padding:28px 28px 22px; margin-bottom:24px; }
.pf-header-top  { display:flex; align-items:center; gap:22px; margin-bottom:20px; flex-wrap:wrap; }
.pf-avatar-wrap { position:relative; flex-shrink:0; }
.pf-avatar      { width:84px; height:84px; border-radius:50%; object-fit:cover; border:3px solid var(--border-color); display:block; }
.pf-avatar-init { width:84px; height:84px; border-radius:50%; background:linear-gradient(135deg,#6366f1,#3b82f6); display:flex; align-items:center; justify-content:center; font-size:28px; font-weight:700; color:#fff; }
.pf-cam-label   { position:absolute; bottom:2px; right:2px; width:26px; height:26px; background:#6366f1; border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer; border:2px solid var(--content-bg); }
.pf-cam-label i { color:#fff; font-size:11px; }

.pf-name        { font-size:22px; font-weight:800; color:var(--text-primary); margin:0 0 3px; line-height:1.2; }
.pf-email       { font-size:13.5px; color:var(--text-muted); margin:0 0 10px; }
.pf-role-badge  { display:inline-flex; align-items:center; gap:5px; background:#ededff; color:#4338ca; border-radius:20px; padding:3px 12px; font-size:12px; font-weight:700; }

.pf-stats       { display:flex; gap:28px; padding-top:16px; border-top:1px solid var(--border-color); flex-wrap:wrap; }
.pf-stat-val    { font-size:22px; font-weight:800; color:var(--primary); line-height:1; }
.pf-stat-lbl    { font-size:12px; color:var(--text-muted); margin-top:3px; }

/* ── Cards ── */
.pf-card        { background:var(--content-bg); border:1px solid var(--border-color); border-radius:14px; padding:22px; margin-bottom:20px; }
.pf-card-title  { font-size:14.5px; font-weight:700; color:var(--text-primary); margin:0 0 18px; padding-bottom:12px; border-bottom:1px solid var(--border-color); display:flex; align-items:center; gap:8px; }
.pf-card-title i { color:var(--primary); }

/* ── Form ── */
.pf-grid        { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.pf-field       { display:flex; flex-direction:column; gap:5px; }
.pf-field.full  { grid-column:1/-1; }
.pf-label       { font-size:13px; font-weight:600; color:var(--text-primary); }
.pf-input       { background:var(--card-bg); border:1px solid var(--border-color); border-radius:9px; padding:9px 13px; font-size:13.5px; color:var(--text-primary); font-family:inherit; outline:none; transition:border-color .15s; width:100%; }
.pf-input:focus { border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.1); }
textarea.pf-input { resize:vertical; min-height:80px; }
.pf-btn         { background:#6366f1; color:#fff; border:none; border-radius:9px; padding:10px 26px; font-size:14px; font-weight:600; cursor:pointer; transition:background .15s; }
.pf-btn:hover   { background:#4f46e5; }

/* ── Activity ── */
.pf-act-row     { display:flex; align-items:center; gap:12px; padding:10px 0; border-bottom:1px solid var(--border-color); }
.pf-act-row:last-child { border-bottom:none; }
.pf-act-icon    { width:34px; height:34px; border-radius:10px; background:#ededff; display:flex; align-items:center; justify-content:center; flex-shrink:0; }

/* ── Info list ── */
.pf-info-row    { display:flex; align-items:center; gap:12px; padding:9px 0; border-bottom:1px solid var(--border-color); font-size:13.5px; }
.pf-info-row:last-child { border-bottom:none; }
.pf-info-row i  { color:var(--primary); width:18px; flex-shrink:0; }
.pf-info-lbl    { flex:1; color:var(--text-muted); }
.pf-info-val    { font-weight:600; color:var(--text-primary); text-align:right; }

@media(max-width:600px){
  .pf-grid { grid-template-columns:1fr; }
  .pf-stats { gap:16px; }
  .pf-header-top { gap:16px; }
}
</style>

<div class="pf-wrap">

<?php if (!empty($flash)): ?>
<div class="alert alert-<?= $flash['type']==='success'?'success':'danger' ?> mb-3 d-flex align-items-center gap-2">
  <i class="bi bi-<?= $flash['type']==='success'?'check-circle':'exclamation-triangle' ?>-fill"></i>
  <?= $e($flash['message']) ?>
</div>
<?php endif; ?>

<!-- ── Header ──────────────────────────────────────────────────────────────── -->
<div class="pf-header">
  <div class="pf-header-top">
    <!-- Avatar -->
    <form method="POST" action="<?= $url('learn/profile/update') ?>"
          enctype="multipart/form-data" id="photoForm">
      <input type="hidden" name="csrf_token" value="<?= $e($csrf_token) ?>">
      <input type="hidden" name="first_name" value="<?= $e($u['first_name']??'') ?>">
      <input type="hidden" name="last_name"  value="<?= $e($u['last_name']??'') ?>">
      <input type="hidden" name="bio"        value="<?= $e($u['bio']??'') ?>">
      <input type="hidden" name="phone"      value="<?= $e($u['phone']??'') ?>">
      <input type="hidden" name="timezone"   value="<?= $e($u['timezone']??'UTC') ?>">
      <div class="pf-avatar-wrap">
        <?php if (!empty($u['profile_photo'])): ?>
          <img src="<?= $e(APP_URL . '/storage/uploads/' . $u['profile_photo']) ?>"
               alt="Profile" class="pf-avatar" id="avatarPreview">
        <?php else: ?>
          <div class="pf-avatar-init" id="avatarPreview"><?= $e($initials) ?></div>
        <?php endif; ?>
        <label class="pf-cam-label" for="photoInput" title="Change photo">
          <i class="bi bi-camera-fill"></i>
        </label>
        <input type="file" id="photoInput" name="profile_photo"
               accept="image/jpeg,image/png,image/webp,image/gif"
               style="display:none"
               onchange="previewAndSubmit(this)">
      </div>
    </form>

    <!-- Name + role -->
    <div style="flex:1;min-width:0">
      <h1 class="pf-name"><?= $e(trim(($u['first_name']??'') . ' ' . ($u['last_name']??''))) ?></h1>
      <p class="pf-email"><?= $e($u['email']??'') ?></p>
      <span class="pf-role-badge">
        <i class="bi bi-person-badge-fill" style="font-size:11px"></i>
        <?= $e($roleDisplay) ?>
      </span>
    </div>
  </div>

  <!-- Stats -->
  <div class="pf-stats">
    <div>
      <div class="pf-stat-val"><?= number_format($stats['enrollments']) ?></div>
      <div class="pf-stat-lbl">Enrolled</div>
    </div>
    <div>
      <div class="pf-stat-val"><?= number_format($stats['completions']) ?></div>
      <div class="pf-stat-lbl">Completed</div>
    </div>
    <div>
      <div class="pf-stat-val"><?= number_format($stats['certificates']) ?></div>
      <div class="pf-stat-lbl">Certificates</div>
    </div>
  </div>
</div>

<div class="row g-4">
  <!-- Left: forms -->
  <div class="col-lg-7">

    <!-- Edit Profile -->
    <div class="pf-card">
      <h5 class="pf-card-title"><i class="bi bi-person-fill"></i> Edit Profile</h5>
      <form method="POST" action="<?= $url('learn/profile/update') ?>" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $e($csrf_token) ?>">
        <input type="hidden" name="profile_photo_skip" value="1">
        <div class="pf-grid mb-3">
          <div class="pf-field">
            <label class="pf-label">First Name</label>
            <input type="text" class="pf-input" name="first_name"
                   value="<?= $e($u['first_name']??'') ?>" required>
          </div>
          <div class="pf-field">
            <label class="pf-label">Last Name</label>
            <input type="text" class="pf-input" name="last_name"
                   value="<?= $e($u['last_name']??'') ?>" required>
          </div>
          <div class="pf-field full">
            <label class="pf-label">Bio</label>
            <textarea class="pf-input" name="bio"
                      placeholder="Tell us a bit about yourself…"><?= $e($u['bio']??'') ?></textarea>
          </div>
          <div class="pf-field">
            <label class="pf-label">Phone</label>
            <input type="tel" class="pf-input" name="phone"
                   value="<?= $e($u['phone']??'') ?>" placeholder="+1 555 000 0000">
          </div>
          <div class="pf-field">
            <label class="pf-label">Timezone</label>
            <select class="pf-input" name="timezone">
              <?php foreach (['UTC','Asia/Kolkata','Asia/Dubai','Asia/Singapore','Asia/Tokyo',
                              'Europe/London','Europe/Paris','America/New_York','America/Los_Angeles',
                              'Australia/Sydney'] as $tz): ?>
              <option value="<?= $e($tz) ?>" <?= ($u['timezone']??'UTC')===$tz?'selected':'' ?>>
                <?= $e($tz) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <button type="submit" class="pf-btn">
          <i class="bi bi-check2 me-1"></i> Save Profile
        </button>
      </form>
    </div>

    <!-- Change Password -->
    <div class="pf-card">
      <h5 class="pf-card-title"><i class="bi bi-shield-lock-fill"></i> Change Password</h5>
      <form method="POST" action="<?= $url('learn/profile/password') ?>">
        <input type="hidden" name="csrf_token" value="<?= $e($csrf_token) ?>">
        <div class="pf-grid mb-3">
          <div class="pf-field full">
            <label class="pf-label">Current Password</label>
            <input type="password" class="pf-input" name="current_password"
                   placeholder="Enter current password" required>
          </div>
          <div class="pf-field">
            <label class="pf-label">New Password</label>
            <input type="password" class="pf-input" name="new_password"
                   minlength="8" placeholder="Min. 8 characters" required>
          </div>
          <div class="pf-field">
            <label class="pf-label">Confirm New Password</label>
            <input type="password" class="pf-input" name="confirm_password"
                   placeholder="Repeat new password" required>
          </div>
        </div>
        <button type="submit" class="pf-btn">
          <i class="bi bi-key-fill me-1"></i> Change Password
        </button>
      </form>
    </div>

  </div>

  <!-- Right: activity + info -->
  <div class="col-lg-5">

    <!-- Recent Activity -->
    <div class="pf-card">
      <h5 class="pf-card-title"><i class="bi bi-clock-history"></i> Recent Activity</h5>
      <?php if (empty($activity)): ?>
        <p style="font-size:13.5px;color:var(--text-muted);margin:0">
          No activity yet. <a href="<?= $url('learn/courses') ?>" style="color:var(--primary)">Start learning →</a>
        </p>
      <?php else: foreach ($activity as $a): ?>
      <div class="pf-act-row">
        <div class="pf-act-icon">
          <i class="bi bi-<?= $a['status']==='completed'?'check-circle-fill':'play-circle-fill' ?>"
             style="color:<?= $a['status']==='completed'?'#059669':'#6366f1' ?>;font-size:15px"></i>
        </div>
        <div style="flex:1;min-width:0">
          <div style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
            <?= $e($a['lesson_title']) ?>
          </div>
          <div style="font-size:11.5px;color:var(--text-muted)"><?= $e($a['course_title']) ?></div>
        </div>
        <div style="font-size:11.5px;color:var(--text-muted);white-space:nowrap;flex-shrink:0">
          <?= $a['last_accessed'] ? date('d M', strtotime($a['last_accessed'])) : '' ?>
        </div>
      </div>
      <?php endforeach; endif; ?>
    </div>

    <!-- Account Info -->
    <div class="pf-card">
      <h5 class="pf-card-title"><i class="bi bi-info-circle-fill"></i> Account Info</h5>
      <?php $info = [
        ['bi-envelope-fill',  'Email',      $u['email']??''],
        ['bi-calendar3',      'Joined',     $u['created_at'] ? date('d M Y', strtotime($u['created_at'])) : '—'],
        ['bi-person-badge',   'Role',       $roleDisplay],
        ['bi-clock-fill',     'Last Login', $u['last_login_at'] ? date('d M Y H:i', strtotime($u['last_login_at'])) : 'First visit'],
      ];
      foreach ($info as [$ico,$lbl,$val]): ?>
      <div class="pf-info-row">
        <i class="bi <?= $ico ?>"></i>
        <span class="pf-info-lbl"><?= $e($lbl) ?></span>
        <span class="pf-info-val"><?= $e($val) ?></span>
      </div>
      <?php endforeach; ?>
    </div>

  </div>
</div>
</div>

<script>
function previewAndSubmit(input) {
  if (!input.files || !input.files[0]) return;
  var reader = new FileReader();
  reader.onload = function(e) {
    var existing = document.getElementById('avatarPreview');
    if (existing) {
      var img = document.createElement('img');
      img.className = 'pf-avatar';
      img.id = 'avatarPreview';
      img.src = e.target.result;
      existing.replaceWith(img);
    }
  };
  reader.readAsDataURL(input.files[0]);
  // Submit the photo-only form
  document.getElementById('photoForm').submit();
}
</script>
