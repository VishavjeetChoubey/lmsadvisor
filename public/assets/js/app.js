/* LMSAdvisor — Student Portal JS */
'use strict';

$(function () {

  // ── Sidebar toggle (mobile) ──────────────────────────────────
  $('#studentSidebarToggle').on('click', function () {
    $('#studentSidebar').toggleClass('open');
  });

  $(document).on('click', function (e) {
    if (window.innerWidth <= 768) {
      if (!$(e.target).closest('#studentSidebar, #studentSidebarToggle').length) {
        $('#studentSidebar').removeClass('open');
      }
    }
  });

  // ── Dark mode toggle ─────────────────────────────────────────
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
      .toggleClass('bi-sun', isDark);
  }

});
