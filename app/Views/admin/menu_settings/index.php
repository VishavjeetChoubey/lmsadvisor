<?php
use App\Core\View;
use App\Services\MenuService;
$e   = fn($v) => View::e($v);
$url = fn($p='') => View::url($p);

$roleColors = [
    'super_admin' => ['#f5f3ff','#7c3aed'],
    'admin'       => ['#ededff','#4338ca'],
    'manager'     => ['#ecfdf5','#059669'],
    'instructor'  => ['#fff7ed','#d97706'],
];
?>

<div class="adm-page-header mb-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
  <div>
    <h2 class="adm-page-title">🔐 Menu Permissions</h2>
    <p class="adm-page-sub">Control which menu items each role can see. Super Admin always sees everything.</p>
  </div>
  <div class="d-flex gap-2">
    <form method="POST" action="<?= $url('admin/menu-settings/reset') ?>"
          onsubmit="return confirm('Reset all permissions to defaults?')">
      <input type="hidden" name="csrf_token" value="<?= $e($csrf_token) ?>">
      <button type="submit" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset to Defaults
      </button>
    </form>
  </div>
</div>

<?php if($flash): ?>
<div class="alert alert-<?= $flash['type']==='success'?'success':'danger' ?> mb-4 d-flex align-items-center gap-2">
  <i class="bi bi-<?= $flash['type']==='success'?'check-circle':'exclamation-triangle' ?>-fill"></i>
  <?= $e($flash['message']) ?>
</div>
<?php endif; ?>

<!-- Legend -->
<div class="d-flex gap-2 mb-4 flex-wrap align-items-center">
  <span style="font-size:13px;font-weight:600;color:var(--bs-secondary-color)">Roles:</span>
  <?php foreach ($roles as $role): if($role==='super_admin') continue;
    [$bg,$clr] = $roleColors[$role];
  ?>
  <span style="background:<?=$bg?>;color:<?=$clr?>;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700">
    <?= MenuService::roleLabel($role) ?>
  </span>
  <?php endforeach; ?>
  <span class="ms-2" style="font-size:12.5px;color:var(--bs-secondary-color)">
    <i class="bi bi-lock-fill me-1" style="color:#7c3aed"></i>Super Admin always has full access (not configurable)
  </span>
</div>

<form method="POST" action="<?= $url('admin/menu-settings') ?>">
  <input type="hidden" name="csrf_token" value="<?= $e($csrf_token) ?>">

  <div class="card border-0 shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0" style="font-size:13.5px">
        <thead>
          <tr style="background:#f8fafc">
            <th style="width:200px;padding:12px 16px">Menu Item</th>
            <th style="width:100px;text-align:center;padding:12px 8px">
              <span style="background:#f5f3ff;color:#7c3aed;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700">Super Admin</span>
            </th>
            <?php foreach ($roles as $role): if($role==='super_admin') continue;
              [$bg,$clr] = $roleColors[$role];
            ?>
            <th style="text-align:center;padding:12px 8px">
              <span style="background:<?=$bg?>;color:<?=$clr?>;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700">
                <?= MenuService::roleLabel($role) ?>
              </span>
            </th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php
          $permsByKey = [];
          foreach ($perms as $p) {
              $permsByKey[$p['menu_key']] = json_decode($p['roles'], true) ?? [];
          }

          foreach ($allItems as $key => [$label,$icon,$href,$seg]):
            $allowed = $permsByKey[$key] ?? ['super_admin'];
          ?>
          <tr>
            <td style="padding:10px 16px">
              <div class="d-flex align-items-center gap-2">
                <i class="bi <?= $e($icon) ?>" style="color:#6366f1;font-size:15px;width:20px;flex-shrink:0"></i>
                <span style="font-weight:600"><?= $e($label) ?></span>
              </div>
            </td>

            <!-- Super Admin — always locked ON -->
            <td style="text-align:center;padding:10px 8px">
              <div style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:8px;background:#f5f3ff">
                <i class="bi bi-lock-fill" style="color:#7c3aed;font-size:13px" title="Always enabled"></i>
              </div>
            </td>

            <!-- Other roles — toggleable -->
            <?php foreach ($roles as $role): if($role==='super_admin') continue;
              $checked  = in_array($role, $allowed, true);
              [$bg,$clr] = $roleColors[$role];
            ?>
            <td style="text-align:center;padding:10px 8px">
              <label class="menu-toggle" style="cursor:pointer;display:inline-flex;align-items:center;justify-content:center">
                <input type="checkbox"
                       name="menu[<?= $e($key) ?>][<?= $e($role) ?>]"
                       value="1"
                       <?= $checked ? 'checked' : '' ?>
                       onchange="updateRow(this)"
                       style="display:none">
                <div class="toggle-pill"
                     style="width:40px;height:22px;border-radius:11px;transition:background .2s;
                            background:<?= $checked ? $clr : '#e2e8f0' ?>;
                            position:relative">
                  <div style="position:absolute;top:3px;left:<?= $checked ? '21px' : '3px' ?>;width:16px;height:16px;border-radius:50%;background:#fff;transition:left .2s;box-shadow:0 1px 3px rgba(0,0,0,.2)"></div>
                </div>
              </label>
            </td>
            <?php endforeach; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="card-footer bg-transparent d-flex align-items-center justify-content-between py-3 px-4 flex-wrap gap-2">
      <div style="font-size:13px;color:var(--bs-secondary-color)">
        Changes take effect immediately on next page load for all users.
      </div>
      <button type="submit" class="btn btn-primary px-5">
        <i class="bi bi-save me-1"></i> Save Permissions
      </button>
    </div>
  </div>
</form>

<script>
function updateRow(checkbox) {
  var pill   = checkbox.nextElementSibling;
  var dot    = pill.querySelector('div');
  var colors = {
    admin:      '#4338ca',
    manager:    '#059669',
    instructor: '#d97706',
  };
  var name = checkbox.name; // menu[key][role]
  var role = name.match(/\[([^\]]+)\]$/)?.[1] || '';

  if (checkbox.checked) {
    pill.style.background = colors[role] || '#6366f1';
    dot.style.left = '21px';
  } else {
    pill.style.background = '#e2e8f0';
    dot.style.left = '3px';
  }
}

// Keyboard shortcut: Ctrl+S to save
document.addEventListener('keydown', function(e) {
  if ((e.ctrlKey || e.metaKey) && e.key === 's') {
    e.preventDefault();
    document.querySelector('form[method="POST"] button[type="submit"]').click();
  }
});
</script>
