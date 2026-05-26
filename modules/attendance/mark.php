<?php
define('BASE_URL', '/vtasis/');
require_once '../../includes/auth.php';
requireRole(['admin','support','faculty']);
require_once '../../config/db.php';

$role = $_SESSION['role'];

if ($role === 'faculty') {
    $fStmt = $pdo->prepare('SELECT id FROM faculty WHERE user_id=?'); $fStmt->execute([$_SESSION['user_id']]);
    $fRow = $fStmt->fetch(); $fid = $fRow ? $fRow['id'] : 0;
    $courses = $pdo->prepare('SELECT DISTINCT c.id, c.name, c.course_code FROM courses c JOIN course_assignments ca ON ca.course_id=c.id WHERE ca.faculty_id=? ORDER BY c.name');
    $courses->execute([$fid]); $courses = $courses->fetchAll();
} else {
    $courses = $pdo->query('SELECT id, name, course_code FROM courses WHERE status="active" ORDER BY name')->fetchAll();
}

$selectedCourse = (int)get('course') ?: (int)post('course');
$selectedDate   = get('date') ?: (post('date') ?: date('Y-m-d'));
$errors = [];

if (isPost() && $selectedCourse && isset($_POST['attendance'])) {
    if (!$selectedDate) $errors[] = 'Date is required.';
    if (!$errors) {
        $pdo->beginTransaction();
        try {
            foreach ($_POST['attendance'] as $studentId => $status) {
                $studentId = (int)$studentId;
                $status = in_array($status,['present','absent','late','excused']) ? $status : 'absent';
                $exists = $pdo->prepare('SELECT id FROM attendance WHERE student_id=? AND course_id=? AND date=?');
                $exists->execute([$studentId,$selectedCourse,$selectedDate]);
                if ($exists->fetch()) {
                    $pdo->prepare('UPDATE attendance SET status=?,marked_by=? WHERE student_id=? AND course_id=? AND date=?')
                        ->execute([$status,$_SESSION['user_id'],$studentId,$selectedCourse,$selectedDate]);
                } else {
                    $pdo->prepare('INSERT INTO attendance (student_id,course_id,date,status,marked_by) VALUES (?,?,?,?,?)')
                        ->execute([$studentId,$selectedCourse,$selectedDate,$status,$_SESSION['user_id']]);
                }
            }
            $pdo->commit();
            flash('Attendance saved for ' . date('d M Y', strtotime($selectedDate)) . '.','success');
            redirect(BASE_URL . 'modules/attendance/mark.php?course=' . $selectedCourse . '&date=' . $selectedDate);
        } catch (Exception $e) { $pdo->rollBack(); $errors[] = $e->getMessage(); }
    }
}

$students = [];
if ($selectedCourse) {
    $sStmt = $pdo->prepare('SELECT s.id, s.student_id_no, s.full_name,
        (SELECT status FROM attendance WHERE student_id=s.id AND course_id=? AND date=?) AS att_status
        FROM enrollments e JOIN students s ON s.id=e.student_id
        WHERE e.course_id=? AND e.status="enrolled" ORDER BY s.full_name');
    $sStmt->execute([$selectedCourse,$selectedDate,$selectedCourse]);
    $students = $sStmt->fetchAll();
}

$pageTitle = 'Mark Attendance';
require_once '../../includes/header.php';
?>
<div class="page-header">
  <div><h1>Mark Attendance</h1></div>
  <a href="index.php" class="btn btn-secondary">← Back</a>
</div>

<?php if ($errors): ?><div class="alert alert-error">❌ <?= implode('<br>', array_map('h',$errors)) ?></div><?php endif; ?>

<div class="card" style="margin-bottom:1.5rem;">
  <div class="card-body">
    <form method="GET" class="search-bar">
      <select name="course" required>
        <option value="">Select Course</option>
        <?php foreach ($courses as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $selectedCourse==$c['id']?'selected':'' ?>><?= h($c['name']) ?> (<?= h($c['course_code']) ?>)</option>
        <?php endforeach; ?>
      </select>
      <input type="date" name="date" value="<?= h($selectedDate) ?>" max="<?= date('Y-m-d') ?>">
      <button class="btn btn-primary" type="submit">Load Students</button>
    </form>
  </div>
</div>

<?php if ($selectedCourse && $students): ?>
<form method="POST">
  <input type="hidden" name="course" value="<?= $selectedCourse ?>">
  <input type="hidden" name="date" value="<?= h($selectedDate) ?>">

  <div class="card">
    <div class="card-header">
      <h2>Attendance — <?= date('d M Y', strtotime($selectedDate)) ?></h2>
      <div class="btn-group no-print">
        <button type="button" onclick="setAll('present')" class="btn btn-sm btn-success">All Present</button>
        <button type="button" onclick="setAll('absent')" class="btn btn-sm btn-danger">All Absent</button>
      </div>
    </div>
    <div class="table-wrapper">
      <table class="table">
        <thead><tr><th>#</th><th>Student ID</th><th>Student Name</th><th>Present</th><th>Absent</th><th>Late</th><th>Excused</th></tr></thead>
        <tbody>
        <?php foreach ($students as $i => $s): $current = $s['att_status'] ?? 'present'; ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td><?= h($s['student_id_no']) ?></td>
            <td><?= h($s['full_name']) ?></td>
            <?php foreach (['present','absent','late','excused'] as $opt): ?>
            <td style="text-align:center;">
              <input type="radio" name="attendance[<?= $s['id'] ?>]" value="<?= $opt ?>" <?= $current===$opt?'checked':'' ?>>
            </td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="card-body" style="padding-top:1rem;">
      <button type="submit" class="btn btn-primary">Save Attendance</button>
      <span class="text-muted" style="margin-left:1rem;font-size:.875rem;"><?= count($students) ?> students</span>
    </div>
  </div>
</form>
<script>
function setAll(status) {
    document.querySelectorAll('input[type=radio][value='+status+']').forEach(r => r.checked = true);
}
</script>
<?php elseif ($selectedCourse && !$students): ?>
<div class="empty-state"><div class="empty-icon">📅</div><h3>No enrolled students in this course</h3></div>
<?php elseif (!$selectedCourse): ?>
<div class="empty-state"><div class="empty-icon">📅</div><h3>Select a course and date to begin</h3></div>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>
