<?php
define('BASE_URL', '/vtasis/');
require_once '../../includes/auth.php';
requireRole(['admin']);
require_once '../../config/db.php';

$id = (int)get('id');
if (!$id) redirect(BASE_URL . 'modules/faculty/index.php');

$fStmt = $pdo->prepare('SELECT * FROM faculty WHERE id=?'); $fStmt->execute([$id]);
$faculty = $fStmt->fetch();
if (!$faculty) { flash('Faculty not found.','error'); redirect(BASE_URL . 'modules/faculty/index.php'); }

$departments = $pdo->query('SELECT id, name FROM departments ORDER BY name')->fetchAll();
$errors = [];

if (isPost()) {
    $data = ['full_name'=>post('full_name'),'email'=>post('email'),'phone'=>post('phone'),
        'specialization'=>post('specialization'),'department_id'=>post('department_id'),
        'address'=>post('address'),'is_active'=>(int)post('is_active')];
    if (!$data['full_name']) $errors[] = 'Full name is required.';
    if (!$errors) {
        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE users SET full_name=?,email=? WHERE id=?')->execute([$data['full_name'],$data['email'],$faculty['user_id']]);
            $pdo->prepare('UPDATE faculty SET full_name=?,email=?,phone=?,specialization=?,department_id=?,address=?,is_active=? WHERE id=?')
                ->execute([$data['full_name'],$data['email'],$data['phone'],$data['specialization'],
                    $data['department_id']?:null,$data['address'],$data['is_active'],$id]);
            $pdo->commit();
            flash('Faculty updated.','success');
            redirect(BASE_URL . 'modules/faculty/index.php');
        } catch (Exception $e) { $pdo->rollBack(); $errors[] = $e->getMessage(); }
    }
} else {
    $data = $faculty;
}

$pageTitle = 'Edit Faculty';
require_once '../../includes/header.php';
?>
<div class="page-header">
  <div><h1>Edit Faculty</h1><p><?= h($faculty['full_name']) ?></p></div>
  <a href="index.php" class="btn btn-secondary">← Back</a>
</div>
<?php if ($errors): ?><div class="alert alert-error">❌ <?= implode('<br>', array_map('h',$errors)) ?></div><?php endif; ?>
<form method="POST" class="form-card">
  <div class="form-row">
    <div class="form-group">
      <label>Faculty Code</label>
      <input type="text" value="<?= h($faculty['faculty_code']) ?>" disabled style="background:#f3f4f6;">
    </div>
    <div class="form-group">
      <label>Full Name *</label>
      <input type="text" name="full_name" value="<?= h($data['full_name']) ?>" required>
    </div>
  </div>
  <div class="form-row">
    <div class="form-group">
      <label>Email *</label>
      <input type="email" name="email" value="<?= h($data['email']) ?>" required>
    </div>
    <div class="form-group">
      <label>Phone</label>
      <input type="text" name="phone" value="<?= h($data['phone'] ?? '') ?>">
    </div>
  </div>
  <div class="form-row">
    <div class="form-group">
      <label>Specialization</label>
      <input type="text" name="specialization" value="<?= h($data['specialization'] ?? '') ?>">
    </div>
    <div class="form-group">
      <label>Department</label>
      <select name="department_id">
        <option value="">None</option>
        <?php foreach ($departments as $d): ?>
        <option value="<?= $d['id'] ?>" <?= ($data['department_id']??'')==$d['id']?'selected':'' ?>><?= h($d['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="form-group">
    <label>Address</label>
    <textarea name="address"><?= h($data['address'] ?? '') ?></textarea>
  </div>
  <div class="form-group">
    <label>Status</label>
    <select name="is_active">
      <option value="1" <?= ($data['is_active']??1)?'selected':'' ?>>Active</option>
      <option value="0" <?= !($data['is_active']??1)?'selected':'' ?>>Inactive</option>
    </select>
  </div>
  <div class="form-actions">
    <button type="submit" class="btn btn-primary">Update Faculty</button>
    <a href="index.php" class="btn btn-secondary">Cancel</a>
  </div>
</form>
<?php require_once '../../includes/footer.php'; ?>
