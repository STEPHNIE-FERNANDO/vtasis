<?php
define('BASE_URL', '/vtasis/');
require_once '../../includes/auth.php';
requireLogin();
require_once '../../config/db.php';

$role    = $_SESSION['role'];
$userId  = $_SESSION['user_id'];
$stats   = [];
$recentStudents = [];

if (in_array($role, ['admin', 'support'])) {
    $stats[] = ['icon' => '👨‍🎓', 'color' => 'red',    'value' => $pdo->query('SELECT COUNT(*) FROM students WHERE is_active=1')->fetchColumn(),  'label' => 'Active Students'];
    $stats[] = ['icon' => '👨‍🏫', 'color' => 'blue',   'value' => $pdo->query('SELECT COUNT(*) FROM faculty WHERE is_active=1')->fetchColumn(),   'label' => 'Faculty Members'];
    $stats[] = ['icon' => '📚',   'color' => 'green',  'value' => $pdo->query('SELECT COUNT(*) FROM courses WHERE status="active"')->fetchColumn(), 'label' => 'Active Courses'];
    $stats[] = ['icon' => '📋',   'color' => 'orange', 'value' => $pdo->query('SELECT COUNT(*) FROM enrollments WHERE status="enrolled"')->fetchColumn(), 'label' => 'Enrollments'];

    $recentStudents = $pdo->query('SELECT s.full_name, s.student_id_no, d.name AS dept, s.enrolled_date
        FROM students s LEFT JOIN departments d ON d.id=s.department_id
        WHERE s.is_active=1 ORDER BY s.created_at DESC LIMIT 5')->fetchAll();
} elseif ($role === 'faculty') {
    $fac = $pdo->prepare('SELECT id FROM faculty WHERE user_id=?');
    $fac->execute([$userId]);
    $facRow = $fac->fetch();
    if ($facRow) {
        $fid = $facRow['id'];
        $c = $pdo->prepare('SELECT COUNT(*) FROM course_assignments WHERE faculty_id=?');
        $c->execute([$fid]); $stats[] = ['icon'=>'📚','color'=>'red','value'=>$c->fetchColumn(),'label'=>'My Courses'];
        $s = $pdo->prepare('SELECT COUNT(DISTINCT e.student_id) FROM enrollments e JOIN course_assignments ca ON ca.course_id=e.course_id WHERE ca.faculty_id=?');
        $s->execute([$fid]); $stats[] = ['icon'=>'👨‍🎓','color'=>'blue','value'=>$s->fetchColumn(),'label'=>'My Students'];
        $g = $pdo->prepare('SELECT COUNT(*) FROM enrollments e JOIN course_assignments ca ON ca.course_id=e.course_id LEFT JOIN grades gr ON gr.enrollment_id=e.id WHERE ca.faculty_id=? AND gr.id IS NULL AND e.status="enrolled"');
        $g->execute([$fid]); $stats[] = ['icon'=>'📝','color'=>'orange','value'=>$g->fetchColumn(),'label'=>'Pending Grades'];
    }
} elseif ($role === 'student') {
    $stu = $pdo->prepare('SELECT id FROM students WHERE user_id=?');
    $stu->execute([$userId]);
    $stuRow = $stu->fetch();
    if ($stuRow) {
        $sid = $stuRow['id'];
        $e = $pdo->prepare('SELECT COUNT(*) FROM enrollments WHERE student_id=? AND status="enrolled"');
        $e->execute([$sid]); $stats[] = ['icon'=>'📚','color'=>'red','value'=>$e->fetchColumn(),'label'=>'Enrolled Courses'];
        $att = $pdo->prepare('SELECT COUNT(*) FROM attendance WHERE student_id=? AND status="present"');
        $att->execute([$sid]); $pres = (int)$att->fetchColumn();
        $attT = $pdo->prepare('SELECT COUNT(*) FROM attendance WHERE student_id=?');
        $attT->execute([$sid]); $total = (int)$attT->fetchColumn();
        $pct = $total > 0 ? round(($pres/$total)*100) : 0;
        $stats[] = ['icon'=>'📅','color'=>'green','value'=>$pct.'%','label'=>'Attendance Rate'];
        $gpa = $pdo->prepare('SELECT AVG(total_score) FROM grades g JOIN enrollments e ON e.id=g.enrollment_id WHERE e.student_id=?');
        $gpa->execute([$sid]); $avg = round((float)$gpa->fetchColumn(), 1);
        $stats[] = ['icon'=>'📈','color'=>'blue','value'=>$avg ?: '—','label'=>'Average Score'];
    }
}

$pageTitle = 'Dashboard';
require_once '../../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1>Welcome back, <?= h(explode(' ', $_SESSION['full_name'] ?? 'User')[0]) ?>! 👋</h1>
    <p>Here's what's happening at VTA today.</p>
  </div>
  <span class="badge badge-primary" style="font-size:.8rem;padding:.4rem .8rem;"><?= h(ucfirst($role)) ?></span>
</div>

<div class="stats-grid">
<?php foreach ($stats as $s): ?>
  <div class="stat-card">
    <div class="stat-icon <?= h($s['color']) ?>"><?= $s['icon'] ?></div>
    <div class="stat-body">
      <div class="stat-value"><?= h($s['value']) ?></div>
      <div class="stat-label"><?= h($s['label']) ?></div>
    </div>
  </div>
<?php endforeach; ?>
</div>

<?php if ($recentStudents): ?>
<div class="card">
  <div class="card-header">
    <h2>Recently Added Students</h2>
    <a href="<?= BASE_URL ?>modules/students/index.php" class="btn btn-secondary btn-sm">View All</a>
  </div>
  <div class="table-wrapper">
    <table class="table">
      <thead><tr><th>Student ID</th><th>Name</th><th>Department</th><th>Enrolled</th></tr></thead>
      <tbody>
      <?php foreach ($recentStudents as $r): ?>
        <tr>
          <td><?= h($r['student_id_no']) ?></td>
          <td><?= h($r['full_name']) ?></td>
          <td><?= h($r['dept'] ?? '—') ?></td>
          <td><?= h($r['enrolled_date'] ?? '—') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>
