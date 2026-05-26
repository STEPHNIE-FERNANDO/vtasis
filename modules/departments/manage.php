<?php
define('BASE_URL', '/vtasis/');
require_once '../../includes/auth.php';
requireRole(['admin','support']);
require_once '../../config/db.php';

$id = (int)get('id');
$dept = null;
$errors = [];

if ($id) {
    $s = $pdo->prepare('SELECT * FROM departments WHERE id=?'); $s->execute([$id]);
    $dept = $s->fetch();
    if (!$dept) { flash('Department not found.','error'); redirect(BASE_URL . 'modules/departments/index.php'); }
}

if (isPost()) {
    $data = ['name'=>post('name'),'code'=>post('code'),'description'=>post('description')];
    if (!$data['name']) $errors[] = 'Department name is required.';
    if (!$errors) {
        if ($id) {
            $pdo->prepare('UPDATE departments SET name=?,code=?,description=? WHERE id=?')
                ->execute([$data['name'],$data['code'],$data['description'],$id]);
            flash('Department updated.','success');
        } else {
            $pdo->prepare('INSERT INTO departments (name,code,description) VALUES (?,?,?)')
                ->execute([$data['name'],$data['code'],$data['description']]);
            flash('Department added.','success');
        }
        redirect(BASE_URL . 'modules/departments/index.php');
    }
} else {
    $data = $dept ?? ['name'=>'','code'=>'','description'=>''];
}

$pageTitle = $id ? 'Edit Department' : 'Add Department';
require_once '../../includes/header.php';
?>
<div class="page-header">
  <div><h1><?= $id ? 'Edit Department' : 'Add Department' ?></h1></div>
  <a href="index.php" class="btn btn-secondary">← Back</a>
</div>
<?php if ($errors): ?><div class="alert alert-error">❌ <?= implode('<br>', array_map('h',$errors)) ?></div><?php endif; ?>
<form method="POST" class="form-card">
  <div class="form-row">
    <div class="form-group">
      <label>Department Name *</label>
      <input type="text" name="name" value="<?= h($data['name']) ?>" required>
    </div>
    <div class="form-group">
      <label>Department Code</label>
      <input type="text" name="code" value="<?= h($data['code'] ?? '') ?>" placeholder="e.g. CS, EE">
    </div>
  </div>
  <div class="form-group">
    <label>Description</label>
    <textarea name="description"><?= h($data['description'] ?? '') ?></textarea>
  </div>
  <div class="form-actions">
    <button type="submit" class="btn btn-primary"><?= $id ? 'Update' : 'Save' ?> Department</button>
    <a href="index.php" class="btn btn-secondary">Cancel</a>
  </div>
</form>
<?php require_once '../../includes/footer.php'; ?>
