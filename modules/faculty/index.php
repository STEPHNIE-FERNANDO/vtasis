<?php
define('BASE_URL', '/vtasis/');
require_once '../../includes/auth.php';
requireRole(['admin','support']);
require_once '../../config/db.php';

$search = get('search');
$page = max(1,(int)get('page','1'));
$perPage = 20;
$where = $search ? 'WHERE (f.full_name LIKE ? OR f.faculty_code LIKE ? OR f.email LIKE ?)' : '';
$params = $search ? ["%$search%","%$search%","%$search%"] : [];

$cnt = $pdo->prepare("SELECT COUNT(*) FROM faculty f $where"); $cnt->execute($params);
$pager = paginate((int)$cnt->fetchColumn(), $perPage, $page);

$stmt = $pdo->prepare("SELECT f.*, d.name AS dept_name FROM faculty f LEFT JOIN departments d ON d.id=f.department_id $where ORDER BY f.full_name LIMIT {$pager['limit']} OFFSET {$pager['offset']}");
$stmt->execute($params);
$faculty = $stmt->fetchAll();

$pageTitle = 'Faculty';
require_once '../../includes/header.php';
?>
<div class="page-header">
  <div><h1>Faculty</h1><p><?= $pager['total'] ?> members</p></div>
  <a href="add.php" class="btn btn-primary">+ Add Faculty</a>
</div>
<div class="card">
  <div class="card-body" style="padding-bottom:.75rem;">
    <form method="GET" class="search-bar">
      <input type="text" name="search" value="<?= h($search) ?>" placeholder="Search name, code or email…">
      <button class="btn btn-primary" type="submit">Search</button>
      <?php if ($search): ?><a href="index.php" class="btn btn-secondary">Clear</a><?php endif; ?>
    </form>
  </div>
  <div class="table-wrapper">
    <table class="table">
      <thead><tr><th>Code</th><th>Full Name</th><th>Department</th><th>Specialization</th><th>Email</th><th>Phone</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($faculty as $f): ?>
        <tr>
          <td><strong><?= h($f['faculty_code']) ?></strong></td>
          <td><?= h($f['full_name']) ?></td>
          <td><?= h($f['dept_name'] ?? '—') ?></td>
          <td><?= h($f['specialization'] ?? '—') ?></td>
          <td><?= h($f['email']) ?></td>
          <td><?= h($f['phone'] ?? '—') ?></td>
          <td><span class="badge badge-<?= $f['is_active']?'success':'danger' ?>"><?= $f['is_active']?'Active':'Inactive' ?></span></td>
          <td><a href="edit.php?id=<?= $f['id'] ?>" class="btn btn-sm btn-primary">Edit</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$faculty): ?><tr><td colspan="8"><div class="empty-state"><div class="empty-icon">👨‍🏫</div><h3>No faculty found</h3></div></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pager['pages']>1): ?>
  <div style="padding:1rem 1.5rem;">
    <div class="pagination">
      <?php for ($i=1;$i<=$pager['pages'];$i++): ?>
        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" class="<?= $i===$pager['current']?'active':'' ?>"><?= $i ?></a>
      <?php endfor; ?>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php require_once '../../includes/footer.php'; ?>
