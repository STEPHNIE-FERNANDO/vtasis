<?php
define('BASE_URL', '/vtasis/');
require_once '../../includes/auth.php';
requireRole(['admin','support']);
require_once '../../config/db.php';

$deptFilter = get('dept');
$yearFilter = get('year');
$semFilter  = get('sem');

$departments = $pdo->query('SELECT id, name FROM departments ORDER BY name')->fetchAll();
$years       = $pdo->query('SELECT DISTINCT academic_year FROM enrollments ORDER BY academic_year DESC')->fetchAll(PDO::FETCH_COLUMN);

$where = 'WHERE 1=1';
$params = [];
if ($deptFilter) { $where .= ' AND s.department_id=?'; $params[] = $deptFilter; }

$students = [];
if ($deptFilter || $yearFilter || $semFilter || isset($_GET['show'])) {
    $stmt = $pdo->prepare("SELECT s.id, s.student_id_no, s.full_name, d.name AS dept_name FROM students s LEFT JOIN departments d ON d.id=s.department_id $where AND s.is_active=1 ORDER BY s.full_name");
    $stmt->execute($params);
    $students = $stmt->fetchAll();
}

foreach ($students as &$student) {
    $gWhere = 'WHERE e.student_id=?';
    $gParams = [$student['id']];
    if ($yearFilter) { $gWhere .= ' AND e.academic_year=?'; $gParams[] = $yearFilter; }
    if ($semFilter)  { $gWhere .= ' AND e.semester=?'; $gParams[] = $semFilter; }

    $gStmt = $pdo->prepare("SELECT c.course_code, c.name AS course_name, e.academic_year, e.semester,
        g.midterm_score, g.final_score, g.assignment_score, g.total_score, g.grade_letter
        FROM enrollments e JOIN courses c ON c.id=e.course_id LEFT JOIN grades g ON g.enrollment_id=e.id
        $gWhere ORDER BY e.academic_year DESC, e.semester, c.name");
    $gStmt->execute($gParams);
    $student['grades'] = $gStmt->fetchAll();

    $scores = array_filter(array_column($student['grades'],'total_score'), fn($v) => $v !== null);
    $student['avg'] = count($scores) > 0 ? round(array_sum($scores)/count($scores),1) : null;
}
unset($student);

$pageTitle = 'Academic Report';
require_once '../../includes/header.php';
?>
<div class="page-header">
  <div><h1>Academic Report</h1></div>
  <button id="printBtn" class="btn btn-secondary no-print">🖨️ Print</button>
</div>

<div class="card no-print" style="margin-bottom:1.5rem;">
  <div class="card-body">
    <form method="GET" class="search-bar">
      <select name="dept">
        <option value="">All Departments</option>
        <?php foreach ($departments as $d): ?>
        <option value="<?= $d['id'] ?>" <?= $deptFilter==$d['id']?'selected':'' ?>><?= h($d['name']) ?></option>
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
      <button class="btn btn-primary" type="submit" name="show" value="1">Generate Report</button>
      <?php if ($deptFilter||$yearFilter||$semFilter||isset($_GET['show'])): ?><a href="academic.php" class="btn btn-secondary">Clear</a><?php endif; ?>
    </form>
  </div>
</div>

<?php if (isset($_GET['show']) || $deptFilter || $yearFilter || $semFilter): ?>
<div style="margin-bottom:1rem;font-size:.875rem;color:var(--text-muted);">
  Generated: <?= date('d M Y H:i') ?> &nbsp;|&nbsp; <?= count($students) ?> students
</div>

<?php foreach ($students as $student): ?>
<div class="card" style="margin-bottom:1.5rem;page-break-inside:avoid;">
  <div class="card-header" style="background:var(--primary-bg);">
    <div>
      <strong><?= h($student['full_name']) ?></strong>
      <span class="text-muted" style="margin-left:.75rem;"><?= h($student['student_id_no']) ?></span>
    </div>
    <div style="display:flex;align-items:center;gap:1rem;">
      <span style="font-size:.875rem;">Dept: <?= h($student['dept_name'] ?? '—') ?></span>
      <?php if ($student['avg'] !== null): ?>
      <span class="badge badge-<?= $student['avg']>=65?'success':($student['avg']>=45?'warning':'danger') ?>">
        Avg: <?= $student['avg'] ?>
      </span>
      <?php endif; ?>
    </div>
  </div>
  <?php if ($student['grades']): ?>
  <div class="table-wrapper">
    <table class="table">
      <thead><tr><th>Code</th><th>Course</th><th>Year</th><th>Sem</th><th>Midterm</th><th>Final</th><th>Assignment</th><th>Total</th><th>Grade</th></tr></thead>
      <tbody>
      <?php foreach ($student['grades'] as $g): ?>
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
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="card-body text-muted" style="font-size:.875rem;">No grade records for selected filters.</div>
  <?php endif; ?>
</div>
<?php endforeach; ?>

<?php if (!$students): ?>
<div class="empty-state"><div class="empty-icon">📈</div><h3>No students found for the selected filters</h3></div>
<?php endif; ?>

<?php else: ?>
<div class="empty-state"><div class="empty-icon">📈</div><h3>Select filters and click Generate Report</h3></div>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>
