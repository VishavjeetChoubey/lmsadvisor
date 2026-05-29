<?php use App\Core\View; $e=fn($v)=>View::e($v); $url=fn($p='')=>View::url($p); ?>
<div class="adm-page-header mb-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
  <div>
    <h2 class="adm-page-title">🏭 Corporate Training</h2>
    <p class="adm-page-sub">Manage corporate clients, bulk enroll employees, track compliance.</p>
  </div>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newOrgModal">
    <i class="bi bi-plus-circle me-1"></i> New Organisation
  </button>
</div>
<?php if($flash): ?><div class="alert alert-<?=$flash['type']==='success'?'success':'danger'?> mb-4"><?=$e($flash['message'])?></div><?php endif; ?>

<div class="row g-3">
  <?php if(empty($orgs)): ?>
  <div class="col-12">
    <div class="card border-0 shadow-sm text-center py-5">
      <div style="font-size:3rem">🏭</div>
      <div class="fw-bold mt-2">No organisations yet</div>
      <div class="text-muted">Create your first corporate client to get started.</div>
    </div>
  </div>
  <?php else: foreach($orgs as $org): ?>
  <div class="col-12 col-md-6 col-xl-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between mb-3">
          <div>
            <div class="fw-bold fs-6"><?=$e($org['name'])?></div>
            <?php if($org['domain']): ?><div class="text-muted" style="font-size:12px"><?=$e($org['domain'])?></div><?php endif; ?>
          </div>
          <span class="badge bg-<?=$org['status']==='active'?'success':'danger'?>-subtle text-<?=$org['status']==='active'?'success':'danger'?>"><?=ucfirst($e($org['status']))?></span>
        </div>
        <div class="d-flex gap-3 mb-3" style="font-size:13px;color:var(--bs-secondary-color)">
          <span><i class="bi bi-people me-1"></i><?=$org['member_count']?> / <?=$org['seat_limit']?> seats</span>
          <?php if($org['billing_email']): ?><span><i class="bi bi-envelope me-1"></i><?=$e($org['billing_email'])?></span><?php endif; ?>
        </div>
      </div>
      <div class="card-footer bg-transparent border-top-0 pt-0">
        <a href="<?=$url('admin/organisations/'.$org['uuid'])?>" class="btn btn-sm btn-outline-primary w-100">
          <i class="bi bi-arrow-right-circle me-1"></i> Manage
        </a>
      </div>
    </div>
  </div>
  <?php endforeach; endif; ?>
</div>

<!-- New Org Modal -->
<div class="modal fade" id="newOrgModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">New Organisation</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <form method="POST" action="<?=$url('admin/organisations')?>">
        <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(\App\Middleware\CsrfMiddleware::token())?>">
        <div class="modal-body row g-3">
          <div class="col-12"><label class="form-label fw-semibold">Organisation Name *</label><input type="text" class="form-control" name="name" required></div>
          <div class="col-md-6"><label class="form-label fw-semibold">Company Domain</label><input type="text" class="form-control" name="domain" placeholder="acme.com"></div>
          <div class="col-md-6"><label class="form-label fw-semibold">Seat Limit</label><input type="number" class="form-control" name="seat_limit" value="50" min="1"></div>
          <div class="col-12"><label class="form-label fw-semibold">Billing Email</label><input type="email" class="form-control" name="billing_email"></div>
        </div>
        <div class="modal-footer"><button type="submit" class="btn btn-primary">Create Organisation</button></div>
      </form>
    </div>
  </div>
</div>
