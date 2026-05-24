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

  $('#darkModeToggle').on('click', function () {
    darkMode = !darkMode;
    localStorage.setItem('lms_dark_mode', darkMode ? '1' : '0');
    applyTheme(darkMode);
  });

  function applyTheme(isDark) {
    document.documentElement.setAttribute('data-theme', isDark ? 'dark' : 'light');
    $('#darkModeToggle i')
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
