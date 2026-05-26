<?php
define('BASE_URL', '/vtasis/');
require_once '../../includes/auth.php';
requireRole(['admin']);
require_once '../../config/db.php';

$departments = $pdo->query('SELECT id, name FROM departments ORDER BY name')->fetchAll();
$errors = [];
$data = ['faculty_code'=>'','full_name'=>'','email'=>'','phone'=>'','specialization'=>'','department_id'=>'','address'=>''];

if (isPost()) {
    foreach ($data as $k=>$_) $data[$k] = post($k);
    if (!$data['faculty_code']) $errors[] = 'Faculty code is required.';
    if (!$data['full_name'])    $errors[] = 'Full name is required.';
    if (!$data['email'])        $errors[] = 'Email is required.';
    if (!$errors) {
        $chk = $pdo->prepare('SELECT id FROM faculty WHERE faculty_code=?'); $chk->execute([$data['faculty_code']]);
        if ($chk->fetch()) $errors[] = 'Faculty code already exists.';
    }
    if (!$errors) {
        $pdo->beginTransaction();
        try {
            $uStmt = $pdo->prepare('INSERT INTO users (username,password,role,full_name,email,is_active) VALUES (?,?,?,?,?,1)');
            $uStmt->execute([$data['faculty_code'], password_hash('faculty123', PASSWORD_DEFAULT), 'faculty', $data['full_name'], $data['email']]);
            $uid = $pdo->lastInsertId();
            $pdo->prepare('INSERT INTO faculty (user_id,faculty_code,full_name,email,phone,specialization,department_id,address,is_active) VALUES (?,?,?,?,?,?,?,?,1)')
                ->execute([$uid,$data['faculty_code'],$data['full_name'],$data['email'],$data['phone'],$data['specialization'],$data['department_id']?:null,$data['address']]);
            $pdo->commit();
            flash('Faculty added. Default password: <strong>faculty123</strong>','success');
            redirect(BASE_URL . 'modules/faculty/index.php');
        } catch (Exception $e) { $pdo->rollBack(); $errors[] = $e->getMessage(); }
    }
}

$pageTitle = 'Add Faculty';
require_once '../../includes/header.php';
?>
<div class="page-header">
  <div><h1>Add Faculty</h1></div>
  <a href="index.php" class="btn btn-secondary">← Back</a>
</div>
<?php if ($errors): ?><div class="alert alert-error">❌ <?= implode('<br>', array_map('h',$errors)) ?></div><?php endif; ?>
<form method="POST" class="form-card">
  <div class="form-section-title">Faculty Information</div>
  <div class="form-row">
    <div class="form-group">
      <label>Faculty Code *</label>
      <input type="text" name="faculty_code" value="<?= h($data['faculty_code']) ?>" required placeholder="e.g. FAC001">
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
      <input type="text" name="phone" value="<?= h($data['phone']) ?>">
    </div>
  </div>
  <div class="form-row">
    <div class="form-group">
      <label>Specialization</label>
      <input type="text" name="specialization" value="<?= h($data['specialization']) ?>">
    </div>
    <div class="form-group">
      <label>Department</label>
      <select name="department_id">
        <option value="">None</option>
        <?php foreach ($departments as $d): ?>
        <option value="<?= $d['id'] ?>" <?= $data['department_id']==$d['id']?'selected':'' ?>><?= h($d['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="form-group">
    <label>Address</label>
    <textarea name="address"><?= h($data['address']) ?></textarea>
  </div>
  <div class="form-actions">
    <button type="submit" class="btn btn-primary">Save Faculty</button>
    <a href="index.php" class="btn btn-secondary">Cancel</a>
  </div>
</form>
<?php require_once '../../includes/footer.php'; ?>
