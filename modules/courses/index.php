<?php
define('BASE_URL', '/vtasis/');
require_once '../../includes/auth.php';
requireLogin();
require_once '../../config/db.php';

$search = get('search');
$deptFilter = get('dept');
$page = max(1,(int)get('page','1'));
$perPage = 20;

$where = 'WHERE 1=1';
$params = [];
if ($search) { $where .= ' AND (c.name LIKE ? OR c.course_code LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($deptFilter) { $where .= ' AND c.department_id=?'; $params[] = $deptFilter; }

$cnt = $pdo->prepare("SELECT COUNT(*) FROM courses c $where"); $cnt->execute($params);
$pager = paginate((int)$cnt->fetchColumn(), $perPage, $page);

$stmt = $pdo->prepare("SELECT c.*, d.name AS dept_name,
    (SELECT COUNT(*) FROM enrollments e WHERE e.course_id=c.id AND e.status='enrolled') AS enrolled_count
    FROM courses c LEFT JOIN departments d ON d.id=c.department_id $where ORDER BY c.name
    LIMIT {$pager['limit']} OFFSET {$pager['offset']}");
$stmt->execute($params);
$courses = $stmt->fetchAll();

$departments = $pdo->query('SELECT id, name FROM departments ORDER BY name')->fetchAll();

$pageTitle = 'Courses';
require_once '../../includes/header.php';
?>
<div class="page-header">
  <div><h1>Courses</h1><p><?= $pager['total'] ?> total courses</p></div>
  <?php if (in_array($_SESSION['role'],['admin','support'])): ?>
  <a href="add.php" class="btn btn-primary">+ Add Course</a>
  <?php endif; ?>
</div>

<div class="card">
  <div class="card-body" style="padding-bottom:.75rem;">
    <form method="GET" class="search-bar">
      <input type="text" name="search" value="<?= h($search) ?>" placeholder="Search course name or code…">
      <select name="dept">
        <option value="">All Departments</option>
        <?php foreach ($departments as $d): ?>
        <option value="<?= $d['id'] ?>" <?= $deptFilter==$d['id']?'selected':'' ?>><?= h($d['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-primary" type="submit">Search</button>
      <?php if ($search||$deptFilter): ?><a href="index.php" class="btn btn-secondary">Clear</a><?php endif; ?>
    </form>
  </div>

  <div class="table-wrapper">
    <table class="table">
      <thead><tr><th>Code</th><th>Course Name</th><th>Department</th><th>Credits</th><th>Duration</th><th>Enrolled</th><th>Max</th><th>Status</th>
        <?php if (in_array($_SESSION['role'],['admin','support'])): ?><th>Actions</th><?php endif; ?>
      </tr></thead>
      <tbody>
      <?php foreach ($courses as $c): ?>
        <tr>
          <td><strong><?= h($c['course_code']) ?></strong></td>
          <td><?= h($c['name']) ?></td>
          <td><?= h($c['dept_name'] ?? '—') ?></td>
          <td><?= h($c['credits']) ?></td>
          <td><?= h($c['duration_weeks']) ?> wks</td>
          <td><?= $c['enrolled_count'] ?></td>
          <td><?= h($c['max_students']) ?></td>
          <td><span class="badge badge-<?= $c['status']==='active'?'success':'gray' ?>"><?= h(ucfirst($c['status'])) ?></span></td>
          <?php if (in_array($_SESSION['role'],['admin','support'])): ?>
          <td><div class="btn-group"><a href="edit.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-primary">Edit</a></div></td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
      <?php if (!$courses): ?><tr><td colspan="9"><div class="empty-state"><div class="empty-icon">📚</div><h3>No courses found</h3></div></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pager['pages']>1): ?>
  <div style="padding:1rem 1.5rem;">
    <div class="pagination">
      <?php for ($i=1;$i<=$pager['pages'];$i++): ?>
        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&dept=<?= urlencode($deptFilter) ?>" class="<?= $i===$pager['current']?'active':'' ?>"><?= $i ?></a>
      <?php endfor; ?>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php require_once '../../includes/footer.php'; ?>
