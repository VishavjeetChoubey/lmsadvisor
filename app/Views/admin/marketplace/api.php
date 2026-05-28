<?php use App\Core\View; $e=fn($v)=>View::e($v); $url=fn($p='')=>View::url($p); ?>

<div class="adm-page-header mb-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
  <div>
    <h2 class="adm-page-title">🔌 API Developer Portal</h2>
    <p class="adm-page-sub">REST API documentation, token management, and usage analytics.</p>
  </div>
  <a href="<?=$url('admin/api')?>" class="btn btn-outline-primary btn-sm">
    <i class="bi bi-key me-1"></i> Manage Tokens
  </a>
</div>

<!-- Usage summary -->
<div class="row g-3 mb-4">
  <?php
  $totalCalls = array_sum(array_column($tokens,'calls_total'));
  $todayCalls = array_sum(array_column($tokens,'calls_today'));
  $activeTokens = count(array_filter($tokens, fn($t) => $t['is_active']));
  $cards = [
    ['bi-key','Active Tokens',$activeTokens,'#6366f1'],
    ['bi-activity','Calls Today',number_format($todayCalls),'#059669'],
    ['bi-graph-up','Total Calls',number_format($totalCalls),'#0891b2'],
    ['bi-lightning','Endpoints',array_sum(array_map(fn($g)=>count($g),$docs)),'#d97706'],
  ];
  foreach($cards as [$ico,$lbl,$val,$clr]): ?>
  <div class="col-6 col-xl-3">
    <div class="card border-0 shadow-sm">
      <div class="card-body d-flex align-items-center gap-3">
        <div style="width:42px;height:42px;border-radius:12px;background:<?=$clr?>18;display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <i class="bi <?=$ico?>" style="color:<?=$clr?>;font-size:18px"></i>
        </div>
        <div>
          <div style="font-size:20px;font-weight:800"><?=$e((string)$val)?></div>
          <div class="text-muted" style="font-size:12px"><?=$e($lbl)?></div>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="row g-4">
  <!-- Endpoint docs -->
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-transparent fw-bold py-3">
        <i class="bi bi-book me-2"></i>Endpoint Reference
        <span class="badge bg-dark ms-2" style="font-size:11px">Base: <?=rtrim(APP_URL,'/')?>/api/v1</span>
      </div>
      <div class="card-body p-0">
        <?php $methodColors=['GET'=>'#059669','POST'=>'#2563eb','PUT'=>'#d97706','DELETE'=>'#dc2626','PATCH'=>'#7c3aed'];
        foreach($docs as $group=>$endpoints): ?>
        <div style="padding:14px 20px 0;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--bs-secondary-color)"><?=$e($group)?></div>
        <?php foreach($endpoints as $ep): [$method,$path,$desc,$params,$note]=$ep; ?>
        <div style="display:flex;align-items:flex-start;gap:12px;padding:12px 20px;border-bottom:1px solid #f1f5f9">
          <span style="background:<?=$methodColors[$method]??'#374151'?>18;color:<?=$methodColors[$method]??'#374151'?>;font-size:11px;font-weight:700;padding:3px 8px;border-radius:6px;min-width:54px;text-align:center;flex-shrink:0"><?=$e($method)?></span>
          <div style="flex:1;min-width:0">
            <code style="font-size:12.5px;color:#5b5ef6"><?=$e($path)?></code>
            <span style="font-size:13px;color:var(--bs-body-color);margin-left:10px"><?=$e($desc)?></span>
            <?php if($params&&$params!=='—'): ?><div style="font-size:12px;color:var(--bs-secondary-color);margin-top:3px"><i class="bi bi-arrow-right me-1"></i><?=$e($params)?></div><?php endif; ?>
            <?php if($note&&$note!=='—'): ?><div style="font-size:11.5px;color:#d97706;margin-top:2px"><i class="bi bi-info-circle me-1"></i><?=$e($note)?></div><?php endif; ?>
          </div>
        </div>
        <?php endforeach; endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Token usage -->
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-transparent fw-bold py-3"><i class="bi bi-speedometer2 me-2"></i>Token Usage</div>
      <div class="card-body p-0">
        <?php if(empty($tokens)): ?>
        <p class="text-muted text-center py-3" style="font-size:13px">No tokens yet.</p>
        <?php else: foreach(array_slice($tokens,0,8) as $t): ?>
        <div style="padding:10px 16px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:10px">
          <div style="flex:1;min-width:0">
            <div style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?=$e($t['app_name']??$t['name']??'Token #'.$t['id'])?></div>
            <div style="font-size:11.5px;color:var(--bs-secondary-color)"><?=$e($t['first_name'].' '.$t['last_name'])?></div>
          </div>
          <div style="text-align:right;flex-shrink:0">
            <div style="font-size:14px;font-weight:700;color:#6366f1"><?=number_format((int)$t['calls_today'])?></div>
            <div style="font-size:10px;color:var(--bs-secondary-color)">today</div>
          </div>
        </div>
        <?php endforeach; endif; ?>
      </div>
    </div>

    <!-- Authentication guide -->
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-transparent fw-bold py-3"><i class="bi bi-shield-check me-2"></i>Authentication</div>
      <div class="card-body">
        <p style="font-size:13px">All API requests require a Bearer token in the Authorization header:</p>
        <pre style="background:#0f172a;color:#e2e8f0;padding:12px;border-radius:8px;font-size:12px;overflow-x:auto">Authorization: Bearer YOUR_TOKEN
Content-Type: application/json</pre>
        <p style="font-size:13px;margin-top:12px">Generate tokens in <a href="<?=$url('admin/api')?>">Admin → API Tokens</a>.</p>
      </div>
    </div>
  </div>
</div>
