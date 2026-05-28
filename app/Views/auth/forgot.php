<?php use App\Core\View; $e=fn($v)=>View::e($v); $url=fn($p='')=>View::url($p); ?>
<div class="auth-card-wrap">
  <div class="auth-card">
    <div class="auth-logo"><i class="bi bi-key-fill" style="font-size:32px;color:#6366f1"></i></div>
    <h2 class="auth-title">Forgot Password</h2>
    <p class="auth-sub">Enter your email and we'll send a reset link.</p>
    <?php if($flash): ?>
    <div class="alert alert-<?=$flash['type']==='success'?'success':'danger'?> mb-3"><?=$e($flash['message'])?></div>
    <?php endif; ?>
    <form method="POST" action="<?=$url('forgot-password')?>">
      <input type="hidden" name="csrf_token" value="<?=$e($csrf_token)?>">
      <div class="mb-3">
        <label class="form-label fw-semibold">Email Address</label>
        <input type="email" class="form-control" name="email" required autofocus placeholder="you@example.com">
      </div>
      <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
    </form>
    <div class="text-center mt-3" style="font-size:13.5px">
      <a href="<?=$url('login')?>" style="color:#6366f1">← Back to Login</a>
    </div>
  </div>
</div>
