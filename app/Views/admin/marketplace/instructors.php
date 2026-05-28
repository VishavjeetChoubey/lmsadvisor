<?php use App\Core\View; $e=fn($v)=>View::e($v); $url=fn($p='')=>View::url($p); ?>

<div class="adm-page-header mb-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
  <div>
    <h2 class="adm-page-title">🎓 Instructor Marketplace</h2>
    <p class="adm-page-sub">Review instructor applications, manage revenue splits, track performance.</p>
  </div>
</div>

<?php if($flash): ?><div class="alert alert-<?=$flash['type']==='success'?'success':'danger'?> mb-4"><?=$e($flash['message'])?></div><?php endif; ?>

<!-- Summary cards -->
<div class="row g-3 mb-4">
  <?php $cards=[
    ['bi-hourglass-split','Pending', count($pending),'#d97706','#fffbeb'],
    ['bi-check-circle',  'Approved',count($approved),'#059669','#ecfdf5'],
    ['bi-x-circle',      'Rejected',count($rejected),'#dc2626','#fef2f2'],
    ['bi-graph-up',      'Total Enrollments via Instructors',array_sum(array_column($revenue,'total_enrollments')),'#6366f1','#f5f3ff'],
  ];
  foreach($cards as [$ico,$lbl,$val,$clr,$bg]): ?>
  <div class="col-6 col-xl-3">
    <div class="card border-0 shadow-sm" style="background:<?=$bg?>">
      <div class="card-body d-flex align-items-center gap-3">
        <div style="width:42px;height:42px;border-radius:12px;background:<?=$clr?>20;display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <i class="bi <?=$ico?>" style="color:<?=$clr?>;font-size:18px"></i>
        </div>
        <div>
          <div style="font-size:20px;font-weight:800;color:<?=$clr?>"><?=$e((string)$val)?></div>
          <div style="font-size:12px;color:var(--bs-secondary-color)"><?=$e($lbl)?></div>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4">
  <li class="nav-item"><a class="nav-link active" href="#" data-tab="pending">⏳ Pending (<?=count($pending)?>)</a></li>
  <li class="nav-item"><a class="nav-link" href="#" data-tab="approved">✅ Approved (<?=count($approved)?>)</a></li>
  <li class="nav-item"><a class="nav-link" href="#" data-tab="revenue">💰 Revenue</a></li>
</ul>

<!-- Pending applications -->
<div class="tab-pane-content" id="tab-pending">
<?php if(empty($pending)): ?>
<div class="card border-0 shadow-sm"><div class="card-body text-center py-5"><div style="font-size:3rem">✅</div><div class="fw-bold mt-2">No pending applications</div></div></div>
<?php else: foreach($pending as $a): ?>
<div class="card border-0 shadow-sm mb-3">
  <div class="card-body">
    <div class="d-flex align-items-start gap-3 flex-wrap">
      <!-- Avatar — initials fallback -->
      <div style="width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#3b82f6);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:20px;flex-shrink:0">
        <?=strtoupper(substr($a['first_name']??'',0,1).substr($a['last_name']??'',0,1))?>
      </div>
      <!-- Info -->
      <div style="flex:1;min-width:0">
        <div class="fw-bold fs-6"><?=$e($a['first_name'].' '.$a['last_name'])?></div>
        <div class="text-muted" style="font-size:13px"><?=$e($a['email'])?></div>
        <?php if(!empty($a['expertise'])): ?><div style="font-size:13px;margin-top:4px"><strong>Expertise:</strong> <?=$e($a['expertise'])?></div><?php endif; ?>
        <?php if(!empty($a['bio'])): ?><div style="font-size:13px;color:var(--bs-secondary-color);margin-top:6px"><?=$e(mb_substr($a['bio'],0,200))?><?=strlen($a['bio'])>200?'…':''?></div><?php endif; ?>
        <?php if(!empty($a['portfolio_url'])): ?><div style="margin-top:6px"><a href="<?=$e($a['portfolio_url'])?>" target="_blank" style="font-size:13px"><i class="bi bi-link-45deg me-1"></i>Portfolio</a></div><?php endif; ?>
        <div style="font-size:12px;color:var(--bs-secondary-color);margin-top:6px">Applied <?=date('d M Y',strtotime($a['applied_at']))?> · <?=(int)$a['course_count']?> courses · <?=(int)$a['student_count']?> students</div>
      </div>
      <!-- Actions -->
      <div class="d-flex flex-column gap-2" style="flex-shrink:0;min-width:180px">
        <div class="d-flex align-items-center gap-2 mb-1">
          <label style="font-size:12px;white-space:nowrap">Revenue %</label>
          <input type="number" class="form-control form-control-sm" id="rev-<?=(int)$a['id']?>" value="70" min="10" max="95" style="width:70px">
        </div>
        <button class="btn btn-success btn-sm review-btn" data-id="<?=(int)$a['id']?>" data-decision="approved" data-csrf="<?=htmlspecialchars($csrf_token)?>">
          <i class="bi bi-check2 me-1"></i> Approve
        </button>
        <button class="btn btn-outline-danger btn-sm review-btn" data-id="<?=(int)$a['id']?>" data-decision="rejected" data-csrf="<?=htmlspecialchars($csrf_token)?>">
          <i class="bi bi-x me-1"></i> Reject
        </button>
      </div>
    </div>
  </div>
</div>
<?php endforeach; endif; ?>
</div>

<!-- Approved instructors -->
<div class="tab-pane-content d-none" id="tab-approved">
<?php if(empty($approved)): ?>
<div class="card border-0 shadow-sm"><div class="card-body text-center py-4 text-muted">No approved instructors yet.</div></div>
<?php else: ?>
<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0" style="font-size:13.5px">
      <thead class="table-light"><tr><th>Instructor</th><th>Expertise</th><th>Courses</th><th>Students</th><th>Revenue %</th><th>Approved</th></tr></thead>
      <tbody>
        <?php foreach($approved as $a): ?>
        <tr>
          <td>
            <div class="fw-semibold"><?=$e($a['first_name'].' '.$a['last_name'])?></div>
            <div style="font-size:12px;color:var(--bs-secondary-color)"><?=$e($a['email'])?></div>
          </td>
          <td style="font-size:12.5px"><?=$e(mb_substr($a['expertise']??'',0,60))?></td>
          <td><?=(int)$a['course_count']?></td>
          <td><?=(int)$a['student_count']?></td>
          <td><span class="badge bg-success-subtle text-success"><?=(int)$a['revenue_pct']?>%</span></td>
          <td style="color:var(--bs-secondary-color);font-size:12px"><?=$a['reviewed_at']?date('d M Y',strtotime($a['reviewed_at'])):'-'?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
</div>

<!-- Revenue breakdown -->
<div class="tab-pane-content d-none" id="tab-revenue">
<?php if(empty($revenue)): ?>
<div class="card border-0 shadow-sm"><div class="card-body text-center py-4 text-muted">No revenue data yet.</div></div>
<?php else: ?>
<div class="card border-0 shadow-sm">
  <div class="card-header bg-transparent fw-bold py-3">💰 Instructor Revenue Split</div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0" style="font-size:13.5px">
      <thead class="table-light"><tr><th>Instructor</th><th>Courses</th><th>Enrollments</th><th>Instructor Gets</th><th>Platform Gets</th></tr></thead>
      <tbody>
        <?php foreach($revenue as $r):
          $instrPct = (int)($r['revenue_pct']??70);
          $platPct  = 100 - $instrPct;
        ?>
        <tr>
          <td>
            <div class="fw-semibold"><?=$e($r['first_name'].' '.$r['last_name'])?></div>
            <div style="font-size:12px;color:var(--bs-secondary-color)"><?=$e($r['email'])?></div>
          </td>
          <td><?=(int)$r['courses']?></td>
          <td><?=(int)$r['total_enrollments']?></td>
          <td><span class="badge bg-success-subtle text-success fw-bold"><?=$instrPct?>%</span></td>
          <td><span class="badge bg-primary-subtle text-primary fw-bold"><?=$platPct?>%</span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
</div>

<script>
// Tab switching
document.querySelectorAll('[data-tab]').forEach(function(link) {
  link.addEventListener('click', function(e) {
    e.preventDefault();
    document.querySelectorAll('[data-tab]').forEach(function(l) { l.classList.remove('active'); });
    document.querySelectorAll('.tab-pane-content').forEach(function(p) { p.classList.add('d-none'); });
    this.classList.add('active');
    document.getElementById('tab-' + this.dataset.tab).classList.remove('d-none');
  });
});

// Review buttons
document.querySelectorAll('.review-btn').forEach(function(btn) {
  btn.addEventListener('click', function() {
    var id       = this.dataset.id;
    var decision = this.dataset.decision;
    var csrf     = this.dataset.csrf;
    var revPct   = document.getElementById('rev-' + id)?.value || 70;
    var label    = decision === 'approved' ? 'approve' : 'reject';
    if (!confirm('Are you sure you want to ' + label + ' this application?')) return;
    this.disabled = true;
    var self = this;
    fetch('<?=$url('admin/marketplace/instructors/')?>' + id + '/review', {
      method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body:'csrf_token='+encodeURIComponent(csrf)+'&decision='+decision+'&revenue_pct='+revPct
    }).then(r=>r.json()).then(function(d) {
      if(d.success) { location.reload(); }
      else { alert(d.message||'Error'); self.disabled=false; }
    });
  });
});
</script>
