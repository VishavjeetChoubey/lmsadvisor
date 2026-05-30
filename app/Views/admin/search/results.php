<?php
use App\Core\View;
$e   = fn($v) => View::e($v);
$url = fn($p='') => View::url($p);

$methodColors = ['GET'=>'#059669','POST'=>'#2563eb','PUT'=>'#d97706','DELETE'=>'#dc2626'];
$typeIcons    = ['course'=>'bi-journal-bookmark-fill','user'=>'bi-person-fill','enrollment'=>'bi-person-check-fill','lesson'=>'bi-play-circle-fill'];
$typeLabels   = ['course'=>'Course','user'=>'Student','enrollment'=>'Enrollment','lesson'=>'Lesson'];
$typeLinks    = ['course'=>'admin/courses/','user'=>'admin/users/','enrollment'=>'admin/courses/','lesson'=>'admin/courses/'];
$typeColors   = ['course'=>'#6366f1','user'=>'#059669','enrollment'=>'#0891b2','lesson'=>'#d97706'];
$typeSuffix   = ['course'=>'/edit','user'=>'/edit','enrollment'=>'/edit','lesson'=>'/edit'];
?>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
  <div>
    <h2 class="adm-page-title">Search Results</h2>
    <?php if ($q): ?>
    <p class="adm-page-sub">Showing results for <strong>"<?= $e($q) ?>"</strong></p>
    <?php endif; ?>
  </div>
</div>

<!-- Search bar -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-body">
    <form method="GET" action="<?= $url('admin/search') ?>" class="d-flex gap-3 align-items-center">
      <div style="position:relative;flex:1">
        <i class="bi bi-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--bs-secondary-color)"></i>
        <input type="text" name="q" value="<?= $e($q) ?>"
               class="form-control ps-5"
               placeholder="Search courses, students, lessons…"
               autofocus>
      </div>
      <button type="submit" class="btn btn-primary px-4">Search</button>
    </form>
  </div>
</div>

<?php if (!$q): ?>
<!-- Empty state -->
<div class="card border-0 shadow-sm">
  <div class="card-body text-center py-5">
    <i class="bi bi-search" style="font-size:3rem;color:var(--bs-secondary-color);opacity:.4"></i>
    <div class="fw-bold mt-3">Enter a search term above</div>
    <div class="text-muted" style="font-size:13.5px">Search across courses, students, enrollments and lessons.</div>
  </div>
</div>

<?php else:
  // Run the search server-side for the full results page
  $pdo   = \App\Core\Database::getInstance();
  $like  = '%' . $q . '%';
  $allResults = [];

  // Courses
  $stmt = $pdo->prepare('SELECT "course" AS type, c.uuid, c.title,
    CONCAT(c.status, " · ", COALESCE(c.level,""), " · ",
    (SELECT COUNT(*) FROM enrollments e WHERE e.course_id=c.id), " students") AS meta
    FROM courses c WHERE c.title LIKE ? OR c.short_description LIKE ?
    ORDER BY c.status="published" DESC LIMIT 20');
  $stmt->execute([$like, $like]);
  $courses = $stmt->fetchAll();

  // Students
  $stmt = $pdo->prepare('SELECT "user" AS type, u.uuid,
    CONCAT(u.first_name," ",u.last_name) AS title,
    CONCAT(u.email, " · ", (SELECT COUNT(*) FROM enrollments e WHERE e.user_id=u.id), " courses") AS meta
    FROM users u WHERE u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?
    ORDER BY u.created_at DESC LIMIT 20');
  $stmt->execute([$like, $like, $like]);
  $students = $stmt->fetchAll();

  // Enrollments
  $stmt = $pdo->prepare('SELECT "enrollment" AS type, c.uuid,
    CONCAT(u.first_name," ",u.last_name) AS title,
    CONCAT(c.title, " · ", e.status) AS meta
    FROM enrollments e
    JOIN users u ON u.id=e.user_id
    JOIN courses c ON c.id=e.course_id
    WHERE u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR c.title LIKE ?
    ORDER BY e.enrolled_at DESC LIMIT 20');
  $stmt->execute([$like, $like, $like, $like]);
  $enrollments = $stmt->fetchAll();

  // Lessons
  $stmt = $pdo->prepare('SELECT "lesson" AS type, c.uuid, l.title,
    CONCAT(c.title, " · ", s.title) AS meta
    FROM lessons l
    JOIN sections s ON s.id=l.section_id
    JOIN courses c ON c.id=l.course_id
    WHERE l.title LIKE ? ORDER BY l.sort_order LIMIT 20');
  $stmt->execute([$like]);
  $lessons = $stmt->fetchAll();

  $groups = ['Courses' => $courses, 'Students' => $students, 'Enrollments' => $enrollments, 'Lessons' => $lessons];
  $total  = array_sum(array_map('count', $groups));
?>

<?php if ($total === 0): ?>
<div class="card border-0 shadow-sm">
  <div class="card-body text-center py-5">
    <i class="bi bi-search" style="font-size:3rem;color:var(--bs-secondary-color);opacity:.4"></i>
    <div class="fw-bold mt-3">No results found for "<?= $e($q) ?>"</div>
    <div class="text-muted" style="font-size:13.5px">Try a different search term.</div>
  </div>
</div>

<?php else: ?>

<div class="text-muted mb-3" style="font-size:13.5px">
  Found <strong><?= $total ?></strong> result<?= $total !== 1 ? 's' : '' ?>
</div>

<div class="row g-4">
  <?php foreach ($groups as $groupLabel => $items):
    if (empty($items)) continue;
    $firstType = $items[0]['type'] ?? 'course';
    $icon   = $typeIcons[$firstType]  ?? 'bi-search';
    $color  = $typeColors[$firstType] ?? '#6366f1';
    $link   = $typeLinks[$firstType]  ?? 'admin/';
  ?>
  <div class="col-12 col-lg-6">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-transparent py-3 d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-bold d-flex align-items-center gap-2">
          <i class="bi <?= $icon ?>" style="color:<?= $color ?>"></i>
          <?= $e($groupLabel) ?>
        </h6>
        <span class="badge bg-secondary-subtle text-secondary"><?= count($items) ?></span>
      </div>
      <div class="card-body p-0">
        <?php foreach ($items as $r):
          $rowLink = $url($typeLinks[$firstType] . ($r['uuid'] ?? '') . ($typeSuffix[$firstType] ?? ''));
          if ($firstType === 'user') $rowLink = $url('admin/users/' . ($r['uuid'] ?? '') . '/edit');
        ?>
        <a href="<?= $e($rowLink) ?>"
           style="display:flex;align-items:center;gap:12px;padding:12px 16px;border-bottom:1px solid var(--bs-border-color);text-decoration:none;transition:background .1s"
           onmouseover="this.style.background='var(--bs-tertiary-bg)'"
           onmouseout="this.style.background=''">
          <div style="width:34px;height:34px;border-radius:10px;background:<?= $color ?>18;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="bi <?= $icon ?>" style="color:<?= $color ?>;font-size:15px"></i>
          </div>
          <div style="min-width:0;flex:1">
            <div style="font-size:13.5px;font-weight:600;color:var(--bs-body-color);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
              <?= $e($r['title'] ?? '') ?>
            </div>
            <div style="font-size:12px;color:var(--bs-secondary-color);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
              <?= $e($r['meta'] ?? '') ?>
            </div>
          </div>
          <i class="bi bi-chevron-right" style="color:var(--bs-secondary-color);font-size:12px;flex-shrink:0"></i>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php endif; endif; ?>
