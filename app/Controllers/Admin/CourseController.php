<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Services\AuthService;
use App\Services\StorageService;
use App\Models\Course;
use App\Models\Category;
use App\Models\Section;
use App\Models\Lesson;
use App\Models\AuditLog;
use App\Helpers\Sanitizer;
use App\Helpers\Slug;
use App\Helpers\Uuid;
use App\Helpers\Validator;

class CourseController extends Controller
{
    private Course   $course;
    private Category $category;
    private Section  $section;
    private Lesson   $lesson;

    public function __construct($request, $response)
    {
        parent::__construct($request, $response);
        AuthMiddleware::handle();
        RoleMiddleware::require(['super_admin', 'admin', 'manager']);
        $this->course   = new Course();
        $this->category = new Category();
        $this->section  = new Section();
        $this->lesson   = new Lesson();
    }

    // ── GET /admin/courses ────────────────────────────────────────────────────
    public function index(array $params): void
    {
        $page       = max(1, (int)$this->request->get('page', 1));
        $search     = Sanitizer::string($this->request->get('search', ''), 100);
        $status     = Sanitizer::string($this->request->get('status', ''), 20);
        $categoryId = (int)$this->request->get('category', 0);

        $data = $this->course->paginate($page, 20, $search, $status, $categoryId);

        $this->view('admin.courses.index', [
            'title'        => 'Courses — LMSAdvisor',
            'page_title'   => 'Courses',
            'breadcrumbs'  => [['label' => 'Courses']],
            'flash'        => $this->getFlash(),
            'auth_user'    => AuthService::user(),
            'rows'         => $data['rows'],
            'total'        => $data['total'],
            'page'         => $data['page'],
            'perPage'      => $data['perPage'],
            'totalPages'   => (int)ceil($data['total'] / $data['perPage']),
            'search'       => $search,
            'statusFilter' => $status,
            'categoryId'   => $categoryId,
            'statusCounts' => $this->course->countByStatus(),
            'categories'   => $this->category->forDropdown(),
            'csrf_token'   => CsrfMiddleware::token(),
        ]);
    }

    // ── GET /admin/courses/create ─────────────────────────────────────────────
    public function create(array $params): void
    {
        $this->view('admin.courses.create', [
            'title'       => 'New Course — LMSAdvisor',
            'page_title'  => 'Create Course',
            'breadcrumbs' => [['label'=>'Courses','url'=>'/admin/courses'],['label'=>'New Course']],
            'flash'       => $this->getFlash(),
            'auth_user'   => AuthService::user(),
            'csrf_token'  => CsrfMiddleware::token(),
            'categories'  => $this->category->forDropdown(),
        ]);
    }

    // ── POST /admin/courses/create ────────────────────────────────────────────
    public function store(array $params): void
    {
        CsrfMiddleware::verify();

        $title = Sanitizer::string($this->request->post('title', ''), 255);
        $v = (new Validator())->required('title', $title, 'Course title');
        if ($v->fails()) {
            $this->flash('error', $v->firstError());
            $this->redirect('/admin/courses/create');
        }

        $uuid = Uuid::v4();
        $slug = Slug::unique($title, 'courses', 'slug');

        // Handle thumbnail upload
        $thumbnail = null;
        if (!empty($_FILES['thumbnail']['name'])) {
            try {
                $thumbnail = StorageService::upload('thumbnail', 'image', 'thumbnails');
            } catch (\RuntimeException $e) {
                $this->flash('error', 'Thumbnail: ' . $e->getMessage());
                $this->redirect('/admin/courses/create');
            }
        }

        $data = $this->buildCourseData($uuid, $slug, $thumbnail);
        $id   = $this->course->create($data);
        AuditLog::write('course.create', 'course', $id, null, ['title' => $title]);

        $this->flash('success', 'Course created. Now add sections and lessons below.');
        $this->redirect('/admin/courses/' . $uuid . '/edit');
    }

    // ── GET /admin/courses/:uuid/edit ─────────────────────────────────────────
    public function edit(array $params): void
    {
        $course = $this->getCourseOrFail($params['uuid'] ?? '');

        $this->view('admin.courses.edit', [
            'title'       => 'Edit Course — LMSAdvisor',
            'page_title'  => 'Edit: ' . $course['title'],
            'breadcrumbs' => [
                ['label' => 'Courses', 'url' => '/admin/courses'],
                ['label' => $course['title']],
            ],
            'flash'       => $this->getFlash(),
            'auth_user'   => AuthService::user(),
            'csrf_token'  => CsrfMiddleware::token(),
            'course'      => $course,
            'categories'  => $this->category->forDropdown(),
            'sections'    => $this->course->sectionsWithLessons((int)$course['id']),
        ]);
    }

    // ── POST /admin/courses/:uuid/edit ────────────────────────────────────────
    public function update(array $params): void
    {
        CsrfMiddleware::verify();
        $course = $this->getCourseOrFail($params['uuid'] ?? '');

        $title = Sanitizer::string($this->request->post('title', ''), 255);
        $v = (new Validator())->required('title', $title, 'Course title');
        if ($v->fails()) {
            $this->flash('error', $v->firstError());
            $this->redirect('/admin/courses/' . $course['uuid'] . '/edit');
        }

        $slug = Slug::unique($title, 'courses', 'slug', (int)$course['id']);

        // Handle thumbnail
        $thumbnail = $course['thumbnail'];
        if (!empty($_FILES['thumbnail']['name'])) {
            try {
                // Delete old
                if ($thumbnail) StorageService::delete($thumbnail);
                $thumbnail = StorageService::upload('thumbnail', 'image', 'thumbnails');
            } catch (\RuntimeException $e) {
                $this->flash('error', 'Thumbnail: ' . $e->getMessage());
                $this->redirect('/admin/courses/' . $course['uuid'] . '/edit');
            }
        }

        $data = $this->buildCourseData($course['uuid'], $slug, $thumbnail);
        $this->course->update($course['uuid'], $data);
        AuditLog::write('course.update', 'course', (int)$course['id']);

        $this->flash('success', 'Course saved successfully.');
        $this->redirect('/admin/courses/' . $course['uuid'] . '/edit');
    }

    // ── POST /admin/courses/:uuid/delete ──────────────────────────────────────
    public function delete(array $params): void
    {
        CsrfMiddleware::verify();
        $course = $this->getCourseOrFail($params['uuid'] ?? '');

        AuditLog::write('course.delete', 'course', (int)$course['id'], ['title' => $course['title']]);
        $this->course->delete($course['uuid']);
        $this->json(['success' => true]);
    }

    // ── POST /admin/courses/ai-generate — AI course generation ────────────────
    public function aiGenerate(array $params): void
    {
        CsrfMiddleware::verify();
        try {
            $contentTypes = $this->request->post('content_types', []);
            if (!is_array($contentTypes) || empty($contentTypes)) {
                $contentTypes = ['text', 'video', 'quiz'];
            }
            $result = \App\Services\AiCourseService::generate([
                'topic'              => Sanitizer::string($this->request->post('topic', ''), 200),
                'level'              => Sanitizer::string($this->request->post('level', 'beginner'), 20),
                'num_sections'       => (int)$this->request->post('num_sections', 5),
                'num_lessons'        => (int)$this->request->post('num_lessons', 3),
                'language'           => Sanitizer::string($this->request->post('language', 'English'), 50),
                'extra_instructions' => Sanitizer::string($this->request->post('extra_instructions', ''), 500),
                'content_types'      => array_map(fn($t) => Sanitizer::string($t, 20), $contentTypes),
            ]);
            $this->json(['success' => true, 'course' => $result]);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // ── POST /admin/courses/ai-save — Save AI-generated course ────────────────
    public function aiSave(array $params): void
    {
        CsrfMiddleware::verify();
        $user   = \App\Services\AuthService::user();
        $data   = json_decode($this->request->post('course_data', '{}'), true);

        if (!$data || empty($data['title'])) {
            $this->json(['success' => false, 'message' => 'Invalid course data.']);
        }

        // Create the course
        $courseId = $this->course->create([
            'uuid'              => \App\Helpers\Uuid::v4(),
            'title'             => Sanitizer::string($data['title'], 255),
            'slug'              => \App\Helpers\Slug::make($data['title'] ?? 'course') . '-' . substr(uniqid(), -5),
            'short_description' => Sanitizer::string($data['short_description'] ?? '', 500),
            'description'       => $data['description'] ?? '',
            'level'             => $data['level'] ?? 'beginner',
            'language'          => $data['language'] ?? 'English',
            'duration_hours'    => (float)($data['duration_hours'] ?? 0),
            'grade_points'      => (int)($data['grade_points'] ?? 0),
            'status'            => 'draft',
            'created_by'        => (int)$user['id'],
        ]);

        $courseRow = $this->course->find($courseId);

        // Create sections + lessons
        $secOrder = 0;
        foreach ($data['sections'] ?? [] as $secData) {
            $sectionId = $this->section->create([
                'uuid'        => \App\Helpers\Uuid::v4(),
                'course_id'   => $courseId,
                'title'       => Sanitizer::string($secData['title'], 255),
                'description' => Sanitizer::string($secData['description'] ?? '', 500),
                'sort_order'  => ++$secOrder,
            ]);
            $lesOrder = 0;
            foreach ($secData['lessons'] ?? [] as $lesData) {
                $type    = $lesData['type'] ?? 'text';
                $content = '';

                if ($type === 'text') {
                    // Use rich HTML content if AI generated it, else build from description
                    $content = !empty($lesData['content'])
                        ? $lesData['content']
                        : '<h2>' . htmlspecialchars($lesData['title'] ?? '', ENT_QUOTES) . '</h2>'
                          . '<p>' . htmlspecialchars($lesData['description'] ?? '', ENT_QUOTES) . '</p>';
                } elseif ($type === 'video') {
                    $content = $lesData['video_topic'] ?? $lesData['description'] ?? '';
                } elseif ($type === 'quiz') {
                    $content = $lesData['description'] ?? '';
                }

                $lessonId = $this->lesson->create([
                    'uuid'         => \App\Helpers\Uuid::v4(),
                    'course_id'    => $courseId,
                    'section_id'   => $sectionId,
                    'title'        => Sanitizer::string($lesData['title'], 255),
                    'type'         => $type,
                    'content'      => $content,
                    'duration_sec' => (int)($lesData['duration_sec'] ?? 600),
                    'sort_order'   => ++$lesOrder,
                    'is_mandatory' => 1,
                ]);

                // ── Save quiz questions ────────────────────────────────────────
                if ($type === 'quiz' && !empty($lesData['questions'])) {
                    $pdo = \App\Core\Database::getInstance();

                    // Create the quiz record first
                    $pdo->prepare(
                        'INSERT INTO quizzes (lesson_id, title, time_limit_sec, pass_percentage, max_attempts)
                         VALUES (?,?,?,?,?)'
                    )->execute([
                        $lessonId,
                        Sanitizer::string($lesData['title'], 255),
                        (int)($lesData['duration_sec'] ?? 600),
                        70, // default pass %
                        3,  // default max attempts
                    ]);
                    $quizId = (int)$pdo->lastInsertId();

                    $qOrder = 0;
                    foreach ($lesData['questions'] as $qData) {
                        $options  = array_values($qData['options'] ?? []);
                        $correct  = (int)($qData['correct_index'] ?? 0);

                        // Insert question
                        $pdo->prepare(
                            'INSERT INTO questions (quiz_id, question_text, type, explanation, sort_order)
                             VALUES (?,?,?,?,?)'
                        )->execute([
                            $quizId,
                            $qData['question'] ?? '',
                            'mcq',
                            $qData['explanation'] ?? '',
                            ++$qOrder,
                        ]);
                        $questionId = (int)$pdo->lastInsertId();

                        // Insert options
                        foreach ($options as $i => $optText) {
                            $pdo->prepare(
                                'INSERT INTO question_options (question_id, option_text, is_correct, sort_order)
                                 VALUES (?,?,?,?)'
                            )->execute([
                                $questionId,
                                $optText,
                                $i === $correct ? 1 : 0,
                                $i + 1,
                            ]);
                        }
                    }
                }
            }
        }

        AuditLog::write('course.ai_generate', 'course', $courseId, null, ['title' => $courseRow['title']]);
        $this->json(['success' => true, 'uuid' => $courseRow['uuid']]);
    }

    // ── GET /admin/courses/:uuid/preview ──────────────────────────────────────
    public function preview(array $params): void
    {
        $course   = $this->getCourseOrFail($params['uuid'] ?? '');
        $sections = $this->course->sectionsWithLessons((int)$course['id']);

        $this->view('admin.courses.preview', [
            'title'      => '[Preview] ' . $course['title'],
            'page_title' => 'Preview: ' . $course['title'],
            'breadcrumbs'=> [
                ['label' => 'Courses', 'url' => '/admin/courses'],
                ['label' => $course['title'], 'url' => '/admin/courses/' . $course['uuid'] . '/edit'],
                ['label' => 'Preview'],
            ],
            'flash'      => $this->getFlash(),
            'auth_user'  => AuthService::user(),
            'course'     => $course,
            'sections'   => $sections,
        ]);
    }

    // ── GET /admin/courses/:uuid/export ───────────────────────────────────────
    public function export(array $params): void
    {
        $course = $this->getCourseOrFail($params['uuid'] ?? '');
        $json   = $this->course->exportJson($course['uuid']);

        $filename = 'course-' . $course['uuid'] . '-' . date('Ymd') . '.json';
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ── POST /admin/courses/import ────────────────────────────────────────────
    public function import(array $params): void
    {
        CsrfMiddleware::verify();

        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            $this->flash('error', 'Please upload a valid JSON file.');
            $this->redirect('/admin/courses');
        }

        $json = file_get_contents($_FILES['import_file']['tmp_name']);
        $data = json_decode($json, true);

        if (!$data || empty($data['lmsadvisor_export']) || empty($data['course'])) {
            $this->flash('error', 'Invalid export file format.');
            $this->redirect('/admin/courses');
        }

        $c       = $data['course'];
        $uuid    = Uuid::v4();
        $slug    = Slug::unique($c['title'] . ' imported', 'courses', 'slug');
        $authUser = AuthService::user();
        $userId   = (int)($authUser['id'] ?? 0);

        $c['uuid']         = $uuid;
        $c['slug']         = $slug;
        $c['status']       = 'draft';
        $c['thumbnail']    = null;
        $c['created_by']   = $userId;

        $courseId = $this->course->create($c);

        foreach ($data['sections'] ?? [] as $s) {
            $sUuid = Uuid::v4();
            $s['uuid']      = $sUuid;
            $s['course_id'] = $courseId;
            $sId = $this->section->create($s);

            foreach ($s['lessons'] ?? [] as $l) {
                $l['uuid']       = Uuid::v4();
                $l['section_id'] = $sId;
                $l['course_id']  = $courseId;
                $l['file_path']  = null; // can't import files
                $this->lesson->create($l);
            }
        }

        AuditLog::write('course.import', 'course', $courseId, null, ['title' => $c['title']]);
        $this->flash('success', 'Course imported as draft. Review and publish when ready.');
        $this->redirect('/admin/courses/' . $uuid . '/edit');
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  SECTION AJAX ENDPOINTS
    // ══════════════════════════════════════════════════════════════════════════

    // ── POST /admin/courses/:uuid/sections ────────────────────────────────────
    public function addSection(array $params): void
    {
        CsrfMiddleware::verify();
        $course = $this->getCourseOrFail($params['uuid'] ?? '');

        $title = Sanitizer::string($this->request->post('title', 'New Section'), 255);
        $count = count($this->section->forCourse((int)$course['id']));

        $id = $this->section->create([
            'uuid'       => Uuid::v4(),
            'course_id'  => $course['id'],
            'title'      => $title,
            'description'=> '',
            'drip_days'  => '',
            'sort_order' => $count,
        ]);

        $this->json(['success' => true, 'id' => $id, 'title' => $title]);
    }

    // ── POST /admin/sections/:id/update ──────────────────────────────────────
    public function updateSection(array $params): void
    {
        CsrfMiddleware::verify();
        $id    = (int)($params['id'] ?? 0);
        $title = Sanitizer::string($this->request->post('title', ''), 255);
        $desc  = Sanitizer::string($this->request->post('description', ''), 5000);
        $drip  = $this->request->post('drip_days', '');

        $this->section->update($id, [
            'title'       => $title,
            'description' => $desc,
            'drip_days'   => $drip,
            'sort_order'  => (int)$this->request->post('sort_order', 0),
        ]);
        $this->json(['success' => true]);
    }

    // ── POST /admin/sections/:id/delete ──────────────────────────────────────
    public function deleteSection(array $params): void
    {
        CsrfMiddleware::verify();
        $this->section->delete((int)($params['id'] ?? 0));
        $this->json(['success' => true]);
    }

    // ── POST /admin/sections/reorder ─────────────────────────────────────────
    public function reorderSections(array $params): void
    {
        CsrfMiddleware::verify();
        $ids = $this->request->post('ids', []);
        if (is_array($ids)) {
            $this->section->reorder(array_map('intval', $ids));
        }
        $this->json(['success' => true]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  LESSON AJAX ENDPOINTS
    // ══════════════════════════════════════════════════════════════════════════

    // ── POST /admin/sections/:id/lessons ─────────────────────────────────────
    public function addLesson(array $params): void
    {
        CsrfMiddleware::verify();
        $sectionId = (int)($params['id'] ?? 0);

        // Get course_id from section
        $pdo  = \App\Core\Database::getInstance();
        $row  = $pdo->prepare('SELECT course_id FROM sections WHERE id = ?');
        $row->execute([$sectionId]);
        $sec  = $row->fetch();
        if (!$sec) {
            $this->json(['success' => false, 'message' => 'Section not found.'], 404);
        }

        $count = count($this->lesson->forSection($sectionId));
        $type  = Sanitizer::string($this->request->post('type', 'text'), 20);
        $title = Sanitizer::string($this->request->post('title', 'New Lesson'), 255);

        $id = $this->lesson->create([
            'uuid'          => Uuid::v4(),
            'section_id'    => $sectionId,
            'course_id'     => $sec['course_id'],
            'title'         => $title,
            'type'          => $type,
            'video_type'    => null,
            'content'       => null,
            'file_path'     => null,
            'thumbnail'     => null,
            'duration_sec'  => '',
            'drip_days'     => '',
            'is_previewable'=> 0,
            'is_mandatory'  => 1,
            'sort_order'    => $count,
        ]);

        $this->json(['success' => true, 'id' => $id, 'title' => $title, 'type' => $type]);
    }

    // ── POST /admin/lessons/:id/update ───────────────────────────────────────
    public function updateLesson(array $params): void
    {
        CsrfMiddleware::verify();
        $id   = (int)($params['id'] ?? 0);
        $type = Sanitizer::string($this->request->post('type', 'text'), 20);

        // Handle file upload for video / document / scorm lessons
        $filePath = $this->request->post('existing_file', null);
        if (!empty($_FILES['lesson_file']['name'])) {
            // Map lesson type → StorageService type + subdirectory
            $uploadType = match($type) {
                'video'    => 'video',
                'scorm'    => 'scorm',
                'document' => 'document',
                default    => 'document',
            };
            $subDir = match($uploadType) {
                'video'    => 'videos',
                'scorm'    => 'scorm',
                'document' => 'documents',
                default    => 'documents',
            };
            try {
                if ($filePath) StorageService::delete($filePath);
                $filePath = StorageService::upload('lesson_file', $uploadType, $subDir);

                // Auto-extract SCORM packages immediately after upload
                if ($type === 'scorm' && $filePath) {
                    try {
                        \App\Services\ScormService::extract($filePath, $id);
                    } catch (\Throwable $ex) {
                        error_log('[SCORM extract] ' . $ex->getMessage());
                        // Don't fail the upload — extraction can be retried
                    }
                }
            } catch (\RuntimeException $e) {
                $this->json(['success' => false, 'message' => $e->getMessage()]);
            }
        }

        $this->lesson->update($id, [
            'title'          => Sanitizer::string($this->request->post('title', ''), 255),
            'type'           => $type,
            'video_type'     => $this->request->post('video_type', null),
            'content'        => $this->request->post('content', null), // Quill HTML or URL
            'file_path'      => $filePath,
            'thumbnail'      => null,
            'duration_sec'   => $this->request->post('duration_sec', ''),
            'drip_days'      => $this->request->post('drip_days', ''),
            'is_previewable' => (int)(bool)$this->request->post('is_previewable', 0),
            'is_mandatory'   => (int)(bool)$this->request->post('is_mandatory', 1),
            'sort_order'     => (int)$this->request->post('sort_order', 0),
        ]);

        $this->json(['success' => true]);
    }

    // ── POST /admin/lessons/:id/delete ───────────────────────────────────────
    public function deleteLesson(array $params): void
    {
        CsrfMiddleware::verify();
        $this->lesson->delete((int)($params['id'] ?? 0));
        $this->json(['success' => true]);
    }

    // ── POST /admin/lessons/reorder ───────────────────────────────────────────
    public function reorderLessons(array $params): void
    {
        CsrfMiddleware::verify();
        $ids = $this->request->post('ids', []);
        if (is_array($ids)) {
            $this->lesson->reorder(array_map('intval', $ids));
        }
        $this->json(['success' => true]);
    }

    // ── POST /admin/courses/:uuid/toggle-status ───────────────────────────────
    public function toggleStatus(array $params): void
    {
        CsrfMiddleware::verify();
        $course    = $this->getCourseOrFail($params['uuid'] ?? '');
        $newStatus = $this->request->post('status', 'draft');

        if (!in_array($newStatus, ['draft', 'published', 'archived'], true)) {
            $this->json(['success' => false, 'message' => 'Invalid status.']);
        }

        $publishedAt = ($newStatus === 'published' && !$course['published_at'])
            ? date('Y-m-d H:i:s') : $course['published_at'];

        $pdo = \App\Core\Database::getInstance();
        $pdo->prepare('UPDATE courses SET status=?, published_at=?, updated_at=NOW() WHERE uuid=?')
            ->execute([$newStatus, $publishedAt, $course['uuid']]);

        AuditLog::write('course.status_change', 'course', (int)$course['id'],
            ['status' => $course['status']], ['status' => $newStatus]);

        $this->json(['success' => true, 'status' => $newStatus]);
    }

    // ── GET  /admin/courses/:uuid/instructors (AJAX) ──────────────────────────
    public function getInstructors(array $params): void
    {
        $course = $this->getCourseOrFail($params['uuid'] ?? '');
        $users  = $this->course->getAssignedUsers((int)$course['id']);
        $this->json(['success' => true, 'users' => $users]);
    }

    // ── POST /admin/courses/:uuid/instructors ─────────────────────────────────
    public function assignInstructor(array $params): void
    {
        CsrfMiddleware::verify();
        $course = $this->getCourseOrFail($params['uuid'] ?? '');
        $userId = (int)$this->request->post('user_id', 0);
        $role   = Sanitizer::string($this->request->post('role', 'instructor'), 20);

        if (!$userId) {
            $this->json(['success' => false, 'message' => 'User ID required.']);
        }
        if (!in_array($role, ['instructor', 'manager'], true)) {
            $this->json(['success' => false, 'message' => 'Invalid role.']);
        }

        $this->course->assignUser((int)$course['id'], $userId, $role);
        AuditLog::write('course.assign_instructor', 'course', (int)$course['id'], null,
            ['user_id' => $userId, 'role' => $role]);
        $this->json(['success' => true]);
    }

    // ── POST /admin/courses/:uuid/instructors/remove ──────────────────────────
    public function removeInstructor(array $params): void
    {
        CsrfMiddleware::verify();
        $course = $this->getCourseOrFail($params['uuid'] ?? '');
        $userId = (int)$this->request->post('user_id', 0);
        $this->course->removeAssignedUser((int)$course['id'], $userId);
        AuditLog::write('course.remove_instructor', 'course', (int)$course['id'], null,
            ['user_id' => $userId]);
        $this->json(['success' => true]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  HELPERS
    // ══════════════════════════════════════════════════════════════════════════

    private function getCourseOrFail(string $uuid): array
    {
        $course = $this->course->findByUuidFull($uuid);
        if (!$course) {
            $this->flash('error', 'Course not found.');
            $this->redirect('/admin/courses');
        }
        return $course;
    }

    private function buildCourseData(string $uuid, string $slug, ?string $thumbnail): array
    {
        $p = fn(string $k, mixed $d = '') => $this->request->post($k, $d);
        $authUser = AuthService::user();

        // Verify the session user ID actually exists in the DB.
        // After `migrate.php fresh` the auto-increment resets but old sessions
        // can still carry the previous ID.
        $userId = (int)($authUser['id'] ?? 0);
        if ($userId === 0) {
            $this->flash('error', 'Session error: could not identify current user. Please log in again.');
            $this->redirect('/logout');
        }

        // Confirm the user row exists — cheap single-column SELECT
        $pdo  = \App\Core\Database::getInstance();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        if (!$stmt->fetch()) {
            $this->flash('error', 'Your session user no longer exists in the database. Please log in again.');
            $this->redirect('/logout');
        }

        return [
            'uuid'                => $uuid,
            'title'               => Sanitizer::string($p('title'), 255),
            'slug'                => $slug,
            'description'         => $p('description'),   // Quill HTML
            'short_description'   => Sanitizer::string($p('short_description'), 500),
            'thumbnail'           => $thumbnail,
            'preview_video'       => Sanitizer::string($p('preview_video'), 500),
            'category_id'         => (int)$p('category_id') ?: null,
            'level'               => Sanitizer::string($p('level', 'beginner'), 20),
            'language'            => Sanitizer::string($p('language', 'English'), 50),
            'is_rtl'              => (int)(bool)$p('is_rtl'),
            'status'              => Sanitizer::string($p('status', 'draft'), 20),
            'visibility'          => Sanitizer::string($p('visibility', 'public'), 20),
            'password'            => $p('course_password'),
            'enrollment_type'     => 'admin_only',
            'pass_percentage'     => (int)$p('pass_percentage', 80),
            'certificate_enabled' => (int)(bool)$p('certificate_enabled'),
            'forum_enabled'       => (int)(bool)$p('forum_enabled'),
            'forum_enrolled_only' => (int)(bool)$p('forum_enrolled_only', 1),
            'drip_enabled'        => (int)(bool)$p('drip_enabled'),
            'end_date'            => $p('end_date') ?: null,
            'grade_points'        => (int)$p('grade_points', 0),
            'duration_hours'      => $p('duration_hours') !== '' ? (float)$p('duration_hours') : null,
            'sort_order'          => (int)$p('sort_order', 0),
            'created_by'          => $userId,
        ];
    }
}
