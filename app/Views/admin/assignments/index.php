<?php use App\Core\View; $e=fn($v)=>View::e($v); $url=fn($p='')=>View::url($p);
$statusColors=['submitted'=>'warning','graded'=>'info','pass'=>'success','fail'=>'danger','resubmit'=>'warning'];
?>
<div class="card lms-card">
  <div class="card-header lms-card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><i class="bi bi-clipboard-check me-2"></i>Assignment Submissions</h5>
    <span class="badge bg-secondary"><?=count($submissions)?> total</span>
  </div>
  <?php if(empty($submissions)):?>
  <div class="card-body text-center py-5 text-muted">
    <i class="bi bi-inbox" style="font-size:3rem;opacity:.2"></i>
    <h5 class="mt-3">No submissions yet</h5>
    <p>Students will appear here after they submit their assignments.</p>
  </div>
  <?php else:?>
  <div class="table-responsive">
    <table class="table table-hover lms-table mb-0">
      <thead><tr><th>Student</th><th>Assignment</th><th>Lesson</th><th>Attempt</th><th>Status</th><th>Score</th><th>Submitted</th><th></th></tr></thead>
      <tbody>
      <?php foreach($submissions as $sub):?>
      <tr>
        <td>
          <div class="fw-semibold" style="font-size:13.5px"><?=$e($sub['student_name'])?></div>
          <div class="text-muted" style="font-size:12px"><?=$e($sub['email'])?></div>
        </td>
        <td style="font-size:13px"><?=$e($sub['assignment_title'])?></td>
        <td style="font-size:13px;color:var(--text-muted)"><?=$e($sub['lesson_title'])?></td>
        <td class="text-center"><?=$sub['attempt']?>/<?=$sub['max_attempts']??3?></td>
        <td><span class="badge bg-<?=$statusColors[$sub['status']]??'secondary'?>-subtle text-<?=$statusColors[$sub['status']]??'secondary'?>"><?=ucfirst($sub['status'])?></span></td>
        <td><?=$sub['score']!==null?$sub['score'].'/'.$sub['max_score']:'—'?></td>
        <td style="font-size:12px"><?=date('d M H:i',strtotime($sub['submitted_at']))?></td>
        <td>
          <button class="btn btn-xs btn-outline-primary grade-btn"
                  data-id="<?=$sub['id']?>"
                  data-name="<?=$e($sub['student_name'])?>"
                  data-max="<?=$sub['max_score']?>"
                  data-score="<?=$sub['score']??''?>"
                  data-feedback="<?=$e($sub['feedback']??'')?>">
            <i class="bi bi-pen"></i> Grade
          </button>
          <?php if($sub['file_path']):?>
          <a href="<?=APP_URL?>/storage/<?=$e($sub['file_path'])?>" class="btn btn-xs btn-outline-secondary" target="_blank" download><i class="bi bi-download"></i></a>
          <?php endif;?>
        </td>
      </tr>
      <?php endforeach;?>
      </tbody>
    </table>
  </div>
  <?php endif;?>
</div>

<!-- Grade modal -->
<div class="modal fade" id="gradeModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0"><h5 class="modal-title fw-bold">Grade Submission</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body px-4">
        <div id="gradeStudentName" class="mb-3 fw-semibold"></div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Score (out of <span id="gradeMax"></span>)</label>
          <input type="number" id="gradeScore" class="form-control" min="0">
        </div>
        <div class="mb-0">
          <label class="form-label fw-semibold">Feedback</label>
          <textarea id="gradeFeedback" class="form-control" rows="4" placeholder="Write feedback for the student…"></textarea>
        </div>
      </div>
      <div class="modal-footer border-0">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" id="submitGradeBtn"><i class="bi bi-check-lg me-1"></i>Save Grade</button>
      </div>
    </div>
  </div>
</div>

<script>
var BASE=(window.LMS&&window.LMS.BASE)||'', CSRF='<?=$e($csrf_token)?>', currentSubId=null;
document.querySelectorAll('.grade-btn').forEach(b=>b.addEventListener('click',function(){
  currentSubId=this.dataset.id;
  document.getElementById('gradeStudentName').textContent='Student: '+this.dataset.name;
  document.getElementById('gradeMax').textContent=this.dataset.max;
  document.getElementById('gradeScore').max=this.dataset.max;
  document.getElementById('gradeScore').value=this.dataset.score||'';
  document.getElementById('gradeFeedback').value=this.dataset.feedback||'';
  new bootstrap.Modal(document.getElementById('gradeModal')).show();
}));
document.getElementById('submitGradeBtn')?.addEventListener('click',function(){
  if(!currentSubId)return;
  var score=document.getElementById('gradeScore').value;
  var fb=document.getElementById('gradeFeedback').value;
  var url=BASE+'/admin/courses/<?=$e($course_uuid)?>/assignments/'+currentSubId+'/grade';
  fetch(url,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'csrf_token='+encodeURIComponent(CSRF)+'&score='+encodeURIComponent(score)+'&feedback='+encodeURIComponent(fb)})
    .then(r=>r.json()).then(d=>{LMS.toast(d.success?'success':'error',d.message);if(d.success)setTimeout(()=>location.reload(),800);});
});
</script>
