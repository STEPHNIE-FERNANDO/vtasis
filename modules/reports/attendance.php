<?php
define('BASE_URL', '/vtasis/');
require_once '../../includes/auth.php';
requireRole(['admin','support']);
require_once '../../config/db.php';

$courseFilter = (int)get('course');
$yearFilter   = get('year');
$semFilter    = get('sem');

$courses = $pdo->query('SELECT id, course_code, name FROM courses ORDER BY name')->fetchAll();
$years   = $pdo->query('SELECT DISTINCT academic_year FROM enrollments ORDER BY academic_year DESC')->fetchAll(PDO::FETCH_COLUMN);

$rows = [];
if ($courseFilter || isset($_GET['show'])) {
    $eWhere = 'WHERE 1=1';
    $eParams = [];
    if ($courseFilter) { $eWhere .= ' AND e.course_id=?'; $eParams[] = $courseFilter; }
    if ($yearFilter)   { $eWhere .= ' AND e.academic_year=?'; $eParams[] = $yearFilter; }
    if ($semFilter)    { $eWhere .= ' AND e.semester=?'; $eParams[] = $semFilter; }

    $stmt = $pdo->prepare("SELECT s.id AS student_id, s.student_id_no, s.full_name, c.course_code, c.name AS course_name,
        e.academic_year, e.semester,
        COUNT(a.id) AS total_classes,
        SUM(CASE WHEN a.status='present' THEN 1 ELSE 0 END) AS present,
        SUM(CASE WHEN a.status='absent'  THEN 1 ELSE 0 END) AS absent,
        SUM(CASE WHEN a.status='late'    THEN 1 ELSE 0 END) AS late
        FROM enrollments e
        JOIN students s ON s.id=e.student_id
        JOIN courses c ON c.id=e.course_id
        LEFT JOIN attendance a ON a.student_id=s.id AND a.course_id=e.course_id
        $eWhere AND e.status='enrolled'
        GROUP BY s.id, c.id, e.academic_year, e.semester
        ORDER BY c.name, s.full_name");
    $stmt->execute($eParams);
    $rows = $stmt->fetchAll();
}

$pageTitle = 'Attendance Report';
require_once '../../includes/header.php';
?>
<div class="page-header">
  <div><h1>Attendance Report</h1></div>
  <button id="printBtn" class="btn btn-secondary no-print">🖨️ Print</button>
</div>

<div class="card no-print" style="margin-bottom:1.5rem;">
  <div class="card-body">
    <form method="GET" class="search-bar">
      <select name="course">
        <option value="">All Courses</option>
        <?php foreach ($courses as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $courseFilter==$c['id']?'selected':'' ?>><?= h($c['name']) ?> (<?= h($c['course_code']) ?>)</option>
        <?php endforeach; ?>
      </select>
      <select name="year">
        <option value="">All Years</option>
        <?php foreach ($years as $y): ?>
        <option value="<?= h($y) ?>" <?= $yearFilter===$y?'selected':'' ?>><?= h($y) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="sem">
        <option value="">All Semesters</option>
        <?php foreach (['1','2','3'] as $s): ?>
        <option value="<?= $s ?>" <?= $semFilter===$s?'selected':'' ?>>Semester <?= $s ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-primary" type="submit" name="show" value="1">Generate</button>
      <?php if ($courseFilter||$yearFilter||$semFilter||isset($_GET['show'])): ?><a href="attendance.php" class="btn btn-secondary">Clear</a><?php endif; ?>
    </form>
  </div>
</div>

<?php if ($rows): ?>
<div style="margin-bottom:1rem;font-size:.875rem;color:var(--text-muted);">
  Generated: <?= date('d M Y H:i') ?> &nbsp;|&nbsp; <?= count($rows) ?> records
  &nbsp;|&nbsp; <span style="color:var(--danger);font-weight:600;">Red = below 75% attendance</span>
</div>
<div class="card">
  <div class="table-wrapper">
    <table class="table">
      <thead>
        <tr>
          <th>Student ID</th><th>Student Name</th><th>Course</th><th>Year</th><th>Sem</th>
          <th>Total</th><th>Present</th><th>Absent</th><th>Late</th><th>Attendance %</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r):
        $pct = $r['total_classes'] > 0 ? round(($r['present']/$r['total_classes'])*100) : 0;
        $low = $pct < 75 && $r['total_classes'] > 0;
      ?>
        <tr class="<?= $low ? 'att-low' : '' ?>">
          <td><?= h($r['student_id_no']) ?></td>
          <td><?= h($r['full_name']) ?></td>
          <td><?= h($r['course_name']) ?> <span class="text-muted">(<?= h($r['course_code']) ?>)</span></td>
          <td><?= h($r['academic_year']) ?></td>
          <td><?= h($r['semester']) ?></td>
          <td><?= $r['total_classes'] ?></td>
          <td style="color:var(--success);font-weight:600;"><?= $r['present'] ?></td>
          <td style="color:var(--danger);font-weight:600;"><?= $r['absent'] ?></td>
          <td style="color:var(--warning);font-weight:600;"><?= $r['late'] ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:.5rem;">
              <div class="progress-bar" style="width:80px;"><div class="progress-fill <?= $pct>=75?'green':($pct>=50?'orange':'red') ?>" style="width:<?= $pct ?>%;"></div></div>
              <strong><?= $pct ?>%</strong>
              <?php if ($low): ?><span style="color:var(--danger);">⚠️</span><?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php elseif (isset($_GET['show']) || $courseFilter): ?>
<div class="empty-state"><div class="empty-icon">📉</div><h3>No attendance records found</h3></div>
<?php else: ?>
<div class="empty-state"><div class="empty-icon">📉</div><h3>Select filters and click Generate</h3></div>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>
