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
$router->get('/learn/certificate/:enrollmentId', 'Student\DashboardController@certificate');

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
