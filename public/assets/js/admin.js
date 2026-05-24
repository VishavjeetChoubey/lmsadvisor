/* LMSAdvisor — Admin JS (Phase 1 skeleton) */
'use strict';

$(function () {

  // ── Sidebar toggle (mobile) ──────────────────────────────────
  $('#sidebarToggle').on('click', function () {
    $('#adminSidebar').toggleClass('open');
  });

  // Close sidebar when clicking outside on mobile
  $(document).on('click', function (e) {
    if (window.innerWidth <= 768) {
      if (!$(e.target).closest('#adminSidebar, #sidebarToggle').length) {
        $('#adminSidebar').removeClass('open');
      }
    }
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
