<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_login();
$pageTitle = 'Departments';

// --- Handle actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $code = trim($_POST['dept_code']);
        $name = trim($_POST['dept_name']);
        if ($code === '' || $name === '') {
            set_flash('danger', 'Department code and name are required.');
        } else {
            try {
                $stmt = $pdo->prepare('INSERT INTO departments (dept_code, dept_name) VALUES (?, ?)');
                $stmt->execute([$code, $name]);
                set_flash('success', 'Department added.');
            } catch (PDOException $e) {
                set_flash('danger', 'Could not add department (code may already exist).');
            }
        }
    } elseif ($action === 'edit') {
        $id = (int)$_POST['id'];
        $code = trim($_POST['dept_code']);
        $name = trim($_POST['dept_name']);
        $stmt = $pdo->prepare('UPDATE departments SET dept_code=?, dept_name=? WHERE id=?');
        $stmt->execute([$code, $name, $id]);
        set_flash('success', 'Department updated.');
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        try {
            $stmt = $pdo->prepare('DELETE FROM departments WHERE id=?');
            $stmt->execute([$id]);
            set_flash('success', 'Department deleted.');
        } catch (PDOException $e) {
            set_flash('danger', 'Cannot delete: department has linked students or exams.');
        }
    }
    header('Location: departments.php');
    exit;
}

$departments = $pdo->query(
    "SELECT d.*, (SELECT COUNT(*) FROM students s WHERE s.department_id=d.id) as student_count
     FROM departments d ORDER BY d.dept_name"
)->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="fw-bold mb-0">Departments</h5>
  <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-lg me-1"></i>Add Department</button>
</div>

<div class="card p-3">
  <input type="text" id="tableFilter" class="form-control mb-3 w-auto" placeholder="Search departments...">
  <div class="table-responsive">
    <table class="table table-hover align-middle" id="dataTable">
      <thead><tr><th>Code</th><th>Name</th><th>Students</th><th class="no-print">Actions</th></tr></thead>
      <tbody>
      <?php foreach ($departments as $d): ?>
        <tr>
          <td><span class="badge bg-secondary"><?= h($d['dept_code']) ?></span></td>
          <td><?= h($d['dept_name']) ?></td>
          <td><?= (int)$d['student_count'] ?></td>
          <td class="no-print">
            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $d['id'] ?>"><i class="bi bi-pencil"></i></button>
            <form action="departments.php" method="post" class="d-inline">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= $d['id'] ?>">
              <button class="btn btn-sm btn-outline-danger confirm-delete"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>

        <!-- Edit modal -->
        <div class="modal fade" id="editModal<?= $d['id'] ?>" tabindex="-1">
          <div class="modal-dialog">
            <form action="departments.php" method="post" class="modal-content">
              <div class="modal-header"><h6 class="modal-title">Edit Department</h6><button class="btn-close" data-bs-dismiss="modal"></button></div>
              <div class="modal-body">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?= $d['id'] ?>">
                <div class="mb-3"><label class="form-label">Code</label><input class="form-control" name="dept_code" value="<?= h($d['dept_code']) ?>" required></div>
                <div class="mb-3"><label class="form-label">Name</label><input class="form-control" name="dept_name" value="<?= h($d['dept_name']) ?>" required></div>
              </div>
              <div class="modal-footer"><button class="btn btn-primary">Save changes</button></div>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (!$departments): ?>
        <tr><td colspan="4" class="text-center text-muted py-4">No departments yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add modal -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog">
    <form action="departments.php" method="post" class="modal-content">
      <div class="modal-header"><h6 class="modal-title">Add Department</h6><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="add">
        <div class="mb-3"><label class="form-label">Code (e.g. CSE)</label><input class="form-control" name="dept_code" required></div>
        <div class="mb-3"><label class="form-label">Name</label><input class="form-control" name="dept_name" required></div>
      </div>
      <div class="modal-footer"><button class="btn btn-primary">Add</button></div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
