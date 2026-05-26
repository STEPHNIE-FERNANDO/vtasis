<?php
define('BASE_URL', '/vtasis/');
require_once '../../includes/auth.php';
requireRole(['admin','support']);
require_once '../../config/db.php';

$departments = $pdo->query('SELECT id, name FROM departments ORDER BY name')->fetchAll();
$errors = [];
$data = ['course_code'=>'','name'=>'','description'=>'','department_id'=>'','credits'=>'','duration_weeks'=>'','max_students'=>'','status'=>'active'];

if (isPost()) {
    foreach ($data as $k=>$_) $data[$k] = post($k);
    if (!$data['course_code']) $errors[] = 'Course code is required.';
    if (!$data['name'])        $errors[] = 'Course name is required.';
    if ($data['credits'] !== '' && ((int)$data['credits'] < 1 || (int)$data['credits'] > 20)) $errors[] = 'Credits must be 1–20.';
    if (!$errors) {
        $chk = $pdo->prepare('SELECT id FROM courses WHERE course_code=?'); $chk->execute([$data['course_code']]);
        if ($chk->fetch()) $errors[] = 'Course code already exists.';
    }
    if (!$errors) {
        $pdo->prepare('INSERT INTO courses (course_code,name,description,department_id,credits,duration_weeks,max_students,status)
            VALUES (?,?,?,?,?,?,?,?)')
            ->execute([$data['course_code'],$data['name'],$data['description'],$data['department_id']?:null,
                $data['credits']?:null,$data['duration_weeks']?:null,$data['max_students']?:null,$data['status']]);
        flash('Course added successfully.','success');
        redirect(BASE_URL . 'modules/courses/index.php');
    }
}

$pageTitle = 'Add Course';
require_once '../../includes/header.php';
?>
<div class="page-header">
  <div><h1>Add Course</h1></div>
  <a href="index.php" class="btn btn-secondary">← Back</a>
</div>
<?php if ($errors): ?><div class="alert alert-error">❌ <?= implode('<br>', array_map('h',$errors)) ?></div><?php endif; ?>
<form method="POST" class="form-card">
  <div class="form-section-title">Course Information</div>
  <div class="form-row">
    <div class="form-group">
      <label>Course Code *</label>
      <input type="text" name="course_code" value="<?= h($data['course_code']) ?>" required placeholder="e.g. CS101">
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
        <option value="<?= $d['id'] ?>" <?= $data['department_id']==$d['id']?'selected':'' ?>><?= h($d['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label>Status</label>
      <select name="status">
        <option value="active" <?= $data['status']==='active'?'selected':'' ?>>Active</option>
        <option value="inactive" <?= $data['status']==='inactive'?'selected':'' ?>>Inactive</option>
      </select>
    </div>
  </div>
  <div class="form-row">
    <div class="form-group">
      <label>Credits</label>
      <input type="number" name="credits" value="<?= h($data['credits']) ?>" min="1" max="20">
    </div>
    <div class="form-group">
      <label>Duration (weeks)</label>
      <input type="number" name="duration_weeks" value="<?= h($data['duration_weeks']) ?>" min="1">
    </div>
    <div class="form-group">
      <label>Max Students</label>
      <input type="number" name="max_students" value="<?= h($data['max_students']) ?>" min="1">
    </div>
  </div>
  <div class="form-group">
    <label>Description</label>
    <textarea name="description"><?= h($data['description']) ?></textarea>
  </div>
  <div class="form-actions">
    <button type="submit" class="btn btn-primary">Save Course</button>
    <a href="index.php" class="btn btn-secondary">Cancel</a>
  </div>
</form>
<?php require_once '../../includes/footer.php'; ?>
