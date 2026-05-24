# Vendor Libraries

Place local copies of all CDN libraries here for offline/production use.

Expected structure:
  bootstrap/          — bootstrap.min.css, bootstrap.bundle.min.js
  bootstrap-icons/    — bootstrap-icons.min.css + fonts/
  fontawesome/        — css/all.min.css + webfonts/
  jquery/             — jquery.min.js
  quill/              — quill.snow.css, quill.min.js
  sortablejs/         — Sortable.min.js
  chartjs/            — chart.umd.min.js
  fullcalendar/       — index.global.min.js
  tom-select/         — tom-select.complete.min.css, tom-select.complete.min.js
  plyr/               — plyr.css, plyr.min.js
  pdfjs/              — pdf.min.mjs, pdf.worker.min.mjs
  inter/              — inter.css + woff2 files

All are CDN-fallback in development — local copies only needed in production.
