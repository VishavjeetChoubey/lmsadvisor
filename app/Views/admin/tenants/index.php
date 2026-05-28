<?php use App\Core\View; $e=fn($v)=>View::e($v); $url=fn($p='')=>View::url($p); ?>
<div class="adm-page-header mb-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
  <div>
    <h2 class="adm-page-title">🏢 Tenants — White-label Clients</h2>
    <p class="adm-page-sub">Manage white-label LMS instances for your clients.</p>
  </div>
  <a href="<?=$url('admin/tenants/create')?>" class="btn btn-primary">
    <i class="bi bi-plus-circle me-1"></i> New Tenant
  </a>
</div>
<?php if($flash): ?><div class="alert alert-<?=$flash['type']==='success'?'success':'danger'?> mb-4"><?=$e($flash['message'])?></div><?php endif; ?>

<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0" style="font-size:13.5px">
      <thead class="table-light">
        <tr><th>Tenant</th><th>Slug</th><th>Plan</th><th>Status</th><th>Seats</th><th>Trial Ends</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php if(empty($tenants)): ?>
        <tr><td colspan="7" class="text-center py-4 text-muted">No tenants yet. <a href="<?=$url('admin/tenants/create')?>">Create the first one →</a></td></tr>
        <?php else: foreach($tenants as $t):
          $planColors = ['trial'=>'secondary','starter'=>'info','pro'=>'primary','enterprise'=>'warning'];
          $statColors = ['active'=>'success','suspended'=>'danger','trial'=>'warning'];
        ?>
        <tr>
          <td>
            <div class="fw-semibold"><?=$e($t['name'])?></div>
            <?php if($t['custom_domain']): ?><div style="font-size:12px;color:var(--bs-secondary-color)"><?=$e($t['custom_domain'])?></div><?php endif; ?>
          </td>
          <td><code style="font-size:12px"><?=$e($t['slug'])?>.lmsadvisor.com</code></td>
          <td><span class="badge bg-<?=$planColors[$t['plan']]??'secondary'?>"><?=ucfirst($e($t['plan']))?></span></td>
          <td><span class="badge bg-<?=$statColors[$t['status']]??'secondary'?>-subtle text-<?=$statColors[$t['status']]??'secondary'?>"><?=ucfirst($e($t['status']))?></span></td>
          <td><?=$e((string)($t['user_count']??0))?> / <?=$e((string)$t['seat_limit'])?></td>
          <td style="font-size:12px;color:var(--bs-secondary-color)"><?=$t['trial_ends_at']?date('d M Y',strtotime($t['trial_ends_at'])):'-'?></td>
          <td>
            <a href="<?=$url('admin/tenants/'.$t['uuid'].'/edit')?>" class="btn btn-sm btn-outline-primary">
              <i class="bi bi-pencil"></i> Edit
            </a>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
