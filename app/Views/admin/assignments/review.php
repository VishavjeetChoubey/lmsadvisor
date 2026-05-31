<?php
use App\Core\View;
$e   = fn($v) => View::e($v);
$url = fn($p='') => View::url($p);
$csrf = $csrf_token;
?>

<div class="adm-page-header mb-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
  <div>
    <h2 class="adm-page-title">📋 Assignment Submissions</h2>
    <p class="adm-page-sub">Review and grade student submissions for this course.</p>
  </div>
  <a href="<?=$url('admin/assignments')?>" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i> All Assignments
  </a>
</div>

<?php if(empty($submissions)): ?>
<div class="card border-0 shadow-sm">
  <div class="card-body text-center py-5">
    <i class="bi bi-inbox" style="font-size:3rem;opacity:.3"></i>
    <div class="fw-bold mt-3">No submissions yet</div>
    <p class="text-muted">Students haven't submitted any assignments for this course.</p>
  </div>
</div>
<?php else: ?>
<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0" style="font-size:13.5px">
      <thead class="table-light">
        <tr>
          <th>Student</th>
          <th>Assignment</th>
          <th>Submitted</th>
          <th>Status</th>
          <th>Score</th>
          <th>File</th>
          <th style="width:160px">Grade</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($submissions as $s): ?>
        <tr id="row-<?=(int)$s['id']?>">
          <td>
            <div class="fw-semibold"><?=$e($s['student_name'])?></div>
            <div style="font-size:12px;color:var(--bs-secondary-color)"><?=$e($s['email'])?></div>
          </td>
          <td><?=$e($s['assignment_title'] ?? $s['lesson_title'] ?? '—')?></td>
          <td style="font-size:12px;white-space:nowrap"><?=$s['submitted_at']?date('d M Y H:i',strtotime($s['submitted_at'])):'-'?></td>
          <td>
            <?php if($s['status']==='graded'): ?>
              <span class="badge bg-success-subtle text-success">Graded</span>
            <?php else: ?>
              <span class="badge bg-warning text-dark">Pending</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if($s['status']==='graded'): ?>
              <strong><?=(int)$s['score']?></strong> / <?=(int)$s['max_score']?>
            <?php else: ?>
              <span class="text-muted">—</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if($s['file_path']): ?>
              <a href="<?=$e(APP_URL.'/storage/uploads/'.$s['file_path'])?>" target="_blank"
                 class="btn btn-xs btn-outline-secondary">
                <i class="bi bi-download me-1"></i>Download
              </a>
            <?php else: ?>
              <span class="text-muted">No file</span>
            <?php endif; ?>
          </td>
          <td>
            <div class="d-flex align-items-center gap-1">
              <input type="number" class="form-control form-control-sm" min="0"
                     max="<?=(int)$s['max_score']?>"
                     value="<?=$s['status']==='graded'?(int)$s['score']:''?>"
                     id="score-<?=(int)$s['id']?>"
                     placeholder="0–<?=(int)$s['max_score']?>"
                     style="width:70px">
              <button class="btn btn-sm btn-primary grade-btn"
                      data-id="<?=(int)$s['id']?>"
                      data-csrf="<?=htmlspecialchars($csrf)?>"
                      data-url="<?=$url('admin/courses/'.$course_uuid.'/assignments/'.$s['id'].'/grade')?>">
                <i class="bi bi-check2"></i>
              </button>
            </div>
            <?php if($s['feedback']): ?>
            <div style="font-size:11.5px;color:var(--bs-secondary-color);margin-top:4px"><?=$e($s['feedback'])?></div>
            <?php endif; ?>
            <!-- Feedback -->
            <input type="text" class="form-control form-control-sm mt-1" id="fb-<?=(int)$s['id']?>"
                   value="<?=$e($s['feedback']??'')?>"
                   placeholder="Feedback (optional)">
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
document.querySelectorAll('.grade-btn').forEach(function(btn) {
  btn.addEventListener('click', function() {
    var id    = this.dataset.id;
    var csrf  = this.dataset.csrf;
    var url   = this.dataset.url;
    var score = document.getElementById('score-' + id).value;
    var fb    = document.getElementById('fb-' + id).value;
    var self  = this;
    self.disabled = true;
    self.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    fetch(url, {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body: 'csrf_token=' + encodeURIComponent(csrf)
          + '&score=' + encodeURIComponent(score)
          + '&feedback=' + encodeURIComponent(fb)
    }).then(function(r){ return r.json(); }).then(function(d) {
      if(d.success) {
        var badge = document.querySelector('#row-' + id + ' .badge');
        if(badge) { badge.className='badge bg-success-subtle text-success'; badge.textContent='Graded'; }
        self.innerHTML = '<i class="bi bi-check2"></i>';
        self.className = 'btn btn-sm btn-success grade-btn';
      } else {
        alert(d.message || 'Error saving grade');
        self.innerHTML = '<i class="bi bi-check2"></i>';
      }
      self.disabled = false;
    }).catch(function() { self.disabled = false; self.innerHTML = '<i class="bi bi-check2"></i>'; });
  });
});
</script>
<?php endif; ?>
