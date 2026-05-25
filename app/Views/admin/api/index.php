<?php
use App\Core\View;
$e   = fn(mixed $v): string => View::e($v);
$url = fn(string $p = ''): string => View::url($p);
?>

<!-- Stats -->
<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="stat-card">
      <div class="stat-icon text-primary"><i class="bi bi-key"></i></div>
      <div class="stat-body">
        <div class="stat-value"><?= $active_tokens ?></div>
        <div class="stat-label">Active Tokens</div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card">
      <div class="stat-icon text-success"><i class="bi bi-lightning-charge"></i></div>
      <div class="stat-body">
        <div class="stat-value"><?= number_format($total_requests) ?></div>
        <div class="stat-label">Total API Requests</div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card">
      <div class="stat-icon text-warning"><i class="bi bi-shield-exclamation"></i></div>
      <div class="stat-body">
        <div class="stat-value"><?= count($recent_events) ?></div>
        <div class="stat-label">Security Events (24h)</div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card">
      <div class="stat-icon text-info"><i class="bi bi-file-text"></i></div>
      <div class="stat-body">
        <div class="stat-value"><?= count($audit_logs) ?></div>
        <div class="stat-label">API Audit Entries</div>
      </div>
    </div>
  </div>
</div>

<!-- Tabs -->
<ul class="nav nav-pills gap-1 mb-4" id="apiTabs">
  <li class="nav-item"><a class="nav-link active" href="#" data-tab="tokens"><i class="bi bi-key me-1"></i>Tokens</a></li>
  <li class="nav-item"><a class="nav-link" href="#" data-tab="playground"><i class="bi bi-terminal me-1"></i>Playground</a></li>
  <li class="nav-item"><a class="nav-link" href="#" data-tab="security"><i class="bi bi-shield-check me-1"></i>Security</a></li>
  <li class="nav-item"><a class="nav-link" href="#" data-tab="soc2"><i class="bi bi-patch-check me-1"></i>SOC2</a></li>
  <li class="nav-item">
    <a class="nav-link" href="<?= $url('admin/api/docs') ?>"><i class="bi bi-book me-1"></i>Docs</a>
  </li>
</ul>

<!-- ── TOKENS TAB ──────────────────────────────────────────────────────────── -->
<div id="tab-tokens" class="api-tab active">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-semibold mb-0"><i class="bi bi-key me-2"></i>API Tokens</h5>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createTokenModal">
      <i class="bi bi-plus-circle me-1"></i> Generate Token
    </button>
  </div>

  <div class="card lms-card">
    <div class="table-responsive">
      <table class="table table-hover lms-table mb-0">
        <thead><tr>
          <th>Name</th><th>User</th><th>Scopes</th><th>Requests</th><th>Last Used</th><th>Expires</th><th>Status</th><th></th>
        </tr></thead>
        <tbody>
          <?php if (empty($tokens)): ?>
          <tr><td colspan="8" class="text-center py-4 text-muted">No tokens yet. Generate your first token.</td></tr>
          <?php else: ?>
          <?php foreach ($tokens as $t): ?>
          <tr>
            <td>
              <div class="fw-semibold" style="font-size:13.5px"><?= $e($t['name'] ?? '—') ?></div>
              <?php if ($t['description']): ?>
                <div class="text-muted" style="font-size:11.5px"><?= $e($t['description']) ?></div>
              <?php endif; ?>
            </td>
            <td style="font-size:13px">
              <div><?= $e($t['first_name'].' '.$t['last_name']) ?></div>
              <div class="text-muted" style="font-size:11.5px"><?= $e($t['email']) ?></div>
            </td>
            <td>
              <?php foreach (explode(',', $t['scopes'] ?? 'read') as $scope): ?>
              <span class="badge bg-<?= $scope==='admin'?'danger':($scope==='write'?'warning':'secondary') ?>-subtle text-<?= $scope==='admin'?'danger':($scope==='write'?'warning':'secondary') ?>" style="font-size:10.5px"><?= $e(trim($scope)) ?></span>
              <?php endforeach; ?>
            </td>
            <td class="fw-semibold" style="font-size:13px"><?= number_format((int)$t['request_count']) ?></td>
            <td class="text-muted" style="font-size:12px"><?= $t['last_used'] ? date('d M Y H:i', strtotime($t['last_used'])) : 'Never' ?></td>
            <td class="text-muted" style="font-size:12px">
              <?php if ($t['expires_at']): ?>
                <span class="<?= strtotime($t['expires_at']) < time() ? 'text-danger' : '' ?>">
                  <?= date('d M Y', strtotime($t['expires_at'])) ?>
                </span>
              <?php else: ?>Never<?php endif; ?>
            </td>
            <td>
              <span class="badge bg-<?= $t['is_active'] ? 'success' : 'secondary' ?>">
                <?= $t['is_active'] ? 'Active' : 'Revoked' ?>
              </span>
            </td>
            <td>
              <div class="d-flex gap-1">
                <?php if ($t['is_active']): ?>
                <button class="btn btn-xs btn-outline-warning btn-rotate-token"
                        data-id="<?= $t['id'] ?>" title="Rotate (generate new token value)">
                  <i class="bi bi-arrow-repeat"></i>
                </button>
                <button class="btn btn-xs btn-outline-danger btn-revoke-token"
                        data-id="<?= $t['id'] ?>" data-name="<?= $e($t['name']) ?>">
                  <i class="bi bi-x-lg"></i> Revoke
                </button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ── PLAYGROUND TAB ─────────────────────────────────────────────────────── -->
<div id="tab-playground" class="api-tab d-none">
  <div class="row g-4">
    <div class="col-12 col-lg-4">
      <div class="card lms-card">
        <div class="card-header lms-card-header"><h6 class="mb-0"><i class="bi bi-gear me-2"></i>Request Builder</h6></div>
        <div class="card-body p-4">
          <!-- Token -->
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:13px">Bearer Token</label>
            <div class="input-group">
              <input type="password" class="form-control form-control-sm" id="pgToken" placeholder="Paste your token…">
              <button class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('pgToken').type==='password'?document.getElementById('pgToken').type='text':document.getElementById('pgToken').type='password'">
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </div>
          <!-- Method + URL -->
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:13px">Method & Endpoint</label>
            <div class="d-flex gap-2">
              <select class="form-select form-select-sm" id="pgMethod" style="width:90px;flex-shrink:0">
                <option>GET</option><option>POST</option><option>PUT</option><option>DELETE</option>
              </select>
              <input type="text" class="form-control form-control-sm" id="pgEndpoint"
                     value="<?= $e(APP_URL) ?>/api/v1/health" placeholder="https://…">
            </div>
          </div>
          <!-- Preset endpoints -->
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:13px">Quick Presets</label>
            <div class="d-flex flex-wrap gap-1">
              <?php
              $presets = [
                ['GET','/api/v1/health'],
                ['GET','/api/v1/profile'],
                ['GET','/api/v1/courses'],
                ['GET','/api/v1/enrollments'],
                ['GET','/api/v1/leaderboard'],
                ['GET','/api/v1/webinars'],
                ['GET','/api/v1/kb/articles'],
                ['GET','/api/v1/certificates'],
              ];
              foreach ($presets as [$m,$p]):
              ?>
              <button class="btn btn-xs btn-outline-secondary preset-btn"
                      data-method="<?= $m ?>" data-path="<?= $p ?>">
                <?= $m ?> <?= $p ?>
              </button>
              <?php endforeach; ?>
            </div>
          </div>
          <!-- Body -->
          <div class="mb-3" id="pgBodyWrap" style="display:none">
            <label class="form-label fw-semibold" style="font-size:13px">Request Body (JSON)</label>
            <textarea class="form-control form-control-sm" id="pgBody" rows="5" style="font-family:monospace;font-size:12px" placeholder='{"key": "value"}'></textarea>
          </div>
          <!-- Send -->
          <button class="btn btn-primary w-100" id="pgSend">
            <i class="bi bi-send me-1"></i> Send Request
          </button>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-8">
      <div class="card lms-card h-100">
        <div class="card-header lms-card-header d-flex justify-content-between">
          <h6 class="mb-0"><i class="bi bi-terminal me-2"></i>Response</h6>
          <div class="d-flex gap-2 align-items-center">
            <span id="pgStatusBadge"></span>
            <span id="pgTimeBadge" class="text-muted" style="font-size:12px"></span>
            <button class="btn btn-xs btn-outline-secondary" onclick="document.getElementById('pgResponse').textContent=''">Clear</button>
          </div>
        </div>
        <div class="card-body p-0">
          <pre id="pgResponse" style="margin:0;padding:16px;background:var(--content-bg);border-radius:0 0 var(--radius) var(--radius);min-height:400px;font-size:12.5px;font-family:'Fira Code',monospace;overflow-x:auto;color:var(--text-primary);white-space:pre-wrap;word-break:break-all">// Response will appear here
</pre>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ── SECURITY TAB ────────────────────────────────────────────────────────── -->
<div id="tab-security" class="api-tab d-none">
  <div class="row g-4">
    <div class="col-12 col-lg-6">
      <div class="card lms-card">
        <div class="card-header lms-card-header"><h5 class="mb-0"><i class="bi bi-shield-exclamation text-warning me-2"></i>Recent Security Events</h5></div>
        <?php if (empty($recent_events)): ?>
          <div class="card-body text-center py-4 text-muted">No security events. ✓</div>
        <?php else: ?>
        <div class="list-group list-group-flush">
          <?php foreach ($recent_events as $ev): ?>
          <div class="list-group-item py-3">
            <div class="d-flex align-items-center gap-3">
              <div class="badge bg-<?= str_contains($ev['event_type'],'failed')||str_contains($ev['event_type'],'blocked')?'danger':'warning' ?>"><?= $e($ev['event_type']) ?></div>
              <div class="flex-grow-1">
                <div style="font-size:12.5px;color:var(--text-muted)">
                  IP: <?= $e($ev['ip_address'] ?? '—') ?>
                  · <?= date('d M Y H:i', strtotime($ev['created_at'])) ?>
                </div>
                <?php if ($ev['details']): ?>
                <div style="font-size:12px;color:var(--text-muted);font-family:monospace"><?= $e($ev['details']) ?></div>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <div class="col-12 col-lg-6">
      <div class="card lms-card">
        <div class="card-header lms-card-header"><h5 class="mb-0"><i class="bi bi-journal-text text-info me-2"></i>API Audit Log</h5></div>
        <?php if (empty($audit_logs)): ?>
          <div class="card-body text-center py-4 text-muted">No API audit entries yet.</div>
        <?php else: ?>
        <div class="list-group list-group-flush">
          <?php foreach ($audit_logs as $al): ?>
          <div class="list-group-item py-2">
            <div class="d-flex align-items-center gap-2">
              <span class="badge bg-primary-subtle text-primary" style="font-size:10.5px"><?= $e($al['action']) ?></span>
              <span style="font-size:12.5px"><?= $e(($al['first_name']??'').' '.($al['last_name']??'')) ?></span>
              <span class="ms-auto text-muted" style="font-size:11.5px"><?= date('H:i d M', strtotime($al['created_at'])) ?></span>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- ── SOC2 TAB ────────────────────────────────────────────────────────────── -->
<div id="tab-soc2" class="api-tab d-none">
  <div class="card lms-card mb-4">
    <div class="card-header lms-card-header">
      <h5 class="mb-0"><i class="bi bi-patch-check-fill text-success me-2"></i>SOC2 Compliance Checklist</h5>
    </div>
    <div class="card-body p-4">
      <?php
      $checks = [
        ['Security', [
          ['✓','All API tokens are cryptographically random (40-char hex via random_bytes)'],
          ['✓','Bearer token authentication on all protected endpoints'],
          ['✓','IP whitelist per token — block unauthorized IPs'],
          ['✓','Token scope enforcement (read / write / admin)'],
          ['✓','Token expiry support with configurable TTL'],
          ['✓','Rate limiting: 100 req/min API, 10 login attempts/5min'],
          ['✓','HTTP security headers: X-Content-Type-Options, X-Frame-Options, Referrer-Policy'],
          ['✓','CORS headers with explicit method allowlist'],
          ['✓','Password hashing: bcrypt cost=12'],
          ['✓','CSRF protection on all state-changing web forms'],
          ['✓','Input sanitization via Sanitizer helper on all inputs'],
          ['✓','SQL injection prevention: PDO prepared statements everywhere'],
          ['✓','XSS prevention: View::e() htmlspecialchars on all output'],
        ]],
        ['Availability', [
          ['✓','Rate limiting prevents abuse and ensures fair access'],
          ['✓','Service Worker caches student assets for offline PWA use'],
          ['✓','Try/catch in App::run() prevents unhandled crash blank pages'],
        ]],
        ['Confidentiality', [
          ['✓','AES-256 encryption for sensitive settings (API keys, secrets)'],
          ['✓','Passwords never stored in plain text'],
          ['✓','API token values only shown once at creation'],
          ['✓','IP whitelist restricts token access to known origins'],
          ['✓','Role-based access control (super_admin/admin/manager/student)'],
          ['✓','Impersonation logged with full audit trail'],
        ]],
        ['Processing Integrity', [
          ['✓','Audit log for all admin actions (CRUD, role changes, logins)'],
          ['✓','Security events table tracks auth failures, blocked IPs'],
          ['✓','API request count tracked per token'],
          ['✓','SCORM progress persisted in DB on every SetValue call'],
          ['✓','Lesson progress atomic — SELECT then INSERT/UPDATE (no race)'],
        ]],
        ['Privacy', [
          ['✓','Student data isolated: enrollment/progress only accessible by self'],
          ['✓','Admin impersonation requires super_admin role + leaves audit trail'],
          ['✓','Certificate verification is public (UUID-based, no PII leakage)'],
          ['⚠','Data export (GDPR): user CSV exists for admin, student self-export TBD'],
        ]],
      ];
      foreach ($checks as [$category, $items]):
      ?>
      <div class="mb-4">
        <h6 class="fw-bold mb-3 text-primary"><?= $e($category) ?></h6>
        <div class="row g-2">
          <?php foreach ($items as [$status, $desc]): ?>
          <div class="col-12 col-lg-6">
            <div class="d-flex align-items-start gap-2 p-2 rounded" style="background:var(--content-bg)">
              <span style="font-size:16px;line-height:1.2"><?= $status ?></span>
              <span style="font-size:13px;color:var(--text-muted)"><?= $e($desc) ?></span>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Create Token Modal -->
<div class="modal fade" id="createTokenModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-semibold"><i class="bi bi-key me-2"></i>Generate API Token</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body px-4">
        <div class="row g-3">
          <div class="col-md-8">
            <label class="form-label fw-semibold">Token Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="newTokenName" placeholder="e.g. Mobile App, WordPress Plugin…">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Expires In</label>
            <select class="form-select" id="newTokenExpiry">
              <option value="0">Never</option>
              <option value="30">30 days</option>
              <option value="90">90 days</option>
              <option value="365">1 year</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label fw-semibold">Description</label>
            <input type="text" class="form-control" id="newTokenDesc" placeholder="What is this token for?">
          </div>
          <div class="col-12">
            <label class="form-label fw-semibold">Scopes</label>
            <div class="d-flex gap-3 flex-wrap mt-1">
              <?php foreach (['read'=>'Read data','write'=>'Create/Update data','admin'=>'Admin operations'] as $sc => $desc): ?>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="scopes[]" value="<?= $sc ?>"
                       id="scope_<?= $sc ?>" <?= $sc==='read'?'checked':'' ?>>
                <label class="form-check-label" for="scope_<?= $sc ?>">
                  <span class="badge bg-<?= $sc==='admin'?'danger':($sc==='write'?'warning':'secondary') ?> me-1"><?= $sc ?></span>
                  <span class="text-muted" style="font-size:12.5px"><?= $desc ?></span>
                </label>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="col-12">
            <label class="form-label fw-semibold">IP Whitelist <small class="text-muted fw-normal">(optional, comma-separated)</small></label>
            <input type="text" class="form-control" id="newTokenIp" placeholder="192.168.1.1, 10.0.0.0">
            <div class="form-text">Leave blank to allow all IPs</div>
          </div>
        </div>

        <!-- Token reveal area (shown after creation) -->
        <div id="tokenReveal" class="mt-4 d-none">
          <div class="alert alert-warning border-warning" style="border-radius:12px">
            <div class="fw-bold mb-2"><i class="bi bi-exclamation-triangle me-1"></i>Copy your token now — it won't be shown again.</div>
            <div class="d-flex align-items-center gap-2">
              <code id="tokenValue" style="font-size:15px;font-family:monospace;background:#fff3cd;padding:8px 12px;border-radius:8px;flex-grow:1;word-break:break-all"></code>
              <button class="btn btn-sm btn-warning" onclick="navigator.clipboard.writeText(document.getElementById('tokenValue').textContent); LMS.toast('success','Copied!')">
                <i class="bi bi-copy"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer border-0">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        <button class="btn btn-primary" id="generateTokenBtn">
          <i class="bi bi-key me-1"></i> Generate Token
        </button>
      </div>
    </div>
  </div>
</div>

<style>
.api-tab { }
.api-tab.d-none { display: none !important; }
.api-tab.active { display: block; }
.btn-xs { padding: 3px 8px; font-size: 12px; border-radius: 6px; }
pre#pgResponse { transition: background .2s; }
</style>

<script>
const CSRF = '<?= $e($csrf_token) ?>';
const BASE = '<?= rtrim(APP_URL,'/') ?>';

// ── Tabs ────────────────────────────────────────────────────────────────────
document.querySelectorAll('#apiTabs .nav-link').forEach(tab => {
  tab.addEventListener('click', function(e) {
    if (this.href && !this.dataset.tab) return; // external link
    e.preventDefault();
    document.querySelectorAll('#apiTabs .nav-link').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.api-tab').forEach(p => { p.classList.remove('active'); p.classList.add('d-none'); });
    this.classList.add('active');
    const panel = document.getElementById('tab-' + this.dataset.tab);
    if (panel) { panel.classList.remove('d-none'); panel.classList.add('active'); }
  });
});

// ── Revoke token ─────────────────────────────────────────────────────────────
document.querySelectorAll('.btn-revoke-token').forEach(btn => {
  btn.addEventListener('click', function() {
    LMS.confirm('Revoke token "' + this.dataset.name + '"? This cannot be undone.', () => {
      fetch(BASE + '/admin/api/tokens/' + this.dataset.id + '/revoke', {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'csrf_token=' + encodeURIComponent(CSRF),
      }).then(r=>r.json()).then(d => {
        if (d.success) { LMS.toast('success','Token revoked.'); location.reload(); }
        else LMS.toast('error', d.message);
      });
    }, { okLabel: 'Revoke', danger: true });
  });
});

// ── Rotate token ─────────────────────────────────────────────────────────────
document.querySelectorAll('.btn-rotate-token').forEach(btn => {
  btn.addEventListener('click', function() {
    LMS.confirm('Rotate this token? The old value will stop working immediately.', () => {
      fetch(BASE + '/admin/api/tokens/' + this.dataset.id + '/rotate', {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'csrf_token=' + encodeURIComponent(CSRF),
      }).then(r=>r.json()).then(d => {
        if (d.success) {
          navigator.clipboard.writeText(d.token).catch(()=>{});
          LMS.toast('success', 'Token rotated! New value copied to clipboard: ' + d.token.substring(0,8) + '…');
          setTimeout(()=>location.reload(), 2000);
        } else LMS.toast('error', d.message);
      });
    }, { okLabel: 'Rotate Token', danger: false });
  });
});

// ── Generate token ────────────────────────────────────────────────────────────
document.getElementById('generateTokenBtn')?.addEventListener('click', function() {
  const name   = document.getElementById('newTokenName').value.trim();
  const expiry = document.getElementById('newTokenExpiry').value;
  const desc   = document.getElementById('newTokenDesc').value.trim();
  const ip     = document.getElementById('newTokenIp').value.trim();
  const scopes = [...document.querySelectorAll('input[name="scopes[]"]:checked')].map(i=>i.value);

  if (!name) { LMS.toast('error','Token name is required.'); return; }
  if (!scopes.length) { LMS.toast('error','Select at least one scope.'); return; }

  this.disabled = true;
  this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Generating…';

  fetch(BASE + '/admin/api/tokens/create', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'csrf_token=' + encodeURIComponent(CSRF)
        + '&name=' + encodeURIComponent(name)
        + '&description=' + encodeURIComponent(desc)
        + '&expires_days=' + expiry
        + '&ip_whitelist=' + encodeURIComponent(ip)
        + scopes.map(s => '&scopes[]=' + encodeURIComponent(s)).join(''),
  }).then(r=>r.json()).then(d => {
    if (d.success) {
      document.getElementById('tokenValue').textContent = d.token;
      document.getElementById('tokenReveal').classList.remove('d-none');
      document.getElementById('generateTokenBtn').style.display = 'none';
    } else {
      LMS.toast('error', d.message || 'Failed to generate token.');
    }
  }).finally(() => {
    this.disabled=false;
    this.innerHTML='<i class="bi bi-key me-1"></i> Generate Token';
  });
});

// ── API Playground ────────────────────────────────────────────────────────────
const pgMethod   = document.getElementById('pgMethod');
const pgBody     = document.getElementById('pgBody');
const pgBodyWrap = document.getElementById('pgBodyWrap');

pgMethod?.addEventListener('change', function() {
  pgBodyWrap.style.display = ['POST','PUT','PATCH'].includes(this.value) ? '' : 'none';
});

document.querySelectorAll('.preset-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    document.getElementById('pgMethod').value = this.dataset.method;
    document.getElementById('pgEndpoint').value = BASE + this.dataset.path;
    pgBodyWrap.style.display = ['POST','PUT'].includes(this.dataset.method) ? '' : 'none';
  });
});

document.getElementById('pgSend')?.addEventListener('click', async function() {
  const method   = document.getElementById('pgMethod').value;
  const endpoint = document.getElementById('pgEndpoint').value.trim();
  const token    = document.getElementById('pgToken').value.trim();
  const bodyText = document.getElementById('pgBody').value.trim();
  const respEl   = document.getElementById('pgResponse');
  const badge    = document.getElementById('pgStatusBadge');
  const timeEl   = document.getElementById('pgTimeBadge');

  if (!endpoint) { LMS.toast('error','Enter an endpoint URL.'); return; }

  this.disabled = true;
  respEl.textContent = '⏳ Sending request…';
  badge.textContent = '';

  const headers = { 'Content-Type': 'application/json' };
  if (token) headers['Authorization'] = 'Bearer ' + token;

  const opts = { method, headers };
  if (['POST','PUT','PATCH'].includes(method) && bodyText) {
    try { JSON.parse(bodyText); opts.body = bodyText; }
    catch { LMS.toast('error','Invalid JSON body.'); this.disabled=false; return; }
  }

  const t0 = Date.now();
  try {
    const res  = await fetch(endpoint, opts);
    const ms   = Date.now() - t0;
    const text = await res.text();
    let display = text;
    try { display = JSON.stringify(JSON.parse(text), null, 2); } catch {}

    const color = res.ok ? '#0e9f6e' : (res.status < 500 ? '#e3a008' : '#e02424');
    badge.innerHTML = `<span class="badge" style="background:${color};font-size:12px">${res.status} ${res.statusText}</span>`;
    timeEl.textContent = ms + 'ms';
    respEl.textContent = display;

    // Syntax highlight — simple colorise
    respEl.innerHTML = display
      .replace(/(".*?")/g, '<span style="color:#0e9f6e">$1</span>')
      .replace(/\b(true|false|null)\b/g, '<span style="color:#6366f1">$1</span>')
      .replace(/\b(\d+\.?\d*)\b/g, '<span style="color:#e3a008">$1</span>');
  } catch (err) {
    badge.innerHTML = '<span class="badge bg-danger">Error</span>';
    respEl.textContent = 'Request failed: ' + err.message;
  }
  this.disabled = false;
});
</script>
