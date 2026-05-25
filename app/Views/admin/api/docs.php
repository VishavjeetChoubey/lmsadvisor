<?php
use App\Core\View;
$e   = fn(mixed $v): string => View::e($v);
$url = fn(string $p = ''): string => View::url($p);

$endpoints = [
  'Authentication' => [
    ['POST','/api/v1/auth/token','Get a Bearer token','Public',['email'=>'string','password'=>'string','token_name'=>'string (optional)'],'{"success":true,"token":"abc123...","user":{...}}'],
    ['DELETE','/api/v1/auth/token','Revoke current token','Authenticated',[],'{"success":true,"message":"Token revoked."}'],
  ],
  'Profile' => [
    ['GET','/api/v1/profile','Get my profile','read',[],'{"data":{...user object...}}'],
    ['GET','/api/v1/leaderboard','Top 50 leaderboard','read',[],'{"data":[...]}'],
  ],
  'Courses' => [
    ['GET','/api/v1/courses','List published courses','read',['search'=>'string','cat'=>'int','page'=>'int'],'{"data":[...],"meta":{"total":10,"page":1,"per_page":25}}'],
    ['GET','/api/v1/courses/:uuid','Get single course with sections','read',[],'{"data":{...course with sections}}'],
    ['GET','/api/v1/courses/:uuid/progress','My progress in course','read',[],'{"enrollment":{...},"lessons":[...]}'],
    ['GET','/api/v1/courses/:uuid/quizzes','List quizzes in course','read',[],'{"data":[...]}'],
    ['GET','/api/v1/courses/:uuid/reviews','Approved reviews','read',[],'{"data":[...]}'],
    ['POST','/api/v1/courses/:uuid/reviews','Submit a review (completed only)','write',['rating'=>'1-5','comment'=>'string'],'{"success":true}'],
    ['GET','/api/v1/courses/:uuid/forum/threads','Forum threads','read',['page'=>'int'],'{"data":[...]}'],
    ['POST','/api/v1/courses/:uuid/forum/threads','Create thread','write',['title'=>'string','body'=>'string'],'{"success":true,"id":1}'],
  ],
  'Enrollments' => [
    ['GET','/api/v1/enrollments','My enrollments','read',[],'{"data":[...]}'],
    ['POST','/api/v1/enrollments','Enroll a user (admin only)','write',['course_uuid'=>'string','user_id'=>'int'],'{"message":"Enrolled."}'],
  ],
  'Lessons' => [
    ['POST','/api/v1/lessons/:id/complete','Mark lesson complete','write',[],'{"success":true}'],
  ],
  'Quizzes' => [
    ['GET','/api/v1/quizzes/:id/results','My quiz attempts','read',[],'{"data":[...]}'],
  ],
  'Forum' => [
    ['GET','/api/v1/forum/threads/:id','Thread + replies','read',[],'{"data":{...thread with replies}}'],
  ],
  'Certificates' => [
    ['GET','/api/v1/certificates','My certificates','read',[],'{"data":[...]}'],
    ['GET','/api/v1/certificates/:uuid/verify','Verify any cert (public)','Public',[],'{"valid":true,"data":{...}}'],
  ],
  'Knowledge Base' => [
    ['GET','/api/v1/kb/articles','Published articles','read',['search'=>'string'],'{"data":[...]}'],
    ['GET','/api/v1/kb/articles/:uuid','Article detail','read',[],'{"data":{...}}'],
  ],
  'Webinars' => [
    ['GET','/api/v1/webinars','Scheduled/live webinars','read',[],'{"data":[...]}'],
    ['GET','/api/v1/webinars/:uuid','Webinar detail','read',[],'{"data":{...}}'],
  ],
  'Users' => [
    ['GET','/api/v1/users','List users (admin only)','read',['search'=>'string','role'=>'string','page'=>'int'],'{"data":[...],"meta":{...}}'],
    ['GET','/api/v1/users/:uuid','User detail','read',[],'{"data":{...}}'],
  ],
  'Notifications' => [
    ['GET','/api/notifications','My notifications','read',['page'=>'int'],'{"rows":[...],"total":10}'],
    ['GET','/api/notifications/count','Unread count','read',[],'{"count":3}'],
    ['POST','/api/notifications/read-all','Mark all read','write',[],'{"success":true}'],
    ['POST','/api/notifications/:id/read','Mark one read','write',[],'{"success":true}'],
  ],
];

$methodColors = ['GET'=>'success','POST'=>'primary','PUT'=>'warning','PATCH'=>'info','DELETE'=>'danger'];
$scopeColors  = ['read'=>'secondary','write'=>'warning','admin'=>'danger','Public'=>'success','Authenticated'=>'info'];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <a href="<?= $url('admin/api') ?>" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i> Back to API Management
  </a>
  <div class="d-flex gap-2">
    <span class="badge bg-secondary">Base URL: <?= $e(APP_URL) ?>/api/v1</span>
    <span class="badge bg-primary">v1.0</span>
  </div>
</div>

<!-- Auth section -->
<div class="card lms-card mb-4">
  <div class="card-body p-4">
    <h5 class="fw-bold mb-3"><i class="bi bi-shield-lock me-2 text-primary"></i>Authentication</h5>
    <p style="font-size:14px;color:var(--text-muted)">
      All API requests (except <code>POST /api/v1/auth/token</code> and certificate verify) require a Bearer token.
      Include it in the <code>Authorization</code> header:
    </p>
    <div class="p-3 rounded" style="background:var(--content-bg);font-family:monospace;font-size:13px">
      <span style="color:#0e9f6e">Authorization</span>: Bearer <span style="color:#e3a008">YOUR_TOKEN_HERE</span>
    </div>
    <div class="row g-3 mt-3">
      <div class="col-md-4">
        <div class="p-3 rounded border text-center">
          <div class="badge bg-secondary mb-2 px-3">read</div>
          <div style="font-size:13px">GET endpoints, list/show data</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="p-3 rounded border text-center">
          <div class="badge bg-warning text-dark mb-2 px-3">write</div>
          <div style="font-size:13px">POST/PUT, create/update data</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="p-3 rounded border text-center">
          <div class="badge bg-danger mb-2 px-3">admin</div>
          <div style="font-size:13px">Admin-only operations</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Endpoints -->
<?php foreach ($endpoints as $group => $routes): ?>
<div class="card lms-card mb-4">
  <div class="card-header lms-card-header">
    <h5 class="mb-0 fw-bold"><?= $e($group) ?></h5>
  </div>
  <div class="p-0">
    <?php foreach ($routes as [$method, $path, $desc, $scope, $params, $example]): ?>
    <div class="border-bottom p-4">
      <div class="d-flex align-items-center gap-3 mb-2 flex-wrap">
        <span class="badge bg-<?= $methodColors[$method] ?? 'secondary' ?>" style="font-size:12px;min-width:56px;text-align:center"><?= $method ?></span>
        <code style="font-size:14px;color:var(--primary)"><?= $e(APP_URL . $path) ?></code>
        <span class="badge bg-<?= $scopeColors[$scope] ?? 'secondary' ?>-subtle text-<?= $scopeColors[$scope] ?? 'secondary' ?>" style="font-size:11px"><?= $scope ?></span>
        <span class="text-muted" style="font-size:13px"><?= $e($desc) ?></span>
      </div>
      <?php if ($params): ?>
      <div class="mt-2 mb-2">
        <span class="text-muted" style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Parameters:</span>
        <div class="d-flex gap-2 mt-1 flex-wrap">
          <?php foreach ($params as $pk => $pv): ?>
          <code style="font-size:12px;background:var(--content-bg);padding:2px 8px;border-radius:4px">
            <?= $e($pk) ?>: <span style="color:#0e9f6e"><?= $e($pv) ?></span>
          </code>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
      <details>
        <summary style="font-size:12px;color:var(--text-muted);cursor:pointer">Example response</summary>
        <pre style="background:var(--content-bg);border-radius:8px;padding:10px;font-size:12px;margin-top:8px;overflow-x:auto"><?= $e($example) ?></pre>
      </details>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endforeach; ?>

<!-- Error codes -->
<div class="card lms-card">
  <div class="card-header lms-card-header"><h5 class="mb-0">Error Codes</h5></div>
  <div class="table-responsive">
    <table class="table lms-table mb-0">
      <thead><tr><th>HTTP</th><th>Code</th><th>Meaning</th></tr></thead>
      <tbody>
        <?php foreach ([
          [200,'OK','Request succeeded'],
          [201,'Created','Resource created successfully'],
          [204,'No Content','Success with no body (OPTIONS preflight)'],
          [400,'Bad Request','Invalid parameters'],
          [401,'AUTH_FAILED / TOKEN_MISSING / TOKEN_INVALID','Authentication failed'],
          [403,'FORBIDDEN / SCOPE_MISSING / IP_NOT_ALLOWED','Authorization failed'],
          [404,'Not Found','Resource does not exist'],
          [409,'DUPLICATE_REVIEW','Duplicate resource (e.g. second review)'],
          [422,'Unprocessable','Validation error'],
          [429,'Rate Limited','Too many requests — see Retry-After header'],
          [500,'Server Error','Internal error — check PHP logs'],
        ] as [$code, $name, $meaning]): ?>
        <tr>
          <td><span class="badge bg-<?= $code < 300 ? 'success' : ($code < 400 ? 'info' : ($code < 500 ? 'warning' : 'danger')) ?>"><?= $code ?></span></td>
          <td><code style="font-size:12px"><?= $e($name) ?></code></td>
          <td class="text-muted" style="font-size:13px"><?= $e($meaning) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
