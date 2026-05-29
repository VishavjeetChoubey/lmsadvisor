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
.pf{max-width:960px;margin:0 auto;padding:28px 20px 60px}

/* cover hero */
.pf-hero{background:var(--content-bg);border:1px solid var(--border-color);border-radius:18px;overflow:hidden;margin-bottom:24px}
.pf-cover{height:100px;background:linear-gradient(135deg,#6366f1 0%,#3b82f6 100%)}
.pf-hero-body{padding:0 28px 24px;position:relative}
.pf-av-ring{position:absolute;top:-42px;left:28px;width:84px;height:84px;border-radius:50%;border:4px solid var(--content-bg);overflow:hidden;background:#e2e8f0;cursor:pointer}
.pf-av-ring img{width:100%;height:100%;object-fit:cover;display:block}
.pf-av-init{width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:800;color:#fff;background:linear-gradient(135deg,#6366f1,#3b82f6)}
.pf-av-overlay{position:absolute;inset:0;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity .15s;border-radius:50%}
.pf-av-ring:hover .pf-av-overlay{opacity:1}
.pf-av-overlay i{color:#fff;font-size:20px}
.pf-hero-info{padding-top:52px}
.pf-fullname{font-size:20px;font-weight:800;color:var(--text-primary);margin:0 0 2px}
.pf-email-line{font-size:13px;color:var(--text-muted);margin:0 0 12px}
.pf-badges{display:flex;gap:8px;flex-wrap:wrap}
.pf-badge{display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700}
.pf-badge-role{background:#ededff;color:#4338ca}
.pf-stats{display:flex;gap:0;border-top:1px solid var(--border-color);margin-top:20px}
.pf-stat{flex:1;text-align:center;padding:14px 8px;border-right:1px solid var(--border-color)}
.pf-stat:last-child{border-right:none}
.pf-stat-n{font-size:22px;font-weight:800;color:#6366f1;line-height:1}
.pf-stat-l{font-size:12px;color:var(--text-muted);margin-top:4px}

/* section card */
.pf-card{background:var(--content-bg);border:1px solid var(--border-color);border-radius:16px;margin-bottom:20px;overflow:hidden}
.pf-card-hd{padding:18px 24px 16px;border-bottom:1px solid var(--border-color);display:flex;align-items:center;gap:10px}
.pf-card-hd-icon{width:34px;height:34px;border-radius:10px;background:#ededff;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.pf-card-hd-icon i{color:#6366f1;font-size:17px}
.pf-card-hd-text{flex:1}
.pf-card-hd-title{font-size:14px;font-weight:700;color:var(--text-primary);margin:0}
.pf-card-hd-sub{font-size:12px;color:var(--text-muted);margin:2px 0 0}
.pf-card-body{padding:22px 24px}

/* inputs */
.pf-row{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:18px}
.pf-row.single{grid-template-columns:1fr}
.pf-fg{display:flex;flex-direction:column;gap:6px}
.pf-label{font-size:12.5px;font-weight:700;color:var(--text-primary);letter-spacing:.01em}
.pf-hint{font-size:11.5px;color:var(--text-muted);margin-top:-2px}
.pf-inp{width:100%;padding:10px 14px;border-radius:10px;border:1.5px solid var(--border-color);background:var(--card-bg,#f8fafc);color:var(--text-primary);font-size:14px;font-family:inherit;outline:none;transition:border-color .15s,box-shadow .15s;line-height:1.4}
.pf-inp:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.12);background:var(--content-bg)}
.pf-inp::placeholder{color:var(--text-muted);opacity:.7}
textarea.pf-inp{resize:vertical;min-height:90px}
select.pf-inp{cursor:pointer}

/* password fields */
.pf-pw-wrap{position:relative}
.pf-pw-wrap .pf-inp{padding-right:44px}
.pf-pw-eye{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted);font-size:16px;padding:2px;line-height:1}
.pf-pw-eye:hover{color:var(--text-primary)}

/* save button */
.pf-btn{display:inline-flex;align-items:center;gap:7px;padding:11px 28px;border-radius:10px;border:none;background:#6366f1;color:#fff;font-size:14px;font-weight:700;cursor:pointer;transition:background .15s,transform .1s;font-family:inherit}
.pf-btn:hover{background:#4f46e5}
.pf-btn:active{transform:scale(.98)}
.pf-btn-ghost{background:transparent;color:var(--text-muted);border:1.5px solid var(--border-color)}
.pf-btn-ghost:hover{background:var(--card-bg);color:var(--text-primary);border-color:var(--border-color)}

/* activity */
.pf-act{display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid var(--border-color)}
.pf-act:last-child{border-bottom:none;padding-bottom:0}
.pf-act-ic{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.pf-act-ic.done{background:#ecfdf5}
.pf-act-ic.prog{background:#ededff}

/* info table */
.pf-info-r{display:flex;align-items:center;padding:10px 0;border-bottom:1px solid var(--border-color);font-size:13.5px;gap:10px}
.pf-info-r:last-child{border-bottom:none;padding-bottom:0}
.pf-info-r i{color:#6366f1;font-size:15px;width:20px;flex-shrink:0}
.pf-info-k{flex:1;color:var(--text-muted)}
.pf-info-v{font-weight:700;color:var(--text-primary)}

/* flash */
.pf-flash{padding:13px 18px;border-radius:12px;margin-bottom:20px;font-size:14px;font-weight:600;display:flex;align-items:center;gap:10px}
.pf-flash.ok{background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0}
.pf-flash.err{background:#fef2f2;color:#991b1b;border:1px solid #fca5a5}

@media(max-width:640px){
  .pf-row{grid-template-columns:1fr}
  .pf-stats{flex-wrap:wrap}
  .pf-stat{min-width:33%}
}
</style>

<div class="pf">

<?php if(!empty($flash)): ?>
<div class="pf-flash <?= $flash['type']==='success'?'ok':'err' ?>">
  <i class="bi bi-<?= $flash['type']==='success'?'check-circle-fill':'exclamation-circle-fill' ?>"></i>
  <?= $e($flash['message']) ?>
</div>
<?php endif; ?>

<!-- ── Hero card ─────────────────────────────────────────────────────────────── -->
<div class="pf-hero">
  <div class="pf-cover"></div>
  <div class="pf-hero-body">
    <!-- Avatar — click to change photo -->
    <form method="POST" action="<?= $url('learn/profile/update') ?>"
          enctype="multipart/form-data" id="pf-photo-form">
      <input type="hidden" name="csrf_token"      value="<?= $e($csrf_token) ?>">
      <input type="hidden" name="first_name"       value="<?= $e($u['first_name']??'') ?>">
      <input type="hidden" name="last_name"        value="<?= $e($u['last_name']??'') ?>">
      <input type="hidden" name="bio"              value="<?= $e($u['bio']??'') ?>">
      <input type="hidden" name="phone"            value="<?= $e($u['phone']??'') ?>">
      <input type="hidden" name="timezone"         value="<?= $e($u['timezone']??'UTC') ?>">
      <label class="pf-av-ring" for="pf-photo-inp" title="Click to change photo">
        <?php if(!empty($u['profile_photo'])): ?>
          <img src="<?= $e(APP_URL.'/storage/uploads/'.$u['profile_photo']) ?>"
               alt="Profile photo" id="pf-av-img">
        <?php else: ?>
          <div class="pf-av-init" id="pf-av-img"><?= $e($initials) ?></div>
        <?php endif; ?>
        <div class="pf-av-overlay"><i class="bi bi-camera-fill"></i></div>
      </label>
      <input type="file" id="pf-photo-inp" name="profile_photo"
             accept="image/jpeg,image/png,image/webp,image/gif"
             style="display:none" onchange="pfPhotoChange(this)">
    </form>

    <div class="pf-hero-info">
      <h1 class="pf-fullname"><?= $e($fullName) ?></h1>
      <p class="pf-email-line"><?= $e($u['email']??'') ?></p>
      <div class="pf-badges">
        <span class="pf-badge pf-badge-role">
          <i class="bi bi-person-badge-fill" style="font-size:11px"></i>
          <?= $e($roleDisplay) ?>
        </span>
        <?php if($stats['completions']>0): ?>
        <span class="pf-badge" style="background:#ecfdf5;color:#065f46">
          <i class="bi bi-award-fill" style="font-size:11px"></i>
          <?= $stats['completions'] ?> course<?= $stats['completions']!==1?'s':'' ?> completed
        </span>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="pf-stats">
    <div class="pf-stat">
      <div class="pf-stat-n"><?= number_format($stats['enrollments']) ?></div>
      <div class="pf-stat-l">Enrolled</div>
    </div>
    <div class="pf-stat">
      <div class="pf-stat-n"><?= number_format($stats['completions']) ?></div>
      <div class="pf-stat-l">Completed</div>
    </div>
    <div class="pf-stat">
      <div class="pf-stat-n"><?= number_format($stats['certificates']) ?></div>
      <div class="pf-stat-l">Certificates</div>
    </div>
  </div>
</div>

<div class="row g-4">
<div class="col-lg-7">

  <!-- ── Edit Profile ──────────────────────────────────────────────────────── -->
  <div class="pf-card">
    <div class="pf-card-hd">
      <div class="pf-card-hd-icon"><i class="bi bi-person-fill"></i></div>
      <div class="pf-card-hd-text">
        <p class="pf-card-hd-title">Personal Information</p>
        <p class="pf-card-hd-sub">Update your name, bio and contact details</p>
      </div>
    </div>
    <div class="pf-card-body">
      <form method="POST" action="<?= $url('learn/profile/update') ?>" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $e($csrf_token) ?>">
        <div class="pf-row">
          <div class="pf-fg">
            <label class="pf-label" for="pf-fn">First name</label>
            <input id="pf-fn" type="text" class="pf-inp" name="first_name"
                   value="<?= $e($u['first_name']??'') ?>"
                   placeholder="Your first name" required>
          </div>
          <div class="pf-fg">
            <label class="pf-label" for="pf-ln">Last name</label>
            <input id="pf-ln" type="text" class="pf-inp" name="last_name"
                   value="<?= $e($u['last_name']??'') ?>"
                   placeholder="Your last name" required>
          </div>
        </div>
        <div class="pf-row single" style="margin-bottom:18px">
          <div class="pf-fg">
            <label class="pf-label" for="pf-bio">Bio</label>
            <p class="pf-hint">A short description about yourself</p>
            <textarea id="pf-bio" class="pf-inp" name="bio"
                      placeholder="e.g. Senior developer passionate about clean code…"><?= $e($u['bio']??'') ?></textarea>
          </div>
        </div>
        <div class="pf-row" style="margin-bottom:24px">
          <div class="pf-fg">
            <label class="pf-label" for="pf-phone">Phone</label>
            <input id="pf-phone" type="tel" class="pf-inp" name="phone"
                   value="<?= $e($u['phone']??'') ?>"
                   placeholder="+1 555 000 0000">
          </div>
          <div class="pf-fg">
            <label class="pf-label" for="pf-tz">Timezone</label>
            <select id="pf-tz" class="pf-inp" name="timezone">
              <?php foreach(['UTC','Asia/Kolkata','Asia/Dubai','Asia/Singapore','Asia/Tokyo',
                             'Europe/London','Europe/Paris','America/New_York',
                             'America/Los_Angeles','Australia/Sydney'] as $tz): ?>
              <option value="<?= $e($tz) ?>" <?= ($u['timezone']??'UTC')===$tz?'selected':'' ?>>
                <?= $e($tz) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <button type="submit" class="pf-btn">
          <i class="bi bi-check2"></i> Save Changes
        </button>
      </form>
    </div>
  </div>

  <!-- ── Change Password ───────────────────────────────────────────────────── -->
  <div class="pf-card">
    <div class="pf-card-hd">
      <div class="pf-card-hd-icon" style="background:#fef2f2">
        <i class="bi bi-shield-lock-fill" style="color:#dc2626"></i>
      </div>
      <div class="pf-card-hd-text">
        <p class="pf-card-hd-title">Change Password</p>
        <p class="pf-card-hd-sub">Choose a strong password of at least 8 characters</p>
      </div>
    </div>
    <div class="pf-card-body">
      <form method="POST" action="<?= $url('learn/profile/password') ?>">
        <input type="hidden" name="csrf_token" value="<?= $e($csrf_token) ?>">
        <div class="pf-row single" style="margin-bottom:18px">
          <div class="pf-fg">
            <label class="pf-label" for="pf-cpw">Current password</label>
            <div class="pf-pw-wrap">
              <input id="pf-cpw" type="password" class="pf-inp" name="current_password"
                     placeholder="Enter your current password" required autocomplete="current-password">
              <button type="button" class="pf-pw-eye" onclick="pfTogglePw('pf-cpw',this)">
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </div>
        </div>
        <div class="pf-row" style="margin-bottom:24px">
          <div class="pf-fg">
            <label class="pf-label" for="pf-npw">New password</label>
            <div class="pf-pw-wrap">
              <input id="pf-npw" type="password" class="pf-inp" name="new_password"
                     placeholder="Min. 8 characters" minlength="8" required autocomplete="new-password">
              <button type="button" class="pf-pw-eye" onclick="pfTogglePw('pf-npw',this)">
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </div>
          <div class="pf-fg">
            <label class="pf-label" for="pf-cpw2">Confirm new password</label>
            <div class="pf-pw-wrap">
              <input id="pf-cpw2" type="password" class="pf-inp" name="confirm_password"
                     placeholder="Repeat new password" required autocomplete="new-password">
              <button type="button" class="pf-pw-eye" onclick="pfTogglePw('pf-cpw2',this)">
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </div>
        </div>
        <!-- Strength indicator -->
        <div id="pf-strength-wrap" style="margin-bottom:20px;display:none">
          <div style="display:flex;gap:4px;margin-bottom:5px" id="pf-strength-bars">
            <?php for($i=0;$i<5;$i++): ?>
            <div style="flex:1;height:4px;border-radius:2px;background:var(--border-color);transition:background .2s" data-bar="<?=$i?>"></div>
            <?php endfor; ?>
          </div>
          <span id="pf-strength-lbl" style="font-size:12px;color:var(--text-muted)"></span>
        </div>
        <button type="submit" class="pf-btn" style="background:#dc2626">
          <i class="bi bi-key-fill"></i> Change Password
        </button>
      </form>
    </div>
  </div>

</div><!-- /col-lg-7 -->
<div class="col-lg-5">

  <!-- ── Recent Activity ───────────────────────────────────────────────────── -->
  <div class="pf-card">
    <div class="pf-card-hd">
      <div class="pf-card-hd-icon" style="background:#fff7ed">
        <i class="bi bi-clock-history" style="color:#d97706"></i>
      </div>
      <div class="pf-card-hd-text">
        <p class="pf-card-hd-title">Recent Activity</p>
        <p class="pf-card-hd-sub">Your last 5 lesson interactions</p>
      </div>
    </div>
    <div class="pf-card-body" style="padding-bottom:16px">
      <?php if(empty($activity)): ?>
        <div style="text-align:center;padding:20px 0;color:var(--text-muted)">
          <i class="bi bi-journal-x" style="font-size:32px;opacity:.4;display:block;margin-bottom:8px"></i>
          <p style="font-size:13.5px;margin:0">No lessons yet.</p>
          <a href="<?= $url('learn/courses') ?>" style="font-size:13px;color:#6366f1;font-weight:700">
            Browse courses →
          </a>
        </div>
      <?php else: foreach($activity as $a):
        $done = $a['status']==='completed';
      ?>
      <div class="pf-act">
        <div class="pf-act-ic <?= $done?'done':'prog' ?>">
          <i class="bi bi-<?= $done?'check-circle-fill':'play-circle-fill' ?>"
             style="color:<?= $done?'#059669':'#6366f1' ?>;font-size:16px"></i>
        </div>
        <div style="flex:1;min-width:0">
          <div style="font-size:13px;font-weight:600;color:var(--text-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
            <?= $e($a['lesson_title']) ?>
          </div>
          <div style="font-size:11.5px;color:var(--text-muted);margin-top:2px">
            <?= $e($a['course_title']) ?>
          </div>
        </div>
        <div style="font-size:11.5px;color:var(--text-muted);flex-shrink:0">
          <?= $a['last_accessed']?date('d M',strtotime($a['last_accessed'])):'' ?>
        </div>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>

  <!-- ── Account Info ──────────────────────────────────────────────────────── -->
  <div class="pf-card">
    <div class="pf-card-hd">
      <div class="pf-card-hd-icon" style="background:#ecfdf5">
        <i class="bi bi-info-circle-fill" style="color:#059669"></i>
      </div>
      <div class="pf-card-hd-text">
        <p class="pf-card-hd-title">Account Details</p>
        <p class="pf-card-hd-sub">Your account information</p>
      </div>
    </div>
    <div class="pf-card-body">
      <?php $rows=[
        ['bi-envelope-fill','Email',    $u['email']??''],
        ['bi-calendar3',    'Joined',   $u['created_at']?date('d M Y',strtotime($u['created_at'])):'—'],
        ['bi-person-badge', 'Role',     $roleDisplay],
        ['bi-clock-fill',   'Last login',$u['last_login_at']?date('d M Y H:i',strtotime($u['last_login_at'])):'First visit'],
      ];
      foreach($rows as [$ico,$k,$v]): ?>
      <div class="pf-info-r">
        <i class="bi <?= $ico ?>"></i>
        <span class="pf-info-k"><?= $e($k) ?></span>
        <span class="pf-info-v"><?= $e($v) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

</div><!-- /col-lg-5 -->
</div><!-- /row -->
</div><!-- /.pf -->

<script>
function pfTogglePw(id,btn){
  var inp=document.getElementById(id);
  var ico=btn.querySelector('i');
  if(inp.type==='password'){inp.type='text';ico.className='bi bi-eye-slash';}
  else{inp.type='password';ico.className='bi bi-eye';}
}

function pfPhotoChange(inp){
  if(!inp.files||!inp.files[0]) return;
  var reader=new FileReader();
  reader.onload=function(e){
    var el=document.getElementById('pf-av-img');
    if(!el) return;
    if(el.tagName==='IMG'){el.src=e.target.result;}
    else{
      var img=document.createElement('img');
      img.id='pf-av-img'; img.alt='Profile photo';
      img.style.cssText='width:100%;height:100%;object-fit:cover;display:block';
      img.src=e.target.result;
      el.replaceWith(img);
    }
  };
  reader.readAsDataURL(inp.files[0]);
  document.getElementById('pf-photo-form').submit();
}

/* Password strength meter */
(function(){
  var inp=document.getElementById('pf-npw');
  var wrap=document.getElementById('pf-strength-wrap');
  var bars=document.querySelectorAll('[data-bar]');
  var lbl=document.getElementById('pf-strength-lbl');
  var levels=[
    {min:0, color:'#e2e8f0',label:''},
    {min:1, color:'#ef4444',label:'Very weak'},
    {min:2, color:'#f97316',label:'Weak'},
    {min:3, color:'#eab308',label:'Fair'},
    {min:4, color:'#22c55e',label:'Strong'},
    {min:5, color:'#059669',label:'Very strong'},
  ];
  inp&&inp.addEventListener('input',function(){
    var v=this.value;
    if(!v){wrap.style.display='none';return;}
    wrap.style.display='block';
    var score=0;
    if(v.length>=8)score++;
    if(v.length>=12)score++;
    if(/[A-Z]/.test(v))score++;
    if(/[0-9]/.test(v))score++;
    if(/[^A-Za-z0-9]/.test(v))score++;
    var lvl=levels[Math.min(score,5)];
    bars.forEach(function(b,i){
      b.style.background=i<score?lvl.color:'var(--border-color)';
    });
    lbl.textContent=lvl.label;
    lbl.style.color=lvl.color;
  });
})();
</script>
