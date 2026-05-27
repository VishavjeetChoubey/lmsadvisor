<?php use App\Core\View; $e=fn($v)=>View::e($v); $url=fn($p='')=>View::url($p); ?>

<!-- KPI row -->
<div class="row g-3 mb-4">
<?php
$kpis=[
  ['Enrolled',     $stats['enrolled'],       'bi-people-fill',        '#5b5ef6','#ededff'],
  ['Completed',    $stats['completed'],       'bi-patch-check-fill',   '#059669','#ecfdf5'],
  ['Completion %', $stats['completion_pct'].'%', 'bi-graph-up-arrow', '#2563eb','#eff6ff'],
  ['Avg Progress', $stats['avg_progress'].'%','bi-hourglass-split',    '#d97706','#fffbeb'],
  ['Avg Quiz',     $stats['avg_quiz_score'].'%','bi-clipboard-check',  '#7c3aed','#f5f3ff'],
  ['Rating',       number_format($stats['avg_rating'],1).'★ ('.$stats['rating_count'].')','bi-star-fill','#f59e0b','#fffbeb'],
];
foreach($kpis as [$label,$val,$icon,$color,$bg]):?>
<div class="col-6 col-md-4 col-xl-2">
  <div class="card lms-card p-3 text-center">
    <div style="width:40px;height:40px;border-radius:10px;background:<?=$bg?>;display:flex;align-items:center;justify-content:center;margin:0 auto 8px"><i class="bi <?=$icon?>" style="font-size:18px;color:<?=$color?>"></i></div>
    <div style="font-size:22px;font-weight:800;color:var(--text-1);line-height:1"><?=$val?></div>
    <div style="font-size:12px;color:var(--text-3);margin-top:4px;font-weight:600"><?=$label?></div>
  </div>
</div>
<?php endforeach;?>
</div>

<div class="row g-4 mb-4">
  <!-- Completion funnel -->
  <div class="col-12 col-lg-6">
    <div class="card lms-card">
      <div class="card-header lms-card-header"><h5 class="mb-0">📉 Completion Funnel <small class="text-muted fw-normal">(where students drop off)</small></h5></div>
      <div class="card-body p-4">
        <?php foreach($funnel['sections'] as $i=>$sec):?>
        <div class="mb-3">
          <div class="d-flex justify-content-between mb-1" style="font-size:13px">
            <span class="fw-semibold"><?=$e($sec['title'])?></span>
            <span style="font-weight:700;color:<?=$sec['completion_pct']>=60?'#059669':($sec['completion_pct']>=30?'#d97706':'#dc2626')?>"><?=$sec['completions']?>/<?=$funnel['total_enrolled']?> (<?=$sec['completion_pct']?>%)</span>
          </div>
          <div style="height:24px;background:var(--border-color);border-radius:6px;overflow:hidden;position:relative">
            <div style="height:100%;width:<?=$sec['completion_pct']?>%;background:<?=$sec['completion_pct']>=60?'#059669':($sec['completion_pct']>=30?'#d97706':'#dc2626')?>;border-radius:6px;transition:width .4s;display:flex;align-items:center;padding-left:8px">
              <?php if($sec['completion_pct']>15):?><span style="font-size:11px;font-weight:700;color:#fff"><?=$sec['completion_pct']?>%</span><?php endif;?>
            </div>
          </div>
        </div>
        <?php endforeach;?>
      </div>
    </div>
  </div>

  <!-- Hardest quiz questions -->
  <div class="col-12 col-lg-6">
    <div class="card lms-card">
      <div class="card-header lms-card-header"><h5 class="mb-0">❓ Hardest Quiz Questions</h5></div>
      <?php if(empty($hardest)):?>
      <div class="card-body text-center py-4 text-muted">No quiz attempt data yet.</div>
      <?php else:?>
      <div class="list-group list-group-flush">
        <?php foreach($hardest as $q):
          $pc=(int)$q['pass_pct'];
          $c=$pc<40?'#dc2626':($pc<70?'#d97706':'#059669');
        ?>
        <div class="list-group-item py-3">
          <div class="d-flex align-items-start gap-3">
            <span class="badge" style="background:<?=$c?>20;color:<?=$c?>;font-size:12px;font-weight:700;white-space:nowrap;padding:4px 8px"><?=$pc?>% pass</span>
            <div>
              <div style="font-size:13.5px;font-weight:600;color:var(--text-1)"><?=$e(mb_strimwidth($q['question'],0,100,'…'))?></div>
              <div style="font-size:12px;color:var(--text-3)"><?=$q['attempts']?> attempts</div>
            </div>
          </div>
        </div>
        <?php endforeach;?>
      </div>
      <?php endif;?>
    </div>
  </div>
</div>

<!-- Avg time per lesson -->
<div class="card lms-card mb-4">
  <div class="card-header lms-card-header"><h5 class="mb-0">⏱ Avg Time per Lesson</h5></div>
  <div class="table-responsive"><table class="table table-hover lms-table mb-0">
    <thead><tr><th>Lesson</th><th>Type</th><th>Learners</th><th>Avg Time</th></tr></thead>
    <tbody>
    <?php foreach($times as $t):?>
    <tr>
      <td style="font-size:13.5px;font-weight:600"><?=$e($t['title'])?></td>
      <td><span class="badge bg-secondary-subtle text-secondary" style="font-size:11px"><?=$t['type']?></span></td>
      <td style="font-size:13px"><?=$t['learners']?></td>
      <td style="font-size:13px"><?=$t['avg_seconds']?gmdate('i:s',(int)$t['avg_seconds']).'min':'—'?></td>
    </tr>
    <?php endforeach;?>
    </tbody>
  </table></div>
</div>

<!-- Grade Book -->
<div class="card lms-card">
  <div class="card-header lms-card-header"><h5 class="mb-0">📋 Grade Book</h5></div>
  <?php if(empty($grades['students'])):?>
  <div class="card-body text-center py-4 text-muted">No students enrolled yet.</div>
  <?php else:?>
  <div class="table-responsive"><table class="table table-hover lms-table mb-0">
    <thead><tr>
      <th>Student</th>
      <th>Progress</th>
      <th>Status</th>
      <?php // Quiz columns
      $quizNames=[];
      foreach($grades['quiz_scores'] as $uid=>$qs) foreach($qs as $q) if(!in_array($q['title'],$quizNames))$quizNames[]=$q['title'];
      foreach($quizNames as $qn):?><th style="font-size:12px;max-width:100px"><?=$e(mb_strimwidth($qn,0,30,'…'))?></th><?php endforeach;?>
      <th>Overall</th>
    </tr></thead>
    <tbody>
    <?php foreach($grades['students'] as $s):
      $userQuizzes = $grades['quiz_scores'][$s['id']] ?? [];
      $quizByName  = array_column($userQuizzes,'best_score','title');
      $passCount   = count(array_filter($userQuizzes,fn($q)=>$q['passed']));
      $totalCount  = count($userQuizzes);
      $avgScore    = $totalCount?round(array_sum(array_column($userQuizzes,'best_score'))/$totalCount):0;
    ?>
    <tr>
      <td>
        <div class="fw-semibold" style="font-size:13px"><?=$e($s['first_name'].' '.$s['last_name'])?></div>
        <div class="text-muted" style="font-size:11px"><?=$e($s['email'])?></div>
      </td>
      <td>
        <div style="display:flex;align-items:center;gap:6px">
          <div style="width:60px;height:6px;background:var(--border-color);border-radius:3px;overflow:hidden"><div style="height:100%;width:<?=$s['progress_pct']?>%;background:#5b5ef6;border-radius:3px"></div></div>
          <span style="font-size:12px;font-weight:600"><?=$s['progress_pct']?>%</span>
        </div>
      </td>
      <td><span class="badge bg-<?=$s['enrollment_status']==='completed'?'success':'primary'?>-subtle text-<?=$s['enrollment_status']==='completed'?'success':'primary'?>" style="font-size:11px"><?=ucfirst($s['enrollment_status'])?></span></td>
      <?php foreach($quizNames as $qn):
        $sc=$quizByName[$qn]??null;
        $color=$sc===null?'var(--text-3)':($sc>=70?'#059669':'#dc2626');
      ?>
      <td style="font-size:13px;font-weight:600;color:<?=$color?>"><?=$sc!==null?$sc.'%':'—'?></td>
      <?php endforeach;?>
      <td style="font-size:14px;font-weight:800;color:<?=$avgScore>=70?'#059669':'#d97706'?>"><?=$totalCount?$avgScore.'%':'—'?></td>
    </tr>
    <?php endforeach;?>
    </tbody>
  </table></div>
  <?php endif;?>
</div>
