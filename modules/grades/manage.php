<?php
define('BASE_URL', '/vtasis/');
require_once '../../includes/auth.php';
requireRole(['admin','support','faculty']);
require_once '../../config/db.php';

$enrollmentId = (int)get('enrollment_id');
if (!$enrollmentId) redirect(BASE_URL . 'modules/grades/index.php');

$eStmt = $pdo->prepare('SELECT e.*, s.full_name AS student_name, s.student_id_no, c.name AS course_name, c.course_code
    FROM enrollments e JOIN students s ON s.id=e.student_id JOIN courses c ON c.id=e.course_id WHERE e.id=?');
$eStmt->execute([$enrollmentId]); $enrollment = $eStmt->fetch();
if (!$enrollment) { flash('Enrollment not found.','error'); redirect(BASE_URL . 'modules/grades/index.php'); }

$gStmt = $pdo->prepare('SELECT * FROM grades WHERE enrollment_id=?');
$gStmt->execute([$enrollmentId]); $grade = $gStmt->fetch();

$errors = [];

if (isPost()) {
    $mid  = post('midterm_score');
    $fin  = post('final_score');
    $asgn = post('assignment_score');

    foreach (['midterm_score' => $mid, 'final_score' => $fin, 'assignment_score' => $asgn] as $field => $val) {
        if ($val !== '' && ((float)$val < 0 || (float)$val > 100))
            $errors[] = ucwords(str_replace('_',' ',$field)) . ' must be 0–100.';
    }

    if (!$errors) {
        $total = ($mid !== '' && $fin !== '' && $asgn !== '')
            ? round(((float)$mid * 0.3) + ((float)$fin * 0.5) + ((float)$asgn * 0.2), 2)
            : null;
        $letter = $total !== null ? calcGrade($total) : null;

        if ($grade) {
            $pdo->prepare('UPDATE grades SET midterm_score=?,final_score=?,assignment_score=?,total_score=?,grade_letter=? WHERE enrollment_id=?')
                ->execute([$mid?:(null),$fin?:(null),$asgn?:(null),$total,$letter,$enrollmentId]);
        } else {
            $pdo->prepare('INSERT INTO grades (enrollment_id,student_id,course_id,midterm_score,final_score,assignment_score,total_score,grade_letter,academic_year,semester)
                VALUES (?,?,?,?,?,?,?,?,?,?)')
                ->execute([$enrollmentId,$enrollment['student_id'],$enrollment['course_id'],
                    $mid?:null,$fin?:null,$asgn?:null,$total,$letter,$enrollment['academic_year'],$enrollment['semester']]);
        }
        flash('Grades saved.','success');
        redirect(BASE_URL . 'modules/grades/index.php?course=' . $enrollment['course_id']);
    }
}

$pageTitle = 'Enter Grades';
require_once '../../includes/header.php';
?>
<div class="page-header">
  <div><h1>Enter Grades</h1><p><?= h($enrollment['student_name']) ?> — <?= h($enrollment['course_name']) ?></p></div>
  <a href="<?= BASE_URL ?>modules/grades/index.php?course=<?= $enrollment['course_id'] ?>" class="btn btn-secondary">← Back</a>
</div>

<?php if ($errors): ?><div class="alert alert-error">❌ <?= implode('<br>', array_map('h',$errors)) ?></div><?php endif; ?>

<div class="card" style="max-width:600px;margin-bottom:1.5rem;">
  <div class="card-body" style="font-size:.875rem;display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
    <div><strong>Student:</strong> <?= h($enrollment['student_name']) ?></div>
    <div><strong>ID:</strong> <?= h($enrollment['student_id_no']) ?></div>
    <div><strong>Course:</strong> <?= h($enrollment['course_name']) ?></div>
    <div><strong>Code:</strong> <?= h($enrollment['course_code']) ?></div>
    <div><strong>Year:</strong> <?= h($enrollment['academic_year']) ?></div>
    <div><strong>Semester:</strong> <?= h($enrollment['semester']) ?></div>
  </div>
</div>

<form method="POST" class="form-card">
  <div class="form-section-title">Score Entry (0–100 each)</div>
  <div class="form-row">
    <div class="form-group">
      <label>Midterm Score (30%)</label>
      <input type="number" id="midterm_score" name="midterm_score" value="<?= h($grade['midterm_score'] ?? post('midterm_score')) ?>" min="0" max="100" step="0.1" placeholder="0–100">
    </div>
    <div class="form-group">
      <label>Final Score (50%)</label>
      <input type="number" id="final_score" name="final_score" value="<?= h($grade['final_score'] ?? post('final_score')) ?>" min="0" max="100" step="0.1" placeholder="0–100">
    </div>
    <div class="form-group">
      <label>Assignment Score (20%)</label>
      <input type="number" id="assignment_score" name="assignment_score" value="<?= h($grade['assignment_score'] ?? post('assignment_score')) ?>" min="0" max="100" step="0.1" placeholder="0–100">
    </div>
  </div>
  <div class="form-row">
    <div class="form-group">
      <label>Total Score (auto-calculated)</label>
      <input type="text" id="total_score" value="<?= h($grade['total_score'] ?? '') ?>" disabled style="background:#f3f4f6;font-weight:700;">
    </div>
    <div class="form-group">
      <label>Grade Letter (auto-assigned)</label>
      <input type="text" id="grade_letter" value="<?= h($grade['grade_letter'] ?? '') ?>" disabled style="background:#f3f4f6;font-weight:700;font-size:1.25rem;">
    </div>
  </div>
  <div style="font-size:.8rem;color:var(--text-muted);margin-bottom:1rem;">
    Formula: Total = (Midterm × 0.3) + (Final × 0.5) + (Assignment × 0.2)<br>
    Grades: A ≥75 | B ≥65 | C ≥55 | D ≥45 | F &lt;45
  </div>
  <div class="form-actions">
    <button type="submit" class="btn btn-primary">Save Grades</button>
    <a href="<?= BASE_URL ?>modules/grades/index.php?course=<?= $enrollment['course_id'] ?>" class="btn btn-secondary">Cancel</a>
  </div>
</form>
<?php require_once '../../includes/footer.php'; ?>
