<?php
define('BASE_URL', '/vtasis/');
require_once '../../includes/auth.php';
requireRole(['admin', 'support']);
require_once '../../config/db.php';

$id = (int)get('id');
if (!$id) redirect(BASE_URL . 'modules/students/index.php');

$stuStmt = $pdo->prepare('SELECT s.*, u.username FROM students s JOIN users u ON u.id=s.user_id WHERE s.id=?');
$stuStmt->execute([$id]);
$student = $stuStmt->fetch();
if (!$student) { flash('Student not found.', 'error'); redirect(BASE_URL . 'modules/students/index.php'); }

$departments = $pdo->query('SELECT id, name FROM departments ORDER BY name')->fetchAll();
$errors = [];

if (isPost()) {
    $data = [
        'full_name'      => post('full_name'),
        'email'          => post('email'),
        'phone'          => post('phone'),
        'nic'            => post('nic'),
        'address'        => post('address'),
        'dob'            => post('dob'),
        'gender'         => post('gender'),
        'department_id'  => post('department_id'),
        'enrolled_date'  => post('enrolled_date'),
        'guardian_name'  => post('guardian_name'),
        'guardian_phone' => post('guardian_phone'),
        'is_active'      => post('is_active') !== '' ? (int)post('is_active') : 1,
    ];

    if (!$data['full_name']) $errors[] = 'Full name is required.';
    if (!$data['email'])     $errors[] = 'Email is required.';

    if (!$errors) {
        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE users SET full_name=?, email=? WHERE id=?')
                ->execute([$data['full_name'], $data['email'], $student['user_id']]);
            $pdo->prepare('UPDATE students SET full_name=?,email=?,phone=?,nic=?,address=?,dob=?,gender=?,
                department_id=?,enrolled_date=?,guardian_name=?,guardian_phone=?,is_active=? WHERE id=?')
                ->execute([$data['full_name'],$data['email'],$data['phone'],$data['nic'],$data['address'],
                    $data['dob']?:null,$data['gender'],$data['department_id']?:null,$data['enrolled_date'],
                    $data['guardian_name'],$data['guardian_phone'],$data['is_active'],$id]);
            $pdo->commit();
            flash('Student updated successfully.', 'success');
            redirect(BASE_URL . 'modules/students/view.php?id=' . $id);
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Update failed: ' . $e->getMessage();
        }
    }
} else {
    $data = $student;
}

$pageTitle = 'Edit Student';
require_once '../../includes/header.php';
?>
<div class="page-header">
  <div><h1>Edit Student</h1><p><?= h($student['full_name']) ?></p></div>
  <div class="btn-group">
    <a href="view.php?id=<?= $id ?>" class="btn btn-secondary">← View</a>
    <a href="index.php" class="btn btn-secondary">List</a>
  </div>
</div>

<?php if ($errors): ?><div class="alert alert-error">❌ <?= implode('<br>', array_map('h', $errors)) ?></div><?php endif; ?>

<form method="POST" class="form-card">
  <div class="form-section-title">Personal Information</div>
  <div class="form-row">
    <div class="form-group">
      <label>Student ID</label>
      <input type="text" value="<?= h($student['student_id_no']) ?>" disabled style="background:#f3f4f6;">
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
      <label>NIC</label>
      <input type="text" name="nic" value="<?= h($data['nic'] ?? '') ?>">
    </div>
    <div class="form-group">
      <label>Date of Birth</label>
      <input type="date" name="dob" value="<?= h($data['dob'] ?? '') ?>">
    </div>
  </div>
  <div class="form-row">
    <div class="form-group">
      <label>Gender</label>
      <select name="gender">
        <option value="">Select</option>
        <?php foreach (['male','female','other'] as $g): ?>
        <option value="<?= $g ?>" <?= ($data['gender']??'')===$g?'selected':'' ?>><?= ucfirst($g) ?></option>
        <?php endforeach; ?>
      </select>
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
  <div class="form-section-title" style="margin-top:.5rem;">Guardian & Status</div>
  <div class="form-row">
    <div class="form-group">
      <label>Guardian Name</label>
      <input type="text" name="guardian_name" value="<?= h($data['guardian_name'] ?? '') ?>">
    </div>
    <div class="form-group">
      <label>Guardian Phone</label>
      <input type="text" name="guardian_phone" value="<?= h($data['guardian_phone'] ?? '') ?>">
    </div>
  </div>
  <div class="form-row">
    <div class="form-group">
      <label>Enrollment Date</label>
      <input type="date" name="enrolled_date" value="<?= h($data['enrolled_date'] ?? '') ?>">
    </div>
    <div class="form-group">
      <label>Status</label>
      <select name="is_active">
        <option value="1" <?= ($data['is_active']??1)?'selected':'' ?>>Active</option>
        <option value="0" <?= !($data['is_active']??1)?'selected':'' ?>>Inactive</option>
      </select>
    </div>
  </div>
  <div class="form-actions">
    <button type="submit" class="btn btn-primary">Update Student</button>
    <a href="view.php?id=<?= $id ?>" class="btn btn-secondary">Cancel</a>
  </div>
</form>
<?php require_once '../../includes/footer.php'; ?>
