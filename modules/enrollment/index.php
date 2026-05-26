<?php
define('BASE_URL', '/vtasis/');
require_once '../../includes/auth.php';
requireLogin();
require_once '../../config/db.php';

$role = $_SESSION['role'];
$yearFilter = get('year');
$semFilter  = get('sem');
$page = max(1,(int)get('page','1'));
$perPage = 25;

if ($role === 'student') {
    $stuStmt = $pdo->prepare('SELECT id FROM students WHERE user_id=?'); $stuStmt->execute([$_SESSION['user_id']]);
    $stuRow = $stuStmt->fetch();
    $sid = $stuRow ? $stuRow['id'] : 0;

    $where = 'WHERE e.student_id=?';
    $params = [$sid];
    if ($yearFilter) { $where .= ' AND e.academic_year=?'; $params[] = $yearFilter; }
    if ($semFilter)  { $where .= ' AND e.semester=?'; $params[] = $semFilter; }

    $cnt = $pdo->prepare("SELECT COUNT(*) FROM enrollments e $where"); $cnt->execute($params);
    $pager = paginate((int)$cnt->fetchColumn(),$perPage,$page);

    $stmt = $pdo->prepare("SELECT e.*, c.name AS course_name, c.course_code, g.total_score, g.grade_letter
        FROM enrollments e JOIN courses c ON c.id=e.course_id LEFT JOIN grades g ON g.enrollment_id=e.id
        $where ORDER BY e.academic_year DESC, e.semester LIMIT {$pager['limit']} OFFSET {$pager['offset']}");
    $stmt->execute($params);
    $enrollments = $stmt->fetchAll();
} else {
    $where = 'WHERE 1=1';
    $params = [];
    if ($yearFilter) { $where .= ' AND e.academic_year=?'; $params[] = $yearFilter; }
    if ($semFilter)  { $where .= ' AND e.semester=?'; $params[] = $semFilter; }

    $cnt = $pdo->prepare("SELECT COUNT(*) FROM enrollments e $where"); $cnt->execute($params);
    $pager = paginate((int)$cnt->fetchColumn(),$perPage,$page);

    $stmt = $pdo->prepare("SELECT e.*, s.full_name AS student_name, s.student_id_no, c.name AS course_name, c.course_code
        FROM enrollments e JOIN students s ON s.id=e.student_id JOIN courses c ON c.id=e.course_id
        $where ORDER BY e.academic_year DESC, e.semester, s.full_name LIMIT {$pager['limit']} OFFSET {$pager['offset']}");
    $stmt->execute($params);
    $enrollments = $stmt->fetchAll();
}

$years = $pdo->query('SELECT DISTINCT academic_year FROM enrollments ORDER BY academic_year DESC')->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = $role === 'student' ? 'My Courses' : 'Enrollment';
require_once '../../includes/header.php';
?>
<div class="page-header">
  <div><h1><?= $pageTitle ?></h1><p><?= $pager['total'] ?> records</p></div>
  <?php if (in_array($role,['admin','support'])): ?>
  <a href="manage.php" class="btn btn-primary">+ Enroll Student</a>
  <?php endif; ?>
</div>

<div class="card">
  <div class="card-body" style="padding-bottom:.75rem;">
    <form method="GET" class="search-bar">
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
      <button class="btn btn-primary" type="submit">Filter</button>
      <?php if ($yearFilter||$semFilter): ?><a href="index.php" class="btn btn-secondary">Clear</a><?php endif; ?>
    </form>
  </div>

  <div class="table-wrapper">
    <table class="table">
      <?php if ($role === 'student'): ?>
      <thead><tr><th>Code</th><th>Course</th><th>Year</th><th>Semester</th><th>Status</th><th>Score</th><th>Grade</th></tr></thead>
      <tbody>
      <?php foreach ($enrollments as $e): ?>
        <tr>
          <td><?= h($e['course_code']) ?></td>
          <td><?= h($e['course_name']) ?></td>
          <td><?= h($e['academic_year']) ?></td>
          <td>Sem <?= h($e['semester']) ?></td>
          <td><span class="badge badge-<?= $e['status']==='enrolled'?'success':($e['status']==='dropped'?'danger':'info') ?>"><?= h(ucfirst($e['status'])) ?></span></td>
          <td><?= $e['total_score']!==null ? number_format((float)$e['total_score'],1) : '—' ?></td>
          <td><?= $e['grade_letter'] ? '<span class="grade-'.h($e['grade_letter']).'">'.h($e['grade_letter']).'</span>' : '—' ?></td>
        </tr>
      <?php endforeach; ?>
      <?php else: ?>
      <thead><tr><th>Student ID</th><th>Student</th><th>Course</th><th>Year</th><th>Semester</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($enrollments as $e): ?>
        <tr>
          <td><?= h($e['student_id_no']) ?></td>
          <td><?= h($e['student_name']) ?></td>
          <td><?= h($e['course_name']) ?> <span class="text-muted">(<?= h($e['course_code']) ?>)</span></td>
          <td><?= h($e['academic_year']) ?></td>
          <td>Sem <?= h($e['semester']) ?></td>
          <td><span class="badge badge-<?= $e['status']==='enrolled'?'success':($e['status']==='dropped'?'danger':'info') ?>"><?= h(ucfirst($e['status'])) ?></span></td>
          <td><a href="manage.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-primary">Edit</a></td>
        </tr>
      <?php endforeach; ?>
      <?php endif; ?>
      <?php if (!$enrollments): ?><tr><td colspan="7"><div class="empty-state"><div class="empty-icon">📋</div><h3>No enrollments found</h3></div></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pager['pages']>1): ?>
  <div style="padding:1rem 1.5rem;">
    <div class="pagination">
      <?php for ($i=1;$i<=$pager['pages'];$i++): ?>
        <a href="?page=<?= $i ?>&year=<?= urlencode($yearFilter) ?>&sem=<?= urlencode($semFilter) ?>" class="<?= $i===$pager['current']?'active':'' ?>"><?= $i ?></a>
      <?php endfor; ?>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php require_once '../../includes/footer.php'; ?>
