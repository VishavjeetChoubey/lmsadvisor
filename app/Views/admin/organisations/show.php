<?php
use App\Core\View;
$e   = fn($v) => View::e($v);
$url = fn($p='') => View::url($p);
$csrf = \App\Middleware\CsrfMiddleware::token();
?>

<div class="adm-page-header mb-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
  <div>
    <h2 class="adm-page-title">🏭 <?=$e($org['name'])?></h2>
    <p class="adm-page-sub"><?=$e((string)$org['seats_used'])?>/<?=$e((string)$org['seat_limit'])?> seats used</p>
  </div>
  <a href="<?=$url('admin/organisations/'.$org['uuid'].'/export')?>" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-download me-1"></i> Export CSV
  </a>
</div>

<?php if($flash): ?>
<div class="alert alert-<?=$flash['type']==='success'?'success':'danger'?> mb-4"><?=$e($flash['message'])?></div>
<?php endif; ?>

<div class="row g-4">

  <!-- ── Left: Compliance report ───────────────────────────────────────────── -->
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-transparent d-flex align-items-center justify-content-between py-3">
        <h6 class="mb-0 fw-bold"><i class="bi bi-clipboard-check me-2"></i>Compliance Report</h6>
        <span class="badge bg-secondary"><?=count($members)?> members</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:13px">
          <thead class="table-light">
            <tr>
              <th>Employee</th>
              <th>Department</th>
              <?php
              $courseNames = !empty($report) ? array_column($report[0]['courses'], 'course_title') : [];
              foreach ($courseNames as $cn): ?>
              <th><?=$e($cn)?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php if(empty($report)): ?>
            <tr><td colspan="10" class="text-center py-4 text-muted">No members yet. Add members using the panel on the right.</td></tr>
            <?php else: foreach($report as $m): ?>
            <tr>
              <td>
                <div class="fw-semibold"><?=$e($m['name'])?></div>
                <div style="font-size:11px;color:var(--bs-secondary-color)"><?=$e($m['email'])?></div>
              </td>
              <td style="color:var(--bs-secondary-color)"><?=$e($m['department']??'—')?></td>
              <?php foreach($m['courses'] as $c):
                $dot = match($c['status']) {
                  'completed'   => ['#059669','✓'],
                  'active'      => ['#2563eb','▶'],
                  'not_enrolled'=> ['#9ca3af','—'],
                  default       => ['#d97706','⏳'],
                };
              ?>
              <td>
                <span title="<?=$e($c['status'])?>" style="color:<?=$dot[0]?>;font-weight:700"><?=$dot[1]?></span>
                <?php if($c['overdue']): ?>
                  <span class="badge bg-danger-subtle text-danger" style="font-size:10px">Overdue</span>
                <?php endif; ?>
              </td>
              <?php endforeach; ?>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ── Right: Add Member + Assign Course ─────────────────────────────────── -->
  <div class="col-lg-4">

    <!-- Add Member card -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-transparent fw-bold py-3">
        <i class="bi bi-person-plus-fill me-2"></i>Add Member
      </div>
      <div class="card-body">
        <div id="addMemberResult" class="mb-2"></div>

        <div class="mb-2">
          <label class="form-label fw-semibold" style="font-size:13px">Search User by Name or Email</label>
          <input type="text" class="form-control form-control-sm" id="memberEmailSearch"
                 placeholder="Start typing name or email…" autocomplete="off">
          <div id="memberSearchDrop"
               style="display:none;border:1px solid var(--bs-border-color);border-radius:8px;
                      margin-top:4px;background:#fff;max-height:180px;overflow-y:auto;
                      font-size:13px;z-index:100;position:relative;box-shadow:0 4px 12px rgba(0,0,0,.1)">
          </div>
        </div>

        <input type="hidden" id="selectedUserId" value="">

        <div class="mb-2">
          <label class="form-label fw-semibold" style="font-size:13px">Role</label>
          <select class="form-select form-select-sm" id="memberRole">
            <option value="employee">Employee</option>
            <option value="manager">Manager</option>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold" style="font-size:13px">Department</label>
          <input type="text" class="form-control form-control-sm" id="memberDept" placeholder="e.g. Engineering">
        </div>

        <button class="btn btn-primary btn-sm w-100" id="addMemberBtn">
          <i class="bi bi-plus-circle me-1"></i> Add to Organisation
        </button>
      </div>
    </div>

    <!-- Assign Course card -->
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-transparent fw-bold py-3">
        <i class="bi bi-journal-plus me-2"></i>Assign Course
      </div>
      <div class="card-body">
        <div id="assignResult" class="mb-2"></div>

        <div class="mb-2">
          <label class="form-label fw-semibold" style="font-size:13px">Course</label>
          <select class="form-select form-select-sm" id="assignCourse">
            <?php foreach($courses as $c): ?>
            <option value="<?=(int)$c['id']?>" <?=in_array($c['id'],$assignedIds)?'disabled':''?>>
              <?=$e($c['title'])?><?=in_array($c['id'],$assignedIds)?' (assigned)':''?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-2">
          <label class="form-label fw-semibold" style="font-size:13px">Due Date</label>
          <input type="date" class="form-control form-control-sm" id="assignDue">
        </div>

        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" id="assignMandatory" checked>
          <label class="form-check-label" style="font-size:13px">Mandatory</label>
        </div>

        <button class="btn btn-primary btn-sm w-100" id="assignBtn">
          <i class="bi bi-plus-circle me-1"></i> Assign & Enroll All
        </button>
      </div>
    </div>

  </div>
</div>

<script>
(function () {
  var BASE  = '<?=rtrim(APP_URL,'/')?>';
  var CSRF  = '<?=htmlspecialchars($csrf)?>';
  var orgId = '<?=$e($org['uuid'])?>';

  // ── Member search ───────────────────────────────────────────────────────────
  var searchInput = document.getElementById('memberEmailSearch');
  var drop        = document.getElementById('memberSearchDrop');
  var selectedId  = document.getElementById('selectedUserId');
  var timer;

  searchInput.addEventListener('input', function () {
    clearTimeout(timer);
    selectedId.value = ''; // clear selection when typing
    var q = this.value.trim();
    if (q.length < 2) { drop.style.display = 'none'; return; }

    timer = setTimeout(function () {
      fetch(BASE + '/admin/users/search?q=' + encodeURIComponent(q))
        .then(function(r) { return r.json(); })
        .then(function (d) {
          if (!d.users || !d.users.length) { drop.style.display = 'none'; return; }
          drop.innerHTML = d.users.map(function (u) {
            var name = ((u.first_name || '') + ' ' + (u.last_name || '')).trim();
            var safeId   = parseInt(u.id, 10);
            var safeName = name.replace(/\\/g, '\\\\').replace(/'/g, "\\'");
            var safeEmail= (u.email||'').replace(/'/g, "\\'");
            return '<div style="padding:9px 14px;cursor:pointer;border-bottom:1px solid #f1f5f9"'
              + ' onmouseover="this.style.background=\'#f8fafc\'" onmouseout="this.style.background=\'#fff\'"'
              + ' onclick="pickMember(' + safeId + ',\'' + safeName + '\',\'' + safeEmail + '\')">'
              + '<div style="font-weight:600">' + name + '</div>'
              + '<div style="font-size:12px;color:#6b7280">' + (u.email||'') + '</div>'
              + '</div>';
          }).join('');
          drop.style.display = 'block';
        })
        .catch(function () {});
    }, 250);
  });

  // Hide dropdown on outside click
  document.addEventListener('click', function (e) {
    if (!e.target.closest('#memberEmailSearch') && !e.target.closest('#memberSearchDrop')) {
      drop.style.display = 'none';
    }
  });

  // Called from onclick in dropdown
  window.pickMember = function (id, name, email) {
    selectedId.value  = id;
    searchInput.value = name + ' <' + email + '>';
    drop.style.display = 'none';
    document.getElementById('addMemberResult').innerHTML = '';
  };

  // ── Add Member button ───────────────────────────────────────────────────────
  document.getElementById('addMemberBtn').addEventListener('click', function () {
    var userId = selectedId.value;
    var res    = document.getElementById('addMemberResult');

    if (!userId) {
      res.innerHTML = '<div class="alert alert-warning py-2 mb-0" style="font-size:13px">'
        + 'Please type a name or email above and select a user from the dropdown.</div>';
      return;
    }

    this.disabled = true;
    var btn = this;

    fetch(BASE + '/admin/organisations/' + orgId + '/add-member', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'csrf_token=' + encodeURIComponent(CSRF)
          + '&user_id='    + encodeURIComponent(userId)
          + '&role='       + encodeURIComponent(document.getElementById('memberRole').value)
          + '&department=' + encodeURIComponent(document.getElementById('memberDept').value)
    })
    .then(function(r) { return r.json(); })
    .then(function (d) {
      res.innerHTML = '<div class="alert alert-' + (d.success ? 'success' : 'danger')
        + ' py-2 mb-0" style="font-size:13px">' + d.message + '</div>';
      if (d.success) { setTimeout(function () { location.reload(); }, 1000); }
      btn.disabled = false;
    })
    .catch(function () { btn.disabled = false; });
  });

  // ── Assign Course button ────────────────────────────────────────────────────
  document.getElementById('assignBtn').addEventListener('click', function () {
    var res = document.getElementById('assignResult');
    this.disabled = true;
    var btn = this;

    fetch(BASE + '/admin/organisations/' + orgId + '/assign', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'csrf_token=' + encodeURIComponent(CSRF)
          + '&course_id=' + encodeURIComponent(document.getElementById('assignCourse').value)
          + '&due_date='  + encodeURIComponent(document.getElementById('assignDue').value)
          + '&mandatory=' + (document.getElementById('assignMandatory').checked ? '1' : '0')
    })
    .then(function(r) { return r.json(); })
    .then(function (d) {
      res.innerHTML = '<div class="alert alert-' + (d.success ? 'success' : 'danger')
        + ' py-2 mb-0" style="font-size:13px">' + d.message + '</div>';
      if (d.success) { setTimeout(function () { location.reload(); }, 1200); }
      btn.disabled = false;
    })
    .catch(function () { btn.disabled = false; });
  });

})();
</script>
