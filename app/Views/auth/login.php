<?php
use App\Core\View;
use App\Middleware\CsrfMiddleware;
$e   = fn(mixed $v): string => View::e($v);
$url = fn(string $p = ''): string => View::url($p);

$flash             = $flash ?? [];
$recaptchaEnabled  = $recaptcha_enabled  ?? false;
$recaptchaSiteKey  = $recaptcha_site_key ?? '';
?>

<div class="login-split">

  <!-- LEFT — Branding / Feature showcase -->
  <div class="login-split-left">
    <!-- Logo -->
    <div class="text-center mb-5" style="position:relative;z-index:1;width:100%;max-width:400px">
      <div style="width:72px;height:72px;background:rgba(255,255,255,.15);border-radius:20px;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;backdrop-filter:blur(10px)">
        <i class="fas fa-graduation-cap" style="font-size:32px;color:#fff"></i>
      </div>
      <h1 style="color:#fff;font-size:2rem;font-weight:800;margin-bottom:8px;letter-spacing:-.5px">LMS Advisor</h1>
      <p style="color:rgba(255,255,255,.6);font-size:15px;margin-bottom:0">Enterprise Learning Management System</p>
    </div>

    <!-- Feature list -->
    <ul class="login-feature-list" style="max-width:360px;width:100%">
      <li>
        <span class="login-feat-icon"><i class="bi bi-book-half" style="color:#818cf8"></i></span>
        <div>
          <div style="font-weight:600;color:#fff;margin-bottom:2px">Rich Course Library</div>
          <div style="font-size:13px;color:rgba(255,255,255,.55)">Video, SCORM, quizzes and text lessons</div>
        </div>
      </li>
      <li>
        <span class="login-feat-icon"><i class="bi bi-patch-question-fill" style="color:#f59e0b"></i></span>
        <div>
          <div style="font-weight:600;color:#fff;margin-bottom:2px">Smart Assessments</div>
          <div style="font-size:13px;color:rgba(255,255,255,.55)">Auto-graded quizzes with instant feedback</div>
        </div>
      </li>
      <li>
        <span class="login-feat-icon"><i class="bi bi-award-fill" style="color:#10b981"></i></span>
        <div>
          <div style="font-weight:600;color:#fff;margin-bottom:2px">Certificates & Achievements</div>
          <div style="font-size:13px;color:rgba(255,255,255,.55)">Auto-generated on course completion</div>
        </div>
      </li>
      <li>
        <span class="login-feat-icon"><i class="bi bi-trophy-fill" style="color:#f97316"></i></span>
        <div>
          <div style="font-weight:600;color:#fff;margin-bottom:2px">Leaderboard & Gamification</div>
          <div style="font-size:13px;color:rgba(255,255,255,.55)">Points, rankings and progress tracking</div>
        </div>
      </li>
      <li>
        <span class="login-feat-icon"><i class="bi bi-phone-fill" style="color:#38bdf8"></i></span>
        <div>
          <div style="font-weight:600;color:#fff;margin-bottom:2px">Mobile PWA</div>
          <div style="font-size:13px;color:rgba(255,255,255,.55)">Install on phone, learn anywhere offline</div>
        </div>
      </li>
    </ul>

    <!-- Bottom tagline -->
    <p style="position:relative;z-index:1;color:rgba(255,255,255,.3);font-size:12.5px;margin-top:48px;text-align:center">
      Proudly developed by LMS Advisor
    </p>
  </div>

  <!-- RIGHT — Login form -->
  <div class="login-split-right">
    <div class="login-form-card" style="width:100%;max-width:400px">

      <!-- Mobile logo (hidden on desktop) -->
      <div class="d-md-none text-center mb-4">
        <div style="width:56px;height:56px;background:#6366f1;border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 12px">
          <i class="fas fa-graduation-cap" style="font-size:24px;color:#fff"></i>
        </div>
        <h2 style="font-size:1.4rem;font-weight:800;color:var(--text-primary)">LMS Advisor</h2>
      </div>

      <h2 style="font-size:1.6rem;font-weight:800;color:var(--text-primary);margin-bottom:4px">Welcome back 👋</h2>
      <p style="color:var(--text-muted);font-size:14px;margin-bottom:28px">Sign in to your account to continue</p>

      <!-- Flash messages -->
      <?php foreach ($flash as $type => $msg): ?>
        <?php $cls = $type === 'error' ? 'danger' : $e($type); ?>
        <div class="alert alert-<?= $cls ?> d-flex align-items-center gap-2 mb-4" role="alert" style="font-size:13.5px;border-radius:10px">
          <i class="bi <?= $type === 'error' ? 'bi-exclamation-circle' : 'bi-info-circle' ?> flex-shrink-0"></i>
          <div><?= $e($msg) ?></div>
        </div>
      <?php endforeach; ?>

      <!-- Form -->
      <form action="<?= $url('login') ?>" method="POST" id="loginForm" novalidate>
        <?= CsrfMiddleware::field() ?>

        <!-- Email -->
        <div class="mb-3">
          <label class="form-label fw-semibold" style="font-size:13.5px" for="email">Email Address</label>
          <div class="input-group" style="border:1.5px solid var(--border-color);border-radius:10px;overflow:hidden;transition:border-color .15s" id="emailGroup">
            <span class="input-group-text" style="background:transparent;border:none;color:var(--text-muted);padding-left:14px">
              <i class="bi bi-envelope"></i>
            </span>
            <input type="email" class="form-control" id="email" name="email"
                   placeholder="you@example.com" required autocomplete="email"
                   value="<?= $e($_POST['email'] ?? '') ?>"
                   style="border:none;box-shadow:none;font-size:14px;padding:12px 14px 12px 4px"
                   onfocus="document.getElementById('emailGroup').style.borderColor='#6366f1'"
                   onblur="document.getElementById('emailGroup').style.borderColor='var(--border-color)'">
          </div>
        </div>

        <!-- Password -->
        <div class="mb-4">
          <label class="form-label fw-semibold" style="font-size:13.5px" for="password">Password</label>
          <a href="<?= View::url('forgot-password') ?>" style="font-size:12px;color:#6366f1;float:right;text-decoration:none;font-weight:500">Forgot password?</a>
          <div class="input-group" style="border:1.5px solid var(--border-color);border-radius:10px;overflow:hidden;transition:border-color .15s" id="passGroup">
            <span class="input-group-text" style="background:transparent;border:none;color:var(--text-muted);padding-left:14px">
              <i class="bi bi-lock"></i>
            </span>
            <input type="password" class="form-control" id="password" name="password"
                   placeholder="Enter your password" required autocomplete="current-password"
                   style="border:none;box-shadow:none;font-size:14px;padding:12px 4px"
                   onfocus="document.getElementById('passGroup').style.borderColor='#6366f1'"
                   onblur="document.getElementById('passGroup').style.borderColor='var(--border-color)'">
            <button type="button" class="input-group-text" id="togglePw"
                    style="background:transparent;border:none;color:var(--text-muted);padding-right:14px;cursor:pointer">
              <i class="bi bi-eye" id="eyeIcon"></i>
            </button>
          </div>
        </div>

        <?php if ($recaptchaEnabled && $recaptchaSiteKey): ?>
          <div class="g-recaptcha mb-3" data-sitekey="<?= $e($recaptchaSiteKey) ?>"></div>
        <?php endif; ?>

        <!-- Submit -->
        <button type="submit" id="loginBtn"
                style="width:100%;padding:13px;background:linear-gradient(135deg,#6366f1,#1a56db);color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:700;cursor:pointer;transition:opacity .15s,transform .1s;display:flex;align-items:center;justify-content:center;gap:8px">
          <span id="btnLabel"><i class="bi bi-box-arrow-in-right me-1"></i> Sign In</span>
          <span class="spinner-border spinner-border-sm d-none" id="btnSpinner" role="status"></span>
        </button>
      
<?php
$googleEnabled = (bool)(int)\App\Models\Setting::get('google_sso_enabled','0');
$githubEnabled = (bool)(int)\App\Models\Setting::get('github_sso_enabled','0');
if($googleEnabled || $githubEnabled):
?>
<div class="auth-divider" style="display:flex;align-items:center;gap:12px;margin:20px 0">
  <div style="flex:1;height:1px;background:var(--border-color)"></div>
  <span style="font-size:12.5px;color:var(--text-muted);font-weight:600">OR CONTINUE WITH</span>
  <div style="flex:1;height:1px;background:var(--border-color)"></div>
</div>
<div class="d-flex gap-3">
  <?php if($googleEnabled):?>
  <a href="<?= $url('auth/google') ?>" class="btn btn-outline-secondary flex-grow-1 d-flex align-items-center justify-content-center gap-2" style="border-radius:10px;font-weight:600;padding:10px">
    <svg width="18" height="18" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
    Google
  </a>
  <?php endif;?>
  <?php if($githubEnabled):?>
  <a href="<?= $url('auth/github') ?>" class="btn btn-dark flex-grow-1 d-flex align-items-center justify-content-center gap-2" style="border-radius:10px;font-weight:600;padding:10px">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="white"><path d="M12 0C5.374 0 0 5.373 0 12c0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23A11.509 11.509 0 0 1 12 5.803c1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576C20.566 21.797 24 17.3 24 12c0-6.627-5.373-12-12-12z"/></svg>
    GitHub
  </a>
  <?php endif;?>
</div>
<?php endif;?>
</form>

      <p style="text-align:center;font-size:12.5px;color:var(--text-muted);margin-top:24px">
        Protected by enterprise-grade security · <?= date('Y') ?> LMS Advisor
      </p>
    </div>
  </div>

</div>

<style>
/* Override auth-body for split layout */
.auth-body {
  padding: 0 !important;
  align-items: stretch !important;
  justify-content: flex-start !important;
  background: none !important;
}
.login-feat-icon {
  width: 40px; height: 40px;
  border-radius: 10px;
  background: rgba(255,255,255,.1);
  display: flex; align-items: center; justify-content: center;
  font-size: 18px;
  flex-shrink: 0;
  backdrop-filter: blur(4px);
}
.login-split-left .login-feature-list li {
  margin-bottom: 20px;
}
</style>

<?php if ($recaptchaEnabled && $recaptchaSiteKey): ?>
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php endif; ?>

<script>
document.getElementById('togglePw').addEventListener('click', function () {
  const pw  = document.getElementById('password');
  const ico = document.getElementById('eyeIcon');
  if (pw.type === 'password') { pw.type = 'text'; ico.className = 'bi bi-eye-slash'; }
  else { pw.type = 'password'; ico.className = 'bi bi-eye'; }
});

document.getElementById('loginForm').addEventListener('submit', function () {
  const email = document.getElementById('email').value.trim();
  const pass  = document.getElementById('password').value;
  if (!email || !pass) return;
  document.getElementById('loginBtn').disabled = true;
  document.getElementById('btnLabel').classList.add('d-none');
  document.getElementById('btnSpinner').classList.remove('d-none');
});
</script>
