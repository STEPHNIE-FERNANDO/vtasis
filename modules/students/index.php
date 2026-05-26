<?php
define('BASE_URL', '/vtasis/');
require_once '../../includes/auth.php';
requireRole(['admin', 'support']);
require_once '../../config/db.php';

$search = get('search');
$deptFilter = get('dept');
$page   = max(1, (int)get('page', '1'));
$perPage = 20;

$where = 'WHERE 1=1';
$params = [];
if ($search) { $where .= ' AND (s.full_name LIKE ? OR s.student_id_no LIKE ? OR s.email LIKE ?)'; $params = array_merge($params, ["%$search%","%$search%","%$search%"]); }
if ($deptFilter) { $where .= ' AND s.department_id = ?'; $params[] = $deptFilter; }

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM students s $where");
$countStmt->execute($params);
$pager = paginate((int)$countStmt->fetchColumn(), $perPage, $page);

$stmt = $pdo->prepare("SELECT s.id, s.student_id_no, s.full_name, s.email, s.phone, s.is_active, s.enrolled_date, d.name AS dept
    FROM students s LEFT JOIN departments d ON d.id=s.department_id $where ORDER BY s.full_name
    LIMIT {$pager['limit']} OFFSET {$pager['offset']}");
$stmt->execute($params);
$students = $stmt->fetchAll();

$departments = $pdo->query('SELECT id, name FROM departments ORDER BY name')->fetchAll();

$pageTitle = 'Students';
require_once '../../includes/header.php';
?>

<div class="page-header">
  <div><h1>Students</h1><p><?= $pager['total'] ?> total students</p></div>
  <a href="add.php" class="btn btn-primary">+ Add Student</a>
</div>

<div class="card">
  <div class="card-body" style="padding-bottom:.75rem;">
    <form method="GET" class="search-bar">
      <input type="text" name="search" value="<?= h($search) ?>" placeholder="Search name, ID, or email…">
      <select name="dept">
        <option value="">All Departments</option>
        <?php foreach ($departments as $d): ?>
        <option value="<?= $d['id'] ?>" <?= $deptFilter == $d['id'] ? 'selected' : '' ?>><?= h($d['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-primary" type="submit">Search</button>
      <?php if ($search || $deptFilter): ?><a href="index.php" class="btn btn-secondary">Clear</a><?php endif; ?>
    </form>
  </div>

  <div class="table-wrapper">
    <table class="table">
      <thead>
        <tr>
          <th>Student ID</th><th>Full Name</th><th>Department</th>
          <th>Email</th><th>Phone</th><th>Enrolled</th><th>Status</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($students as $s): ?>
        <tr>
          <td><strong><?= h($s['student_id_no']) ?></strong></td>
          <td><?= h($s['full_name']) ?></td>
          <td><?= h($s['dept'] ?? '—') ?></td>
          <td><?= h($s['email']) ?></td>
          <td><?= h($s['phone'] ?? '—') ?></td>
          <td><?= h($s['enrolled_date'] ?? '—') ?></td>
          <td><span class="badge <?= $s['is_active'] ? 'badge-success' : 'badge-danger' ?>"><?= $s['is_active'] ? 'Active' : 'Inactive' ?></span></td>
          <td>
            <div class="btn-group">
              <a href="view.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-secondary">View</a>
              <a href="edit.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$students): ?>
        <tr><td colspan="8"><div class="empty-state"><div class="empty-icon">👨‍🎓</div><h3>No students found</h3></div></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($pager['pages'] > 1): ?>
  <div style="padding:1rem 1.5rem;">
    <div class="pagination">
      <?php for ($i = 1; $i <= $pager['pages']; $i++): ?>
        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&dept=<?= urlencode($deptFilter) ?>" class="<?= $i === $pager['current'] ? 'active' : '' ?>"><?= $i ?></a>
      <?php endfor; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php require_once '../../includes/footer.php'; ?>
