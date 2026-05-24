<?php
use App\Core\View;
use App\Models\Setting;
$e    = fn(mixed $v): string => View::e($v);
$url  = fn(string $p = ''): string => View::url($p);
$asset= fn(string $p): string => View::asset($p);
$s    = fn(string $k, mixed $d = '') => $settings[$k] ?? $d;
$bool = fn(string $k): bool => (bool)(int)($settings[$k] ?? '0');

$tabIcons = [
    'general'      => 'bi-sliders',
    'security'     => 'bi-shield-lock',
    'email'        => 'bi-envelope',
    'certificates' => 'bi-award',
    'social_login' => 'bi-people',
    'webinar'      => 'bi-camera-video',
    'ai'           => 'bi-robot',
    'reviews'      => 'bi-star',
];
?>

<div class="row g-0">

  <!-- Vertical Tab Nav -->
  <div class="col-12 col-lg-3 mb-4 mb-lg-0">
    <div class="card lms-card settings-tab-nav">
      <div class="card-body p-2">
        <nav class="nav flex-column gap-1">
          <?php foreach ($tabs as $key => $label): ?>
          <a href="<?= $url('admin/settings?tab=' . $key) ?>"
             class="nav-link settings-tab-link <?= $activeTab === $key ? 'active' : '' ?>">
            <i class="bi <?= $tabIcons[$key] ?? 'bi-gear' ?> me-2"></i>
            <?= $e($label) ?>
          </a>
          <?php endforeach; ?>
        </nav>
      </div>
    </div>
  </div>

  <!-- Tab Content -->
  <div class="col-12 col-lg-9 ps-lg-4">
    <form action="<?= $url('admin/settings') ?>" method="POST"
          enctype="multipart/form-data" id="settingsForm" novalidate>
      <input type="hidden" name="csrf_token" value="<?= $e($csrf_token) ?>">
      <input type="hidden" name="tab"        value="<?= $e($activeTab) ?>">

      <div class="card lms-card">
        <div class="card-header lms-card-header d-flex align-items-center justify-content-between">
          <h5 class="mb-0">
            <i class="bi <?= $tabIcons[$activeTab] ?? 'bi-gear' ?> me-2"></i>
            <?= $e($tabs[$activeTab] ?? 'Settings') ?>
          </h5>
          <button type="submit" class="btn btn-primary btn-sm px-4" id="saveBtn">
            <i class="bi bi-check-circle me-1"></i> Save Changes
          </button>
        </div>
        <div class="card-body p-4">

          <!-- ═══════════════════════════════════════════════════
               TAB: GENERAL
          ════════════════════════════════════════════════════ -->
          <?php if ($activeTab === 'general'): ?>

          <div class="row g-4">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Site Name</label>
              <input type="text" class="form-control" name="site_name"
                     value="<?= $e($s('site_name', 'LMSAdvisor')) ?>" maxlength="100">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Site Tagline</label>
              <input type="text" class="form-control" name="site_tagline"
                     value="<?= $e($s('site_tagline')) ?>" maxlength="191">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Admin Email</label>
              <input type="email" class="form-control" name="admin_email"
                     value="<?= $e($s('admin_email')) ?>" maxlength="191">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Primary Color</label>
              <div class="input-group">
                <input type="color" class="form-control form-control-color" name="theme_color"
                       value="<?= $e($s('theme_color', '#1a56db')) ?>" id="themeColorPicker"
                       style="width:56px;padding:3px">
                <input type="text" class="form-control" name="theme_color_text"
                       value="<?= $e($s('theme_color', '#1a56db')) ?>" id="themeColorText"
                       placeholder="#1a56db" maxlength="9" readonly>
                <button type="button" class="btn btn-outline-secondary" id="resetColor">Reset</button>
              </div>
              <div id="colorPreview" class="mt-2 p-2 rounded d-flex align-items-center gap-2"
                   style="background:<?= $e($s('theme_color','#1a56db')) ?>;color:#fff;font-size:12.5px">
                <i class="fas fa-graduation-cap"></i> LMSAdvisor — preview
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Timezone</label>
              <select class="form-select" name="timezone">
                <?php
                $tzSelected = $s('timezone', 'UTC');
                $commonTz = ['UTC','Asia/Kolkata','America/New_York','America/Chicago','America/Los_Angeles','Europe/London','Europe/Paris','Asia/Tokyo','Asia/Dubai','Australia/Sydney'];
                foreach ($commonTz as $tz):
                ?>
                <option value="<?= $e($tz) ?>" <?= $tzSelected === $tz ? 'selected' : '' ?>><?= $e($tz) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Date Format</label>
              <select class="form-select" name="date_format">
                <?php
                $dfmt = $s('date_format', 'D M Y');
                $formats = ['D M Y' => '01 Jan 2025', 'Y-m-d' => '2025-01-01', 'd/m/Y' => '01/01/2025', 'm/d/Y' => '01/01/2025'];
                foreach ($formats as $val => $example):
                ?>
                <option value="<?= $e($val) ?>" <?= $dfmt === $val ? 'selected' : '' ?>>
                  <?= $e($example) ?> (<?= $e($val) ?>)
                </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Logo upload -->
            <div class="col-md-6">
              <label class="form-label fw-semibold">Site Logo</label>
              <?php $logo = Setting::get('site_logo',''); ?>
              <?php if ($logo): ?>
                <div class="mb-2">
                  <img src="<?= $e(APP_URL . $logo) ?>" alt="Logo" style="max-height:48px;border-radius:6px;border:1px solid var(--border-color);padding:4px">
                </div>
              <?php endif; ?>
              <input type="file" class="form-control" name="site_logo" accept="image/*">
              <div class="form-text">PNG/SVG recommended. Max 2MB.</div>
            </div>

            <!-- Favicon upload -->
            <div class="col-md-6">
              <label class="form-label fw-semibold">Favicon</label>
              <?php $fav = Setting::get('site_favicon',''); ?>
              <?php if ($fav): ?>
                <div class="mb-2">
                  <img src="<?= $e(APP_URL . $fav) ?>" alt="Favicon" style="max-height:32px;border-radius:4px;border:1px solid var(--border-color);padding:2px">
                </div>
              <?php endif; ?>
              <input type="file" class="form-control" name="site_favicon" accept="image/*,.ico">
              <div class="form-text">ICO or 32×32 PNG. Max 2MB.</div>
            </div>
          </div>

          <!-- ═══════════════════════════════════════════════════
               TAB: SECURITY
          ════════════════════════════════════════════════════ -->
          <?php elseif ($activeTab === 'security'): ?>

          <div class="row g-4">
            <div class="col-12">
              <div class="settings-section-label">Login Lockout</div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Max Login Attempts</label>
              <input type="number" class="form-control" name="login_max_attempts"
                     value="<?= $e($s('login_max_attempts', '5')) ?>" min="1" max="20">
              <div class="form-text">Lock account after this many failed attempts.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Lockout Duration (minutes)</label>
              <input type="number" class="form-control" name="login_lockout_min"
                     value="<?= $e($s('login_lockout_min', '15')) ?>" min="1" max="1440">
            </div>

            <div class="col-12">
              <hr class="my-2">
              <div class="settings-section-label">Google reCAPTCHA v2</div>
            </div>
            <div class="col-12">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="recaptcha_enabled"
                       id="recaptchaEnabled" <?= $bool('recaptcha_enabled') ? 'checked' : '' ?>>
                <label class="form-check-label fw-semibold" for="recaptchaEnabled">
                  Enable reCAPTCHA on Login
                </label>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Site Key</label>
              <input type="text" class="form-control" name="recaptcha_site_key"
                     value="<?= $e($s('recaptcha_site_key')) ?>" placeholder="6Lc…">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Secret Key</label>
              <input type="password" class="form-control" name="recaptcha_secret"
                     placeholder="Leave blank to keep current" autocomplete="new-password">
              <div class="form-text">Leave blank to keep the saved secret.</div>
            </div>
          </div>

          <!-- ═══════════════════════════════════════════════════
               TAB: EMAIL
          ════════════════════════════════════════════════════ -->
          <?php elseif ($activeTab === 'email'): ?>

          <div class="row g-4">
            <div class="col-md-8">
              <label class="form-label fw-semibold">SMTP Host</label>
              <input type="text" class="form-control" name="smtp_host"
                     value="<?= $e($s('smtp_host')) ?>" placeholder="smtp.gmail.com">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">SMTP Port</label>
              <input type="number" class="form-control" name="smtp_port"
                     value="<?= $e($s('smtp_port', '587')) ?>" placeholder="587">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">SMTP Username</label>
              <input type="text" class="form-control" name="smtp_user"
                     value="<?= $e($s('smtp_user')) ?>" autocomplete="off">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">SMTP Password</label>
              <input type="password" class="form-control" name="smtp_pass"
                     placeholder="Leave blank to keep current" autocomplete="new-password">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Encryption</label>
              <select class="form-select" name="smtp_encryption">
                <?php foreach (['tls'=>'TLS (587)','ssl'=>'SSL (465)','none'=>'None'] as $val => $lbl): ?>
                <option value="<?= $val ?>" <?= $s('smtp_encryption','tls') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">From Address</label>
              <input type="email" class="form-control" name="smtp_from"
                     value="<?= $e($s('smtp_from')) ?>" placeholder="noreply@yourdomain.com">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">From Name</label>
              <input type="text" class="form-control" name="smtp_from_name"
                     value="<?= $e($s('smtp_from_name', 'LMSAdvisor')) ?>">
            </div>

            <!-- Test email -->
            <div class="col-12">
              <hr class="my-2">
              <div class="settings-section-label">Send Test Email</div>
              <div class="row g-2 align-items-end mt-1">
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Recipient Address</label>
                  <input type="email" class="form-control" id="testEmailTo"
                         placeholder="test@example.com">
                </div>
                <div class="col-md-3">
                  <button type="button" class="btn btn-outline-primary w-100" id="sendTestEmail">
                    <i class="bi bi-send me-1"></i> Send Test
                  </button>
                </div>
                <div class="col-md-3">
                  <div id="testEmailResult" class="small"></div>
                </div>
              </div>
            </div>
          </div>

          <!-- ═══════════════════════════════════════════════════
               TAB: CERTIFICATES
          ════════════════════════════════════════════════════ -->
          <?php elseif ($activeTab === 'certificates'): ?>

          <div class="row g-4">
            <div class="col-12">
              <div class="alert alert-info d-flex gap-2">
                <i class="bi bi-info-circle-fill mt-1"></i>
                <div>Certificate PDF generation uses mPDF (Phase 12). These settings control the signatory block and branding on all generated certificates.</div>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Signatory Name</label>
              <input type="text" class="form-control" name="cert_signer_name"
                     value="<?= $e($s('cert_signer_name')) ?>" placeholder="Dr. Jane Smith">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Signatory Title</label>
              <input type="text" class="form-control" name="cert_signer_title"
                     value="<?= $e($s('cert_signer_title')) ?>" placeholder="Chief Learning Officer">
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Footer Text</label>
              <textarea class="form-control" name="cert_footer_text" rows="3"
                        placeholder="This certificate is awarded in recognition of successful completion…"><?= $e($s('cert_footer_text')) ?></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Certificate Accent Color</label>
              <div class="input-group">
                <input type="color" class="form-control form-control-color" name="cert_template_color"
                       value="<?= $e($s('cert_template_color', '#1a56db')) ?>"
                       style="width:56px;padding:3px">
                <input type="text" class="form-control"
                       value="<?= $e($s('cert_template_color', '#1a56db')) ?>" readonly>
              </div>
            </div>
          </div>

          <!-- ═══════════════════════════════════════════════════
               TAB: SOCIAL LOGIN
          ════════════════════════════════════════════════════ -->
          <?php elseif ($activeTab === 'social_login'): ?>

          <div class="row g-4">
            <!-- Google -->
            <div class="col-12">
              <div class="settings-section-label">
                <i class="fab fa-google me-1" style="color:#ea4335"></i> Google OAuth
              </div>
            </div>
            <div class="col-12">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="social_google_enabled"
                       id="googleEnabled" <?= $bool('social_google_enabled') ? 'checked' : '' ?>>
                <label class="form-check-label fw-semibold" for="googleEnabled">Enable Google Login</label>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Google Client ID</label>
              <input type="text" class="form-control" name="social_google_id"
                     value="<?= $e($s('social_google_id')) ?>" placeholder="xxxx.apps.googleusercontent.com">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Google Client Secret</label>
              <input type="password" class="form-control" name="social_google_secret"
                     placeholder="Leave blank to keep current" autocomplete="new-password">
            </div>

            <div class="col-12"><hr class="my-1"></div>

            <!-- GitHub -->
            <div class="col-12">
              <div class="settings-section-label">
                <i class="fab fa-github me-1"></i> GitHub OAuth
              </div>
            </div>
            <div class="col-12">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="social_github_enabled"
                       id="githubEnabled" <?= $bool('social_github_enabled') ? 'checked' : '' ?>>
                <label class="form-check-label fw-semibold" for="githubEnabled">Enable GitHub Login</label>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">GitHub Client ID</label>
              <input type="text" class="form-control" name="social_github_id"
                     value="<?= $e($s('social_github_id')) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">GitHub Client Secret</label>
              <input type="password" class="form-control" name="social_github_secret"
                     placeholder="Leave blank to keep current" autocomplete="new-password">
            </div>
          </div>

          <!-- ═══════════════════════════════════════════════════
               TAB: WEBINAR
          ════════════════════════════════════════════════════ -->
          <?php elseif ($activeTab === 'webinar'): ?>

          <div class="row g-4">
            <!-- Zoom -->
            <div class="col-12">
              <div class="settings-section-label">
                <i class="fas fa-video me-1" style="color:#2d8cff"></i> Zoom Integration
              </div>
            </div>
            <div class="col-12">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="zoom_enabled"
                       id="zoomEnabled" <?= $bool('zoom_enabled') ? 'checked' : '' ?>>
                <label class="form-check-label fw-semibold" for="zoomEnabled">Enable Zoom</label>
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Zoom Account ID</label>
              <input type="text" class="form-control" name="zoom_account_id"
                     value="<?= $e($s('zoom_account_id')) ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Zoom API Key (Client ID)</label>
              <input type="text" class="form-control" name="zoom_api_key"
                     value="<?= $e($s('zoom_api_key')) ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Zoom API Secret</label>
              <input type="password" class="form-control" name="zoom_api_secret"
                     placeholder="Leave blank to keep current" autocomplete="new-password">
            </div>

            <div class="col-12"><hr class="my-1"></div>

            <!-- Google Meet -->
            <div class="col-12">
              <div class="settings-section-label">
                <i class="fab fa-google me-1" style="color:#0f9d58"></i> Google Meet Integration
              </div>
            </div>
            <div class="col-12">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="gmeet_enabled"
                       id="gmeetEnabled" <?= $bool('gmeet_enabled') ? 'checked' : '' ?>>
                <label class="form-check-label fw-semibold" for="gmeetEnabled">Enable Google Meet</label>
              </div>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Google Service Account OAuth JSON</label>
              <textarea class="form-control font-monospace" name="gmeet_oauth_json" rows="6"
                        placeholder='{"type":"service_account","project_id":"…"}'
                        style="font-size:12px"><?= $e($s('gmeet_oauth_json')) ?></textarea>
              <div class="form-text">Paste the full contents of your Google service account JSON key file.</div>
            </div>
          </div>

          <!-- ═══════════════════════════════════════════════════
               TAB: AI INTEGRATION
          ════════════════════════════════════════════════════ -->
          <?php elseif ($activeTab === 'ai'): ?>

          <div class="row g-4">
            <div class="col-12">
              <div class="alert alert-info d-flex gap-2">
                <i class="bi bi-robot mt-1"></i>
                <div>AI features are used for course generation (Phase 5). Configure your preferred provider and API key below.</div>
              </div>
            </div>
            <div class="col-12">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="ai_enabled"
                       id="aiEnabled" <?= $bool('ai_enabled') ? 'checked' : '' ?>>
                <label class="form-check-label fw-semibold" for="aiEnabled">Enable AI Features</label>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">AI Provider</label>
              <select class="form-select" name="ai_provider">
                <option value="openai"    <?= $s('ai_provider','openai') === 'openai'    ? 'selected' : '' ?>>OpenAI (GPT)</option>
                <option value="anthropic" <?= $s('ai_provider','openai') === 'anthropic' ? 'selected' : '' ?>>Anthropic (Claude)</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Model Name</label>
              <input type="text" class="form-control" name="ai_model"
                     value="<?= $e($s('ai_model', 'gpt-4o')) ?>"
                     placeholder="gpt-4o or claude-sonnet-4-6">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">OpenAI API Key</label>
              <input type="password" class="form-control" name="ai_openai_key"
                     placeholder="sk-… (leave blank to keep)" autocomplete="new-password">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Anthropic API Key</label>
              <input type="password" class="form-control" name="ai_anthropic_key"
                     placeholder="sk-ant-… (leave blank to keep)" autocomplete="new-password">
            </div>
          </div>

          <!-- ═══════════════════════════════════════════════════
               TAB: REVIEWS & LEADERBOARD
          ════════════════════════════════════════════════════ -->
          <?php elseif ($activeTab === 'reviews'): ?>

          <div class="row g-4">
            <div class="col-12">
              <div class="settings-section-label"><i class="bi bi-star me-1"></i> Course Reviews</div>
            </div>
            <div class="col-12">
              <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="reviews_enabled"
                       id="reviewsEnabled" <?= $bool('reviews_enabled') ? 'checked' : '' ?>>
                <label class="form-check-label fw-semibold" for="reviewsEnabled">
                  Enable Course Reviews
                </label>
                <div class="form-text">Allow students to leave star ratings and reviews on completed courses.</div>
              </div>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="reviews_auto_approve"
                       id="reviewsAutoApprove" <?= $bool('reviews_auto_approve') ? 'checked' : '' ?>>
                <label class="form-check-label fw-semibold" for="reviewsAutoApprove">
                  Auto-Approve Reviews
                </label>
                <div class="form-text">When off, reviews require manual admin approval before appearing.</div>
              </div>
            </div>

            <div class="col-12"><hr class="my-1"></div>

            <div class="col-12">
              <div class="settings-section-label"><i class="bi bi-trophy me-1"></i> Leaderboard</div>
            </div>
            <div class="col-12">
              <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="leaderboard_enabled"
                       id="leaderboardEnabled" <?= $bool('leaderboard_enabled') ? 'checked' : '' ?>>
                <label class="form-check-label fw-semibold" for="leaderboardEnabled">
                  Enable Leaderboard
                </label>
                <div class="form-text">Track and display grade points earned by students.</div>
              </div>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="leaderboard_public"
                       id="leaderboardPublic" <?= $bool('leaderboard_public') ? 'checked' : '' ?>>
                <label class="form-check-label fw-semibold" for="leaderboardPublic">
                  Public Leaderboard
                </label>
                <div class="form-text">When enabled, all enrolled students can see each other's rankings.</div>
              </div>
            </div>
          </div>

          <?php endif; ?>

        </div><!-- /.card-body -->

        <!-- Sticky save footer -->
        <div class="card-footer d-flex justify-content-end gap-2 py-3 px-4">
          <a href="<?= $url('admin/settings?tab=' . $activeTab) ?>"
             class="btn btn-outline-secondary">Discard</a>
          <button type="submit" class="btn btn-primary px-4" id="saveBtnFooter">
            <i class="bi bi-check-circle me-1"></i> Save Changes
          </button>
        </div>
      </div><!-- /.card -->
    </form>
  </div><!-- /.col -->
</div><!-- /.row -->

<style>
/* Settings layout */
.settings-tab-nav .nav-link {
  color: var(--text-muted);
  font-size: 13.5px;
  font-weight: 500;
  padding: 9px 14px;
  border-radius: var(--radius-sm);
  border-left: 3px solid transparent;
  transition: background .15s, color .15s, border-color .15s;
}
.settings-tab-nav .nav-link:hover {
  background: var(--content-bg);
  color: var(--text-primary);
}
.settings-tab-nav .nav-link.active {
  background: var(--primary-light);
  color: var(--primary);
  border-left-color: var(--primary);
  font-weight: 600;
}
.settings-section-label {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: .7px;
  text-transform: uppercase;
  color: var(--text-muted);
  margin-bottom: 4px;
}
</style>

<script>
const CSRF = '<?= $e($csrf_token) ?>';
const BASE  = '<?= rtrim(APP_URL, '/') ?>';

// ── Save loading state ────────────────────────────────────────────────────────
document.getElementById('settingsForm').addEventListener('submit', function () {
  ['saveBtn','saveBtnFooter'].forEach(id => {
    const b = document.getElementById(id);
    if (b) { b.disabled = true; b.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving…'; }
  });
});

// ── Color picker sync ─────────────────────────────────────────────────────────
const picker  = document.getElementById('themeColorPicker');
const textEl  = document.getElementById('themeColorText');
const preview = document.getElementById('colorPreview');
const hiddenColor = document.querySelector('[name="theme_color"]');

if (picker) {
  // Keep hidden field in sync
  const syncColor = (val) => {
    if (textEl)  textEl.value = val;
    if (preview) preview.style.background = val;
    // sync the actual submitted field
    picker.value = val;
  };

  picker.addEventListener('input', () => syncColor(picker.value));

  document.getElementById('resetColor')?.addEventListener('click', () => syncColor('#1a56db'));
}

// ── Test Email ────────────────────────────────────────────────────────────────
document.getElementById('sendTestEmail')?.addEventListener('click', function () {
  const to  = document.getElementById('testEmailTo').value.trim();
  const res = document.getElementById('testEmailResult');
  if (!to) { res.innerHTML = '<span class="text-danger">Enter a recipient email.</span>'; return; }

  this.disabled = true;
  this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
  res.innerHTML = '';

  fetch(BASE + '/admin/settings/test-email', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'csrf_token=' + encodeURIComponent(CSRF) + '&test_email_to=' + encodeURIComponent(to),
  })
  .then(r => r.json())
  .then(data => {
    res.innerHTML = data.success
      ? '<span class="text-success"><i class="bi bi-check-circle me-1"></i>' + data.message + '</span>'
      : '<span class="text-danger"><i class="bi bi-x-circle me-1"></i>' + data.message + '</span>';
  })
  .finally(() => {
    this.disabled = false;
    this.innerHTML = '<i class="bi bi-send me-1"></i> Send Test';
  });
});
</script>
