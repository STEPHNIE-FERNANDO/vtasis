<?php
define('BASE_URL', '/vtasis/');
require_once '../../includes/auth.php';
requireRole(['admin','support']);
require_once '../../config/db.php';

$departments = $pdo->query('SELECT d.*, COUNT(DISTINCT s.id) AS student_count, COUNT(DISTINCT f.id) AS faculty_count
    FROM departments d
    LEFT JOIN students s ON s.department_id=d.id AND s.is_active=1
    LEFT JOIN faculty f ON f.department_id=d.id AND f.is_active=1
    GROUP BY d.id ORDER BY d.name')->fetchAll();

$pageTitle = 'Departments';
require_once '../../includes/header.php';
?>
<div class="page-header">
  <div><h1>Departments</h1><p><?= count($departments) ?> departments</p></div>
  <a href="manage.php" class="btn btn-primary">+ Add Department</a>
</div>

<div class="card">
  <div class="table-wrapper">
    <table class="table">
      <thead><tr><th>Code</th><th>Department Name</th><th>Description</th><th>Students</th><th>Faculty</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($departments as $d): ?>
        <tr>
          <td><strong><?= h($d['code'] ?? '—') ?></strong></td>
          <td><?= h($d['name']) ?></td>
          <td><?= h($d['description'] ?? '—') ?></td>
          <td><span class="badge badge-info"><?= $d['student_count'] ?></span></td>
          <td><span class="badge badge-primary"><?= $d['faculty_count'] ?></span></td>
          <td><a href="manage.php?id=<?= $d['id'] ?>" class="btn btn-sm btn-primary">Edit</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$departments): ?><tr><td colspan="6"><div class="empty-state"><div class="empty-icon">🏛️</div><h3>No departments yet</h3></div></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once '../../includes/footer.php'; ?>
