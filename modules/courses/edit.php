<?php
define('BASE_URL', '/vtasis/');
require_once '../../includes/auth.php';
requireRole(['admin','support']);
require_once '../../config/db.php';

$id = (int)get('id');
if (!$id) redirect(BASE_URL . 'modules/courses/index.php');

$cStmt = $pdo->prepare('SELECT * FROM courses WHERE id=?'); $cStmt->execute([$id]);
$course = $cStmt->fetch();
if (!$course) { flash('Course not found.','error'); redirect(BASE_URL . 'modules/courses/index.php'); }

$departments = $pdo->query('SELECT id, name FROM departments ORDER BY name')->fetchAll();
$errors = [];

if (isPost()) {
    $data = ['name'=>post('name'),'description'=>post('description'),'department_id'=>post('department_id'),
        'credits'=>post('credits'),'duration_weeks'=>post('duration_weeks'),'max_students'=>post('max_students'),'status'=>post('status')];
    if (!$data['name']) $errors[] = 'Course name is required.';
    if (!$errors) {
        $pdo->prepare('UPDATE courses SET name=?,description=?,department_id=?,credits=?,duration_weeks=?,max_students=?,status=? WHERE id=?')
            ->execute([$data['name'],$data['description'],$data['department_id']?:null,
                $data['credits']?:null,$data['duration_weeks']?:null,$data['max_students']?:null,$data['status'],$id]);
        flash('Course updated.','success');
        redirect(BASE_URL . 'modules/courses/index.php');
    }
} else {
    $data = $course;
}

$pageTitle = 'Edit Course';
require_once '../../includes/header.php';
?>
<div class="page-header">
  <div><h1>Edit Course</h1><p><?= h($course['name']) ?></p></div>
  <a href="index.php" class="btn btn-secondary">← Back</a>
</div>
<?php if ($errors): ?><div class="alert alert-error">❌ <?= implode('<br>', array_map('h',$errors)) ?></div><?php endif; ?>
<form method="POST" class="form-card">
  <div class="form-section-title">Course Information</div>
  <div class="form-row">
    <div class="form-group">
      <label>Course Code</label>
      <input type="text" value="<?= h($course['course_code']) ?>" disabled style="background:#f3f4f6;">
    </div>
    <div class="form-group">
      <label>Course Name *</label>
      <input type="text" name="name" value="<?= h($data['name']) ?>" required>
    </div>
  </div>
  <div class="form-row">
    <div class="form-group">
      <label>Department</label>
      <select name="department_id">
        <option value="">None</option>
        <?php foreach ($departments as $d): ?>
        <option value="<?= $d['id'] ?>" <?= ($data['department_id']??'')==$d['id']?'selected':'' ?>><?= h($d['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label>Status</label>
      <select name="status">
        <option value="active" <?= ($data['status']??'')==='active'?'selected':'' ?>>Active</option>
        <option value="inactive" <?= ($data['status']??'')==='inactive'?'selected':'' ?>>Inactive</option>
      </select>
    </div>
  </div>
  <div class="form-row">
    <div class="form-group">
      <label>Credits</label>
      <input type="number" name="credits" value="<?= h($data['credits']??'') ?>" min="1" max="20">
    </div>
    <div class="form-group">
      <label>Duration (weeks)</label>
      <input type="number" name="duration_weeks" value="<?= h($data['duration_weeks']??'') ?>" min="1">
    </div>
    <div class="form-group">
      <label>Max Students</label>
      <input type="number" name="max_students" value="<?= h($data['max_students']??'') ?>" min="1">
    </div>
  </div>
  <div class="form-group">
    <label>Description</label>
    <textarea name="description"><?= h($data['description']??'') ?></textarea>
  </div>
  <div class="form-actions">
    <button type="submit" class="btn btn-primary">Update Course</button>
    <a href="index.php" class="btn btn-secondary">Cancel</a>
  </div>
</form>
<?php require_once '../../includes/footer.php'; ?>
