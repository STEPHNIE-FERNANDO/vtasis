<?php
define('BASE_URL', '/vtasis/');
require_once '../../includes/auth.php';
requireLogin();
require_once '../../config/db.php';

$id = (int)get('id');
if (!$id && $_SESSION['role'] === 'student') {
    $r = $pdo->prepare('SELECT id FROM students WHERE user_id=?');
    $r->execute([$_SESSION['user_id']]);
    $row = $r->fetch();
    $id = $row ? (int)$row['id'] : 0;
}
if (!$id) redirect(BASE_URL . 'modules/students/index.php');

if ($_SESSION['role'] === 'student') {
    $me = $pdo->prepare('SELECT id FROM students WHERE user_id=?');
    $me->execute([$_SESSION['user_id']]);
    $meRow = $me->fetch();
    if (!$meRow || (int)$meRow['id'] !== $id) redirect(BASE_URL . 'modules/dashboard/index.php');
}

$stmt = $pdo->prepare('SELECT s.*, d.name AS dept_name FROM students s LEFT JOIN departments d ON d.id=s.department_id WHERE s.id=?');
$stmt->execute([$id]);
$student = $stmt->fetch();
if (!$student) { flash('Student not found.', 'error'); redirect(BASE_URL . 'modules/students/index.php'); }

$enrollments = $pdo->prepare('SELECT e.*, c.name AS course_name, c.course_code, g.total_score, g.grade_letter
    FROM enrollments e JOIN courses c ON c.id=e.course_id LEFT JOIN grades g ON g.enrollment_id=e.id
    WHERE e.student_id=? ORDER BY e.academic_year DESC, e.semester');
$enrollments->execute([$id]);
$enrollments = $enrollments->fetchAll();

$attTotal = (int)$pdo->prepare('SELECT COUNT(*) FROM attendance WHERE student_id=?')->execute([$id]) ? 0 : 0;
$t = $pdo->prepare('SELECT COUNT(*) FROM attendance WHERE student_id=?'); $t->execute([$id]); $attTotal = (int)$t->fetchColumn();
$p = $pdo->prepare('SELECT COUNT(*) FROM attendance WHERE student_id=? AND status="present"'); $p->execute([$id]); $attPresent = (int)$p->fetchColumn();
$attPct = $attTotal > 0 ? round(($attPresent / $attTotal) * 100) : 0;

$pageTitle = 'Student Profile';
require_once '../../includes/header.php';
?>
<div class="page-header">
  <div><h1><?= h($student['full_name']) ?></h1><p>Student ID: <?= h($student['student_id_no']) ?></p></div>
  <div class="btn-group">
    <?php if (in_array($_SESSION['role'], ['admin','support'])): ?>
    <a href="edit.php?id=<?= $id ?>" class="btn btn-primary">Edit</a>
    <a href="index.php" class="btn btn-secondary">← Back</a>
    <?php endif; ?>
  </div>
</div>

<div style="display:grid;grid-template-columns:280px 1fr;gap:1.5rem;margin-bottom:1.5rem;">
  <div>
    <div class="card" style="margin-bottom:1.25rem;">
      <div class="card-body" style="text-align:center;">
        <div style="width:80px;height:80px;background:var(--primary);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2rem;color:white;margin:0 auto 1rem;">
          <?= strtoupper(substr($student['full_name'],0,1)) ?>
        </div>
        <h2 style="font-size:1.1rem;margin-bottom:.25rem;"><?= h($student['full_name']) ?></h2>
        <p class="text-muted" style="font-size:.875rem;"><?= h($student['student_id_no']) ?></p>
        <span class="badge <?= $student['is_active'] ? 'badge-success' : 'badge-danger' ?>" style="margin-top:.5rem;"><?= $student['is_active'] ? 'Active' : 'Inactive' ?></span>
        <hr style="margin:1rem 0;border-color:var(--border);">
        <div style="text-align:left;font-size:.85rem;display:flex;flex-direction:column;gap:.5rem;">
          <p><span class="text-muted">Dept:</span> <?= h($student['dept_name'] ?? '—') ?></p>
          <p><span class="text-muted">Enrolled:</span> <?= h($student['enrolled_date'] ?? '—') ?></p>
          <p><span class="text-muted">Gender:</span> <?= h(ucfirst($student['gender'] ?? '—')) ?></p>
          <p><span class="text-muted">DOB:</span> <?= h($student['dob'] ?? '—') ?></p>
        </div>
      </div>
    </div>
    <div class="card">
      <div class="card-header"><h2>Attendance</h2></div>
      <div class="card-body">
        <div style="display:flex;justify-content:space-between;font-size:.875rem;margin-bottom:.5rem;">
          <span><?= $attPresent ?>/<?= $attTotal ?> classes</span>
          <strong><?= $attPct ?>%</strong>
        </div>
        <div class="progress-bar">
          <div class="progress-fill <?= $attPct>=75?'green':($attPct>=50?'orange':'red') ?>" style="width:<?= $attPct ?>%;"></div>
        </div>
        <?php if ($attPct < 75 && $attTotal > 0): ?>
        <p style="margin-top:.5rem;font-size:.75rem;color:var(--danger);">⚠️ Below 75% threshold</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div style="display:flex;flex-direction:column;gap:1.25rem;">
    <div class="card">
      <div class="card-header"><h2>Contact Information</h2></div>
      <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;font-size:.875rem;">
        <div><p class="text-muted" style="font-size:.75rem;font-weight:600;text-transform:uppercase;">Email</p><p><?= h($student['email']) ?></p></div>
        <div><p class="text-muted" style="font-size:.75rem;font-weight:600;text-transform:uppercase;">Phone</p><p><?= h($student['phone'] ?? '—') ?></p></div>
        <div><p class="text-muted" style="font-size:.75rem;font-weight:600;text-transform:uppercase;">NIC</p><p><?= h($student['nic'] ?? '—') ?></p></div>
        <div><p class="text-muted" style="font-size:.75rem;font-weight:600;text-transform:uppercase;">Address</p><p><?= h($student['address'] ?? '—') ?></p></div>
        <div><p class="text-muted" style="font-size:.75rem;font-weight:600;text-transform:uppercase;">Guardian</p><p><?= h($student['guardian_name'] ?? '—') ?></p></div>
        <div><p class="text-muted" style="font-size:.75rem;font-weight:600;text-transform:uppercase;">Guardian Phone</p><p><?= h($student['guardian_phone'] ?? '—') ?></p></div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h2>Enrolled Courses</h2></div>
      <div class="table-wrapper">
        <table class="table">
          <thead><tr><th>Code</th><th>Course</th><th>Year</th><th>Sem</th><th>Status</th><th>Score</th><th>Grade</th></tr></thead>
          <tbody>
          <?php foreach ($enrollments as $e): ?>
            <tr>
              <td><?= h($e['course_code']) ?></td>
              <td><?= h($e['course_name']) ?></td>
              <td><?= h($e['academic_year']) ?></td>
              <td><?= h($e['semester']) ?></td>
              <td><span class="badge badge-<?= $e['status']==='enrolled'?'success':($e['status']==='dropped'?'danger':'info') ?>"><?= h(ucfirst($e['status'])) ?></span></td>
              <td><?= $e['total_score']!==null ? number_format((float)$e['total_score'],1) : '—' ?></td>
              <td><?= $e['grade_letter'] ? '<span class="grade-'.h($e['grade_letter']).'">'.h($e['grade_letter']).'</span>' : '—' ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$enrollments): ?><tr><td colspan="7"><div class="empty-state"><h3>No enrollments</h3></div></td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require_once '../../includes/footer.php'; ?>
