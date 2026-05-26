/* LMSAdvisor — Student Portal JS */
'use strict';

$(function () {

  // ── Sidebar toggle (desktop + mobile) ────────────────────────────────────
  var $sidebar = $('#studentSidebar');
  var $main    = $('#studentMain');

  // Desktop sidebar toggle (hamburger in topbar)
  $('#studentSidebarToggle').on('click', function () {
    $sidebar.toggleClass('collapsed');
    $main.toggleClass('sidebar-collapsed');
  });

  // Mobile sidebar toggle
  $('#studentSidebarToggleMobile').on('click', function () {
    $sidebar.toggleClass('open');
  });

  // Close mobile sidebar when clicking outside
  $(document).on('click', function (e) {
    if (window.innerWidth < 992) {
      if (!$(e.target).closest('#studentSidebar, #studentSidebarToggle, #studentSidebarToggleMobile').length) {
        $sidebar.removeClass('open');
      }
    }
  });

  // ── Dark mode toggle ──────────────────────────────────────────────────────
  var darkMode = localStorage.getItem('lms_dark_mode') === '1';
  applyTheme(darkMode);

  $('#stuDarkToggle').on('click', function () {
    darkMode = !darkMode;
    localStorage.setItem('lms_dark_mode', darkMode ? '1' : '0');
    applyTheme(darkMode);
  });

  function applyTheme(isDark) {
    document.documentElement.setAttribute('data-theme', isDark ? 'dark' : 'light');
    var icon = document.getElementById('stuDarkIcon');
    if (icon) {
      icon.className = isDark ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
    }
  }

});

/* ── LMS global utilities (student portal) ──────────────────────────────── */
window.LMS = window.LMS || {};

window.LMS.toast = function (type, message) {
  const colors = { success:'#0e9f6e', error:'#e02424', warning:'#e3a008', info:'#1a56db' };
  const icons  = { success:'bi-check-circle-fill', error:'bi-x-circle-fill', warning:'bi-exclamation-triangle-fill', info:'bi-info-circle-fill' };
  const color  = colors[type] || colors.info;
  const icon   = icons[type]  || icons.info;

  const el = document.createElement('div');
  el.style.cssText = 'position:fixed;bottom:84px;right:20px;z-index:99999;'
    + 'background:' + color + ';color:#fff;padding:12px 18px;border-radius:12px;'
    + 'font-size:13.5px;font-weight:600;box-shadow:0 8px 24px rgba(0,0,0,.2);'
    + 'display:flex;align-items:center;gap:10px;max-width:340px;'
    + 'animation:lmsSlideUp .3s ease;';
  el.innerHTML = '<i class="bi ' + icon + '"></i><span>' + message + '</span>';

  if (!document.getElementById('lmsToastStyle')) {
    const st = document.createElement('style');
    st.id = 'lmsToastStyle';
    st.textContent = '@keyframes lmsSlideUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}';
    document.head.appendChild(st);
  }

  document.body.appendChild(el);
  setTimeout(() => { el.style.opacity='0'; el.style.transition='opacity .3s'; setTimeout(() => el.remove(), 300); }, 3500);
};

window.LMS.confirm = function (message, onConfirm) {
  if (window.confirm(message)) onConfirm();
};

/* ── Dark mode (initialise on page load) ─────────────────────────────────── */
$(function () {
  var dark = localStorage.getItem('lms_dark_mode') === '1';
  // Apply theme on load
  document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
  var icon = document.getElementById('stuDarkIcon');
  if (icon) icon.className = dark ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';

  $('#stuDarkToggle').on('click', function () {
    dark = !dark;
    localStorage.setItem('lms_dark_mode', dark ? '1' : '0');
    document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
    var ic = document.getElementById('stuDarkIcon');
    if (ic) ic.className = dark ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
  });

  /* ── Mobile sidebar toggle ─────────────────────────────────────────────── */
  $('#stuHamburger').on('click', function () {
    $('#stuSidebar').toggleClass('mobile-open');
    $('#stuOverlay').toggleClass('visible');
  });
  $('#stuOverlay').on('click', function () {
    $('#stuSidebar').removeClass('mobile-open');
    $('#stuOverlay').removeClass('visible');
  });
});

/* ── LESSON PLAYER — AJAX navigation (no flicker) ─────────────────────────
   Intercepts all .lp-lesson link clicks, loads content via fetch,
   updates the DOM without a full page reload.
   Fallback: normal navigation if AJAX fails.
──────────────────────────────────────────────────────────────────────────── */
(function () {
  'use strict';

  function initLessonNav() {
    const shell = document.getElementById('lpShell');
    if (!shell) return; // not on player page

    // Intercept lesson sidebar clicks
    document.addEventListener('click', function (e) {
      const link = e.target.closest('.lp-lesson[href]');
      if (!link) return;
      e.preventDefault();
      loadLesson(link.href, link);
    });

    // Intercept prev/next lesson buttons in topbar
    document.addEventListener('click', function (e) {
      const btn = e.target.closest('.lp-prev-btn[href], .lp-next-btn[href]');
      if (!btn) return;
      e.preventDefault();
      loadLesson(btn.href, null);
    });
  }

  function loadLesson(href, clickedLink) {
    const body   = document.getElementById('lpBody');
    const topbar = document.getElementById('lpTopbar');
    if (!body) { window.location.href = href; return; }

    // Show loading overlay
    body.style.opacity   = '.4';
    body.style.transition = 'opacity .15s';

    fetch(href, { headers: { 'X-Requested-With': 'LessonAJAX' } })
      .then(r => {
        if (!r.ok) throw new Error('load failed');
        return r.text();
      })
      .then(html => {
        const parser  = new DOMParser();
        const doc     = parser.parseFromString(html, 'text/html');

        // Extract new lesson body content
        const newBody  = doc.getElementById('lpBody');
        const newTopbar= doc.getElementById('lpTopbar');
        const newSidebar= doc.getElementById('lpSections');

        if (newBody)   body.innerHTML     = newBody.innerHTML;
        if (newTopbar) topbar.innerHTML   = newTopbar.innerHTML;
        if (newSidebar) {
          const curSidebar = document.getElementById('lpSections');
          if (curSidebar) curSidebar.innerHTML = newSidebar.innerHTML;
        }

        // Update page title
        const newTitle = doc.querySelector('title');
        if (newTitle) document.title = newTitle.textContent;

        // Update URL without reload
        history.pushState({}, '', href);

        // Restore fullscreen if it was active
        if (sessionStorage.getItem('lp_fullscreen') === '1') {
          setTimeout(() => {
            if (typeof enterFullscreen === 'function') enterFullscreen();
          }, 50);
        }

        // Re-init mark-complete forms (they use normal POST)
        body.style.opacity = '1';
      })
      .catch(() => {
        // Fallback to normal navigation on error
        window.location.href = href;
      });
  }

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLessonNav);
  } else {
    initLessonNav();
  }
})();

/* ── Student notification bell ──────────────────────────────────────────────── */
(function initStudentNotifications() {
  var bell    = document.getElementById('stuNotifBell');
  var badge   = document.getElementById('stuNotifBadge');
  var list    = document.getElementById('stuNotifList');
  var empty   = document.getElementById('stuNotifEmpty');
  var markAll = document.getElementById('stuMarkAllRead');
  if (!bell) return;

  var BASE = (window.LMS && window.LMS.BASE) || '';

  function timeAgo(dateStr) {
    var diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
    if (diff < 60)    return 'just now';
    if (diff < 3600)  return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    return Math.floor(diff / 86400) + 'd ago';
  }

  function updateBadge(count) {
    if (!badge) return;
    if (count > 0) { badge.textContent = count > 99 ? '99+' : count; badge.classList.remove('d-none'); }
    else badge.classList.add('d-none');
  }

  function loadNotifs() {
    fetch(BASE + '/api/notifications?page=1')
      .then(function(r) { return r.json(); })
      .then(function(data) {
        var rows   = data.rows || [];
        var unread = rows.filter(function(r) { return !r.is_read; }).length;
        updateBadge(unread);
        if (!list) return;
        if (!rows.length) { if (empty) empty.style.display = ''; list.innerHTML = ''; list.appendChild(empty); return; }
        if (empty) empty.style.display = 'none';
        list.innerHTML = rows.map(function(n) {
          var isUnread = !n.is_read;
          return '<div class="notif-item' + (isUnread ? ' unread' : '') + '" data-id="' + n.id + '" style="display:flex;align-items:flex-start;gap:10px;padding:10px 14px;border-bottom:1px solid var(--border);cursor:pointer;background:' + (isUnread ? 'var(--primary-light)' : 'var(--card)') + '">'
            + '<div style="width:8px;height:8px;border-radius:50%;background:' + (isUnread ? 'var(--primary)' : 'transparent') + ';margin-top:5px;flex-shrink:0"></div>'
            + '<div style="flex:1;min-width:0"><div style="font-size:13px;font-weight:' + (isUnread ? '600' : '400') + ';color:var(--text-1)">' + (n.title || '') + '</div>'
            + (n.body ? '<div style="font-size:12px;color:var(--text-2);margin-top:2px">' + n.body + '</div>' : '')
            + '</div><div style="font-size:11px;color:var(--text-3);white-space:nowrap;margin-top:2px">' + timeAgo(n.created_at) + '</div></div>';
        }).join('');
        list.querySelectorAll('.notif-item').forEach(function(el) {
          el.addEventListener('click', function() {
            var id = this.dataset.id;
            fetch(BASE + '/api/notifications/' + id + '/read', { method: 'POST' }).catch(function(){});
            this.style.background = 'var(--card)';
            this.querySelector('div[style*="border-radius:50%"]').style.background = 'transparent';
            var remaining = list.querySelectorAll('[style*="var(--primary-light)"]').length;
            updateBadge(remaining);
          });
        });
      }).catch(function() {});
  }

  bell.addEventListener('shown.bs.dropdown', loadNotifs);

  markAll && markAll.addEventListener('click', function() {
    fetch(BASE + '/api/notifications/read-all', { method: 'POST' }).then(function() { loadNotifs(); }).catch(function(){});
  });

  // Load count on page load
  fetch(BASE + '/api/notifications/count')
    .then(function(r) { return r.json(); })
    .then(function(d) { updateBadge(d.count || 0); })
    .catch(function() {});

  // Poll every 60s
  setInterval(function() {
    fetch(BASE + '/api/notifications/count')
      .then(function(r) { return r.json(); })
      .then(function(d) { updateBadge(d.count || 0); })
      .catch(function() {});
  }, 60000);
})();
