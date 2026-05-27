<?php use App\Core\View; $e=fn($v)=>View::e($v); $url=fn($p='')=>View::url($p);
$members=$report['members']; $courses=$report['courses']; $progress=$report['progress'];
?>
<div class="card lms-card">
  <div class="card-header lms-card-header">
    <h5 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Progress Report — <?=$e($group['name'])?></h5>
  </div>
  <?php if(empty($courses)): ?>
  <div class="card-body text-center py-4 text-muted">No courses assigned to this group yet.</div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-hover lms-table mb-0">
      <thead><tr>
        <th>Student</th>
        <?php foreach($courses as $c):?><th style="font-size:12px;max-width:120px;white-space:normal"><?=$e($c['title'])?></th><?php endforeach;?>
        <th>Overall</th>
      </tr></thead>
      <tbody>
      <?php foreach($progress as $row):
        $u=$row['user'];
        $done=0; $total=count($courses);
      ?>
      <tr>
        <td>
          <div class="fw-semibold" style="font-size:13.5px"><?=$e($u['first_name'].' '.$u['last_name'])?></div>
          <div class="text-muted" style="font-size:12px"><?=$e($u['email'])?></div>
        </td>
        <?php foreach($courses as $c):
          $enr=$row['courses'][$c['id']]??['status'=>'not_enrolled','progress_pct'=>0];
          $pct=(int)$enr['progress_pct'];
          if($enr['status']==='completed')$done++;
          $color=$enr['status']==='completed'?'#059669':($pct>0?'#5b5ef6':'#d1d5db');
        ?>
        <td>
          <div style="font-size:12px;font-weight:600;color:<?=$color?>"><?=$enr['status']==='completed'?'✓ Done':($pct>0?$pct.'%':'—')?></div>
          <?php if($pct>0&&$enr['status']!=='completed'):?>
          <div style="height:4px;background:var(--border-color);border-radius:2px;margin-top:3px"><div style="height:100%;width:<?=$pct?>%;background:#5b5ef6;border-radius:2px"></div></div>
          <?php endif;?>
        </td>
        <?php endforeach;?>
        <td><span class="fw-bold text-<?=$done===$total?'success':'primary'?>"><?=$done?>/<?=$total?></span></td>
      </tr>
      <?php endforeach;?>
      </tbody>
    </table>
  </div>
  <?php endif;?>
</div>
