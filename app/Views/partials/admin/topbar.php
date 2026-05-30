<?php
use App\Core\View;
use App\Services\AuthService;
$url      = fn(string $p = ''): string => View::url($p);
$e        = fn(mixed $v): string => View::e($v);
$authUser = AuthService::user() ?? [];
$role     = $authUser['role'] ?? 'admin';

$roleIcon = match($role) {
    'super_admin' => '<i class="fas fa-crown" style="font-size:.7rem;color:#e3a008"></i>',
    'admin'       => '<i class="fas fa-shield-alt" style="font-size:.7rem;color:#1a56db"></i>',
    'manager'     => '<i class="fas fa-briefcase" style="font-size:.7rem;color:#0891b2"></i>',
    default       => '<i class="fas fa-graduation-cap" style="font-size:.7rem;color:#0e9f6e"></i>',
};
$firstName = explode(' ', $authUser['name'] ?? 'Admin')[0];
?>
<header class="admin-topbar">
  <button class="topbar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
    <i class="bi bi-list"></i>
  </button>

  <div class="topbar-title"><?= $e($page_title ?? 'Dashboard') ?></div>
  <div class="flex-grow-1"></div>

  <!-- Global search -->
  <div class="topbar-search-wrap" style="position:relative;margin-right:8px">
    <div style="position:relative">
      <i class="bi bi-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:13px;pointer-events:none"></i>
      <input type="text" id="globalSearch"
             placeholder="Search courses, students…"
             autocomplete="off"
             style="background:var(--card-bg);border:1px solid var(--border-color);border-radius:20px;padding:7px 12px 7px 32px;font-size:13px;width:220px;color:var(--text-primary);outline:none;transition:width .2s,border-color .2s"
             onfocus="this.style.width='280px';this.style.borderColor='#6366f1'"
             onblur="this.style.width='220px';this.style.borderColor=''">
    </div>
    <!-- Dropdown results -->
    <div id="searchDropdown" style="display:none;position:absolute;top:calc(100% + 6px);left:0;right:0;min-width:320px;background:var(--content-bg);border:1px solid var(--border-color);border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.12);z-index:9999;max-height:360px;overflow-y:auto">
      <div id="searchResults" style="padding:6px 0"></div>
    </div>
  </div>

  <script>
  (function() {
    var input  = document.getElementById('globalSearch');
    var drop   = document.getElementById('searchDropdown');
    var res    = document.getElementById('searchResults');
    var timer;
    var icons  = { course:'bi-journal-bookmark-fill', user:'bi-person-fill', enrollment:'bi-person-check-fill', lesson:'bi-play-circle-fill' };
    var links  = { course:'/admin/courses/', user:'/admin/users/', enrollment:'/admin/courses/', lesson:'/admin/courses/' };
    var suffix = { course:'/edit', user:'/edit', enrollment:'/edit', lesson:'/edit' };
    var BASE   = '<?= rtrim(APP_URL, '/') ?>';

    input.addEventListener('input', function() {
      clearTimeout(timer);
      var q = this.value.trim();
      if (q.length < 2) { drop.style.display='none'; return; }
      timer = setTimeout(function() {
        fetch(BASE + '/admin/search?q=' + encodeURIComponent(q))
        .then(r => r.json())
        .then(function(d) {
          if (!d.results.length) {
            res.innerHTML = '<div style="padding:16px 14px;font-size:13px;color:var(--text-muted);text-align:center">No results for "' + q + '"</div>';
          } else {
            res.innerHTML = d.results.map(function(r) {
              var icon  = icons[r.type] || 'bi-search';
              var url   = BASE + links[r.type] + (r.uuid||'') + (suffix[r.type]||'');
              return '<a href="' + url + '" style="display:flex;align-items:center;gap:10px;padding:9px 14px;text-decoration:none;border-bottom:1px solid var(--border-color);transition:background .1s" onmouseover="this.style.background=\'var(--card-bg)\'" onmouseout="this.style.background=\'\'">' +
                '<i class="bi ' + icon + '" style="color:#6366f1;font-size:15px;flex-shrink:0"></i>' +
                '<div style="min-width:0"><div style="font-size:13px;font-weight:600;color:var(--text-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">' + r.title + '</div>' +
                '<div style="font-size:11.5px;color:var(--text-muted)">' + r.meta + '</div></div>' +
                '</a>';
            }).join('');
          }
          res.innerHTML += '<a href="' + BASE + '/admin/search?q=' + encodeURIComponent(q) + '" style="display:block;padding:9px 14px;font-size:12.5px;color:#6366f1;text-align:center;font-weight:600;text-decoration:none">See all results →</a>';
          drop.style.display = 'block';
        }).catch(function(){});
      }, 250);
    });

    document.addEventListener('click', function(e) {
      if (!e.target.closest('.topbar-search-wrap')) drop.style.display='none';
    });
    input.addEventListener('keydown', function(e) {
      if (e.key === 'Enter') {
        window.location.href = BASE + '/admin/search?q=' + encodeURIComponent(this.value.trim());
      }
      if (e.key === 'Escape') { drop.style.display='none'; this.blur(); }
    });
  })();
  </script>

  <!-- Notification bell with live unread count -->
  <div class="dropdown">
    <button class="topbar-icon-btn position-relative" id="notifBell" data-bs-toggle="dropdown" title="Notifications">
      <i class="bi bi-bell"></i>
      <span class="notif-badge d-none" id="notifBadge">0</span>
    </button>
    <div class="dropdown-menu dropdown-menu-end shadow" id="notifDropdown"
         style="width:340px;max-height:420px;overflow:hidden;border-radius:14px;margin-top:8px">
      <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
        <span class="fw-semibold" style="font-size:14px">Notifications</span>
        <button class="btn btn-xs btn-outline-secondary" id="markAllRead" style="font-size:11px;border-radius:6px;padding:2px 8px">
          Mark all read
        </button>
      </div>
      <div id="notifList" style="max-height:340px;overflow-y:auto">
        <div class="text-center py-4 text-muted" style="font-size:13px" id="notifEmpty">
          <i class="bi bi-bell-slash" style="font-size:1.5rem;opacity:.3"></i><br>No notifications
        </div>
      </div>
    </div>
  </div>

  <!-- ── Admin-only tools (desktop) ──────────────────────────────────────── -->
  <div class="d-none d-lg-flex align-items-center gap-2">

    <!-- Learner View -->
    <a href="<?= $url('learn/dashboard') ?>" target="_blank"
       style="display:inline-flex;align-items:center;gap:6px;padding:5px 14px;border-radius:20px;border:1px solid #6366f1;color:#6366f1;background:transparent;font-size:12px;font-weight:600;text-decoration:none;white-space:nowrap;transition:all .15s"
       onmouseover="this.style.background='#6366f1';this.style.color='#fff'"
       onmouseout="this.style.background='transparent';this.style.color='#6366f1'">
      <i class="bi bi-mortarboard-fill" style="font-size:12px"></i> Learner View
    </a>

    <!-- Page load speed -->
    <span id="admLoadTime" title="Page load time"
          style="display:inline-flex;align-items:center;gap:4px;font-size:12px;font-weight:600;color:var(--text-muted)">
      <i class="bi bi-lightning-charge-fill" style="color:#f59e0b;font-size:13px"></i>
      <span id="admLoadMs">—</span>
    </span>

    <!-- WiFi / connectivity — label hidden, shown on hover via title -->
    <span title="" id="admNetWrap"
          style="display:inline-flex;align-items:center;cursor:default">
      <i class="bi bi-wifi" id="admWifiIcon" style="font-size:15px;color:#9ca3af"></i>
    </span>

  </div>

  <div class="topbar-divider"></div>

  <script>
  (function(){
    // Page load time — show in seconds e.g. 0.8s
    window.addEventListener('load', function(){
      var el = document.getElementById('admLoadMs');
      if(!el || !window.performance) return;
      setTimeout(function(){
        var nav = performance.getEntriesByType('navigation')[0];
        var ms  = nav ? Math.round(nav.duration) : Math.round(performance.now());
        var sec = (ms / 1000).toFixed(1) + 's';
        el.textContent = sec;
        el.style.color = ms < 800 ? '#059669' : ms < 2000 ? '#d97706' : '#dc2626';
      }, 100);
    });

    // Network status — color only on icon, speed shown in title on hover
    function updateNet(){
      var icon = document.getElementById('admWifiIcon');
      var wrap = document.getElementById('admNetWrap');
      if(!icon) return;
      if(!navigator.onLine){
        icon.style.color = '#dc2626';
        if(wrap) wrap.title = 'Offline'; return;
      }
      var conn  = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
      var speed = conn ? conn.downlink : null;
      var color = speed === null ? '#6366f1' : speed >= 5 ? '#059669' : speed >= 1 ? '#d97706' : '#dc2626';
      var tip   = speed === null ? 'Online' : speed >= 5 ? speed.toFixed(0)+' Mbps' : speed >= 1 ? speed.toFixed(1)+' Mbps' : 'Slow connection';
      icon.style.color = color;
      if(wrap) wrap.title = tip;
    }
    updateNet();
    window.addEventListener('online',  updateNet);
    window.addEventListener('offline', updateNet);
    if(navigator.connection) navigator.connection.addEventListener('change', updateNet);
  })();
  </script>

  <div class="dropdown">
    <button class="topbar-user-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
      <span class="topbar-avatar"><?= $roleIcon ?></span>
      <span class="topbar-username d-none d-sm-inline"><?= $e($firstName) ?></span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end topbar-dropdown shadow-sm">
      <li class="px-3 py-2 border-bottom">
        <div style="font-size:12.5px;font-weight:600;color:var(--text-primary)"><?= $e($authUser['name'] ?? '') ?></div>
        <div style="font-size:11px;color:var(--text-muted)"><?= $e($authUser['email'] ?? '') ?></div>
      </li>
      <li><a class="dropdown-item mt-1" href="<?= $url('admin/settings') ?>">
        <i class="bi bi-gear me-2"></i> Settings
      </a></li>
      <li><hr class="dropdown-divider my-1"></li>
      <li><a class="dropdown-item text-danger" href="<?= $url('logout') ?>">
        <i class="bi bi-box-arrow-right me-2"></i> Sign Out
      </a></li>
    </ul>
  </div>
</header>
