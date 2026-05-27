<?php use App\Core\View; $e=fn($v)=>View::e($v); $url=fn($p='')=>View::url($p);
$subCount = count($submissions);
$maxAttempts = (int)($assignment['max_attempts']??3);
$canSubmit = $subCount < $maxAttempts && ($submissions[0]['status']??'')!=='pass';
?>
<div class="row g-4">
  <div class="col-12 col-lg-8">
    <!-- Assignment brief -->
    <div class="lms-surface p-4 mb-4">
      <div class="d-flex align-items-center gap-3 mb-3">
        <div style="width:44px;height:44px;border-radius:12px;background:#ededff;display:flex;align-items:center;justify-content:center"><i class="bi bi-clipboard-fill" style="font-size:20px;color:#5b5ef6"></i></div>
        <div>
          <h2 style="font-size:18px;font-weight:800;margin:0"><?=$e($assignment['title']??'Assignment')?></h2>
          <div style="font-size:12.5px;color:var(--text-3)">
            <?php if($assignment['deadline']):?>Deadline: <?=date('d M Y H:i',strtotime($assignment['deadline']))?> · <?php endif;?>
            Max Score: <?=$assignment['max_score']?> · Pass Score: <?=$assignment['pass_score']?>
          </div>
        </div>
      </div>
      <?php if($assignment['brief']):?>
      <div style="font-size:15px;line-height:1.8;color:var(--text-1)"><?=$assignment['brief']?></div>
      <?php endif;?>
      <?php if($assignment['rubric']):?>
      <div class="mt-3 p-3 rounded-3" style="background:var(--bg);border:1px solid var(--border)">
        <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-3);margin-bottom:6px">Grading Rubric</div>
        <div style="font-size:14px;color:var(--text-2)"><?=$e($assignment['rubric'])?></div>
      </div>
      <?php endif;?>
    </div>

    <!-- Submit form -->
    <?php if($canSubmit):?>
    <div class="lms-surface p-4 mb-4">
      <h4 style="font-size:16px;font-weight:700;margin-bottom:16px">
        Submit Assignment <?=$subCount>0?'(Attempt '.($subCount+1).'/'.$maxAttempts.')':''?>
      </h4>
      <div class="mb-3">
        <label class="form-label fw-semibold">Upload File</label>
        <div style="font-size:12.5px;color:var(--text-3);margin-bottom:8px">Allowed: <?=$e($assignment['allowed_types']??'pdf,zip,doc,docx')?> · Max <?=$assignment['max_file_mb']??20?>MB</div>
        <input type="file" class="form-control" id="assignFile" accept=".pdf,.zip,.doc,.docx,.jpg,.png">
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold">Comment (optional)</label>
        <textarea class="form-control" id="assignComment" rows="3" placeholder="Describe your approach, any notes for the instructor…"></textarea>
      </div>
      <button class="btn-course-action btn-primary-action" id="submitAssignBtn">
        <i class="bi bi-cloud-upload me-1"></i>Submit Assignment
      </button>
    </div>
    <?php elseif(!$canSubmit && ($submissions[0]['status']??'')==='pass'):?>
    <div class="lms-surface p-4 mb-4 text-center">
      <div style="font-size:3rem">🎉</div>
      <h4 style="font-weight:700;color:#059669;margin:12px 0 4px">Assignment Passed!</h4>
      <p style="color:var(--text-2)">Score: <?=$submissions[0]['score']?>/<?=$assignment['max_score']?></p>
    </div>
    <?php else:?>
    <div class="lms-surface p-4 mb-4 text-center">
      <p style="color:var(--text-2)">Maximum attempts (<?=$maxAttempts?>) reached.</p>
    </div>
    <?php endif;?>
  </div>

  <!-- Submissions history -->
  <div class="col-12 col-lg-4">
    <div class="lms-surface p-3">
      <h5 style="font-size:15px;font-weight:700;margin-bottom:14px">Your Submissions</h5>
      <?php if(empty($submissions)):?>
      <p style="font-size:13.5px;color:var(--text-3)">No submissions yet.</p>
      <?php else:?>
      <?php foreach($submissions as $sub):
        $sc=$sub['score']??null;
        $st=$sub['status'];
        $stColors=['submitted'=>'primary','graded'=>'warning','pass'=>'success','fail'=>'danger','resubmit'=>'warning'];
      ?>
      <div class="mb-3 p-3 rounded-2" style="background:var(--bg);border:1px solid var(--border)">
        <div class="d-flex justify-content-between mb-1">
          <span style="font-size:13px;font-weight:600">Attempt <?=$sub['attempt']?></span>
          <span class="badge bg-<?=$stColors[$st]??'secondary'?>-subtle text-<?=$stColors[$st]??'secondary'?>"><?=ucfirst($st)?></span>
        </div>
        <div style="font-size:12px;color:var(--text-3)"><?=date('d M Y H:i',strtotime($sub['submitted_at']))?></div>
        <?php if($sc!==null):?>
        <div style="font-size:14px;font-weight:700;color:<?=$st==='pass'?'#059669':'#dc2626'?>;margin-top:6px">
          Score: <?=$sc?>/<?=$assignment['max_score']?>
        </div>
        <?php endif;?>
        <?php if($sub['feedback']):?>
        <div style="font-size:13px;color:var(--text-2);margin-top:6px;padding:8px;background:var(--card);border-radius:8px">
          <strong>Feedback:</strong> <?=$e($sub['feedback'])?>
        </div>
        <?php endif;?>
      </div>
      <?php endforeach;?>
      <?php endif;?>
    </div>
  </div>
</div>

<script>
document.getElementById('submitAssignBtn')?.addEventListener('click',function(){
  var file=document.getElementById('assignFile').files[0];
  if(!file){LMS.toast('error','Please select a file');return;}
  var BASE=(window.LMS&&window.LMS.BASE)||'';
  var fd=new FormData();
  fd.append('csrf_token','<?=$e($csrf_token)?>');
  fd.append('file',file);
  fd.append('comment',document.getElementById('assignComment').value);
  this.disabled=true; this.innerHTML='<i class="bi bi-cloud-upload me-1"></i>Uploading…';
  fetch(BASE+'/learn/courses/<?=$e($lesson['course_uuid'])?>/assignments/<?=$e($lesson['id'])?>/submit',{method:'POST',body:fd})
    .then(r=>r.json()).then(d=>{
      if(d.success){LMS.toast('success',d.message);setTimeout(()=>location.reload(),1200);}
      else{LMS.toast('error',d.message);this.disabled=false;this.innerHTML='<i class="bi bi-cloud-upload me-1"></i>Submit Assignment';}
    });
});
</script>
