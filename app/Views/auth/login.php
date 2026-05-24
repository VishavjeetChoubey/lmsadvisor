<?php
use App\Core\View;
use App\Middleware\CsrfMiddleware;
$e   = fn(mixed $v): string => View::e($v);
$url = fn(string $p = ''): string => View::url($p);

$flash             = $flash ?? [];
$recaptchaEnabled  = $recaptcha_enabled  ?? false;
$recaptchaSiteKey  = $recaptcha_site_key ?? '';
?>
<div class="auth-card">

  <!-- Logo -->
  <div class="auth-logo">
    <i class="fas fa-graduation-cap"></i>
  </div>
  <h1 class="auth-title">Welcome back</h1>
  <p class="auth-subtitle">Sign in to your LMSAdvisor account</p>

  <!-- Flash messages -->
  <?php foreach ($flash as $type => $msg): ?>
    <?php $cls = $type === 'error' ? 'danger' : $e($type); ?>
    <div class="alert alert-<?= $cls ?> d-flex align-items-center gap-2 mb-3" role="alert">
      <i class="bi <?= $type === 'error' ? 'bi-x-circle' : 'bi-info-circle' ?>"></i>
      <div><?= $e($msg) ?></div>
    </div>
  <?php endforeach; ?>

  <!-- Login form -->
  <form action="<?= $url('login') ?>" method="POST" id="loginForm" novalidate>
    <?= CsrfMiddleware::field() ?>

    <div class="form-floating mb-3">
      <input type="email" class="form-control" id="email" name="email"
             placeholder="you@example.com" required autocomplete="email"
             value="<?= $e($_POST['email'] ?? '') ?>">
      <label for="email"><i class="bi bi-envelope me-1"></i> Email address</label>
    </div>

    <div class="form-floating mb-3 position-relative">
      <input type="password" class="form-control" id="password" name="password"
             placeholder="Password" required autocomplete="current-password">
      <label for="password"><i class="bi bi-lock me-1"></i> Password</label>
      <button type="button" class="password-toggle" id="togglePassword" tabindex="-1">
        <i class="bi bi-eye" id="eyeIcon"></i>
      </button>
    </div>

    <?php if ($recaptchaEnabled && $recaptchaSiteKey): ?>
      <div class="g-recaptcha mb-3" data-sitekey="<?= $e($recaptchaSiteKey) ?>"></div>
    <?php endif; ?>

    <button type="submit" class="btn btn-primary w-100 btn-login" id="loginBtn">
      <span class="btn-label"><i class="bi bi-box-arrow-in-right me-2"></i> Sign In</span>
      <span class="spinner-border spinner-border-sm d-none" id="loginSpinner" role="status"></span>
    </button>
  </form>

</div>

<?php if ($recaptchaEnabled && $recaptchaSiteKey): ?>
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php endif; ?>

<script>
document.getElementById('togglePassword').addEventListener('click', function () {
  const pw  = document.getElementById('password');
  const ico = document.getElementById('eyeIcon');
  if (pw.type === 'password') {
    pw.type = 'text';
    ico.className = 'bi bi-eye-slash';
  } else {
    pw.type = 'password';
    ico.className = 'bi bi-eye';
  }
});

document.getElementById('loginForm').addEventListener('submit', function (e) {
  const email = document.getElementById('email').value.trim();
  const pass  = document.getElementById('password').value;
  if (!email || !pass) return; // let HTML5 handle it
  document.getElementById('loginBtn').disabled = true;
  document.querySelector('.btn-label').classList.add('d-none');
  document.getElementById('loginSpinner').classList.remove('d-none');
});
</script>
