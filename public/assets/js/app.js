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
