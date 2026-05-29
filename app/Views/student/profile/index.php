<?php
use App\Core\View;
$e   = fn($v) => View::e($v);
$url = fn($p='') => View::url($p);
$u   = $full_user;
$initials    = strtoupper(substr($u['first_name']??'',0,1).substr($u['last_name']??'',0,1));
$roleName    = $u['role_name']    ?? $auth_user['role']         ?? 'student';
$roleDisplay = $u['role_display'] ?? $auth_user['role_display'] ?? ucfirst($roleName);
$fullName    = trim(($u['first_name']??'').' '.($u['last_name']??''));
?>
<style>
/* ── layout ── */
.pf-wrap{max-width:1040px;margin:0 auto;padding:28px 20px 60px;display:grid;grid-template-columns:280px 1fr;gap:24px;align-items:start}
@media(max-width:720px){.pf-wrap{grid-template-columns:1fr}}

/* ── left sidebar card ── */
.pf-sidebar{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:28px 20px 20px;text-align:center;position:sticky;top:80px}
.pf-av-outer{position:relative;display:inline-block;margin-bottom:14px}
.pf-av{width:90px;height:90px;border-radius:50%;border:3px solid #e5e7eb;object-fit:cover;display:block}
.pf-av-init{width:90px;height:90px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#3b82f6);display:flex;align-items:center;justify-content:center;font-size:30px;font-weight:800;color:#fff;border:3px solid #e5e7eb}
.pf-av-cam{position:absolute;bottom:2px;right:2px;width:26px;height:26px;border-radius:50%;background:#6366f1;border:2px solid #fff;display:flex;align-items:center;justify-content:center;cursor:pointer}
.pf-av-cam i{color:#fff;font-size:11px}
.pf-sid-name{font-size:17px;font-weight:800;color:#111827;margin:0 0 3px}
.pf-sid-email{font-size:13px;color:#6b7280;margin:0 0 12px}
.pf-sid-role{display:inline-block;background:#6366f1;color:#fff;border-radius:20px;padding:5px 18px;font-size:12.5px;font-weight:700;margin-bottom:20px}
.pf-sid-stats{display:grid;grid-template-columns:repeat(3,1fr);border-top:1px solid #f3f4f6;padding-top:16px;margin-bottom:12px}
.pf-sid-stat-n{font-size:20px;font-weight:800;line-height:1}
.pf-sid-stat-l{font-size:11.5px;color:#9ca3af;margin-top:3px}
.pf-sid-last{font-size:12px;color:#9ca3af;padding-top:12px;border-top:1px solid #f3f4f6}

/* ── right column ── */
.pf-right{display:flex;flex-direction:column;gap:20px}

/* ── section card ── */
.pf-card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;overflow:hidden}
.pf-card-hd{display:flex;align-items:center;gap:12px;padding:18px 24px;border-bottom:1px solid #f3f4f6}
.pf-card-hd-ico{font-size:18px;color:#6b7280}
.pf-card-title{font-size:15.5px;font-weight:700;color:#111827;margin:0}
.pf-card-body{padding:24px}

/* ── form elements ── */
.pf-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:18px}
.pf-grid.single{grid-template-columns:1fr}
.pf-fg{display:flex;flex-direction:column;gap:6px}
.pf-lbl{font-size:13px;font-weight:600;color:#374151;display:flex;align-items:center;gap:4px}
.pf-req{color:#ef4444;font-size:12px}
.pf-inp{width:100%;padding:11px 14px;border:1.5px solid #d1d5db;border-radius:10px;background:#fff;color:#111827;font-size:14px;font-family:inherit;outline:none;transition:border-color .15s,box-shadow .15s;line-height:1.4}
.pf-inp:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.12)}
.pf-inp::placeholder{color:#9ca3af}
textarea.pf-inp{resize:vertical;min-height:90px}
select.pf-inp{cursor:pointer;appearance:auto}
.pf-pw-wrap{position:relative}
.pf-pw-wrap .pf-inp{padding-right:46px}
.pf-pw-eye{position:absolute;right:13px;top:50%;transform:translateY(-50%);background:none;border:1px solid #d1d5db;border-radius:6px;width:28px;height:28px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#6b7280;transition:border-color .15s,color .15s;padding:0}
.pf-pw-eye:hover{border-color:#6366f1;color:#6366f1}

/* ── buttons ── */
.pf-btn{display:inline-flex;align-items:center;gap:7px;padding:11px 26px;border-radius:10px;border:2px solid transparent;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;transition:all .15s}
.pf-btn-primary{background:#6366f1;color:#fff;border-color:#6366f1}
.pf-btn-primary:hover{background:#4f46e5;border-color:#4f46e5}
.pf-btn-warning{background:#f59e0b;color:#fff;border-color:#f59e0b}
.pf-btn-warning:hover{background:#d97706;border-color:#d97706}

/* ── activity ── */
.pf-act{display:flex;align-items:center;gap:12px;padding:11px 0;border-bottom:1px solid #f3f4f6}
.pf-act:last-child{border-bottom:none;padding-bottom:0}
.pf-act-ic{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0}

/* ── info rows ── */
.pf-info{display:flex;align-items:center;padding:10px 0;border-bottom:1px solid #f3f4f6;font-size:13.5px;gap:10px}
.pf-info:last-child{border-bottom:none;padding-bottom:0}
.pf-info-k{flex:1;color:#6b7280}
.pf-info-v{font-weight:700;color:#111827}

/* strength */
.pf-str-bars{display:flex;gap:4px;margin-bottom:5px}
.pf-str-bar{flex:1;height:4px;border-radius:2px;background:#e5e7eb;transition:background .2s}
.pf-str-lbl{font-size:12px}

/* flash */
.pf-flash{padding:13px 18px;border-radius:12px;margin-bottom:0;font-size:14px;font-weight:600;display:flex;align-items:center;gap:10px}
</style>

<?php if(!empty($flash)): ?>
<div style="max-width:1040px;margin:24px auto 0;padding:0 20px">
  <div class="pf-flash" style="background:<?=$flash['type']==='success'?'#ecfdf5':'#fef2f2'?>;color:<?=$flash['type']==='success'?'#065f46':'#991b1b'?>;border:1px solid <?=$flash['type']==='success'?'#a7f3d0':'#fca5a5'?>">
    <i class="bi bi-<?=$flash['type']==='success'?'check-circle-fill':'exclamation-circle-fill'?>"></i>
    <?=$e($flash['message'])?>
  </div>
</div>
<?php endif; ?>

<div class="pf-wrap">

  <!-- ── Left sidebar ─────────────────────────────────────────────────────── -->
  <div class="pf-sidebar">

    <!-- Avatar preview — file input is inside the main form below -->
    <div class="pf-av-outer" style="display:inline-block;position:relative;margin-bottom:14px">
      <?php if(!empty($u['profile_photo'])): ?>
        <img src="<?=$e(APP_URL.'/storage/uploads/'.$u['profile_photo'])?>"
             alt="Profile" class="pf-av" id="pf-av-el">
      <?php else: ?>
        <div class="pf-av-init" id="pf-av-el"><?=$e($initials)?></div>
      <?php endif; ?>
      <label class="pf-av-cam" for="pf-photo-inp" title="Change photo — click Save Changes to upload">
        <i class="bi bi-camera-fill"></i>
      </label>
    </div>

    <h2 class="pf-sid-name"><?=$e($fullName)?></h2>
    <p class="pf-sid-email"><?=$e($u['email']??'')?></p>
    <span class="pf-sid-role"><?=$e($roleDisplay)?></span>

    <div class="pf-sid-stats">
      <div>
        <div class="pf-sid-stat-n" style="color:#6366f1"><?=number_format($stats['enrollments'])?></div>
        <div class="pf-sid-stat-l">Enrolled</div>
      </div>
      <div>
        <div class="pf-sid-stat-n" style="color:#059669"><?=number_format($stats['completions'])?></div>
        <div class="pf-sid-stat-l">Completed</div>
      </div>
      <div>
        <div class="pf-sid-stat-n" style="color:#d97706"><?=number_format($stats['certificates'])?></div>
        <div class="pf-sid-stat-l">Certs</div>
      </div>
    </div>

    <?php if(!empty($u['last_login_at'])): ?>
    <div class="pf-sid-last">
      <i class="bi bi-clock me-1"></i>
      Last login: <?=date('d M Y, H:i',strtotime($u['last_login_at']))?>
    </div>
    <?php endif; ?>
  </div>

  <!-- ── Right column ─────────────────────────────────────────────────────── -->
  <div class="pf-right">

    <!-- Edit Profile -->
    <div class="pf-card">
      <div class="pf-card-hd">
        <i class="bi bi-person-fill pf-card-hd-ico"></i>
        <h3 class="pf-card-title">Edit Profile</h3>
      </div>
      <div class="pf-card-body">
        <form method="POST" action="<?=$url('learn/profile/update')?>" enctype="multipart/form-data" id="pf-main-form">
          <input type="hidden" name="csrf_token" value="<?=$e($csrf_token)?>">

          <!-- Profile photo (hidden input — triggered by camera label in sidebar) -->
          <input type="file" id="pf-photo-inp" name="profile_photo"
                 accept="image/jpeg,image/png,image/webp,image/gif"
                 style="display:none" onchange="pfPhotoPreview(this)">
          <div id="pf-photo-notice" style="display:none;background:#fffbeb;border:1px solid #fde68a;border-radius:9px;padding:10px 14px;margin-bottom:16px;font-size:13px;color:#92400e;display:flex;align-items:center;gap:8px">
            <i class="bi bi-image-fill" style="flex-shrink:0"></i>
            <span>New photo selected — click <strong>Save Changes</strong> below to upload it.</span>
          </div>

          <div class="pf-grid" style="margin-bottom:18px">
            <div class="pf-fg">
              <label class="pf-lbl" for="pf-fn">First Name <span class="pf-req">*</span></label>
              <input id="pf-fn" type="text" class="pf-inp" name="first_name"
                     value="<?=$e($u['first_name']??'')?>" placeholder="First name" required>
            </div>
            <div class="pf-fg">
              <label class="pf-lbl" for="pf-ln">Last Name <span class="pf-req">*</span></label>
              <input id="pf-ln" type="text" class="pf-inp" name="last_name"
                     value="<?=$e($u['last_name']??'')?>" placeholder="Last name" required>
            </div>
          </div>
          <div class="pf-grid single" style="margin-bottom:18px">
            <div class="pf-fg">
              <label class="pf-lbl" for="pf-email">Email Address <span class="pf-req">*</span></label>
              <input id="pf-email" type="email" class="pf-inp"
                     value="<?=$e($u['email']??'')?>" disabled
                     style="background:#f9fafb;color:#6b7280;cursor:not-allowed"
                     title="Email cannot be changed">
            </div>
          </div>
          <div class="pf-grid single" style="margin-bottom:18px">
            <div class="pf-fg">
              <label class="pf-lbl" for="pf-bio">Bio</label>
              <textarea id="pf-bio" class="pf-inp" name="bio"
                        placeholder="Write a short bio about yourself…"><?=$e($u['bio']??'')?></textarea>
            </div>
          </div>
          <div class="pf-grid" style="margin-bottom:24px">
            <div class="pf-fg">
              <label class="pf-lbl" for="pf-phone">Phone</label>
              <input id="pf-phone" type="tel" class="pf-inp" name="phone"
                     value="<?=$e($u['phone']??'')?>" placeholder="+1 555 000 0000">
            </div>
            <div class="pf-fg">
              <label class="pf-lbl" for="pf-tz">Timezone</label>
              <select id="pf-tz" class="pf-inp" name="timezone">
                <?php foreach(['UTC','Asia/Kolkata','Asia/Dubai','Asia/Singapore','Asia/Tokyo',
                               'Europe/London','Europe/Paris','America/New_York',
                               'America/Los_Angeles','Australia/Sydney'] as $tz): ?>
                <option value="<?=$e($tz)?>" <?=($u['timezone']??'UTC')===$tz?'selected':''?>>
                  <?=$e($tz)?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <button type="submit" class="pf-btn pf-btn-primary">
            <i class="bi bi-check-circle-fill"></i> Save Changes
          </button>
        </form>
      </div>
    </div>

    <!-- Change Password -->
    <div class="pf-card">
      <div class="pf-card-hd">
        <i class="bi bi-shield-lock-fill pf-card-hd-ico"></i>
        <h3 class="pf-card-title">Change Password</h3>
      </div>
      <div class="pf-card-body">
        <form method="POST" action="<?=$url('learn/profile/password')?>">
          <input type="hidden" name="csrf_token" value="<?=$e($csrf_token)?>">
          <div class="pf-grid single" style="margin-bottom:18px">
            <div class="pf-fg">
              <label class="pf-lbl" for="pf-cpw">Current Password <span class="pf-req">*</span></label>
              <div class="pf-pw-wrap">
                <input id="pf-cpw" type="password" class="pf-inp" name="current_password"
                       placeholder="Enter current password" required autocomplete="current-password">
                <button type="button" class="pf-pw-eye" onclick="pfEye('pf-cpw',this)" title="Show/hide">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>
          </div>
          <div class="pf-grid" style="margin-bottom:18px">
            <div class="pf-fg">
              <label class="pf-lbl" for="pf-npw">New Password <span class="pf-req">*</span></label>
              <div class="pf-pw-wrap">
                <input id="pf-npw" type="password" class="pf-inp" name="new_password"
                       placeholder="Min. 8 characters" minlength="8" required autocomplete="new-password"
                       oninput="pfStrength(this.value)">
                <button type="button" class="pf-pw-eye" onclick="pfEye('pf-npw',this)" title="Show/hide">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>
            <div class="pf-fg">
              <label class="pf-lbl" for="pf-pw2">Confirm New Password <span class="pf-req">*</span></label>
              <div class="pf-pw-wrap">
                <input id="pf-pw2" type="password" class="pf-inp" name="confirm_password"
                       placeholder="Repeat new password" required autocomplete="new-password">
                <button type="button" class="pf-pw-eye" onclick="pfEye('pf-pw2',this)" title="Show/hide">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>
          </div>
          <!-- Strength -->
          <div id="pf-str" style="margin-bottom:20px;display:none">
            <div class="pf-str-bars">
              <?php for($i=0;$i<5;$i++): ?>
              <div class="pf-str-bar" id="pf-bar-<?=$i?>"></div>
              <?php endfor; ?>
            </div>
            <span class="pf-str-lbl" id="pf-str-lbl" style="color:#9ca3af"></span>
          </div>
          <button type="submit" class="pf-btn pf-btn-warning">
            <i class="bi bi-shield-check"></i> Update Password
          </button>
        </form>
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="pf-card">
      <div class="pf-card-hd">
        <i class="bi bi-clock-history pf-card-hd-ico"></i>
        <h3 class="pf-card-title">Recent Activity</h3>
      </div>
      <div class="pf-card-body" style="padding-top:8px;padding-bottom:8px">
        <?php if(empty($activity)): ?>
        <div style="text-align:center;padding:24px 0;color:#9ca3af">
          <i class="bi bi-journal-x" style="font-size:2rem;display:block;margin-bottom:8px;opacity:.4"></i>
          <p style="font-size:13.5px;margin:0 0 8px">No lessons started yet.</p>
          <a href="<?=$url('learn/courses')?>" style="font-size:13px;color:#6366f1;font-weight:700">
            Browse courses →
          </a>
        </div>
        <?php else: foreach($activity as $a): $done=$a['status']==='completed'; ?>
        <div class="pf-act">
          <div class="pf-act-ic" style="background:<?=$done?'#ecfdf5':'#ededff'?>">
            <i class="bi bi-<?=$done?'check-circle-fill':'play-circle-fill'?>"
               style="color:<?=$done?'#059669':'#6366f1'?>;font-size:16px"></i>
          </div>
          <div style="flex:1;min-width:0">
            <div style="font-size:13.5px;font-weight:600;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
              <?=$e($a['lesson_title'])?>
            </div>
            <div style="font-size:12px;color:#9ca3af;margin-top:2px"><?=$e($a['course_title'])?></div>
          </div>
          <div style="font-size:12px;color:#9ca3af;flex-shrink:0">
            <?=$a['last_accessed']?date('d M',strtotime($a['last_accessed'])):''?>
          </div>
        </div>
        <?php endforeach; endif; ?>
      </div>
    </div>

  </div><!-- /.pf-right -->
</div><!-- /.pf-wrap -->

<script>
function pfPhotoPreview(inp){
  if(!inp.files||!inp.files[0])return;
  var reader=new FileReader();
  reader.onload=function(e){
    var el=document.getElementById('pf-av-el');
    if(!el)return;
    if(el.tagName==='IMG'){el.src=e.target.result;}
    else{
      var img=document.createElement('img');
      img.className='pf-av';img.id='pf-av-el';
      img.src=e.target.result;el.replaceWith(img);
    }
    // Show notice to click Save Changes
    var notice=document.getElementById('pf-photo-notice');
    if(notice)notice.style.display='flex';
  };
  reader.readAsDataURL(inp.files[0]);
}

function pfEye(id,btn){
  var inp=document.getElementById(id);
  var i=btn.querySelector('i');
  inp.type=inp.type==='password'?'text':'password';
  i.className=inp.type==='password'?'bi bi-eye':'bi bi-eye-slash';
}

function pfStrength(v){
  var w=document.getElementById('pf-str');
  var lbl=document.getElementById('pf-str-lbl');
  if(!v){w.style.display='none';return;}
  w.style.display='block';
  var s=0;
  if(v.length>=8)s++;if(v.length>=12)s++;
  if(/[A-Z]/.test(v))s++;if(/[0-9]/.test(v))s++;if(/[^A-Za-z0-9]/.test(v))s++;
  var cfg=[
    ['#e5e7eb',''],['#ef4444','Very weak'],['#f97316','Weak'],
    ['#eab308','Fair'],['#22c55e','Strong'],['#059669','Very strong']
  ];
  var c=cfg[Math.min(s,5)];
  for(var i=0;i<5;i++){
    document.getElementById('pf-bar-'+i).style.background=i<s?c[0]:'#e5e7eb';
  }
  lbl.textContent=c[1];lbl.style.color=c[0];
}
</script>
