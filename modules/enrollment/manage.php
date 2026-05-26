<?php
define('BASE_URL', '/vtasis/');
require_once '../../includes/auth.php';
requireRole(['admin','support']);
require_once '../../config/db.php';

$id = (int)get('id');
$enrollment = null;
$errors = [];

if ($id) {
    $es = $pdo->prepare('SELECT e.*, s.full_name AS sname, c.name AS cname FROM enrollments e JOIN students s ON s.id=e.student_id JOIN courses c ON c.id=e.course_id WHERE e.id=?');
    $es->execute([$id]); $enrollment = $es->fetch();
    if (!$enrollment) { flash('Enrollment not found.','error'); redirect(BASE_URL . 'modules/enrollment/index.php'); }
}

$students = $pdo->query('SELECT id, student_id_no, full_name FROM students WHERE is_active=1 ORDER BY full_name')->fetchAll();
$courses  = $pdo->query('SELECT id, course_code, name, max_students FROM courses WHERE status="active" ORDER BY name')->fetchAll();

if (isPost()) {
    $data = ['student_id'=>(int)post('student_id'),'course_id'=>(int)post('course_id'),
        'academic_year'=>post('academic_year'),'semester'=>post('semester'),'status'=>post('status')];

    if (!$data['student_id'])    $errors[] = 'Student is required.';
    if (!$data['course_id'])     $errors[] = 'Course is required.';
    if (!$data['academic_year']) $errors[] = 'Academic year is required.';
    if (!$data['semester'])      $errors[] = 'Semester is required.';

    if (!$errors && !$id) {
        $dup = $pdo->prepare('SELECT id FROM enrollments WHERE student_id=? AND course_id=? AND academic_year=? AND semester=?');
        $dup->execute([$data['student_id'],$data['course_id'],$data['academic_year'],$data['semester']]);
        if ($dup->fetch()) $errors[] = 'Student is already enrolled in this course for the selected year/semester.';
    }

    if (!$errors && !$id) {
        $course = null;
        foreach ($courses as $c) { if ($c['id'] == $data['course_id']) { $course = $c; break; } }
        if ($course && $course['max_students']) {
            $enrolled = $pdo->prepare('SELECT COUNT(*) FROM enrollments WHERE course_id=? AND academic_year=? AND semester=? AND status="enrolled"');
            $enrolled->execute([$data['course_id'],$data['academic_year'],$data['semester']]);
            if ((int)$enrolled->fetchColumn() >= (int)$course['max_students'])
                $errors[] = 'Course has reached maximum capacity (' . $course['max_students'] . ').';
        }
    }

    if (!$errors) {
        if ($id) {
            $pdo->prepare('UPDATE enrollments SET status=?,academic_year=?,semester=? WHERE id=?')
                ->execute([$data['status'],$data['academic_year'],$data['semester'],$id]);
            flash('Enrollment updated.','success');
        } else {
            $pdo->prepare('INSERT INTO enrollments (student_id,course_id,academic_year,semester,status,enrolled_at) VALUES (?,?,?,?,?,NOW())')
                ->execute([$data['student_id'],$data['course_id'],$data['academic_year'],$data['semester'],'enrolled']);
            flash('Student enrolled successfully.','success');
        }
        redirect(BASE_URL . 'modules/enrollment/index.php');
    }
} else {
    $data = $enrollment ?? ['student_id'=>'','course_id'=>'','academic_year'=>date('Y').'/'.((int)date('Y')+1),'semester'=>'1','status'=>'enrolled'];
}

$pageTitle = $id ? 'Edit Enrollment' : 'Enroll Student';
require_once '../../includes/header.php';
?>
<div class="page-header">
  <div><h1><?= $pageTitle ?></h1></div>
  <a href="index.php" class="btn btn-secondary">← Back</a>
</div>
<?php if ($errors): ?><div class="alert alert-error">❌ <?= implode('<br>', array_map('h',$errors)) ?></div><?php endif; ?>
<form method="POST" class="form-card">
  <?php if (!$id): ?>
  <div class="form-row">
    <div class="form-group">
      <label>Student *</label>
      <select name="student_id" required>
        <option value="">Select Student</option>
        <?php foreach ($students as $s): ?>
        <option value="<?= $s['id'] ?>" <?= ($data['student_id']==$s['id'])?'selected':'' ?>><?= h($s['full_name']) ?> (<?= h($s['student_id_no']) ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label>Course *</label>
      <select name="course_id" required>
        <option value="">Select Course</option>
        <?php foreach ($courses as $c): ?>
        <option value="<?= $c['id'] ?>" <?= ($data['course_id']==$c['id'])?'selected':'' ?>><?= h($c['name']) ?> (<?= h($c['course_code']) ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <?php else: ?>
  <div class="alert alert-info">ℹ️ Editing enrollment for <strong><?= h($enrollment['sname']) ?></strong> in <strong><?= h($enrollment['cname']) ?></strong></div>
  <?php endif; ?>
  <div class="form-row">
    <div class="form-group">
      <label>Academic Year *</label>
      <input type="text" name="academic_year" value="<?= h($data['academic_year']) ?>" placeholder="e.g. 2024/2025" required>
    </div>
    <div class="form-group">
      <label>Semester *</label>
      <select name="semester" required>
        <?php foreach (['1','2','3'] as $s): ?>
        <option value="<?= $s ?>" <?= ($data['semester']==$s)?'selected':'' ?>>Semester <?= $s ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php if ($id): ?>
    <div class="form-group">
      <label>Status</label>
      <select name="status">
        <?php foreach (['enrolled','dropped','completed'] as $st): ?>
        <option value="<?= $st ?>" <?= ($data['status']===$st)?'selected':'' ?>><?= ucfirst($st) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
  </div>
  <div class="form-actions">
    <button type="submit" class="btn btn-primary"><?= $id ? 'Update' : 'Enroll' ?></button>
    <a href="index.php" class="btn btn-secondary">Cancel</a>
  </div>
</form>
<?php require_once '../../includes/footer.php'; ?>
