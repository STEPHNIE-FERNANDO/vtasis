<?php
/**
 * VTA SIS — One-time Setup Script
 * Visit: http://localhost/vtasis/setup.php
 * DELETE THIS FILE after running it!
 */
if (session_status() === PHP_SESSION_NONE) session_start();

$secret = 'vtasis-setup-2024';
$key = $_GET['key'] ?? '';
if ($key !== $secret) {
    die('<h2 style="font-family:sans-serif;padding:2rem;color:#8B0000;">Access denied. Add ?key=vtasis-setup-2024 to the URL.</h2>');
}

require_once __DIR__ . '/config/db.php';

$messages = [];
$errors   = [];

if (isPost()) {
    try {
        $adminPass   = password_hash('admin123',   PASSWORD_DEFAULT);
        $supportPass = password_hash('admin123',   PASSWORD_DEFAULT);
        $facPass     = password_hash('faculty123', PASSWORD_DEFAULT);
        $stuPass     = password_hash('student123', PASSWORD_DEFAULT);

        // Truncate in correct order
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach (['grades','attendance','enrollments','course_assignments','students','faculty','courses','departments','users','financial_aid'] as $t) {
            $pdo->exec("TRUNCATE TABLE $t");
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');

        // Departments
        $pdo->exec("INSERT INTO departments (name,code,description) VALUES
            ('Information Technology','IT','Software, networking and IT infrastructure'),
            ('Electrical Engineering','EE','Electrical systems and electronics'),
            ('Mechanical Engineering','ME','Mechanical design and manufacturing'),
            ('Civil Construction','CC','Building and civil engineering'),
            ('Business Management','BM','Business, accounting and management')");
        $messages[] = '✅ Departments inserted';

        // Users
        $users = [
            ['admin',       $adminPass,   'admin',   'System Administrator',  'admin@vtasis.lk'],
            ['support1',    $supportPass, 'support', 'Support Officer',       'support@vtasis.lk'],
            ['FAC001',      $facPass,     'faculty', 'Mr. Kamal Perera',      'kamal@vtasis.lk'],
            ['FAC002',      $facPass,     'faculty', 'Ms. Dilini Silva',      'dilini@vtasis.lk'],
            ['FAC003',      $facPass,     'faculty', 'Mr. Nimal Fernando',    'nimal@vtasis.lk'],
            ['VTA2024001',  $stuPass,     'student', 'Amara Kumari',          'amara@student.lk'],
            ['VTA2024002',  $stuPass,     'student', 'Ruwan Dissanayake',     'ruwan@student.lk'],
            ['VTA2024003',  $stuPass,     'student', 'Sanduni Pathirana',     'sanduni@student.lk'],
            ['VTA2024004',  $stuPass,     'student', 'Chamara Wickramasinghe','chamara@student.lk'],
            ['VTA2024005',  $stuPass,     'student', 'Priya Jayawardena',     'priya@student.lk'],
        ];
        $uStmt = $pdo->prepare('INSERT INTO users (username,password,role,full_name,email,is_active) VALUES (?,?,?,?,?,1)');
        foreach ($users as $u) $uStmt->execute($u);
        $messages[] = '✅ Users inserted (10)';

        // Faculty
        $pdo->exec("INSERT INTO faculty (user_id,faculty_code,full_name,email,phone,specialization,department_id,is_active) VALUES
            (3,'FAC001','Mr. Kamal Perera','kamal@vtasis.lk','0771234567','Software Engineering',1,1),
            (4,'FAC002','Ms. Dilini Silva','dilini@vtasis.lk','0772345678','Electronics',2,1),
            (5,'FAC003','Mr. Nimal Fernando','nimal@vtasis.lk','0773456789','Business Studies',5,1)");
        $messages[] = '✅ Faculty inserted (3)';

        // Students
        $pdo->exec("INSERT INTO students (user_id,student_id_no,full_name,email,phone,gender,department_id,enrolled_date,is_active) VALUES
            (6,'VTA2024001','Amara Kumari','amara@student.lk','0774567890','female',1,'2024-01-15',1),
            (7,'VTA2024002','Ruwan Dissanayake','ruwan@student.lk','0775678901','male',1,'2024-01-15',1),
            (8,'VTA2024003','Sanduni Pathirana','sanduni@student.lk','0776789012','female',2,'2024-01-15',1),
            (9,'VTA2024004','Chamara Wickramasinghe','chamara@student.lk','0777890123','male',5,'2024-01-15',1),
            (10,'VTA2024005','Priya Jayawardena','priya@student.lk','0778901234','female',1,'2024-02-01',1)");
        $messages[] = '✅ Students inserted (5)';

        // Courses
        $pdo->exec("INSERT INTO courses (course_code,name,description,department_id,credits,duration_weeks,max_students,status) VALUES
            ('IT101','Introduction to Programming','Basic programming concepts using Python',1,4,16,30,'active'),
            ('IT201','Web Development Fundamentals','HTML, CSS and JavaScript basics',1,4,16,30,'active'),
            ('IT301','Database Management Systems','Relational databases and SQL',1,3,12,25,'active'),
            ('EE101','Basic Electronics','Electronic components and circuits',2,3,14,25,'active'),
            ('BM101','Principles of Management','Management theories and practices',5,3,12,35,'active')");
        $messages[] = '✅ Courses inserted (5)';

        // Course assignments
        $pdo->exec("INSERT INTO course_assignments (course_id,faculty_id,academic_year,semester) VALUES
            (1,1,'2024/2025',1),(2,1,'2024/2025',1),(3,1,'2024/2025',2),(4,2,'2024/2025',1),(5,3,'2024/2025',1)");
        $messages[] = '✅ Course assignments inserted';

        // Enrollments
        $pdo->exec("INSERT INTO enrollments (student_id,course_id,academic_year,semester,status) VALUES
            (1,1,'2024/2025',1,'enrolled'),(1,2,'2024/2025',1,'enrolled'),
            (2,1,'2024/2025',1,'enrolled'),(2,3,'2024/2025',2,'enrolled'),
            (3,4,'2024/2025',1,'enrolled'),(4,5,'2024/2025',1,'enrolled'),
            (5,1,'2024/2025',1,'enrolled'),(5,2,'2024/2025',1,'enrolled')");
        $messages[] = '✅ Enrollments inserted (8)';

        // Attendance
        $pdo->exec("INSERT INTO attendance (student_id,course_id,date,status,marked_by) VALUES
            (1,1,'2024-02-05','present',1),(1,1,'2024-02-12','present',1),
            (1,1,'2024-02-19','absent',1),(1,1,'2024-02-26','present',1),
            (2,1,'2024-02-05','present',1),(2,1,'2024-02-12','absent',1),
            (2,1,'2024-02-19','absent',1),(2,1,'2024-02-26','present',1),
            (5,1,'2024-02-05','present',1),(5,1,'2024-02-12','present',1),
            (5,1,'2024-02-19','present',1),(5,1,'2024-02-26','late',1)");
        $messages[] = '✅ Attendance inserted (12 records)';

        // Grades
        $pdo->exec("INSERT INTO grades (enrollment_id,student_id,course_id,midterm_score,final_score,assignment_score,total_score,grade_letter,academic_year,semester) VALUES
            (1,1,1,72.0,78.5,80.0,77.15,'A','2024/2025',1),
            (3,2,1,60.0,65.0,70.0,64.50,'B','2024/2025',1),
            (7,5,1,85.0,88.0,90.0,87.90,'A','2024/2025',1)");
        $messages[] = '✅ Grades inserted (3 records)';

        $messages[] = '<strong>🎉 Setup complete! All sample data loaded.</strong>';
    } catch (Exception $e) {
        $errors[] = '❌ Error: ' . htmlspecialchars($e->getMessage());
    }
}

function isPost() { return $_SERVER['REQUEST_METHOD'] === 'POST'; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>VTA SIS Setup</title>
  <style>
    body { font-family: sans-serif; max-width: 700px; margin: 2rem auto; padding: 0 1rem; }
    h1 { color: #8B0000; }
    .msg { padding: .75rem 1rem; margin: .5rem 0; border-radius: 6px; }
    .msg.ok  { background: #e8f5e9; color: #1b5e20; }
    .msg.err { background: #ffebee; color: #c62828; }
    .btn { background: #8B0000; color: white; padding: .75rem 2rem; border: none; border-radius: 6px; font-size: 1rem; cursor: pointer; }
    .btn:hover { background: #b71c1c; }
    .warn { background: #fff3e0; border: 1px solid #ff9800; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; }
    table { border-collapse: collapse; width: 100%; margin-top: 1rem; }
    th, td { padding: .5rem .75rem; border: 1px solid #ddd; text-align: left; font-size: .875rem; }
    th { background: #f3f4f6; }
  </style>
</head>
<body>
<h1>🎓 VTA SIS — Database Setup</h1>

<?php if ($messages || $errors): ?>
  <?php foreach ($messages as $m): ?><div class="msg ok"><?= $m ?></div><?php endforeach; ?>
  <?php foreach ($errors as $e): ?><div class="msg err"><?= $e ?></div><?php endforeach; ?>

  <?php if (!$errors): ?>
  <h2>Login Credentials</h2>
  <table>
    <tr><th>Role</th><th>Username</th><th>Password</th></tr>
    <tr><td>Admin</td><td>admin</td><td>admin123</td></tr>
    <tr><td>Support</td><td>support1</td><td>admin123</td></tr>
    <tr><td>Faculty</td><td>FAC001 / FAC002 / FAC003</td><td>faculty123</td></tr>
    <tr><td>Student</td><td>VTA2024001 – VTA2024005</td><td>student123</td></tr>
  </table>
  <p style="margin-top:1.5rem;"><a href="/vtasis/" style="color:#8B0000;font-weight:600;">→ Go to Login Page</a></p>
  <p style="color:#c62828;margin-top:1rem;font-weight:600;">⚠️ DELETE setup.php from the server now!</p>
  <?php endif; ?>

<?php else: ?>
<div class="warn">
  ⚠️ This will <strong>TRUNCATE all tables</strong> and insert fresh sample data.<br>
  Only run this on a fresh install or development environment.
</div>

<h2>This script will:</h2>
<ul style="line-height:2;">
  <li>Create all required tables (run vtasis_db.sql first if not done)</li>
  <li>Insert 5 departments, 3 faculty, 5 students, 5 courses</li>
  <li>Create sample enrollments, attendance, and grade records</li>
  <li>Set up login accounts for all roles</li>
</ul>

<form method="POST">
  <button type="submit" class="btn">Run Setup &amp; Insert Sample Data</button>
</form>
<?php endif; ?>
</body>
</html>
