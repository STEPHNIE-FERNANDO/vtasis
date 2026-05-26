<?php
define('BASE_URL', '/vtasis/');
require_once '../../includes/auth.php';
requireLogin();
require_once '../../config/db.php';

$role = $_SESSION['role'];

if ($role === 'student') {
    $stuStmt = $pdo->prepare('SELECT id FROM students WHERE user_id=?'); $stuStmt->execute([$_SESSION['user_id']]);
    $stuRow = $stuStmt->fetch(); $sid = $stuRow ? $stuRow['id'] : 0;

    $courses = $pdo->prepare('SELECT c.id, c.name, c.course_code,
        COUNT(a.id) AS total,
        SUM(CASE WHEN a.status="present" THEN 1 ELSE 0 END) AS present,
        SUM(CASE WHEN a.status="absent" THEN 1 ELSE 0 END) AS absent
        FROM enrollments e JOIN courses c ON c.id=e.course_id
        LEFT JOIN attendance a ON a.course_id=c.id AND a.student_id=?
        WHERE e.student_id=? AND e.status="enrolled" GROUP BY c.id ORDER BY c.name');
    $courses->execute([$sid,$sid]);
    $courses = $courses->fetchAll();

    $selectedCourse = (int)get('course');
    $log = [];
    if ($selectedCourse) {
        $lStmt = $pdo->prepare('SELECT * FROM attendance WHERE student_id=? AND course_id=? ORDER BY date DESC');
        $lStmt->execute([$sid,$selectedCourse]); $log = $lStmt->fetchAll();
    }

    $pageTitle = 'My Attendance';
    require_once '../../includes/header.php';
?>
<div class="page-header"><h1>My Attendance</h1></div>

<div class="stats-grid">
<?php foreach ($courses as $c):
    $pct = $c['total'] > 0 ? round(($c['present']/$c['total'])*100) : 0;
    $color = $pct >= 75 ? 'green' : ($pct >= 50 ? 'orange' : 'red');
?>
  <div class="stat-card" style="flex-direction:column;gap:.75rem;">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;">
      <div>
        <div style="font-weight:600;font-size:.9rem;"><?= h($c['name']) ?></div>
        <div class="text-muted" style="font-size:.8rem;"><?= h($c['course_code']) ?></div>
      </div>
      <span style="font-size:1.25rem;font-weight:700;color:var(--<?= $color==='green'?'success':($color==='orange'?'warning':'danger') ?>);"><?= $pct ?>%</span>
    </div>
    <div class="progress-bar"><div class="progress-fill <?= $color ?>" style="width:<?= $pct ?>%;"></div></div>
    <div style="font-size:.8rem;color:var(--text-muted);">
      Present: <?= $c['present'] ?> &nbsp;|&nbsp; Absent: <?= $c['absent'] ?> &nbsp;|&nbsp; Total: <?= $c['total'] ?>
    </div>
    <a href="?course=<?= $c['id'] ?>" class="btn btn-sm btn-secondary">View Log</a>
  </div>
<?php endforeach; ?>
<?php if (!$courses): ?><div class="empty-state"><div class="empty-icon">📅</div><h3>No enrolled courses</h3></div><?php endif; ?>
</div>

<?php if ($selectedCourse && $log): ?>
<div class="card mt-2">
  <div class="card-header"><h2>Attendance Log</h2></div>
  <div class="table-wrapper">
    <table class="table">
      <thead><tr><th>Date</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($log as $l): ?>
        <tr>
          <td><?= h($l['date']) ?></td>
          <td>
            <span class="badge badge-<?= $l['status']==='present'?'success':($l['status']==='absent'?'danger':($l['status']==='late'?'warning':'info')) ?>">
              <?= h(ucfirst($l['status'])) ?>
            </span>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php } elseif (in_array($role, ['admin','support','faculty'])) {

    if ($role === 'faculty') {
        $fStmt = $pdo->prepare('SELECT id FROM faculty WHERE user_id=?'); $fStmt->execute([$_SESSION['user_id']]);
        $fRow = $fStmt->fetch(); $fid = $fRow ? $fRow['id'] : 0;
        $myCourses = $pdo->prepare('SELECT DISTINCT c.id, c.name, c.course_code FROM courses c
            JOIN course_assignments ca ON ca.course_id=c.id WHERE ca.faculty_id=? ORDER BY c.name');
        $myCourses->execute([$fid]); $myCourses = $myCourses->fetchAll();
    } else {
        $myCourses = $pdo->query('SELECT id, name, course_code FROM courses WHERE status="active" ORDER BY name')->fetchAll();
    }

    $selectedCourse = (int)get('course');
    $selectedDate   = get('date') ?: date('Y-m-d');

    $pageTitle = 'Attendance';
    require_once '../../includes/header.php';
?>
<div class="page-header">
  <h1>Attendance</h1>
  <a href="mark.php" class="btn btn-primary">Mark Attendance</a>
</div>

<div class="card">
  <div class="card-body" style="padding-bottom:.75rem;">
    <form method="GET" class="search-bar">
      <select name="course">
        <option value="">Select Course</option>
        <?php foreach ($myCourses as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $selectedCourse==$c['id']?'selected':'' ?>><?= h($c['name']) ?> (<?= h($c['course_code']) ?>)</option>
        <?php endforeach; ?>
      </select>
      <input type="date" name="date" value="<?= h($selectedDate) ?>">
      <button class="btn btn-primary" type="submit">View</button>
    </form>
  </div>

  <?php if ($selectedCourse): ?>
  <div class="table-wrapper">
    <table class="table">
      <thead><tr><th>Student ID</th><th>Student Name</th><th>Status</th><th>Date</th></tr></thead>
      <tbody>
      <?php
      $aStmt = $pdo->prepare('SELECT s.student_id_no, s.full_name, a.status, a.date
          FROM enrollments e JOIN students s ON s.id=e.student_id
          LEFT JOIN attendance a ON a.student_id=s.id AND a.course_id=? AND a.date=?
          WHERE e.course_id=? AND e.status="enrolled" ORDER BY s.full_name');
      $aStmt->execute([$selectedCourse,$selectedDate,$selectedCourse]);
      $rows = $aStmt->fetchAll();
      foreach ($rows as $r): ?>
        <tr>
          <td><?= h($r['student_id_no']) ?></td>
          <td><?= h($r['full_name']) ?></td>
          <td><?php if ($r['status']): ?><span class="badge badge-<?= $r['status']==='present'?'success':($r['status']==='absent'?'danger':($r['status']==='late'?'warning':'info')) ?>"><?= h(ucfirst($r['status'])) ?></span><?php else: ?><span class="badge badge-gray">Not Marked</span><?php endif; ?></td>
          <td><?= h($r['date'] ?? '—') ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="4"><div class="empty-state"><h3>No students found</h3></div></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="card-body"><div class="empty-state"><div class="empty-icon">📅</div><h3>Select a course to view attendance</h3></div></div>
  <?php endif; ?>
</div>

<?php } ?>
<?php require_once '../../includes/footer.php'; ?>
