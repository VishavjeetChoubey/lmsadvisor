<?php
/**
 * LMSAdvisor Installer
 * WordPress-style setup wizard — runs all SQL migrations, creates admin user.
 * DELETE this folder after installation is complete.
 */
declare(strict_types=1);

define('INSTALLER_VERSION', '2.0');
define('BASE_PATH', dirname(__DIR__));

// ── Block if already installed ───────────────────────────────────────────────
$lockFile = BASE_PATH . '/storage/installed.lock';
$step     = $_GET['step'] ?? 'welcome';

if (file_exists($lockFile) && $step !== 'done' && ($_GET['force'] ?? '') !== '1') {
    die(renderPage('Already Installed', '<div class="card">
        <div class="card-icon" style="background:#ecfdf5;color:#059669">✓</div>
        <h2>LMSAdvisor is already installed!</h2>
        <p>The installer has been completed. For security, please delete the <code>/install/</code> folder from your server.</p>
        <p style="margin-top:20px"><a href="../" class="btn">Go to LMSAdvisor →</a></p>
    </div>'));
}

// ── Routing ───────────────────────────────────────────────────────────────────
$steps = ['welcome', 'requirements', 'database', 'configure', 'install', 'done'];
$stepNum = array_search($step, $steps) ?: 0;

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 'database') handleDatabaseStep();
    if ($step === 'configure') handleConfigureStep();
    if ($step === 'install')   handleInstallStep();
}

// ── Step handlers ─────────────────────────────────────────────────────────────

function handleDatabaseStep(): void
{
    $host   = trim($_POST['db_host']   ?? 'localhost');
    $port   = (int)($_POST['db_port']  ?? 3306);
    $name   = trim($_POST['db_name']   ?? '');
    $user   = trim($_POST['db_user']   ?? '');
    $pass   = $_POST['db_pass']        ?? '';
    $prefix = trim($_POST['db_prefix'] ?? '');

    try {
        $pdo = new PDO(
            "mysql:host={$host};port={$port};charset=utf8mb4",
            $user, $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        // Create DB if not exists
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        // Save to session
        session_start();
        $_SESSION['install_db'] = compact('host','port','name','user','pass','prefix');
        header('Location: ?step=configure');
        exit;
    } catch (\PDOException $e) {
        setError('Database connection failed: ' . $e->getMessage());
    }
}

function handleConfigureStep(): void
{
    session_start();
    $site_name  = trim($_POST['site_name']  ?? 'LMSAdvisor');
    $site_url   = rtrim(trim($_POST['site_url'] ?? ''), '/');
    $admin_name = trim($_POST['admin_name'] ?? '');
    $admin_email= trim($_POST['admin_email']?? '');
    $admin_pass = $_POST['admin_pass']      ?? '';
    $admin_pass2= $_POST['admin_pass2']     ?? '';

    $errors = [];
    if (!$site_name)                      $errors[] = 'Site name is required.';
    if (!filter_var($site_url, FILTER_VALIDATE_URL)) $errors[] = 'Site URL must be a valid URL (e.g. http://localhost/lmsadvisor).';
    if (!$admin_name)                     $errors[] = 'Admin name is required.';
    if (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid admin email required.';
    if (strlen($admin_pass) < 8)          $errors[] = 'Password must be at least 8 characters.';
    if ($admin_pass !== $admin_pass2)     $errors[] = 'Passwords do not match.';

    if ($errors) { setErrors($errors); return; }

    $_SESSION['install_site'] = compact('site_name','site_url','admin_name','admin_email','admin_pass');
    header('Location: ?step=install');
    exit;
}

function handleInstallStep(): void
{
    session_start();
    $db   = $_SESSION['install_db']   ?? null;
    $site = $_SESSION['install_site'] ?? null;

    if (!$db || !$site) {
        setError('Session expired. Please restart the installer.');
        header('Location: ?step=database');
        exit;
    }

    try {
        $pdo = new PDO(
            "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset=utf8mb4",
            $db['user'], $db['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );

        // ── 1. Run all migrations ─────────────────────────────────────────
        $migDir = BASE_PATH . '/database/migrations';
        $files  = glob($migDir . '/*.sql');
        sort($files);

        $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
        foreach ($files as $file) {
            $sql = file_get_contents($file);
            // Split on semicolons but skip empty statements
            $statements = array_filter(
                array_map('trim', explode(';', $sql)),
                fn($s) => $s !== '' && !preg_match('/^--/', $s)
            );
            foreach ($statements as $stmt) {
                try { $pdo->exec($stmt); } catch (\PDOException $e) {
                    // Ignore duplicate column / already exists errors
                    if (!in_array($e->getCode(), ['42S21','42701','42S01'])) {
                        // Log but continue
                        error_log('[Installer] ' . $e->getMessage() . ' in ' . basename($file));
                    }
                }
            }
        }
        $pdo->exec("SET FOREIGN_KEY_CHECKS=1");

        // ── 2. Create admin user ──────────────────────────────────────────
        $roleStmt = $pdo->query("SELECT id FROM roles WHERE name='super_admin' LIMIT 1");
        $role     = $roleStmt->fetch();
        $roleId   = $role ? $role['id'] : 1;

        $nameParts = explode(' ', $site['admin_name'], 2);
        $firstName = $nameParts[0];
        $lastName  = $nameParts[1] ?? '';
        $passHash  = password_hash($site['admin_pass'], PASSWORD_BCRYPT, ['cost'=>12]);
        $uuid      = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),
            mt_rand(0,0x0fff)|0x4000,mt_rand(0,0x3fff)|0x8000,
            mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff)
        );

        // Check if admin already exists
        $existing = $pdo->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
        $existing->execute([$site['admin_email']]);
        if (!$existing->fetch()) {
            $pdo->prepare(
                'INSERT INTO users (uuid, first_name, last_name, email, password_hash, role_id, is_active)
                 VALUES (?,?,?,?,?,?,1)'
            )->execute([$uuid, $firstName, $lastName, $site['admin_email'], $passHash, $roleId]);
        } else {
            // Update password for existing
            $pdo->prepare('UPDATE users SET password_hash=? WHERE email=?')
                ->execute([$passHash, $site['admin_email']]);
        }

        // ── 3. Write .env file ────────────────────────────────────────────
        $env = "APP_ENV=production\n"
             . "APP_DEBUG=false\n"
             . "APP_URL={$site['site_url']}\n"
             . "\n"
             . "DB_HOST={$db['host']}\n"
             . "DB_PORT={$db['port']}\n"
             . "DB_NAME={$db['name']}\n"
             . "DB_USER={$db['user']}\n"
             . "DB_PASS={$db['pass']}\n"
             . "DB_CHARSET=utf8mb4\n"
             . "\n"
             . "APP_KEY=" . bin2hex(random_bytes(32)) . "\n";

        file_put_contents(BASE_PATH . '/.env', $env);

        // ── 4. Update site_name + site_url in settings ────────────────────
        $pdo->prepare("UPDATE settings SET value=? WHERE `key`='site_name'")->execute([$site['site_name']]);
        $pdo->prepare("UPDATE settings SET value=? WHERE `key`='site_url'")->execute([$site['site_url']]);

        // ── 5. Write install lock file ────────────────────────────────────
        file_put_contents(
            BASE_PATH . '/storage/installed.lock',
            json_encode([
                'installed_at' => date('c'),
                'version'      => INSTALLER_VERSION,
                'site_url'     => $site['site_url'],
                'admin_email'  => $site['admin_email'],
            ])
        );

        // ── 6. Clear session ──────────────────────────────────────────────
        session_destroy();

        header('Location: ?step=done&url=' . urlencode($site['site_url']));
        exit;

    } catch (\Throwable $e) {
        setError('Installation failed: ' . $e->getMessage());
    }
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function setError(string $msg): void  { $_SESSION['install_errors'] = [$msg]; }
function setErrors(array $msgs): void { $_SESSION['install_errors'] = $msgs; }

function getErrors(): array
{
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $e = $_SESSION['install_errors'] ?? [];
    unset($_SESSION['install_errors']);
    return $e;
}

// ── Check requirements ────────────────────────────────────────────────────────
function checkRequirements(): array
{
    $checks = [];
    $checks[] = ['PHP Version ≥ 8.1', PHP_VERSION_ID >= 80100, PHP_VERSION];
    $checks[] = ['PDO Extension', extension_loaded('pdo'), extension_loaded('pdo') ? 'Loaded' : 'MISSING'];
    $checks[] = ['PDO MySQL Driver', extension_loaded('pdo_mysql'), extension_loaded('pdo_mysql') ? 'Loaded' : 'MISSING'];
    $checks[] = ['cURL Extension', extension_loaded('curl'), extension_loaded('curl') ? 'Loaded' : 'MISSING'];
    $checks[] = ['JSON Extension', extension_loaded('json'), extension_loaded('json') ? 'Loaded' : 'MISSING'];
    $checks[] = ['Mbstring Extension', extension_loaded('mbstring'), extension_loaded('mbstring') ? 'Loaded' : 'MISSING'];
    $checks[] = ['OpenSSL Extension', extension_loaded('openssl'), extension_loaded('openssl') ? 'Loaded' : 'MISSING'];
    $checks[] = ['FileInfo Extension', extension_loaded('fileinfo'), extension_loaded('fileinfo') ? 'Loaded' : 'MISSING'];
    $checks[] = ['storage/ Writable', is_writable(BASE_PATH.'/storage'), is_writable(BASE_PATH.'/storage') ? 'Writable' : 'NOT WRITABLE'];
    $checks[] = ['storage/uploads/ Writable', is_writable(BASE_PATH.'/storage/uploads'), is_writable(BASE_PATH.'/storage/uploads') ? 'Writable' : 'NOT WRITABLE'];
    $checks[] = ['.env Writable', is_writable(BASE_PATH) || is_writable(BASE_PATH.'/.env') || !file_exists(BASE_PATH.'/.env'), is_writable(BASE_PATH) ? 'Writable' : 'Check permissions'];
    $checks[] = ['Migrations Found', count(glob(BASE_PATH.'/database/migrations/*.sql')) >= 10, count(glob(BASE_PATH.'/database/migrations/*.sql')).' files'];
    return $checks;
}

function allRequirementsMet(): bool
{
    foreach (checkRequirements() as $c) { if (!$c[1]) return false; }
    return true;
}

// ── Detect site URL automatically ─────────────────────────────────────────────
function guessUrl(): string
{
    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host  = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script= dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/install/index.php'));
    return rtrim($proto . '://' . $host . $script, '/');
}

// ── HTML Renderer ─────────────────────────────────────────────────────────────
function renderPage(string $title, string $content): string
{
    $stepNum = array_search($_GET['step'] ?? 'welcome', ['welcome','requirements','database','configure','install','done']) ?: 0;
    $stepLabels = ['Welcome','Requirements','Database','Configure','Install','Complete'];
    $stepIcons  = ['🏠','⚙️','🗄️','🔧','🚀','✅'];

    $stepsHtml = '';
    foreach ($stepLabels as $i => $lbl) {
        $cls = $i < $stepNum ? 'done' : ($i === $stepNum ? 'active' : '');
        $stepsHtml .= "<div class=\"step {$cls}\">{$stepIcons[$i]} {$lbl}</div>";
    }

    return '<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>' . htmlspecialchars($title) . ' — LMSAdvisor Installer</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root { --primary:#5b5ef6; --success:#059669; --danger:#dc2626; --warn:#d97706; --bg:#f5f6fa; --card:#fff; --border:#e2e8f0; --text:#1e293b; --muted:#64748b; }
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background:var(--bg); color:var(--text); min-height:100vh; display:flex; flex-direction:column; align-items:center; padding:40px 16px; }
    .logo { display:flex; align-items:center; gap:12px; margin-bottom:28px; }
    .logo-icon { width:52px; height:52px; border-radius:14px; background:linear-gradient(135deg,#5b5ef6,#3b82f6); display:flex; align-items:center; justify-content:center; font-size:24px; box-shadow:0 6px 20px rgba(91,94,246,.35); }
    .logo h1 { font-size:24px; font-weight:800; color:var(--text); }
    .logo span { display:block; font-size:12px; color:var(--muted); }

    /* Steps bar */
    .steps { display:flex; gap:0; margin-bottom:28px; background:var(--card); border:1px solid var(--border); border-radius:12px; overflow:hidden; width:100%; max-width:680px; }
    .step { flex:1; padding:10px 6px; font-size:11.5px; font-weight:600; text-align:center; color:var(--muted); border-right:1px solid var(--border); transition:background .2s; }
    .step:last-child { border-right:none; }
    .step.active { background:var(--primary); color:#fff; }
    .step.done   { background:#ecfdf5; color:var(--success); }

    /* Card */
    .card { background:var(--card); border:1px solid var(--border); border-radius:16px; padding:36px 40px; width:100%; max-width:680px; box-shadow:0 4px 24px rgba(0,0,0,.06); }
    .card-icon { width:60px; height:60px; border-radius:16px; display:flex; align-items:center; justify-content:center; font-size:26px; margin:0 auto 20px; }
    h2 { font-size:22px; font-weight:800; margin-bottom:10px; text-align:center; }
    p, .desc { font-size:14.5px; color:var(--muted); line-height:1.7; margin-bottom:16px; text-align:center; }

    /* Form */
    .form-group { margin-bottom:18px; }
    .form-group label { display:block; font-size:13.5px; font-weight:600; color:var(--text); margin-bottom:5px; }
    .form-group small { font-size:12px; color:var(--muted); display:block; margin-top:4px; }
    input[type=text], input[type=url], input[type=email], input[type=password], input[type=number] {
      width:100%; padding:10px 13px; border:1px solid var(--border); border-radius:9px;
      font-size:14px; color:var(--text); outline:none; transition:border-color .15s;
      background:#fafbfc;
    }
    input:focus { border-color:var(--primary); background:#fff; box-shadow:0 0 0 3px rgba(91,94,246,.1); }
    .row2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
    @media(max-width:520px) { .row2 { grid-template-columns:1fr; } }

    /* Buttons */
    .btn { display:inline-flex; align-items:center; gap:8px; padding:11px 24px; border-radius:9px; font-size:14px; font-weight:700; border:none; cursor:pointer; text-decoration:none; transition:all .15s; }
    .btn-primary  { background:linear-gradient(135deg,#5b5ef6,#3b82f6); color:#fff; box-shadow:0 4px 14px rgba(91,94,246,.35); }
    .btn-primary:hover  { transform:translateY(-1px); box-shadow:0 6px 20px rgba(91,94,246,.4); }
    .btn-outline  { background:#fff; color:var(--text); border:1.5px solid var(--border); }
    .btn-outline:hover  { border-color:var(--primary); color:var(--primary); }
    .btn-success  { background:linear-gradient(135deg,#059669,#10b981); color:#fff; box-shadow:0 4px 14px rgba(5,150,105,.3); }
    .btn-actions  { display:flex; justify-content:space-between; gap:12px; margin-top:24px; }

    /* Alerts */
    .alert { padding:12px 16px; border-radius:9px; margin-bottom:18px; font-size:13.5px; line-height:1.5; }
    .alert-error   { background:#fef2f2; color:var(--danger); border:1px solid #fecaca; }
    .alert-success { background:#ecfdf5; color:var(--success); border:1px solid #a7f3d0; }
    .alert-warn    { background:#fffbeb; color:var(--warn); border:1px solid #fde68a; }
    .alert ul { margin:6px 0 0 16px; }

    /* Requirements table */
    .req-table { width:100%; border-collapse:collapse; margin-top:16px; }
    .req-table th { text-align:left; font-size:12px; text-transform:uppercase; letter-spacing:.05em; color:var(--muted); padding:8px 12px; }
    .req-table td { padding:10px 12px; border-top:1px solid var(--border); font-size:13.5px; }
    .req-table tr:hover td { background:#f8fafc; }
    .badge-ok   { background:#ecfdf5; color:var(--success); padding:2px 8px; border-radius:20px; font-size:12px; font-weight:600; }
    .badge-fail { background:#fef2f2; color:var(--danger);  padding:2px 8px; border-radius:20px; font-size:12px; font-weight:600; }
    .badge-warn { background:#fffbeb; color:var(--warn);   padding:2px 8px; border-radius:20px; font-size:12px; font-weight:600; }

    /* Progress */
    .install-log { background:#0f172a; border-radius:10px; padding:16px; margin:16px 0; font-family:monospace; font-size:13px; color:#94a3b8; max-height:220px; overflow-y:auto; }
    .install-log .ok   { color:#4ade80; }
    .install-log .fail { color:#f87171; }
    .install-log .info { color:#60a5fa; }
    .progress-bar { height:8px; background:#e2e8f0; border-radius:4px; overflow:hidden; margin:12px 0; }
    .progress-fill { height:100%; background:linear-gradient(90deg,#5b5ef6,#3b82f6); border-radius:4px; transition:width .4s; }

    /* Done */
    .done-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin:20px 0; }
    .done-card { background:#f8fafc; border:1px solid var(--border); border-radius:10px; padding:14px; text-align:center; }
    .done-card .icon { font-size:24px; margin-bottom:6px; }
    .done-card strong { display:block; font-size:13px; color:var(--muted); margin-bottom:2px; }
    .done-card span { font-size:13.5px; font-weight:700; color:var(--text); word-break:break-all; }
    .warn-box { background:#fffbeb; border:1px solid #fde68a; border-radius:10px; padding:14px 16px; font-size:13.5px; color:#92400e; margin-top:18px; }

    code { background:#f1f5f9; padding:1px 6px; border-radius:4px; font-size:13px; color:#5b5ef6; }
    .divider { border:none; border-top:1px solid var(--border); margin:22px 0; }
    .footer { margin-top:28px; font-size:12px; color:var(--muted); text-align:center; }
  </style>
</head>
<body>

<div class="logo">
  <div class="logo-icon">🎓</div>
  <div><h1>LMSAdvisor</h1><span>Installer v' . INSTALLER_VERSION . '</span></div>
</div>

<div class="steps">' . $stepsHtml . '</div>

' . $content . '

<div class="footer">LMSAdvisor v2.0 · MIT License · <a href="https://github.com/VishavjeetChoubey/lmsadvisor" target="_blank">GitHub</a></div>

</body></html>';
}

// ── STEP VIEWS ────────────────────────────────────────────────────────────────

if ($step === 'welcome') {
    echo renderPage('Welcome', '
<div class="card">
  <div class="card-icon" style="background:#ededff;color:#5b5ef6">🎓</div>
  <h2>Welcome to LMSAdvisor</h2>
  <p>This wizard will guide you through installing LMSAdvisor on your server.<br>The process takes about 2 minutes.</p>
  <hr class="divider">
  <p style="font-size:13.5px;color:#374151;text-align:left">
    <strong>Before you begin, make sure you have:</strong>
  </p>
  <ul style="font-size:13.5px;color:#6b7280;margin:10px 0 20px 20px;line-height:2">
    <li>A MySQL / MariaDB database ready (the installer can create it)</li>
    <li>Database username and password with CREATE privileges</li>
    <li>Your site URL (e.g. <code>http://localhost/lmsadvisor</code>)</li>
    <li>PHP 8.1+ with PDO, cURL, mbstring</li>
  </ul>
  <div class="btn-actions" style="justify-content:center">
    <a href="?step=requirements" class="btn btn-primary">Let\'s Start →</a>
  </div>
</div>');
    exit;
}

if ($step === 'requirements') {
    $checks = checkRequirements();
    $allOk  = allRequirementsMet();
    $rows   = '';
    foreach ($checks as [$label, $ok, $detail]) {
        $badge = $ok ? '<span class="badge-ok">✓ OK</span>' : '<span class="badge-fail">✗ Fail</span>';
        $rows .= "<tr><td>{$label}</td><td>{$detail}</td><td>{$badge}</td></tr>";
    }
    $btn = $allOk
        ? '<a href="?step=database" class="btn btn-primary">Continue →</a>'
        : '<button class="btn btn-outline" onclick="location.reload()">↺ Re-check</button>';

    echo renderPage('Requirements', '
<div class="card">
  <div class="card-icon" style="background:' . ($allOk ? '#ecfdf5;color:#059669' : '#fef2f2;color:#dc2626') . '">' . ($allOk ? '✓' : '✗') . '</div>
  <h2>Server Requirements</h2>
  <p>' . ($allOk ? 'Great — your server meets all requirements!' : 'Some requirements are not met. Please fix the issues below and re-check.') . '</p>
  <table class="req-table">
    <thead><tr><th>Requirement</th><th>Detail</th><th>Status</th></tr></thead>
    <tbody>' . $rows . '</tbody>
  </table>
  <div class="btn-actions">
    <a href="?step=welcome" class="btn btn-outline">← Back</a>
    ' . $btn . '
  </div>
</div>');
    exit;
}

if ($step === 'database') {
    session_start();
    $errors = getErrors();
    $errHtml = '';
    if ($errors) {
        $errHtml = '<div class="alert alert-error"><ul>' . implode('', array_map(fn($e) => "<li>{$e}</li>", $errors)) . '</ul></div>';
    }
    $old = $_SESSION['install_db'] ?? [];

    echo renderPage('Database', '
<div class="card">
  <div class="card-icon" style="background:#eff6ff;color:#2563eb">🗄️</div>
  <h2>Database Connection</h2>
  <p>Enter your MySQL / MariaDB connection details. The database will be created automatically if it doesn\'t exist.</p>
  ' . $errHtml . '
  <form method="POST" action="?step=database">
    <div class="row2">
      <div class="form-group">
        <label>Database Host</label>
        <input type="text" name="db_host" value="' . htmlspecialchars($old['host'] ?? 'localhost') . '" required placeholder="localhost">
      </div>
      <div class="form-group">
        <label>Database Port</label>
        <input type="number" name="db_port" value="' . htmlspecialchars((string)($old['port'] ?? '3306')) . '" required>
      </div>
    </div>
    <div class="form-group">
      <label>Database Name</label>
      <input type="text" name="db_name" value="' . htmlspecialchars($old['name'] ?? 'lmsadvisor') . '" required placeholder="lmsadvisor">
      <small>The database will be created automatically if it doesn\'t already exist.</small>
    </div>
    <div class="row2">
      <div class="form-group">
        <label>Database Username</label>
        <input type="text" name="db_user" value="' . htmlspecialchars($old['user'] ?? 'root') . '" required>
      </div>
      <div class="form-group">
        <label>Database Password</label>
        <input type="password" name="db_pass" value="" placeholder="(leave blank if none)">
      </div>
    </div>
    <div class="btn-actions">
      <a href="?step=requirements" class="btn btn-outline">← Back</a>
      <button type="submit" class="btn btn-primary">Test & Continue →</button>
    </div>
  </form>
</div>');
    exit;
}

if ($step === 'configure') {
    session_start();
    $errors = getErrors();
    $errHtml = '';
    if ($errors) {
        $errHtml = '<div class="alert alert-error"><ul>' . implode('', array_map(fn($e) => "<li>{$e}</li>", $errors)) . '</ul></div>';
    }
    $guessedUrl = guessUrl();
    $old = $_SESSION['install_site'] ?? [];

    echo renderPage('Configure', '
<div class="card">
  <div class="card-icon" style="background:#fffbeb;color:#d97706">🔧</div>
  <h2>Configure Your LMS</h2>
  <p>Set up your site details and admin account.</p>
  ' . $errHtml . '
  <form method="POST" action="?step=configure">
    <hr class="divider">
    <p style="font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;text-align:left;margin-bottom:14px">Site Information</p>
    <div class="form-group">
      <label>Site Name</label>
      <input type="text" name="site_name" value="' . htmlspecialchars($old['site_name'] ?? 'LMSAdvisor') . '" required placeholder="My Company LMS">
    </div>
    <div class="form-group">
      <label>Site URL</label>
      <input type="url" name="site_url" value="' . htmlspecialchars($old['site_url'] ?? $guessedUrl) . '" required placeholder="http://example.com/lms">
      <small>The full URL where LMSAdvisor is installed. No trailing slash.</small>
    </div>
    <hr class="divider">
    <p style="font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;text-align:left;margin-bottom:14px">Admin Account</p>
    <div class="form-group">
      <label>Full Name</label>
      <input type="text" name="admin_name" value="' . htmlspecialchars($old['admin_name'] ?? '') . '" required placeholder="John Smith">
    </div>
    <div class="form-group">
      <label>Email Address</label>
      <input type="email" name="admin_email" value="' . htmlspecialchars($old['admin_email'] ?? '') . '" required placeholder="admin@example.com">
    </div>
    <div class="row2">
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="admin_pass" required placeholder="Min. 8 characters">
      </div>
      <div class="form-group">
        <label>Confirm Password</label>
        <input type="password" name="admin_pass2" required placeholder="Repeat password">
      </div>
    </div>
    <div class="btn-actions">
      <a href="?step=database" class="btn btn-outline">← Back</a>
      <button type="submit" class="btn btn-primary">Install LMSAdvisor →</button>
    </div>
  </form>
</div>');
    exit;
}

if ($step === 'install') {
    session_start();
    $errors = getErrors();
    if ($errors) {
        $errHtml = '<div class="alert alert-error"><ul>' . implode('', array_map(fn($e) => "<li>{$e}</li>", $errors)) . '</ul></div>';
        echo renderPage('Installing', '<div class="card"><h2>Installation Error</h2>' . $errHtml . '<div class="btn-actions"><a href="?step=configure" class="btn btn-outline">← Back</a></div></div>');
        exit;
    }
    $db   = $_SESSION['install_db']   ?? null;
    $site = $_SESSION['install_site'] ?? null;
    if (!$db || !$site) {
        header('Location: ?step=database'); exit;
    }
    $migrations = count(glob(BASE_PATH . '/database/migrations/*.sql'));

    echo renderPage('Installing', '
<div class="card">
  <div class="card-icon" style="background:#ededff;color:#5b5ef6">🚀</div>
  <h2>Installing LMSAdvisor…</h2>
  <p>Please wait while we set up your database and create your admin account.</p>
  <div class="progress-bar"><div class="progress-fill" id="progressFill" style="width:5%"></div></div>
  <div class="install-log" id="installLog">
    <div class="info">▶ Starting installation…</div>
    <div class="info">▶ Running ' . $migrations . ' database migrations…</div>
  </div>
  <form id="installForm" method="POST" action="?step=install">
    <button type="submit" style="display:none" id="submitBtn">Install</button>
  </form>
</div>
<script>
  // Auto-submit after 300ms so user sees the progress UI
  setTimeout(function() {
    var log = document.getElementById("installLog");
    var bar = document.getElementById("progressFill");
    var msgs = [
      ["Creating database tables…", 25],
      ["Running migrations (0001–0010)…", 40],
      ["Running migrations (0011–0021)…", 60],
      ["Seeding default data…", 75],
      ["Creating admin account…", 85],
      ["Writing configuration…", 95],
    ];
    var i = 0;
    function next() {
      if (i < msgs.length) {
        var div = document.createElement("div");
        div.className = "ok";
        div.textContent = "✓ " + msgs[i][0];
        log.appendChild(div); log.scrollTop = log.scrollHeight;
        bar.style.width = msgs[i][1] + "%";
        i++; setTimeout(next, 400);
      } else {
        bar.style.width = "99%";
        document.getElementById("installForm").submit();
      }
    }
    next();
  }, 300);
</script>');
    exit;
}

if ($step === 'done') {
    $siteUrl = urldecode($_GET['url'] ?? '');
    echo renderPage('Complete', '
<div class="card">
  <div class="card-icon" style="background:#ecfdf5;color:#059669">✅</div>
  <h2>Installation Complete!</h2>
  <p>LMSAdvisor has been successfully installed. You\'re ready to start teaching!</p>
  <div class="done-grid">
    <div class="done-card"><div class="icon">🌐</div><strong>Site URL</strong><span>' . htmlspecialchars($siteUrl) . '</span></div>
    <div class="done-card"><div class="icon">⚙️</div><strong>Admin Panel</strong><span>' . htmlspecialchars($siteUrl) . '/admin</span></div>
    <div class="done-card"><div class="icon">🎓</div><strong>Student Portal</strong><span>' . htmlspecialchars($siteUrl) . '/learn</span></div>
    <div class="done-card"><div class="icon">🔑</div><strong>Login</strong><span>' . htmlspecialchars($siteUrl) . '/login</span></div>
  </div>
  <div class="warn-box">
    ⚠️ <strong>Security:</strong> Delete the <code>/install/</code> folder from your server immediately to prevent unauthorized reinstallation.
    <br><br>
    <code>rm -rf /path/to/lmsadvisor/install/</code>
  </div>
  <div class="btn-actions" style="margin-top:20px">
    <a href="' . htmlspecialchars($siteUrl) . '/login" class="btn btn-success">Go to Login →</a>
    <a href="' . htmlspecialchars($siteUrl) . '/admin" class="btn btn-primary">Go to Admin →</a>
  </div>
</div>');
    exit;
}

echo renderPage('Not Found', '<div class="card"><h2>Step not found</h2><p><a href="?step=welcome" class="btn btn-outline">← Start Over</a></p></div>');
