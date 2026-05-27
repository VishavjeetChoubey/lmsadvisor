<?php use App\Core\View; $e=fn($v)=>View::e($v); $url=fn($p='')=>View::url($p);
$u=$profile_user; $level=$stats['level'];
?>

<div class="row g-4">
  <!-- Left: profile card -->
  <div class="col-12 col-md-4 col-lg-3">
    <div class="lms-surface p-4 text-center mb-4">
      <!-- Avatar -->
      <?php if($avatar_url):?>
      <img src="<?=$e($avatar_url)?>" style="width:88px;height:88px;border-radius:50%;object-fit:cover;border:3px solid var(--primary);box-shadow:0 8px 24px rgba(91,94,246,.25);margin-bottom:12px">
      <?php else:?>
      <div style="width:88px;height:88px;border-radius:50%;background:linear-gradient(135deg,#5b5ef6,#3b82f6);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;box-shadow:0 8px 24px rgba(91,94,246,.25)">
        <span style="color:#fff;font-size:32px;font-weight:700"><?=strtoupper(substr($u['first_name'],0,1))?></span>
      </div>
      <?php endif;?>

      <h2 style="font-size:20px;font-weight:800;color:var(--text-1);margin-bottom:4px"><?=$e($u['first_name'].' '.$u['last_name'])?></h2>

      <!-- Level badge -->
      <div style="display:inline-flex;align-items:center;gap:6px;background:<?=$level['color']?>20;color:<?=$level['color']?>;padding:5px 14px;border-radius:20px;font-size:13px;font-weight:700;margin-bottom:16px">
        <i class="bi <?=$level['icon']?>"></i><?=$level['name']?> Level
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <?php $cards=[
          ['Rank','#'.$rank,'bi-bar-chart','#5b5ef6'],
          ['Points',number_format($stats['points']),'bi-trophy-fill','#d97706'],
          ['Completed',$stats['completed'],'bi-patch-check','#059669'],
          ['Streak',$stats['streak'].'d','bi-lightning-fill','#dc2626'],
          ['Badges',$stats['badge_count'],'bi-award-fill','#7c3aed'],
          ['Best Streak',$stats['longest_streak'].'d','bi-fire','#f59e0b'],
        ];
        foreach($cards as [$lbl,$val,$ico,$col]):?>
        <div style="background:var(--bg);border-radius:10px;padding:12px 8px;text-align:center">
          <i class="bi <?=$ico?>" style="font-size:18px;color:<?=$col?>"></i>
          <div style="font-size:18px;font-weight:800;color:var(--text-1);margin-top:4px"><?=$val?></div>
          <div style="font-size:11px;color:var(--text-3);font-weight:600"><?=$lbl?></div>
        </div>
        <?php endforeach;?>
      </div>

      <div style="font-size:12px;color:var(--text-3);margin-top:14px">
        Member since <?=date('M Y',strtotime($u['created_at']))?>
      </div>
    </div>

    <!-- Badges -->
    <?php if(!empty($stats['badges'])):?>
    <div class="lms-surface p-3">
      <h4 style="font-size:14px;font-weight:700;color:var(--text-1);margin-bottom:12px">🏅 Badges</h4>
      <div style="display:flex;flex-wrap:wrap;gap:8px">
        <?php foreach($stats['badges'] as $b):?>
        <div title="<?=$e($b['name'])?>" style="width:42px;height:42px;border-radius:10px;background:<?=$e($b['color'])?>20;display:flex;align-items:center;justify-content:center" title="<?=$e($b['name'].' — '.$b['description'])?>">
          <i class="bi <?=$e($b['icon'])?>" style="font-size:20px;color:<?=$e($b['color'])?>"></i>
        </div>
        <?php endforeach;?>
      </div>
    </div>
    <?php endif;?>
  </div>

  <!-- Right: completed courses -->
  <div class="col-12 col-md-8 col-lg-9">
    <h3 style="font-size:17px;font-weight:700;color:var(--text-1);margin-bottom:20px">
      <i class="bi bi-patch-check-fill me-2" style="color:#059669"></i>Completed Courses (<?=count($completed)?>)
    </h3>
    <?php if(empty($completed)):?>
    <div class="lms-surface text-center py-5">
      <i class="bi bi-journals" style="font-size:3rem;opacity:.2"></i>
      <h5 class="mt-3" style="color:var(--text-2)">No completed courses yet</h5>
    </div>
    <?php else:?>
    <div class="row g-3">
      <?php foreach($completed as $c):?>
      <div class="col-12 col-sm-6 col-xl-4">
        <a href="<?=$url('learn/courses/'.$c['uuid'])?>" class="text-decoration-none d-block">
          <div class="lms-surface p-0 overflow-hidden" style="border-radius:12px;transition:transform .15s,box-shadow .15s" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,.12)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
            <?php if($c['thumbnail']):?>
            <img src="<?=$e(APP_URL.'/storage/uploads/'.$c['thumbnail'])?>" style="width:100%;height:110px;object-fit:cover;display:block">
            <?php else:?>
            <div style="height:110px;background:linear-gradient(135deg,#5b5ef6,#3b82f6);display:flex;align-items:center;justify-content:center"><i class="bi bi-journal-bookmark-fill" style="font-size:2rem;color:rgba(255,255,255,.4)"></i></div>
            <?php endif;?>
            <div class="p-3">
              <div style="font-size:13.5px;font-weight:700;color:var(--text-1);line-height:1.3;margin-bottom:6px"><?=$e($c['title'])?></div>
              <div style="display:flex;align-items:center;gap:6px">
                <i class="bi bi-patch-check-fill" style="color:#059669;font-size:13px"></i>
                <span style="font-size:12px;color:#059669;font-weight:600">Completed <?=date('d M Y',strtotime($c['completed_at']))?></span>
              </div>
            </div>
          </div>
        </a>
      </div>
      <?php endforeach;?>
    </div>
    <?php endif;?>
  </div>
</div>
