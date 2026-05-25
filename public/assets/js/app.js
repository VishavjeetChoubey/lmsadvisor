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
    $('#stuDarkIcon')
      .toggleClass('bi-moon-stars', !isDark)
      .toggleClass('bi-sun',        isDark);
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

/* ── Dark mode ───────────────────────────────────────────────────────────── */
$(function () {
  var dark = localStorage.getItem('lms_dark_mode') === '1';
  applyTheme(dark);

  $('#stuDarkToggle').on('click', function () {
    dark = !dark;
    localStorage.setItem('lms_dark_mode', dark ? '1' : '0');
    applyTheme(dark);
  });

  function applyTheme(isDark) {
    document.documentElement.setAttribute('data-theme', isDark ? 'dark' : 'light');
    $('#stuDarkIcon')
      .toggleClass('bi-moon-stars', !isDark)
      .toggleClass('bi-sun', isDark);
  }

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
