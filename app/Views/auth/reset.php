<?php use App\Core\View; $e=fn($v)=>View::e($v); $url=fn($p='')=>View::url($p); ?>
<div class="auth-card-wrap">
  <div class="auth-card">
    <div class="auth-logo"><i class="bi bi-shield-lock-fill" style="font-size:32px;color:#6366f1"></i></div>
    <h2 class="auth-title">Reset Password</h2>
    <?php if($flash): ?>
    <div class="alert alert-<?=$flash['type']==='success'?'success':'danger'?> mb-3"><?=$e($flash['message'])?></div>
    <?php endif; ?>
    <?php if(!$valid): ?>
    <div class="alert alert-danger">This reset link is invalid or has expired.</div>
    <div class="text-center mt-3"><a href="<?=$url('forgot-password')?>" class="btn btn-outline-primary btn-sm">Request a new link</a></div>
    <?php else: ?>
    <form method="POST" action="<?=$url('reset-password')?>">
      <input type="hidden" name="csrf_token" value="<?=$e($csrf_token)?>">
      <input type="hidden" name="token" value="<?=$e($token)?>">
      <div class="mb-3">
        <label class="form-label fw-semibold">New Password</label>
        <input type="password" class="form-control" name="password" minlength="8" required autofocus>
        <div class="form-text">Minimum 8 characters.</div>
      </div>
      <div class="mb-4">
        <label class="form-label fw-semibold">Confirm Password</label>
        <input type="password" class="form-control" name="password_confirm" required>
      </div>
      <button type="submit" class="btn btn-primary w-100">Set New Password</button>
    </form>
    <?php endif; ?>
  </div>
</div>
