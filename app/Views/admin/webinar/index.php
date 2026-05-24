<?php
use App\Core\View;
$e   = fn(mixed $v): string => View::e($v);
$url = fn(string $p = ''): string => View::url($p);
$statusColors = ['scheduled'=>'primary','live'=>'success','ended'=>'secondary','cancelled'=>'danger'];
$statusIcons  = ['scheduled'=>'bi-calendar-check','live'=>'bi-broadcast','ended'=>'bi-check-circle','cancelled'=>'bi-x-circle'];
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <div class="d-flex gap-2">
    <?php foreach ([''=>'All','scheduled'=>'Scheduled','live'=>'Live','ended'=>'Ended','cancelled'=>'Cancelled'] as $v => $l): ?>
    <a href="<?= $url('admin/webinars' . ($v ? '?status='.$v : '')) ?>"
       class="btn btn-sm <?= $statusFilter === $v ? 'btn-primary' : 'btn-outline-secondary' ?>">
      <?= $l ?>
    </a>
    <?php endforeach; ?>
  </div>
  <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createWebinarModal">
    <i class="bi bi-plus-circle me-1"></i> Schedule Webinar
  </button>
</div>

<!-- Webinar list -->
<div class="card lms-card">
  <div class="card-header lms-card-header">
    <h5 class="mb-0"><i class="bi bi-camera-video me-2"></i> Webinars
      <span class="badge bg-secondary ms-1"><?= count($webinars) ?></span>
    </h5>
  </div>
  <?php if (empty($webinars)): ?>
  <div class="card-body text-center py-5">
    <i class="bi bi-camera-video" style="font-size:3rem;opacity:.2"></i>
    <h6 class="mt-3 text-muted">No webinars yet</h6>
    <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#createWebinarModal">
      <i class="bi bi-plus-circle me-1"></i> Schedule First Webinar
    </button>
  </div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-hover lms-table mb-0">
      <thead><tr>
        <th>Webinar</th><th>Course</th><th>Provider</th><th>Scheduled</th><th>Duration</th><th>Status</th><th></th>
      </tr></thead>
      <tbody>
        <?php foreach ($webinars as $w): ?>
        <tr>
          <td>
            <div class="fw-semibold" style="font-size:13.5px"><?= $e($w['title']) ?></div>
            <?php if ($w['password']): ?>
              <div class="text-muted" style="font-size:11.5px"><i class="bi bi-lock me-1"></i>Password: <?= $e($w['password']) ?></div>
            <?php endif; ?>
          </td>
          <td>
            <a href="<?= $url('admin/courses/'.$w['course_uuid'].'/edit') ?>" class="text-decoration-none" style="font-size:13px">
              <?= $e(mb_strimwidth($w['course_title']??'—',0,35,'…')) ?>
            </a>
          </td>
          <td>
            <span class="badge bg-<?= $w['provider']==='zoom'?'primary':'success' ?>-subtle text-<?= $w['provider']==='zoom'?'primary':'success' ?>">
              <?= $w['provider']==='zoom' ? '🎥 Zoom' : '📹 Google Meet' ?>
            </span>
          </td>
          <td style="font-size:13px"><?= date('d M Y H:i', strtotime($w['scheduled_at'])) ?></td>
          <td class="text-muted" style="font-size:13px"><?= (int)$w['duration_min'] ?> min</td>
          <td>
            <span class="badge bg-<?= $statusColors[$w['status']] ?>">
              <i class="bi <?= $statusIcons[$w['status']] ?> me-1"></i><?= ucfirst($w['status']) ?>
            </span>
          </td>
          <td>
            <div class="d-flex gap-1">
              <?php if ($w['status'] === 'scheduled'): ?>
              <button class="btn btn-xs btn-success btn-start-webinar" data-uuid="<?= $e($w['uuid']) ?>">
                <i class="bi bi-play-fill"></i> Start
              </button>
              <a href="<?= $e($w['join_url']) ?>" target="_blank" class="btn btn-xs btn-outline-primary">
                <i class="bi bi-box-arrow-up-right"></i> Join URL
              </a>
              <button class="btn btn-xs btn-outline-danger btn-cancel-webinar" data-uuid="<?= $e($w['uuid']) ?>" data-title="<?= $e($w['title']) ?>">
                <i class="bi bi-x"></i>
              </button>
              <?php elseif ($w['status'] === 'live'): ?>
              <a href="<?= $e($w['start_url']) ?>" target="_blank" class="btn btn-xs btn-success">
                <i class="bi bi-broadcast me-1"></i> Join Live
              </a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- Create Webinar Modal -->
<div class="modal fade" id="createWebinarModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-semibold"><i class="bi bi-camera-video me-2"></i>Schedule Webinar</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="<?= $url('admin/webinars/create') ?>" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $e($csrf_token) ?>">
        <div class="modal-body px-4">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="title" required placeholder="Webinar title…">
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Course</label>
              <select class="form-select" name="course_id" required>
                <option value="">— Select Course —</option>
                <?php foreach ($courses as $c): ?>
                <option value="<?= $c['id'] ?>"><?= $e($c['title']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Provider</label>
              <select class="form-select" name="provider">
                <option value="zoom">🎥 Zoom</option>
                <option value="google_meet">📹 Google Meet</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Duration (minutes)</label>
              <input type="number" class="form-control" name="duration_min" value="60" min="15" max="480">
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Scheduled At</label>
              <input type="datetime-local" class="form-control" name="scheduled_at" required>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-calendar-plus me-1"></i> Schedule Webinar
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
const CSRF = '<?= $e($csrf_token) ?>';
const BASE = '<?= rtrim(APP_URL,'/') ?>';

document.querySelectorAll('.btn-cancel-webinar').forEach(btn => {
  btn.addEventListener('click', function() {
    const uuid = this.dataset.uuid, title = this.dataset.title;
    LMS.confirm('Cancel webinar "' + title + '"?', () => {
      fetch(BASE + '/admin/webinars/' + uuid + '/cancel', {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: 'csrf_token=' + encodeURIComponent(CSRF),
      }).then(r=>r.json()).then(d => {
        if (d.success) { LMS.toast('success','Webinar cancelled.'); location.reload(); }
        else LMS.toast('error', d.message);
      });
    });
  });
});

document.querySelectorAll('.btn-start-webinar').forEach(btn => {
  btn.addEventListener('click', function() {
    const uuid = this.dataset.uuid;
    fetch(BASE + '/admin/webinars/' + uuid + '/start', {
      method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body: 'csrf_token=' + encodeURIComponent(CSRF),
    }).then(r=>r.json()).then(d => {
      if (d.success) { window.open(d.start_url, '_blank'); location.reload(); }
      else LMS.toast('error', d.message || 'Failed.');
    });
  });
});
</script>
