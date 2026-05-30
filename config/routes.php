<?php
declare(strict_types=1);
use App\Core\Router;
/** @var Router $router */

$router->get('/', 'Auth\LoginController@showLogin');
$router->get('/api/v1/health', 'Api\HealthController@index');

// Auth
$router->get('/login',  'Auth\LoginController@showLogin');
$router->post('/login', 'Auth\LoginController@handleLogin');
$router->get('/logout', 'Auth\LoginController@logout');

// Admin — Dashboard
$router->get('/admin',           'Admin\DashboardController@index');
$router->get('/admin/dashboard', 'Admin\DashboardController@index');

// Admin — Users
$router->get('/admin/users',                      'Admin\UserController@index');
$router->get('/admin/users/create',               'Admin\UserController@create');
$router->post('/admin/users/create',              'Admin\UserController@store');
$router->get('/admin/users/:uuid/edit',           'Admin\UserController@edit');
$router->post('/admin/users/:uuid/edit',          'Admin\UserController@update');
$router->post('/admin/users/:uuid/delete',        'Admin\UserController@delete');
$router->post('/admin/users/:uuid/toggle-active', 'Admin\UserController@toggleActive');
$router->post('/admin/users/:uuid/impersonate',   'Admin\UserController@impersonate');
$router->post('/admin/impersonate/revert',        'Admin\UserController@revertImpersonation');
$router->get('/admin/users/search',               'Admin\EnrollmentController@searchUsers');

// Admin — Settings
$router->get('/admin/settings',             'Admin\SettingsController@index');
$router->post('/admin/settings',            'Admin\SettingsController@save');
$router->post('/admin/settings/test-email', 'Admin\SettingsController@testEmail');

// Database Upgrader
$router->get('/admin/database',             'Admin\DatabaseController@index');
$router->post('/admin/database/run-all',    'Admin\DatabaseController@runAll');
$router->post('/admin/database/run-one',    'Admin\DatabaseController@runOne');
$router->get('/admin/database/view-sql',    'Admin\DatabaseController@viewSql');

// Admin — Categories
$router->get('/admin/courses/categories',             'Admin\CategoryController@index');
$router->post('/admin/courses/categories',            'Admin\CategoryController@store');
$router->post('/admin/courses/categories/:id',        'Admin\CategoryController@update');
$router->post('/admin/courses/categories/:id/delete', 'Admin\CategoryController@delete');

// Admin — Courses
$router->get('/admin/courses',                      'Admin\CourseController@index');
$router->get('/admin/courses/create',               'Admin\CourseController@create');
$router->post('/admin/courses/create',              'Admin\CourseController@store');
$router->post('/admin/courses/import',              'Admin\CourseController@import');
$router->get('/admin/courses/:uuid/edit',           'Admin\CourseController@edit');
$router->post('/admin/courses/:uuid/edit',          'Admin\CourseController@update');
$router->post('/admin/courses/:uuid/thumbnail',     'Admin\CourseController@updateThumbnail');
$router->post('/admin/courses/:uuid/delete',        'Admin\CourseController@delete');
$router->get('/admin/courses/:uuid/preview',        'Admin\CourseController@preview');
$router->get('/admin/courses/:uuid/export',         'Admin\CourseController@export');
$router->post('/admin/courses/:uuid/toggle-status', 'Admin\CourseController@toggleStatus');
$router->get('/admin/courses/:uuid/enrollments',      'Admin\EnrollmentController@courseEnrollmentsPage');
$router->get('/admin/courses/:uuid/enrollments/data', 'Admin\EnrollmentController@courseEnrolled');

// Admin — Sections
$router->post('/admin/courses/:uuid/sections', 'Admin\CourseController@addSection');
$router->post('/admin/sections/:id/update',    'Admin\CourseController@updateSection');
$router->post('/admin/sections/:id/delete',    'Admin\CourseController@deleteSection');
$router->post('/admin/sections/reorder',       'Admin\CourseController@reorderSections');

// Admin — Lessons
$router->post('/admin/sections/:id/lessons', 'Admin\CourseController@addLesson');
$router->post('/admin/lessons/:id/update',   'Admin\CourseController@updateLesson');
$router->post('/admin/lessons/:id/delete',   'Admin\CourseController@deleteLesson');
$router->post('/admin/lessons/reorder',      'Admin\CourseController@reorderLessons');

// Admin — Quiz Builder
$router->get('/admin/quizzes/lesson/:lessonId',  'Admin\QuizController@builder');
$router->get('/admin/quizzes/:id/preview',       'Admin\QuizController@preview');
$router->post('/admin/quizzes/:id/settings',     'Admin\QuizController@saveSettings');
$router->post('/admin/quizzes/:id/questions',    'Admin\QuizController@addQuestion');
$router->post('/admin/quizzes/:id/reorder',      'Admin\QuizController@reorderQuestions');
$router->post('/admin/questions/:id/save',       'Admin\QuizController@saveQuestion');
$router->post('/admin/questions/:id/delete',     'Admin\QuizController@deleteQuestion');

// Admin — Enrollments
$router->get('/admin/enrollments',             'Admin\EnrollmentController@index');
$router->post('/admin/enrollments/enroll',     'Admin\EnrollmentController@enroll');
$router->post('/admin/enrollments/csv',        'Admin\EnrollmentController@importCsv');
$router->post('/admin/enrollments/:id/remove', 'Admin\EnrollmentController@remove');
$router->post('/admin/enrollments/:id/status', 'Admin\EnrollmentController@updateStatus');

// Admin — Forum
$router->get('/admin/forum',                       'Admin\ForumController@index');
$router->get('/admin/forum/threads/:id',           'Admin\ForumController@thread');
$router->post('/admin/forum/threads',              'Admin\ForumController@createThread');
$router->post('/admin/forum/threads/:id/reply',    'Admin\ForumController@createReply');
$router->post('/admin/forum/threads/:id/pin',      'Admin\ForumController@pin');
$router->post('/admin/forum/threads/:id/lock',     'Admin\ForumController@lock');
$router->post('/admin/forum/threads/:id/delete',   'Admin\ForumController@deleteThread');
$router->post('/admin/forum/replies/:id/delete',   'Admin\ForumController@deleteReply');
$router->post('/admin/forum/replies/:id/solution', 'Admin\ForumController@markSolution');

// Admin — Reviews
$router->get('/admin/reviews',                'Admin\ReviewController@index');
$router->post('/admin/reviews/bulk',          'Admin\ReviewController@bulk');
$router->post('/admin/reviews/:id/approve',   'Admin\ReviewController@approve');
$router->post('/admin/reviews/:id/unapprove', 'Admin\ReviewController@unapprove');
$router->post('/admin/reviews/:id/delete',    'Admin\ReviewController@delete');

// Admin — Leaderboard
$router->get('/admin/leaderboard',        'Admin\LeaderboardController@index');
$router->post('/admin/leaderboard/award', 'Admin\LeaderboardController@award');
$router->post('/admin/leaderboard/reset', 'Admin\LeaderboardController@reset');
$router->get('/admin/leaderboard/data',   'Admin\LeaderboardController@data');

// Admin — Reports (Phase 11)
$router->get('/admin/reports',                  'Admin\ReportController@index');
$router->get('/admin/reports/export/:type',     'Admin\ReportController@export');
$router->get('/admin/reports/chart-data',       'Admin\ReportController@chartData');

// Student
$router->get('/learn',                              'Student\DashboardController@index');
$router->get('/learn/dashboard',                    'Student\DashboardController@index');
$router->get('/learn/courses',                      'Student\DashboardController@courses');
$router->get('/learn/courses/:uuid',          'Student\DashboardController@courseDetail');
$router->get('/learn/courses/:uuid/learn',                    'Student\DashboardController@learn');
$router->post('/learn/courses/:uuid/complete-lesson',         'Student\DashboardController@completeLesson');
$router->get('/learn/courses/:uuid/quiz/:lessonId',           'Student\QuizController@show');
$router->post('/learn/courses/:uuid/quiz/:lessonId/submit',   'Student\QuizController@submit');
$router->get('/learn/courses/:uuid/quiz/:lessonId/result',    'Student\QuizController@result');
$router->get('/learn/calendar',                     'Student\DashboardController@calendar');
$router->get('/learn/leaderboard',                  'Student\DashboardController@leaderboard');
$router->get('/learn/profile',                      'Student\DashboardController@profile');

// 404
$router->get('/404', 'ErrorController@notFound');

// Student — Profile update & certificate
$router->post('/learn/profile/update',           'Student\DashboardController@updateProfile');
$router->post('/learn/profile/change-password',  'Student\DashboardController@changePassword');
// Removed duplicate — handled by CertificateController

// Admin — Knowledge Base
$router->get('/admin/knowledge-base', 'Admin\KnowledgeBaseController@index');

// Admin — Course instructor assignment
$router->get('/admin/courses/:uuid/instructors',         'Admin\CourseController@getInstructors');
$router->post('/admin/courses/:uuid/instructors',        'Admin\CourseController@assignInstructor');
$router->post('/admin/courses/:uuid/instructors/remove', 'Admin\CourseController@removeInstructor');

// Student — Forum
$router->get('/learn/courses/:uuid/forum',                      'Student\ForumController@index');
$router->get('/learn/courses/:uuid/forum/threads/:id',          'Student\ForumController@thread');
$router->post('/learn/courses/:uuid/forum/threads',             'Student\ForumController@createThread');
$router->post('/learn/courses/:uuid/forum/threads/:id/reply',   'Student\ForumController@createReply');

// SCORM file serving + API
$router->get('/scorm/:lessonId',           'Student\ScormController@serveFile');
$router->get('/scorm/:lessonId/*filepath',  'Student\ScormController@serveFile');
$router->post('/scorm/api/:lessonId',     'Student\ScormController@api');

// ── Phase 12: Certificate ─────────────────────────────────────────────────────
$router->get('/certificate/verify',      'Student\CertificateController@verify');
$router->get('/certificate/verify/:uuid','Student\CertificateController@verify');
$router->get('/learn/certificate/:enrollmentId', 'Student\CertificateController@show');

// ── Phase 14: AI Course Generation ───────────────────────────────────────────
$router->post('/admin/courses/ai-generate', 'Admin\CourseController@aiGenerate');
$router->post('/admin/courses/ai-save',     'Admin\CourseController@aiSave');

// ── Phase 16: Knowledge Base full CRUD ───────────────────────────────────────
$router->get('/admin/knowledge-base/create',         'Admin\KnowledgeBaseController@create');
$router->post('/admin/knowledge-base/create',        'Admin\KnowledgeBaseController@store');
$router->get('/admin/knowledge-base/categories',     'Admin\KnowledgeBaseController@categories');
$router->post('/admin/knowledge-base/categories',    'Admin\KnowledgeBaseController@categories');
$router->get('/admin/knowledge-base/:uuid/edit',     'Admin\KnowledgeBaseController@edit');
$router->post('/admin/knowledge-base/:uuid/edit',    'Admin\KnowledgeBaseController@update');
$router->post('/admin/knowledge-base/:uuid/delete',  'Admin\KnowledgeBaseController@delete');

// ── Phase 17: REST API ────────────────────────────────────────────────────────
$router->get('/api/v1/health',                   'Api\ProfileApiController@health');
$router->post('/api/v1/auth/token',              'Api\AuthController@token');
$router->delete('/api/v1/auth/token',            'Api\AuthController@revoke');
$router->get('/api/v1/courses',                  'Api\CourseApiController@index');
$router->get('/api/v1/courses/:uuid',            'Api\CourseApiController@show');
$router->get('/api/v1/courses/:uuid/progress',   'Api\CourseApiController@progress');
$router->get('/api/v1/enrollments',              'Api\EnrollmentApiController@index');
$router->post('/api/v1/enrollments',             'Api\EnrollmentApiController@store');
$router->post('/api/v1/lessons/:id/complete',    'Api\LessonApiController@complete');
$router->get('/api/v1/profile',                  'Api\ProfileApiController@show');
$router->get('/api/v1/leaderboard',              'Api\ProfileApiController@leaderboard');

// ── Phase 15: Webinars ────────────────────────────────────────────────────────
$router->get('/admin/webinars',              'Admin\WebinarController@index');
$router->post('/admin/webinars/create',      'Admin\WebinarController@create');
$router->post('/admin/webinars/:uuid/cancel','Admin\WebinarController@cancel');
$router->post('/admin/webinars/:uuid/start', 'Admin\WebinarController@start');

// ── Phase 18: Notification API ────────────────────────────────────────────────
$router->get('/api/notifications',           'Api\NotificationApiController@index');
$router->get('/api/notifications/count',     'Api\NotificationApiController@unreadCount');
$router->post('/api/notifications/read-all', 'Api\NotificationApiController@readAll');
$router->post('/api/notifications/:id/read', 'Api\NotificationApiController@read');

// ── Student review submission ─────────────────────────────────────────────────
$router->post('/learn/courses/:uuid/review', 'Student\DashboardController@submitReview');

// ── Admin API Management ──────────────────────────────────────────────────────
$router->get('/admin/api',                      'Admin\ApiController@index');
$router->get('/admin/api/docs',                 'Admin\ApiController@docs');
$router->post('/admin/api/tokens/create',       'Admin\ApiController@createToken');
$router->post('/admin/api/tokens/:id/revoke',   'Admin\ApiController@revokeToken');
$router->post('/admin/api/tokens/:id/rotate',   'Admin\ApiController@rotateToken');

// ── Full REST API v1 ──────────────────────────────────────────────────────────
// Users
$router->get('/api/v1/users',                            'Api\UserApiController@index');
$router->get('/api/v1/users/:uuid',                      'Api\UserApiController@show');
// Quizzes
$router->get('/api/v1/courses/:uuid/quizzes',            'Api\QuizApiController@index');
$router->get('/api/v1/quizzes/:id/results',              'Api\QuizApiController@results');
// Forum
$router->get('/api/v1/courses/:uuid/forum/threads',      'Api\ForumApiController@threads');
$router->post('/api/v1/courses/:uuid/forum/threads',     'Api\ForumApiController@createThread');
$router->get('/api/v1/forum/threads/:id',                'Api\ForumApiController@thread');
// Reviews
$router->get('/api/v1/courses/:uuid/reviews',            'Api\ReviewApiController@index');
$router->post('/api/v1/courses/:uuid/reviews',           'Api\ReviewApiController@store');
// Knowledge Base
$router->get('/api/v1/kb/articles',                      'Api\KbApiController@index');
$router->get('/api/v1/kb/articles/:uuid',                'Api\KbApiController@show');
// Webinars
$router->get('/api/v1/webinars',                         'Api\WebinarApiController@index');
$router->get('/api/v1/webinars/:uuid',                   'Api\WebinarApiController@show');
// Certificates
$router->get('/api/v1/certificates',                     'Api\CertificateApiController@index');
$router->get('/api/v1/certificates/:uuid/verify',        'Api\CertificateApiController@verify');


// Admin categories + quizzes list pages (sidebar links)
$router->get('/admin/categories', 'Admin\CategoryController@listPage');
$router->get('/admin/quizzes',    'Admin\QuizController@listPage');

// Profile avatar upload
$router->post('/learn/profile/avatar', 'Student\DashboardController@uploadAvatar');

// User session history
$router->get('/admin/users/:uuid/sessions', 'Admin\UserController@sessions');

// User CSV export
$router->get('/admin/users/export', 'Admin\UserController@export');

// Student GDPR data export
$router->get('/learn/profile/export', 'Student\DashboardController@exportData');

// Analytics
$router->get('/admin/analytics',       'Admin\AnalyticsController@index');
$router->post('/admin/analytics/purge','Admin\AnalyticsController@purge');

// ── Phase 19: Email Notifications ────────────────────────────────────────────
$router->get('/admin/email',                                    'Admin\EmailController@index');
$router->get('/admin/email/templates/:slug/edit',               'Admin\EmailController@editTemplate');
$router->post('/admin/email/templates/:slug/save',              'Admin\EmailController@saveTemplate');
$router->post('/admin/email/test',                              'Admin\EmailController@sendTest');
$router->post('/admin/email/process-queue',                     'Admin\EmailController@processQueue');
$router->get('/unsubscribe/:token',                             'Auth\UnsubscribeController@handle');

// ── Phase 21: Learning Paths ──────────────────────────────────────────────────
$router->get('/admin/learning-paths',                           'Admin\LearningPathController@index');
$router->get('/admin/learning-paths/create',                    'Admin\LearningPathController@create');
$router->post('/admin/learning-paths',                          'Admin\LearningPathController@store');
$router->get('/admin/learning-paths/:uuid/edit',                'Admin\LearningPathController@edit');
$router->post('/admin/learning-paths/:uuid/update',             'Admin\LearningPathController@update');
$router->post('/admin/learning-paths/:uuid/delete',             'Admin\LearningPathController@delete');
$router->get('/learn/paths',                                    'Student\PathController@index');
$router->get('/learn/paths/:uuid',                              'Student\PathController@detail');
$router->post('/learn/paths/:uuid/enroll',                      'Student\PathController@enroll');

// ── Phase 22: Groups ──────────────────────────────────────────────────────────
$router->get('/admin/groups',                                   'Admin\GroupController@index');
$router->get('/admin/groups/create',                            'Admin\GroupController@create');
$router->post('/admin/groups',                                  'Admin\GroupController@store');
$router->get('/admin/groups/:id/edit',                          'Admin\GroupController@edit');
$router->post('/admin/groups/:id/update',                       'Admin\GroupController@update');
$router->post('/admin/groups/:id/members',                      'Admin\GroupController@addMember');
$router->post('/admin/groups/:id/members/remove',               'Admin\GroupController@removeMember');
$router->post('/admin/groups/:id/courses',                      'Admin\GroupController@assignCourse');
$router->post('/admin/groups/:id/courses/remove',               'Admin\GroupController@removeCourse');
$router->get('/admin/groups/:id/report',                        'Admin\GroupController@report');
$router->post('/admin/groups/:id/delete',                       'Admin\GroupController@delete');

// ── Phase 24: Badges ─────────────────────────────────────────────────────────
$router->get('/admin/badges',                                   'Admin\BadgeController@index');
$router->post('/admin/badges',                                  'Admin\BadgeController@store');
$router->post('/admin/badges/:id/update',                       'Admin\BadgeController@update');
$router->post('/admin/badges/:id/delete',                       'Admin\BadgeController@delete');
$router->post('/admin/badges/:id/award',                        'Admin\BadgeController@awardManual');

// ── Phase 25: Assignments ────────────────────────────────────────────────────
$router->get('/admin/courses/:uuid/assignments',                'Admin\AssignmentController@index');
$router->post('/admin/courses/:uuid/assignments/:sub_id/grade', 'Admin\AssignmentController@grade');
$router->get('/learn/courses/:uuid/assignments/:lesson_id',     'Student\AssignmentController@show');
$router->post('/learn/courses/:uuid/assignments/:lesson_id/submit', 'Student\AssignmentController@submit');

// ── Phase 20: Advanced Learner Analytics ─────────────────────────────────────
$router->get('/admin/learner-analytics',              'Admin\LearnerAnalyticsController@index');
$router->get('/admin/learner-analytics/course/:uuid', 'Admin\LearnerAnalyticsController@course');

// ── Phase 23: Collaboration — Notes & Comments (API) ─────────────────────────
$router->get('/api/lessons/:lesson_id/notes',                    'Api\CollaborationApiController@getNotes');
$router->post('/api/lessons/:lesson_id/notes',                   'Api\CollaborationApiController@saveNote');
$router->post('/api/notes/:note_id/delete',                      'Api\CollaborationApiController@deleteNote');
$router->get('/api/lessons/:lesson_id/notes/export/:course_id',  'Api\CollaborationApiController@exportNotes');
$router->get('/api/lessons/:lesson_id/comments',                 'Api\CollaborationApiController@getComments');
$router->post('/api/lessons/:lesson_id/comments',                'Api\CollaborationApiController@addComment');
$router->post('/api/comments/:comment_id/delete',                'Api\CollaborationApiController@deleteComment');
$router->post('/api/comments/:comment_id/pin',                   'Api\CollaborationApiController@pinComment');
$router->post('/api/lessons/:lesson_id/ask',                     'Api\CollaborationApiController@askQuestion');

// ── Phase 24: Public learner profile ─────────────────────────────────────────
$router->get('/profile/:uuid',                                   'Student\PublicProfileController@show');

// ── Phase 29: AI Tutor & Personalization ─────────────────────────────────────
$router->post('/api/ai/chat',               'Api\AiTutorController@chat');
$router->post('/api/ai/summarise',          'Api\AiTutorController@summarise');
$router->post('/api/ai/generate-questions', 'Api\AiTutorController@generateQuestions');
$router->post('/api/ai/translate',          'Api\AiTutorController@translate');
$router->post('/api/ai/improve-writing',    'Api\AiTutorController@improveWriting');
$router->get('/api/ai/recommend-paths',     'Api\AiTutorController@recommendPaths');

// ── Phase 30: Webhooks & Integrations ────────────────────────────────────────
$router->get('/admin/webhooks',                    'Admin\WebhookController@index');
$router->post('/admin/webhooks',                   'Admin\WebhookController@store');
$router->post('/admin/webhooks/:id/update',        'Admin\WebhookController@update');
$router->post('/admin/webhooks/:id/delete',        'Admin\WebhookController@delete');
$router->post('/admin/webhooks/:id/test',          'Admin\WebhookController@test');
$router->post('/admin/webhooks/:id/rotate-secret', 'Admin\WebhookController@rotateSecret');
$router->get('/admin/webhooks/:id/logs',           'Admin\WebhookController@logs');

// ── Social Login (Google + GitHub OAuth) ─────────────────────────────────────
$router->get('/auth/google',          'Auth\SocialController@googleRedirect');
$router->get('/auth/google/callback', 'Auth\SocialController@googleCallback');
$router->get('/auth/github',          'Auth\SocialController@githubRedirect');
$router->get('/auth/github/callback', 'Auth\SocialController@githubCallback');

// ── WooCommerce Plugin API endpoints ─────────────────────────────────────────
$router->post('/api/v1/users',            'Api\UserApiController@create');
$router->put('/api/v1/users/:uuid',       'Api\UserApiController@update');
$router->post('/api/v1/auth/sso-token',   'Api\AuthApiController@ssoToken');
$router->get('/sso/login',                'Auth\SsoController@handle');

// Drop-out Predictor
$router->get('/admin/dropout',              'Admin\DropoutController@index');
$router->post('/admin/dropout/recalculate', 'Admin\DropoutController@recalculate');
$router->post('/admin/dropout/alert',       'Admin\DropoutController@sendAlert');

// Recommendations API
$router->get('/api/v1/recommendations',     'Api\RecommendationApiController@index');
$router->post('/api/v1/recommendations/:course_id/dismiss', 'Api\RecommendationApiController@dismiss');

// Multi-tenant
$router->get('/admin/tenants',                  'Admin\TenantController@index');
$router->get('/admin/tenants/create',           'Admin\TenantController@create');
$router->post('/admin/tenants',                 'Admin\TenantController@store');
$router->get('/admin/tenants/:uuid/edit',       'Admin\TenantController@edit');
$router->post('/admin/tenants/:uuid/update',    'Admin\TenantController@update');

// Corporate Training
$router->get('/admin/organisations',                    'Admin\OrganisationController@index');
$router->post('/admin/organisations',                   'Admin\OrganisationController@store');
$router->get('/admin/organisations/:uuid',              'Admin\OrganisationController@show');
$router->post('/admin/organisations/:uuid/assign',      'Admin\OrganisationController@assignCourse');
$router->get('/admin/organisations/:uuid/export',       'Admin\OrganisationController@exportReport');

// API Marketplace
$router->get('/admin/marketplace/api',                           'Admin\MarketplaceController@apiPortal');
$router->get('/admin/marketplace/instructors',                   'Admin\MarketplaceController@instructors');
$router->post('/admin/marketplace/instructors/:id/review',       'Admin\MarketplaceController@reviewApplication');

// Executive Reporting
$router->get('/admin/reporting',            'Admin\ReportingController@index');
$router->get('/admin/reporting/chart-data', 'Admin\ReportingController@chartData');

// Password reset
$router->get('/forgot-password',         'Auth\PasswordResetController@forgot');
$router->post('/forgot-password',        'Auth\PasswordResetController@sendReset');
$router->get('/reset-password',          'Auth\PasswordResetController@resetForm');
$router->post('/reset-password',         'Auth\PasswordResetController@doReset');

// Student profile
$router->get('/learn/profile',           'Student\ProfileController@show');
$router->post('/learn/profile/update',   'Student\ProfileController@update');
$router->post('/learn/profile/password', 'Student\ProfileController@changePassword');

// Global admin search
$router->get('/admin/search',            'Admin\SearchController@page');
$router->get('/admin/search/api',        'Admin\SearchController@search');

// Public Help Center (no auth required)
$router->get('/help',                      'HelpController@index');
$router->get('/help/search',               'HelpController@search');
$router->get('/help/category/:slug',       'HelpController@category');
$router->get('/help/article/:slug',        'HelpController@article');

// Menu permissions settings (super_admin only)
$router->get('/admin/menu-settings',       'Admin\MenuSettingsController@index');
$router->post('/admin/menu-settings',      'Admin\MenuSettingsController@save');
$router->post('/admin/menu-settings/reset','Admin\MenuSettingsController@reset');

// Assignments overview
$router->get('/admin/assignments', 'Admin\AssignmentsOverviewController@index');
$router->post('/admin/organisations/:uuid/add-member', 'Admin\OrganisationController@addMember');
