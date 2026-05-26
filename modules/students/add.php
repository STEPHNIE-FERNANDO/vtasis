<?php
define('BASE_URL', '/vtasis/');
require_once '../../includes/auth.php';
requireRole(['admin', 'support']);
require_once '../../config/db.php';

$departments = $pdo->query('SELECT id, name FROM departments ORDER BY name')->fetchAll();
$errors = [];
$data = ['student_id_no'=>'','full_name'=>'','email'=>'','phone'=>'','nic'=>'','address'=>'','dob'=>'','gender'=>'','department_id'=>'','enrolled_date'=>date('Y-m-d'),'guardian_name'=>'','guardian_phone'=>''];

if (isPost()) {
    foreach ($data as $k => $_) $data[$k] = post($k);
    $data['enrolled_date'] = $data['enrolled_date'] ?: date('Y-m-d');

    if (!$data['student_id_no']) $errors[] = 'Student ID is required.';
    if (!$data['full_name'])     $errors[] = 'Full name is required.';
    if (!$data['email'])         $errors[] = 'Email is required.';
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';

    if (!$errors) {
        $chk = $pdo->prepare('SELECT id FROM students WHERE student_id_no=?');
        $chk->execute([$data['student_id_no']]);
        if ($chk->fetch()) $errors[] = 'Student ID already exists.';
    }

    if (!$errors) {
        $pdo->beginTransaction();
        try {
            $uStmt = $pdo->prepare('INSERT INTO users (username,password,role,full_name,email,is_active) VALUES (?,?,?,?,?,1)');
            $uStmt->execute([$data['student_id_no'], password_hash('student123', PASSWORD_DEFAULT), 'student', $data['full_name'], $data['email']]);
            $uid = $pdo->lastInsertId();

            $sStmt = $pdo->prepare('INSERT INTO students (user_id,student_id_no,full_name,email,phone,nic,address,dob,gender,department_id,enrolled_date,guardian_name,guardian_phone,is_active)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,1)');
            $sStmt->execute([$uid,$data['student_id_no'],$data['full_name'],$data['email'],$data['phone'],
                $data['nic'],$data['address'],$data['dob']?:null,$data['gender'],$data['department_id']?:null,
                $data['enrolled_date'],$data['guardian_name'],$data['guardian_phone']]);
            $pdo->commit();
            flash('Student added successfully. Default password: <strong>student123</strong>', 'success');
            redirect(BASE_URL . 'modules/students/index.php');
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Failed to save: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Add Student';
require_once '../../includes/header.php';
?>

<div class="page-header">
  <div><h1>Add Student</h1><p>Create a new student account</p></div>
  <a href="index.php" class="btn btn-secondary">← Back</a>
</div>

<?php if ($errors): ?>
<div class="alert alert-error">❌ <?= implode('<br>', array_map('h', $errors)) ?></div>
<?php endif; ?>

<form method="POST" class="form-card">
  <div class="form-section-title">Personal Information</div>
  <div class="form-row">
    <div class="form-group">
      <label>Student ID *</label>
      <input type="text" name="student_id_no" value="<?= h($data['student_id_no']) ?>" placeholder="e.g. VTA2024001" required>
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
      <label>NIC Number</label>
      <input type="text" name="nic" value="<?= h($data['nic']) ?>">
    </div>
    <div class="form-group">
      <label>Date of Birth</label>
      <input type="date" name="dob" value="<?= h($data['dob']) ?>">
    </div>
  </div>
  <div class="form-row">
    <div class="form-group">
      <label>Gender</label>
      <select name="gender">
        <option value="">Select Gender</option>
        <option value="male"   <?= $data['gender']==='male'   ? 'selected' : '' ?>>Male</option>
        <option value="female" <?= $data['gender']==='female' ? 'selected' : '' ?>>Female</option>
        <option value="other"  <?= $data['gender']==='other'  ? 'selected' : '' ?>>Other</option>
      </select>
    </div>
    <div class="form-group">
      <label>Department</label>
      <select name="department_id">
        <option value="">No Department</option>
        <?php foreach ($departments as $d): ?>
        <option value="<?= $d['id'] ?>" <?= $data['department_id']==$d['id'] ? 'selected' : '' ?>><?= h($d['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="form-group">
    <label>Address</label>
    <textarea name="address"><?= h($data['address']) ?></textarea>
  </div>

  <div class="form-section-title" style="margin-top:.5rem;">Guardian & Enrollment</div>
  <div class="form-row">
    <div class="form-group">
      <label>Guardian Name</label>
      <input type="text" name="guardian_name" value="<?= h($data['guardian_name']) ?>">
    </div>
    <div class="form-group">
      <label>Guardian Phone</label>
      <input type="text" name="guardian_phone" value="<?= h($data['guardian_phone']) ?>">
    </div>
  </div>
  <div class="form-row">
    <div class="form-group">
      <label>Enrollment Date</label>
      <input type="date" name="enrolled_date" value="<?= h($data['enrolled_date']) ?>">
    </div>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary">Save Student</button>
    <a href="index.php" class="btn btn-secondary">Cancel</a>
  </div>
</form>

<?php require_once '../../includes/footer.php'; ?>
