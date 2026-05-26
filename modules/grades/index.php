<?php
define('BASE_URL', '/vtasis/');
require_once '../../includes/auth.php';
requireLogin();
require_once '../../config/db.php';

$role = $_SESSION['role'];

if ($role === 'student') {
    $stuStmt = $pdo->prepare('SELECT id FROM students WHERE user_id=?'); $stuStmt->execute([$_SESSION['user_id']]);
    $stuRow = $stuStmt->fetch(); $sid = $stuRow ? $stuRow['id'] : 0;

    $grades = $pdo->prepare('SELECT g.*, c.name AS course_name, c.course_code, e.academic_year, e.semester
        FROM grades g JOIN enrollments e ON e.id=g.enrollment_id JOIN courses c ON c.id=e.course_id
        WHERE e.student_id=? ORDER BY e.academic_year DESC, e.semester, c.name');
    $grades->execute([$sid]); $grades = $grades->fetchAll();

    $gpa = $pdo->prepare('SELECT AVG(g.total_score) FROM grades g JOIN enrollments e ON e.id=g.enrollment_id WHERE e.student_id=?');
    $gpa->execute([$sid]); $avg = round((float)$gpa->fetchColumn(), 2);

    $pageTitle = 'My Grades';
    require_once '../../includes/header.php';
?>
<div class="page-header"><h1>My Grades</h1>
  <?php if ($avg): ?><span class="badge badge-primary" style="font-size:.9rem;padding:.4rem 1rem;">Average: <?= $avg ?></span><?php endif; ?>
</div>
<div class="card">
  <div class="table-wrapper">
    <table class="table">
      <thead><tr><th>Code</th><th>Course</th><th>Year</th><th>Sem</th><th>Midterm (30%)</th><th>Final (50%)</th><th>Assignment (20%)</th><th>Total</th><th>Grade</th></tr></thead>
      <tbody>
      <?php foreach ($grades as $g): ?>
        <tr>
          <td><?= h($g['course_code']) ?></td>
          <td><?= h($g['course_name']) ?></td>
          <td><?= h($g['academic_year']) ?></td>
          <td><?= h($g['semester']) ?></td>
          <td><?= $g['midterm_score']!==null?number_format((float)$g['midterm_score'],1):'—' ?></td>
          <td><?= $g['final_score']!==null?number_format((float)$g['final_score'],1):'—' ?></td>
          <td><?= $g['assignment_score']!==null?number_format((float)$g['assignment_score'],1):'—' ?></td>
          <td><strong><?= $g['total_score']!==null?number_format((float)$g['total_score'],1):'—' ?></strong></td>
          <td><?= $g['grade_letter']?'<span class="grade-'.h($g['grade_letter']).'">'.h($g['grade_letter']).'</span>':'—' ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$grades): ?><tr><td colspan="9"><div class="empty-state"><div class="empty-icon">📝</div><h3>No grades yet</h3></div></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php } elseif (in_array($role,['admin','support','faculty'])) {

    if ($role === 'faculty') {
        $fStmt = $pdo->prepare('SELECT id FROM faculty WHERE user_id=?'); $fStmt->execute([$_SESSION['user_id']]);
        $fRow = $fStmt->fetch(); $fid = $fRow ? $fRow['id'] : 0;
        $courses = $pdo->prepare('SELECT DISTINCT c.id, c.name, c.course_code FROM courses c JOIN course_assignments ca ON ca.course_id=c.id WHERE ca.faculty_id=? ORDER BY c.name');
        $courses->execute([$fid]); $courses = $courses->fetchAll();
    } else {
        $courses = $pdo->query('SELECT id, name, course_code FROM courses WHERE status="active" ORDER BY name')->fetchAll();
    }

    $selectedCourse = (int)get('course');
    $yearFilter = get('year');
    $semFilter  = get('sem');

    $enrollments = [];
    if ($selectedCourse) {
        $where = 'WHERE e.course_id=? AND e.status="enrolled"';
        $params = [$selectedCourse];
        if ($yearFilter) { $where .= ' AND e.academic_year=?'; $params[] = $yearFilter; }
        if ($semFilter)  { $where .= ' AND e.semester=?'; $params[] = $semFilter; }

        $eStmt = $pdo->prepare("SELECT e.id AS enrollment_id, s.student_id_no, s.full_name, e.academic_year, e.semester,
            g.id AS grade_id, g.midterm_score, g.final_score, g.assignment_score, g.total_score, g.grade_letter
            FROM enrollments e JOIN students s ON s.id=e.student_id LEFT JOIN grades g ON g.enrollment_id=e.id
            $where ORDER BY s.full_name");
        $eStmt->execute($params); $enrollments = $eStmt->fetchAll();
    }

    $pageTitle = 'Grades';
    require_once '../../includes/header.php';
?>
<div class="page-header"><h1>Grades</h1></div>

<div class="card" style="margin-bottom:1.5rem;">
  <div class="card-body">
    <form method="GET" class="search-bar">
      <select name="course">
        <option value="">Select Course</option>
        <?php foreach ($courses as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $selectedCourse==$c['id']?'selected':'' ?>><?= h($c['name']) ?> (<?= h($c['course_code']) ?>)</option>
        <?php endforeach; ?>
      </select>
      <input type="text" name="year" value="<?= h($yearFilter) ?>" placeholder="Year e.g. 2024/2025" style="max-width:160px;">
      <select name="sem">
        <option value="">All Semesters</option>
        <?php foreach (['1','2','3'] as $s): ?>
        <option value="<?= $s ?>" <?= $semFilter===$s?'selected':'' ?>>Semester <?= $s ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-primary" type="submit">Load</button>
    </form>
  </div>
</div>

<?php if ($selectedCourse && $enrollments): ?>
<div class="card">
  <div class="card-header"><h2>Grade Entry</h2><span class="text-muted" style="font-size:.875rem;"><?= count($enrollments) ?> students</span></div>
  <div class="table-wrapper">
    <table class="table">
      <thead><tr><th>Student ID</th><th>Name</th><th>Year</th><th>Sem</th><th>Midterm</th><th>Final</th><th>Assignment</th><th>Total</th><th>Grade</th><th>Action</th></tr></thead>
      <tbody>
      <?php foreach ($enrollments as $e): ?>
        <tr>
          <td><?= h($e['student_id_no']) ?></td>
          <td><?= h($e['full_name']) ?></td>
          <td><?= h($e['academic_year']) ?></td>
          <td><?= h($e['semester']) ?></td>
          <td><?= $e['midterm_score']!==null?number_format((float)$e['midterm_score'],1):'—' ?></td>
          <td><?= $e['final_score']!==null?number_format((float)$e['final_score'],1):'—' ?></td>
          <td><?= $e['assignment_score']!==null?number_format((float)$e['assignment_score'],1):'—' ?></td>
          <td><strong><?= $e['total_score']!==null?number_format((float)$e['total_score'],1):'—' ?></strong></td>
          <td><?= $e['grade_letter']?'<span class="grade-'.h($e['grade_letter']).'">'.h($e['grade_letter']).'</span>':'—' ?></td>
          <td><a href="manage.php?enrollment_id=<?= $e['enrollment_id'] ?>" class="btn btn-sm btn-primary"><?= $e['grade_id']?'Edit':'Enter' ?></a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php elseif ($selectedCourse): ?>
<div class="empty-state"><div class="empty-icon">📝</div><h3>No enrolled students found</h3></div>
<?php else: ?>
<div class="empty-state"><div class="empty-icon">📝</div><h3>Select a course to manage grades</h3></div>
<?php endif; ?>

<?php } ?>
<?php require_once '../../includes/footer.php'; ?>
