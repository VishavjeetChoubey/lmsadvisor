/* LMSAdvisor — Admin JS */
'use strict';

$(function () {

  // ── Sidebar toggle (mobile) ──────────────────────────────────────────────
  // Create overlay if not present
  if (!document.getElementById('adminOverlay')) {
    var ov = document.createElement('div');
    ov.id = 'adminOverlay';
    ov.style.cssText = 'display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:950;backdrop-filter:blur(2px)';
    document.body.appendChild(ov);
  }

  function openSidebar() {
    $('#adminSidebar').addClass('mobile-open');
    $('#adminOverlay').fadeIn(200);
    document.body.style.overflow = 'hidden';
  }
  function closeSidebar() {
    $('#adminSidebar').removeClass('mobile-open');
    $('#adminOverlay').fadeOut(150);
    document.body.style.overflow = '';
  }

  $('#sidebarToggle').on('click', function () {
    if ($('#adminSidebar').hasClass('mobile-open')) {
      closeSidebar();
    } else {
      openSidebar();
    }
  });

  $('#adminOverlay').on('click', closeSidebar);

  // Close on nav link click (mobile)
  $('#adminSidebar .adm-nav-btn').on('click', function () {
    if (window.innerWidth <= 991) closeSidebar();
  });

  // Keyboard: Escape closes sidebar
  $(document).on('keydown', function (e) {
    if (e.key === 'Escape') closeSidebar();
  });

  // ── Auto-dismiss Bootstrap toasts ───────────────────────────
  var toastElements = document.querySelectorAll('.toast');
  toastElements.forEach(function (el) {
    var toast = new bootstrap.Toast(el, { delay: 4000 });
    toast.show();
  });

  // ── Active nav item highlight ────────────────────────────────
  var path = window.location.pathname;
  $('.nav-item').each(function () {
    var href = $(this).attr('href');
    if (href && path.includes(href) && href !== '/') {
      $(this).addClass('active');
    }
  });

});

/**
 * Show a toast notification programmatically.
 * Usage: LMS.toast('success', 'Record saved!');
 */
window.LMS = window.LMS || {};
window.LMS.toast = function (type, message) {
  var colors = {
    success: 'bg-success',
    error:   'bg-danger',
    warning: 'bg-warning',
    info:    'bg-primary',
  };
  var icons = {
    success: 'bi-check-circle',
    error:   'bi-x-circle',
    warning: 'bi-exclamation-triangle',
    info:    'bi-info-circle',
  };

  var bg   = colors[type]  || 'bg-secondary';
  var icon = icons[type]   || 'bi-bell';

  var html = '<div class="toast align-items-center text-white ' + bg + ' border-0 mb-2" role="alert" aria-live="assertive" data-bs-autohide="true" data-bs-delay="4000">'
           + '<div class="d-flex">'
           + '<div class="toast-body d-flex align-items-center gap-2">'
           + '<i class="bi ' + icon + '"></i>' + $('<span>').text(message).html()
           + '</div>'
           + '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>'
           + '</div></div>';

  var $toast = $(html).appendTo('#toastContainer');
  new bootstrap.Toast($toast[0], { delay: 4000 }).show();
  $toast.on('hidden.bs.toast', function () { $toast.remove(); });
};

/**
 * Custom confirm modal (no window.confirm ever).
 * Usage: LMS.confirm('Delete this item?', function() { /* proceed *\/ });
 */
window.LMS.confirm = function (message, onConfirm) {
  var existing = document.getElementById('lmsConfirmModal');
  if (existing) existing.remove();

  var html = '<div class="modal fade" id="lmsConfirmModal" tabindex="-1">'
           + '<div class="modal-dialog modal-sm modal-dialog-centered">'
           + '<div class="modal-content">'
           + '<div class="modal-header border-0 pb-0">'
           + '<h6 class="modal-title fw-semibold">Confirm Action</h6>'
           + '<button type="button" class="btn-close" data-bs-dismiss="modal"></button>'
           + '</div>'
           + '<div class="modal-body text-muted" style="font-size:13.5px">' + $('<span>').text(message).html() + '</div>'
           + '<div class="modal-footer border-0 pt-0 gap-2">'
           + '<button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>'
           + '<button type="button" class="btn btn-sm btn-danger" id="lmsConfirmOk">Confirm</button>'
           + '</div></div></div></div>';

  var $modal = $(html).appendTo('body');
  var modal  = new bootstrap.Modal($modal[0]);

  $modal.find('#lmsConfirmOk').on('click', function () {
    modal.hide();
    if (typeof onConfirm === 'function') onConfirm();
  });

  $modal[0].addEventListener('hidden.bs.modal', function () { $modal.remove(); });
  modal.show();
};

/* ── Notification bell ──────────────────────────────────────────────────────── */
(function initNotifications() {
  var BASE = (window.LMS && window.LMS.BASE) || '';

  function timeAgo(dateStr) {
    var diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
    if (diff < 60)   return 'just now';
    if (diff < 3600) return Math.floor(diff/60) + 'm ago';
    if (diff < 86400)return Math.floor(diff/3600) + 'h ago';
    return Math.floor(diff/86400) + 'd ago';
  }

  function loadNotifs() {
    fetch(BASE + '/api/notifications?page=1')
    .then(function(r){ return r.json(); })
    .then(function(data) {
      var rows  = data.rows || [];
      var list  = document.getElementById('notifList');
      var badge = document.getElementById('notifBadge');
      var empty = document.getElementById('notifEmpty');
      if (!list) return;

      var unread = rows.filter(function(r){ return !r.is_read; }).length;
      if (unread > 0) {
        badge.textContent = unread > 99 ? '99+' : unread;
        badge.classList.remove('d-none');
      } else {
        badge.classList.add('d-none');
      }

      if (!rows.length) { if(empty) empty.style.display=''; return; }
      if(empty) empty.style.display='none';

      list.innerHTML = rows.map(function(n) {
        return '<div class="notif-item' + (!n.is_read?' unread':'') + '" data-id="' + n.id + '">'
          + (n.is_read
              ? '<div class="notif-read-dot"></div>'
              : '<div class="notif-dot"></div>')
          + '<div class="flex-grow-1">'
          + '<div class="notif-title">' + $('<span>').text(n.title).html() + '</div>'
          + (n.body ? '<div class="notif-body">' + $('<span>').text(n.body).html() + '</div>' : '')
          + '</div>'
          + '<div class="notif-time">' + timeAgo(n.created_at) + '</div>'
          + '</div>';
      }).join('');

      list.querySelectorAll('.notif-item').forEach(function(el) {
        el.addEventListener('click', function() {
          var id = this.dataset.id;
          fetch(BASE + '/api/notifications/' + id + '/read', { method:'POST' });
          this.classList.remove('unread');
          this.querySelector('.notif-dot')?.classList.replace('notif-dot','notif-read-dot');
          var remaining = list.querySelectorAll('.unread').length;
          if (remaining === 0) badge.classList.add('d-none');
          else badge.textContent = remaining;
        });
      });
    }).catch(function(){});
  }

  // Mark all read
  document.getElementById('markAllRead')?.addEventListener('click', function() {
    fetch(BASE + '/api/notifications/read-all', { method:'POST' })
    .then(function(){ loadNotifs(); });
  });

  // Load when dropdown opens
  var bell = document.getElementById('notifBell');
  if (bell) {
    bell.addEventListener('shown.bs.dropdown', loadNotifs);
    // Poll unread count every 60s
    loadNotifs();
    setInterval(function() {
      fetch(BASE + '/api/notifications/count')
      .then(function(r){ return r.json(); })
      .then(function(d) {
        var badge = document.getElementById('notifBadge');
        if (!badge) return;
        if (d.count > 0) { badge.textContent = d.count > 99 ? '99+' : d.count; badge.classList.remove('d-none'); }
        else badge.classList.add('d-none');
      }).catch(function(){});
    }, 60000);
  }
})();
