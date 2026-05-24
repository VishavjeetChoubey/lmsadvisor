<?php
use App\Core\View;
$e = fn(mixed $v): string => View::e($v);
?>
<div class="card lms-card mb-4">
  <div class="card-header lms-card-header">
    <h5 class="mb-0"><i class="bi bi-calendar3 me-2"></i> My Learning Calendar</h5>
  </div>
  <div class="card-body p-3">
    <div id="calendarEl" style="min-height:500px"></div>
  </div>
</div>

<!-- Legend -->
<div class="card lms-card">
  <div class="card-body py-2 px-4 d-flex gap-4 flex-wrap" style="font-size:13px">
    <span><span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:#1a56db;margin-right:6px"></span>Enrollment</span>
    <span><span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:#e02424;margin-right:6px"></span>Deadline</span>
    <span><span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:#0e9f6e;margin-right:6px"></span>Completion</span>
    <span><span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:#7c3aed;margin-right:6px"></span>Webinar</span>
  </div>
</div>

<script>
const EVENTS = <?= json_encode($events, JSON_HEX_TAG) ?>;

(function loadCalendar() {
  if (!document.querySelector('link[href*="fullcalendar"]')) {
    const css = document.createElement('link');
    css.rel  = 'stylesheet';
    css.href = 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css';
    document.head.appendChild(css);
  }

  const s   = document.createElement('script');
  s.src     = 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js';
  s.onload  = function () {
    const cal = new FullCalendar.Calendar(document.getElementById('calendarEl'), {
      initialView: 'dayGridMonth',
      headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,listWeek' },
      events: EVENTS,
      height: 560,
      eventClick: function (info) {
        const p = info.event.extendedProps;
        alert(info.event.title + '\nCourse: ' + p.course_title + (p.notes ? '\n' + p.notes : ''));
      },
    });
    cal.render();
  };
  document.head.appendChild(s);
})();
</script>
