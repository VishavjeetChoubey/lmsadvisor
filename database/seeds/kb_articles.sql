-- ── KB Seed: All LMSAdvisor features documented ──────────────────────────────
SET FOREIGN_KEY_CHECKS = 0;

-- Categories
INSERT INTO kb_categories (name, slug, sort_order) VALUES
('Getting Started',       'getting-started',      1),
('Course Management',     'course-management',    2),
('Student Experience',    'student-experience',   3),
('Quizzes & Assessments', 'quizzes-assessments',  4),
('AI Features',           'ai-features',          5),
('WooCommerce Integration','woocommerce',          6),
('Admin & Settings',      'admin-settings',       7),
('Enterprise Features',   'enterprise',           8),
('Troubleshooting',       'troubleshooting',      9)
ON DUPLICATE KEY UPDATE sort_order=VALUES(sort_order);

SET @admin_id = (SELECT id FROM users WHERE role_id=(SELECT id FROM roles WHERE name IN ('admin','super_admin') LIMIT 1) LIMIT 1);
SET @cat_start   = (SELECT id FROM kb_categories WHERE slug='getting-started');
SET @cat_course  = (SELECT id FROM kb_categories WHERE slug='course-management');
SET @cat_student = (SELECT id FROM kb_categories WHERE slug='student-experience');
SET @cat_quiz    = (SELECT id FROM kb_categories WHERE slug='quizzes-assessments');
SET @cat_ai      = (SELECT id FROM kb_categories WHERE slug='ai-features');
SET @cat_woo     = (SELECT id FROM kb_categories WHERE slug='woocommerce');
SET @cat_admin   = (SELECT id FROM kb_categories WHERE slug='admin-settings');
SET @cat_ent     = (SELECT id FROM kb_categories WHERE slug='enterprise');
SET @cat_trouble = (SELECT id FROM kb_categories WHERE slug='troubleshooting');

INSERT INTO kb_articles (uuid, category_id, title, slug, body, status, created_by) VALUES

-- ── Getting Started ───────────────────────────────────────────────────────────
(UUID(), @cat_start, 'Welcome to LMSAdvisor', 'welcome-to-lmsadvisor',
'<h2>Welcome to LMSAdvisor</h2>
<p>LMSAdvisor is a powerful, self-hosted Learning Management System built for internal training platforms. This guide will help you get up and running quickly.</p>
<h3>What LMSAdvisor Can Do</h3>
<ul>
<li><strong>Create and manage courses</strong> with sections, lessons, quizzes, and assignments</li>
<li><strong>Enroll students</strong> manually or automatically via WooCommerce purchases</li>
<li><strong>Track progress</strong> with detailed analytics and completion certificates</li>
<li><strong>AI-powered tutoring</strong> — students get live help inside every lesson</li>
<li><strong>Corporate training</strong> — assign mandatory courses to departments</li>
</ul>
<h3>Quick Start Checklist</h3>
<ol>
<li>✅ Complete the installer at <code>/install</code></li>
<li>✅ Log in as admin and configure Settings</li>
<li>✅ Create your first course</li>
<li>✅ Add students and enroll them</li>
<li>✅ Run the Database Upgrader to apply all migrations</li>
</ol>
<h3>Need Help?</h3>
<p>Browse the categories on the left or use the search bar above to find answers quickly.</p>',
'published', @admin_id),

(UUID(), @cat_start, 'System Requirements & Installation', 'system-requirements',
'<h2>System Requirements</h2>
<table>
<tr><th>Component</th><th>Requirement</th></tr>
<tr><td>PHP</td><td>8.2 or higher</td></tr>
<tr><td>Database</td><td>MariaDB 10.6+ or MySQL 8.0+</td></tr>
<tr><td>Web Server</td><td>Apache with mod_rewrite enabled</td></tr>
<tr><td>Extensions</td><td>PDO, pdo_mysql, mbstring, openssl, curl, fileinfo</td></tr>
<tr><td>Storage</td><td>Minimum 2GB for uploads and SCORM packages</td></tr>
</table>
<h3>Installation Steps</h3>
<ol>
<li>Upload files to your web server root</li>
<li>Copy <code>.env.example</code> to <code>.env</code> and fill in your database credentials</li>
<li>Set <code>APP_ENV=production</code> in <code>.env</code></li>
<li>Visit <code>yoursite.com/install</code> to run the web installer</li>
<li>The installer creates all database tables and the first admin account</li>
<li>Delete or disable <code>install.php</code> after installation</li>
</ol>
<h3>Apache Configuration</h3>
<p>Make sure your <code>.htaccess</code> file is present and <code>AllowOverride All</code> is set in your Apache config. This is required for URL routing.</p>
<h3>Environment Variables</h3>
<pre>APP_ENV=production
APP_URL=https://yourdomain.com
DB_HOST=localhost
DB_NAME=lmsadvisor
DB_USER=root
DB_PASS=yourpassword
APP_KEY=random-32-char-string</pre>',
'published', @admin_id),

(UUID(), @cat_start, 'First Login & Admin Setup', 'first-login-admin-setup',
'<h2>First Login</h2>
<p>After installation, log in at <code>/login</code> with the admin credentials you set during installation.</p>
<h3>Admin Dashboard</h3>
<p>The admin dashboard shows platform-wide statistics: total students, enrollments, completions, and active learners. The sidebar gives access to all management sections.</p>
<h3>Essential Settings to Configure</h3>
<ol>
<li><strong>Site Settings</strong> — Go to Settings → General and set your site name, logo, and contact email</li>
<li><strong>Email</strong> — Configure SMTP in Settings → Email so welcome emails and password resets work</li>
<li><strong>AI Integration</strong> — Add your Anthropic or OpenAI API key in Settings → AI to enable the AI Tutor</li>
<li><strong>Database</strong> — Visit Admin → Database and click "Run Pending Migrations" to ensure all tables are up to date</li>
</ol>
<h3>Creating Your First Admin User</h3>
<p>Go to Admin → Users → New User. Set the role to <strong>Admin</strong> or <strong>Super Admin</strong>. Super Admins can impersonate other users and manage system settings.</p>',
'published', @admin_id),

-- ── Course Management ─────────────────────────────────────────────────────────
(UUID(), @cat_course, 'Creating and Managing Courses', 'creating-managing-courses',
'<h2>Creating a Course</h2>
<p>Go to <strong>Admin → Courses → New Course</strong>. Fill in:</p>
<ul>
<li><strong>Title</strong> — Clear, descriptive name</li>
<li><strong>Level</strong> — Beginner, Intermediate, or Advanced</li>
<li><strong>Category</strong> — For filtering and recommendations</li>
<li><strong>Duration</strong> — Estimated hours to complete</li>
<li><strong>Grade Points</strong> — Points awarded on completion (for gamification)</li>
</ul>
<h3>Course Structure</h3>
<p>Every course has <strong>Sections</strong> (chapters) containing <strong>Lessons</strong>. A typical structure:</p>
<pre>Course: Web Development Basics
├── Section 1: HTML Fundamentals
│   ├── Lesson: What is HTML? (text)
│   ├── Lesson: Your First Page (video)
│   └── Lesson: HTML Quiz (quiz)
└── Section 2: CSS Styling
    ├── Lesson: Selectors (text)
    └── Lesson: CSS Quiz (quiz)</pre>
<h3>Lesson Types</h3>
<ul>
<li><strong>Text</strong> — Rich content with HTML, images, and code blocks</li>
<li><strong>Video</strong> — YouTube embed or direct upload</li>
<li><strong>Quiz</strong> — Multiple choice, true/false, fill-in-the-blank</li>
<li><strong>Assignment</strong> — File submission or text response</li>
<li><strong>SCORM</strong> — Import SCORM 1.2 or 2004 packages</li>
<li><strong>Document</strong> — PDF or downloadable file</li>
</ul>
<h3>Publishing</h3>
<p>New courses start as <strong>Draft</strong>. Click <strong>Publish</strong> in the top-right of the course editor to make it visible to students.</p>',
'published', @admin_id),

(UUID(), @cat_course, 'Using the AI Course Creator', 'ai-course-creator',
'<h2>AI Course Creator</h2>
<p>LMSAdvisor can generate a complete course — sections, lessons, and quiz questions — from just a topic description.</p>
<h3>How to Use It</h3>
<ol>
<li>Go to <strong>Admin → Courses → New Course</strong></li>
<li>Click the <strong>"Generate with AI"</strong> button at the top</li>
<li>Fill in the generation form:
  <ul>
    <li><strong>Topic</strong> — e.g. "Python for Data Science Beginners"</li>
    <li><strong>Level</strong> — Beginner / Intermediate / Advanced</li>
    <li><strong>Sections</strong> — How many chapters (3–8 recommended)</li>
    <li><strong>Lessons per section</strong> — 3–5 is ideal</li>
    <li><strong>Content types</strong> — Text, Video, Quiz</li>
    <li><strong>Language</strong> — English, Hindi, etc.</li>
  </ul>
</li>
<li>Click <strong>Generate Course</strong> — takes 15–60 seconds</li>
<li>Review the generated outline</li>
<li>Click <strong>Save Course</strong> to create everything in one click</li>
</ol>
<h3>Tips for Better Results</h3>
<ul>
<li>Be specific: "Python pandas for financial analysts" gives better results than just "Python"</li>
<li>Use Extra Instructions to specify prerequisites or what to exclude</li>
<li>Start with 3 sections and 3 lessons — you can always add more</li>
<li>AI-generated text lessons include real HTML content ready to publish</li>
</ul>
<h3>Requirements</h3>
<p>AI features require an Anthropic or OpenAI API key configured in Settings → AI Integration.</p>',
'published', @admin_id),

(UUID(), @cat_course, 'Enrolling Students in Courses', 'enrolling-students',
'<h2>Manual Enrollment</h2>
<p>Admins and managers can enroll any student in any course.</p>
<ol>
<li>Go to <strong>Admin → Courses → [Course] → Students</strong></li>
<li>Click <strong>Enroll Student</strong></li>
<li>Search by name or email</li>
<li>Optionally set an expiry date for time-limited access</li>
<li>Click Enroll</li>
</ol>
<h3>Bulk Enrollment via Organisations</h3>
<p>For corporate training, assign a course to an Organisation. All current members are automatically enrolled:</p>
<ol>
<li>Go to <strong>Admin → Corporate → [Organisation] → Assign Course</strong></li>
<li>Select the course</li>
<li>Set a due date (optional) and mark as mandatory</li>
<li>Click <strong>Assign &amp; Enroll All</strong></li>
</ol>
<h3>WooCommerce Enrollment</h3>
<p>When a student purchases a course via WooCommerce, they are automatically enrolled when the order status changes to <strong>Processing</strong> or <strong>Completed</strong>. An LMS account is created if they don't have one.</p>
<h3>Enrollment Statuses</h3>
<ul>
<li><strong>Active</strong> — Student has access and is learning</li>
<li><strong>Completed</strong> — All lessons finished, certificate issued</li>
<li><strong>Suspended</strong> — Access removed but record kept</li>
<li><strong>Expired</strong> — Enrollment past its expiry date</li>
</ul>',
'published', @admin_id),

-- ── Student Experience ────────────────────────────────────────────────────────
(UUID(), @cat_student, 'The Student Dashboard', 'student-dashboard',
'<h2>Student Dashboard</h2>
<p>After logging in, students land on their personal dashboard at <code>/learn/dashboard</code>.</p>
<h3>What Students See</h3>
<ul>
<li><strong>My Courses</strong> — All enrolled courses with progress bars and resume buttons</li>
<li><strong>Recommended For You</strong> — AI-powered course suggestions based on completed courses</li>
<li><strong>Achievements</strong> — Badges earned and leaderboard position</li>
<li><strong>Streak</strong> — Daily learning streak counter</li>
</ul>
<h3>Course Player</h3>
<p>Clicking <strong>Resume</strong> or <strong>Start</strong> opens the course player at <code>/learn/courses/[uuid]/learn</code>.</p>
<p>The player has:</p>
<ul>
<li><strong>Left sidebar</strong> — Course sections and lessons, progress indicators</li>
<li><strong>Main area</strong> — Lesson content (text, video, quiz, SCORM)</li>
<li><strong>Right panel</strong> — Notes and comments</li>
<li><strong>AI Tutor</strong> — Purple floating button (✦) opens the AI assistant</li>
</ul>
<h3>Completing a Course</h3>
<p>A course is marked complete when all mandatory lessons are finished. A certificate is automatically generated if the course has certificates enabled.</p>',
'published', @admin_id),

(UUID(), @cat_student, 'Using the AI Tutor', 'using-ai-tutor',
'<h2>AI Tutor</h2>
<p>Every lesson has an AI Tutor — a personal assistant that knows your course content and can answer questions in real time.</p>
<h3>Opening the AI Tutor</h3>
<p>Click the <strong>✦ (stars)</strong> floating button in the bottom-right corner of any lesson page.</p>
<h3>What You Can Do</h3>
<ul>
<li><strong>Type a question</strong> — Ask anything about the current lesson</li>
<li><strong>Voice input</strong> — Click the microphone and speak your question (Chrome/Edge)</li>
<li><strong>Listen to answers</strong> — AI responses are read aloud automatically</li>
<li><strong>Summarise</strong> — Click "Summarise" to get bullet points of the lesson</li>
<li><strong>Quiz Me</strong> — Get a practice question to test your understanding</li>
<li><strong>Translate</strong> — Translate the lesson content into Hindi, Spanish, French, and more</li>
</ul>
<h3>Voice Features</h3>
<ul>
<li>The microphone button turns red when recording</li>
<li>Your speech is transcribed and sent automatically</li>
<li>Use the speaker button (🔊) in the top bar to mute/unmute AI voice</li>
<li>Each AI message has a ▶ button to replay it</li>
</ul>
<h3>Tips</h3>
<ul>
<li>The AI knows your lesson content — ask it to explain confusing parts</li>
<li>Say "give me an example" for practical illustrations</li>
<li>Say "quiz me harder" after the Quiz Me chip for more challenging questions</li>
</ul>',
'published', @admin_id),

(UUID(), @cat_student, 'Certificates and Achievements', 'certificates-achievements',
'<h2>Completion Certificates</h2>
<p>Certificates are automatically issued when a student completes all mandatory lessons in a course (if the course has certificates enabled).</p>
<h3>Downloading Your Certificate</h3>
<ol>
<li>Go to the course detail page</li>
<li>Click the <strong>🏆 Certificate</strong> button that appears after completion</li>
<li>Or find it in <strong>My Profile → Certificates</strong></li>
</ol>
<h3>Gamification — Points and Badges</h3>
<p>LMSAdvisor has a built-in points system:</p>
<ul>
<li><strong>Grade Points</strong> — Each course awards points on completion</li>
<li><strong>Badges</strong> — Earned for milestones (First Lesson, Course Complete, 7-Day Streak, etc.)</li>
<li><strong>Leaderboard</strong> — Students are ranked by total points</li>
<li><strong>Streak</strong> — Login and learn every day to build your streak</li>
</ul>
<h3>For Admins</h3>
<p>Enable/disable certificates per course in <strong>Course → Settings tab → Certificate</strong>. Customize badge criteria in <strong>Admin → Gamification → Badges</strong>.</p>',
'published', @admin_id),

-- ── Quizzes ───────────────────────────────────────────────────────────────────
(UUID(), @cat_quiz, 'Creating Quizzes', 'creating-quizzes',
'<h2>Creating a Quiz Lesson</h2>
<ol>
<li>Open a course in the Content Builder</li>
<li>Add a new lesson and set type to <strong>Quiz</strong></li>
<li>Save the lesson — a quiz editor appears</li>
<li>Set: time limit, pass percentage (default 70%), max attempts, shuffle options</li>
<li>Add questions using the <strong>+ Add Question</strong> button</li>
</ol>
<h3>Question Types</h3>
<ul>
<li><strong>Single choice</strong> — One correct answer from multiple options</li>
<li><strong>Multiple choice</strong> — Multiple correct answers</li>
<li><strong>True / False</strong> — Simple binary question</li>
<li><strong>Fill in the blank</strong> — Student types the answer</li>
</ul>
<h3>Adaptive Quizzes</h3>
<p>Enable <strong>Adaptive Mode</strong> in the quiz settings to make difficulty adjust in real time:</p>
<ul>
<li>3 correct answers in a row → next question gets harder</li>
<li>1 wrong answer → next question gets easier</li>
<li>Assign difficulty (Easy / Medium / Hard) to each question</li>
</ul>
<h3>Quiz Results</h3>
<p>After submission, students see their score, which questions were wrong, and the explanation for each answer. Instructors see all attempts in the Quiz Analytics section.</p>',
'published', @admin_id),

-- ── AI Features ───────────────────────────────────────────────────────────────
(UUID(), @cat_ai, 'AI Features Overview', 'ai-features-overview',
'<h2>AI Features in LMSAdvisor</h2>
<p>LMSAdvisor has deep AI integration powered by Anthropic Claude or OpenAI GPT-4.</p>
<h3>Setup</h3>
<ol>
<li>Go to <strong>Admin → Settings → AI Integration</strong></li>
<li>Select provider: Anthropic or OpenAI</li>
<li>Enter your API key</li>
<li>Toggle <strong>Enable AI Features</strong> ON</li>
</ol>
<h3>Available AI Features</h3>
<table>
<tr><th>Feature</th><th>Where</th><th>What it does</th></tr>
<tr><td>AI Tutor</td><td>Every lesson</td><td>Answers questions, summarises, translates, quizzes</td></tr>
<tr><td>AI Course Creator</td><td>New Course</td><td>Generates full course from a topic in 30 seconds</td></tr>
<tr><td>Adaptive Quizzes</td><td>Quiz settings</td><td>Adjusts difficulty per student in real time</td></tr>
<tr><td>Smart Recommendations</td><td>Student dashboard</td><td>Suggests next courses based on history</td></tr>
<tr><td>Drop-out Predictor</td><td>Admin → At-Risk</td><td>Flags students likely to abandon their course</td></tr>
</table>
<h3>Cost Considerations</h3>
<p>AI Tutor calls are made per conversation turn. For a platform with 100 active students, expect roughly $5–20/month in API costs depending on usage. The AI Course Creator uses more tokens per generation (~$0.10–0.50 per course).</p>',
'published', @admin_id),

(UUID(), @cat_ai, 'Drop-out Predictor & At-Risk Students', 'dropout-predictor',
'<h2>Drop-out Predictor</h2>
<p>The predictor scores every active enrollment on a 0–100 risk scale and flags students likely to abandon their course before completion.</p>
<h3>Risk Factors</h3>
<ul>
<li><strong>Days since last login</strong> (30 pts max) — Not logging in is the strongest signal</li>
<li><strong>Days since last lesson</strong> (25 pts) — Progress stalling</li>
<li><strong>Quiz failure rate</strong> (20 pts) — Repeated failures indicate struggle</li>
<li><strong>Progress behind pace</strong> (15 pts) — Too slow relative to time enrolled</li>
<li><strong>Active engagement bonus</strong> (-10 pts) — Taking notes reduces risk score</li>
</ul>
<h3>Risk Levels</h3>
<ul>
<li>🟢 <strong>Low</strong> — Score under 30 — No action needed</li>
<li>🟡 <strong>Medium</strong> — Score 30–60 — Monitor and consider outreach</li>
<li>🔴 <strong>High</strong> — Score 60–80 — Send a re-engagement email</li>
<li>🔴 <strong>Critical</strong> — Score over 80 — Immediate personal outreach recommended</li>
</ul>
<h3>Taking Action</h3>
<ol>
<li>Go to <strong>Admin → At-Risk</strong></li>
<li>Click <strong>Recalculate</strong> to refresh scores</li>
<li>Filter by risk level</li>
<li>Click <strong>Alert</strong> to send a re-engagement email to a specific student</li>
</ol>',
'published', @admin_id),

-- ── WooCommerce ───────────────────────────────────────────────────────────────
(UUID(), @cat_woo, 'WooCommerce Plugin Setup', 'woocommerce-plugin-setup',
'<h2>LMSAdvisor WooCommerce Plugin</h2>
<p>The plugin connects your WooCommerce store to LMSAdvisor so course purchases automatically create student accounts and enroll students.</p>
<h3>Installation</h3>
<ol>
<li>Download the plugin zip from your LMSAdvisor admin</li>
<li>In WordPress, go to <strong>Plugins → Add New → Upload Plugin</strong></li>
<li>Upload and activate the plugin</li>
<li>Go to <strong>WooCommerce → LMSAdvisor</strong> in the WordPress admin</li>
</ol>
<h3>Configuration</h3>
<ol>
<li><strong>LMS URL</strong> — Your LMSAdvisor domain (e.g. <code>https://learn.yoursite.com</code>)</li>
<li><strong>API Token</strong> — Generate one in LMSAdvisor → Admin → API Tokens</li>
<li>Click <strong>Test Connection</strong> — should show "✓ Connected"</li>
</ol>
<h3>Syncing Courses</h3>
<ol>
<li>Go to the <strong>Courses</strong> section in the plugin settings</li>
<li>Click <strong>Load Courses</strong> to see all published LMS courses</li>
<li>Click <strong>Sync Now</strong> to create WooCommerce draft products for each course</li>
<li>Set prices on the draft products and publish them</li>
</ol>
<h3>How Enrollment Works</h3>
<p>When an order reaches <strong>Processing</strong> or <strong>Completed</strong>:</p>
<ol>
<li>Plugin creates an LMS student account (if new buyer)</li>
<li>Student is enrolled in the purchased course</li>
<li>Welcome email is sent</li>
<li>Student can access the course from WooCommerce <strong>My Courses</strong> tab</li>
</ol>',
'published', @admin_id),

(UUID(), @cat_woo, 'My Courses in WooCommerce — Student Guide', 'my-courses-woocommerce',
'<h2>My Courses Tab</h2>
<p>After purchasing a course, students can access it from their WooCommerce account.</p>
<h3>Accessing Your Courses</h3>
<ol>
<li>Log into the WordPress / WooCommerce site</li>
<li>Go to <strong>My Account → My Courses</strong></li>
<li>Your enrolled courses appear with progress and status</li>
</ol>
<h3>What You See</h3>
<ul>
<li><strong>Course name and thumbnail</strong></li>
<li><strong>Status badge</strong> — In Progress, Completed, or Pending</li>
<li><strong>Enrollment date</strong></li>
<li><strong>Progress bar</strong> — % of lessons completed</li>
<li><strong>Start Learning / Resume / Review</strong> button</li>
</ul>
<h3>Start Learning Button</h3>
<p>Clicking Start Learning generates a secure SSO (Single Sign-On) link that logs you into the LMS automatically — no separate LMS login required.</p>
<p>The link is generated fresh each time you click — it expires after 15 minutes for security.</p>
<h3>If You See "Processing…"</h3>
<p>This appears briefly after purchase while enrollment is being processed. Refresh the page after a few seconds. If it persists after 5 minutes, contact support.</p>',
'published', @admin_id),

-- ── Admin & Settings ──────────────────────────────────────────────────────────
(UUID(), @cat_admin, 'Managing Users and Roles', 'managing-users-roles',
'<h2>User Roles</h2>
<table>
<tr><th>Role</th><th>What they can do</th></tr>
<tr><td>Super Admin</td><td>Everything — including system settings and impersonation</td></tr>
<tr><td>Admin</td><td>Manage courses, users, enrollments, reports</td></tr>
<tr><td>Manager</td><td>Create courses, view their own student reports</td></tr>
<tr><td>Student</td><td>Access enrolled courses only</td></tr>
</table>
<h3>Creating a User</h3>
<ol>
<li>Go to <strong>Admin → Users → New User</strong></li>
<li>Fill in name, email, role</li>
<li>Set a temporary password (student will be prompted to change it)</li>
<li>Save — a welcome email is sent automatically</li>
</ol>
<h3>Login as This User (Impersonation)</h3>
<p>Admins can view the LMS exactly as a specific student sees it:</p>
<ol>
<li>Go to <strong>Admin → Users → [User] → Edit</strong></li>
<li>Click <strong>Login as This User</strong></li>
<li>You are now viewing the LMS as that student</li>
<li>A banner at the top shows "You are viewing as [Name]"</li>
<li>Click <strong>Return to Admin</strong> to switch back</li>
</ol>
<h3>Password Reset</h3>
<p>Students can reset their own password via the <strong>Forgot Password</strong> link on the login page. Admins can also manually reset passwords from the user edit page.</p>',
'published', @admin_id),

(UUID(), @cat_admin, 'Database Upgrader', 'database-upgrader',
'<h2>Database Upgrader</h2>
<p>The Database Upgrader at <strong>Admin → Database</strong> manages schema migrations — adding new tables and columns as LMSAdvisor is updated.</p>
<h3>Running Migrations</h3>
<ol>
<li>Go to <strong>Admin → Database</strong></li>
<li>Check the status — pending migrations show a ⏳ badge</li>
<li>Click <strong>Run Pending Migrations</strong></li>
<li>All pending migrations run in order — already-applied ones are skipped</li>
</ol>
<h3>When to Run Migrations</h3>
<ul>
<li>After every LMSAdvisor update</li>
<li>After adding new features that introduce new database tables</li>
<li>If you see "Table not found" errors anywhere in the system</li>
</ul>
<h3>Fresh Installation</h3>
<p>Use <code>database/schema.sql</code> — a single file combining all migrations. Import it in phpMyAdmin or run: <code>mysql -u root -p lmsadvisor &lt; schema.sql</code></p>
<h3>Viewing Migration SQL</h3>
<p>Click the <code>&lt;/&gt;</code> icon next to any migration to see the exact SQL it runs before executing it.</p>',
'published', @admin_id),

(UUID(), @cat_admin, 'Global Search', 'global-search',
'<h2>Global Admin Search</h2>
<p>The search bar in the admin topbar searches across all content simultaneously.</p>
<h3>Using Search</h3>
<ul>
<li>Click the search bar or press <kbd>Ctrl+K</kbd> to focus</li>
<li>Type at least 2 characters — results appear instantly</li>
<li>Press <kbd>Enter</kbd> to see the full results page</li>
<li>Press <kbd>Escape</kbd> to dismiss</li>
</ul>
<h3>What Gets Searched</h3>
<ul>
<li><strong>Courses</strong> — by title and description</li>
<li><strong>Students</strong> — by name and email</li>
<li><strong>Enrollments</strong> — by student name or course title</li>
<li><strong>Lessons</strong> — by lesson title</li>
</ul>
<h3>Full Results Page</h3>
<p>Press Enter or click "See all results →" to open the full results page at <code>/admin/search?q=your+query</code>. Results are grouped by type with direct links to edit pages.</p>',
'published', @admin_id),

-- ── Enterprise ────────────────────────────────────────────────────────────────
(UUID(), @cat_ent, 'Corporate Training Portal', 'corporate-training-portal',
'<h2>Corporate Training Portal</h2>
<p>The Organisations feature lets you manage corporate clients with bulk course assignments, compliance tracking, and CSV reports.</p>
<h3>Creating an Organisation</h3>
<ol>
<li>Go to <strong>Admin → Corporate → New Organisation</strong></li>
<li>Enter name, company domain, seat limit, and billing email</li>
<li>Save</li>
</ol>
<h3>Adding Members</h3>
<p>Add employees to the organisation. They can be assigned the <strong>Employee</strong> or <strong>Manager</strong> role within the org.</p>
<h3>Assigning Mandatory Courses</h3>
<ol>
<li>Open the organisation</li>
<li>In the <strong>Assign Course</strong> panel, select a course</li>
<li>Set a due date (optional) and check Mandatory</li>
<li>Click <strong>Assign &amp; Enroll All</strong> — all current members are enrolled instantly</li>
</ol>
<h3>Compliance Report</h3>
<p>The compliance matrix shows every employee × every assigned course with status (✓ Completed / ▶ In Progress / ⏳ Not Started / Overdue).</p>
<p>Click <strong>Export CSV</strong> to download for HR or auditing purposes.</p>',
'published', @admin_id),

(UUID(), @cat_ent, 'API Developer Portal', 'api-developer-portal',
'<h2>REST API</h2>
<p>LMSAdvisor exposes a REST API for integration with external systems.</p>
<h3>Authentication</h3>
<p>All API requests require a Bearer token:</p>
<pre>Authorization: Bearer YOUR_TOKEN
Content-Type: application/json</pre>
<p>Generate tokens in <strong>Admin → API Tokens → New Token</strong>.</p>
<h3>Key Endpoints</h3>
<table>
<tr><th>Method</th><th>Endpoint</th><th>What it does</th></tr>
<tr><td>GET</td><td>/api/v1/courses</td><td>List published courses</td></tr>
<tr><td>GET</td><td>/api/v1/courses/:uuid</td><td>Course detail with sections+lessons</td></tr>
<tr><td>POST</td><td>/api/v1/users</td><td>Create student account</td></tr>
<tr><td>POST</td><td>/api/v1/enrollments</td><td>Enroll student in course</td></tr>
<tr><td>POST</td><td>/api/v1/auth/sso-token</td><td>Generate SSO login link</td></tr>
<tr><td>GET</td><td>/api/v1/health</td><td>Health check</td></tr>
</table>
<h3>Rate Limits</h3>
<ul>
<li>Standard API: 120 requests per minute</li>
<li>Auth endpoint: 10 requests per 5 minutes (brute force protection)</li>
<li>Exceeded limit returns HTTP 429 with Retry-After header</li>
</ul>
<h3>Full Documentation</h3>
<p>View the complete endpoint reference at <strong>Admin → Marketplace → API Portal</strong>.</p>',
'published', @admin_id),

-- ── Troubleshooting ───────────────────────────────────────────────────────────
(UUID(), @cat_trouble, 'Common Issues and Solutions', 'common-issues',
'<h2>Common Issues</h2>
<h3>❌ "Table not found" errors</h3>
<p><strong>Fix:</strong> Go to Admin → Database and click "Run Pending Migrations". This creates any missing tables.</p>
<h3>❌ Emails not sending</h3>
<p><strong>Fix:</strong> Check Settings → Email. Test your SMTP credentials with the "Send Test Email" button. Common issues:</p>
<ul>
<li>Wrong port — try 587 (TLS) or 465 (SSL)</li>
<li>Firewall blocking outbound SMTP — use a mail service like SendGrid or Mailgun</li>
<li>App password required (Gmail, Outlook) — not your account password</li>
</ul>
<h3>❌ AI Tutor not responding</h3>
<p><strong>Fix:</strong> Check Settings → AI Integration. Ensure:</p>
<ul>
<li>AI Features is toggled ON</li>
<li>API key is correct and has sufficient credits</li>
<li>Provider matches your key (Anthropic key with OpenAI provider = error)</li>
</ul>
<h3>❌ WooCommerce enrollment not working</h3>
<p><strong>Fix:</strong></p>
<ul>
<li>Check WooCommerce → LMSAdvisor → Activity Log for errors</li>
<li>Ensure the product has <code>_lms_course_uuid</code> meta (run Sync Now)</li>
<li>Check order status — enrollment fires on Processing or Completed only</li>
<li>Try the Retry Enrollment button on the order page</li>
</ul>
<h3>❌ Login as User not switching accounts</h3>
<p><strong>Fix:</strong> Clear your browser cookies for the site and try again. The impersonation uses PHP sessions — stale cookies can prevent the switch.</p>
<h3>❌ FOUC (page flashes unstyled briefly)</h3>
<p>This was a known issue fixed in recent versions. Update to the latest LMSAdvisor release.</p>',
'published', @admin_id),

(UUID(), @cat_trouble, 'Password Reset Not Working', 'password-reset-issues',
'<h2>Password Reset Troubleshooting</h2>
<h3>Student says they did not receive the reset email</h3>
<ol>
<li>Check spam/junk folder</li>
<li>Verify SMTP is configured in Admin → Settings → Email</li>
<li>Send a test email from settings to verify SMTP works</li>
<li>Admin can manually set a new password from Admin → Users → [User] → Edit</li>
</ol>
<h3>Reset link says "expired"</h3>
<p>Reset links expire after <strong>1 hour</strong>. The student must request a new link at the login page → "Forgot password?"</p>
<h3>Reset link says "already used"</h3>
<p>Links are single-use. If the student clicked it twice or the browser auto-fetched the link, they need to request a new one.</p>
<h3>Admin resetting a password directly</h3>
<ol>
<li>Go to Admin → Users → find the user → Edit</li>
<li>Scroll to the <strong>Change Password</strong> section</li>
<li>Enter a new temporary password</li>
<li>Tell the student to change it from their Profile page</li>
</ol>',
'published', @admin_id);

SET FOREIGN_KEY_CHECKS = 1;
