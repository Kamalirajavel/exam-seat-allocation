<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_login();
$pageTitle = 'Students';

$departments = $pdo->query('SELECT * FROM departments ORDER BY dept_name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $reg = trim($_POST['register_no']);
        $name = trim($_POST['name']);
        $dept = (int)$_POST['department_id'];
        $year = (int)$_POST['year_of_study'];
        if ($reg === '' || $name === '' || !$dept) {
            set_flash('danger', 'Register number, name and department are required.');
        } else {
            try {
                $stmt = $pdo->prepare('INSERT INTO students (register_no, name, department_id, year_of_study) VALUES (?,?,?,?)');
                $stmt->execute([$reg, $name, $dept, $year ?: 1]);
                set_flash('success', 'Student added.');
            } catch (PDOException $e) {
                set_flash('danger', 'Could not add student (register number may already exist).');
            }
        }
    } elseif ($action === 'edit') {
        $id = (int)$_POST['id'];
        $reg = trim($_POST['register_no']);
        $name = trim($_POST['name']);
        $dept = (int)$_POST['department_id'];
        $year = (int)$_POST['year_of_study'];
        $stmt = $pdo->prepare('UPDATE students SET register_no=?, name=?, department_id=?, year_of_study=? WHERE id=?');
        $stmt->execute([$reg, $name, $dept, $year ?: 1, $id]);
        set_flash('success', 'Student updated.');
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare('DELETE FROM students WHERE id=?');
        $stmt->execute([$id]);
        set_flash('success', 'Student deleted.');
    } elseif ($action === 'bulk_import') {
        $dept = (int)$_POST['bulk_department_id'];
        $year = (int)$_POST['bulk_year'] ?: 1;
        $lines = preg_split('/\r\n|\r|\n/', trim($_POST['bulk_data']));
        $added = 0; $skipped = 0;
        $stmt = $pdo->prepare('INSERT INTO students (register_no, name, department_id, year_of_study) VALUES (?,?,?,?)');
        foreach ($lines as $line) {
            if (trim($line) === '') continue;
            $parts = array_map('trim', explode(',', $line));
            if (count($parts) < 2) { $skipped++; continue; }
            [$reg, $name] = $parts;
            try {
                $stmt->execute([$reg, $name, $dept, $year]);
                $added++;
            } catch (PDOException $e) {
                $skipped++;
            }
        }
        set_flash('success', "Bulk import complete: $added added, $skipped skipped.");
    }
    header('Location: students.php');
    exit;
}

$students = $pdo->query(
    "SELECT s.*, d.dept_name, d.dept_code FROM students s
     JOIN departments d ON d.id = s.department_id
     ORDER BY d.dept_code, s.register_no"
)->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <h5 class="fw-bold mb-0">Students</h5>
  <div>
    <button class="btn btn-outline-secondary btn-sm me-2" data-bs-toggle="modal" data-bs-target="#bulkModal"><i class="bi bi-upload me-1"></i>Bulk Import</button>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-lg me-1"></i>Add Student</button>
  </div>
</div>

<?php if (!$departments): ?>
  <div class="alert alert-warning">Please <a href="departments.php">add a department</a> first before adding students.</div>
<?php endif; ?>

<div class="card p-3">
  <input type="text" id="tableFilter" class="form-control mb-3 w-auto" placeholder="Search students...">
  <div class="table-responsive">
    <table class="table table-hover align-middle" id="dataTable">
      <thead><tr><th>Reg. No.</th><th>Name</th><th>Department</th><th>Year</th><th class="no-print">Actions</th></tr></thead>
      <tbody>
      <?php foreach ($students as $s): ?>
        <tr>
          <td><?= h($s['register_no']) ?></td>
          <td><?= h($s['name']) ?></td>
          <td><span class="badge bg-secondary"><?= h($s['dept_code']) ?></span></td>
          <td><?= (int)$s['year_of_study'] ?></td>
          <td class="no-print">
            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $s['id'] ?>"><i class="bi bi-pencil"></i></button>
            <form action="students.php" method="post" class="d-inline">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= $s['id'] ?>">
              <button class="btn btn-sm btn-outline-danger confirm-delete"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>

        <div class="modal fade" id="editModal<?= $s['id'] ?>" tabindex="-1">
          <div class="modal-dialog">
            <form action="students.php" method="post" class="modal-content">
              <div class="modal-header"><h6 class="modal-title">Edit Student</h6><button class="btn-close" data-bs-dismiss="modal"></button></div>
              <div class="modal-body">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                <div class="mb-3"><label class="form-label">Register No.</label><input class="form-control" name="register_no" value="<?= h($s['register_no']) ?>" required></div>
                <div class="mb-3"><label class="form-label">Name</label><input class="form-control" name="name" value="<?= h($s['name']) ?>" required></div>
                <div class="mb-3">
                  <label class="form-label">Department</label>
                  <select class="form-select" name="department_id" required>
                    <?php foreach ($departments as $d): ?>
                      <option value="<?= $d['id'] ?>" <?= $d['id']==$s['department_id']?'selected':'' ?>><?= h($d['dept_name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="mb-3"><label class="form-label">Year of Study</label><input type="number" min="1" max="5" class="form-control" name="year_of_study" value="<?= (int)$s['year_of_study'] ?>"></div>
              </div>
              <div class="modal-footer"><button class="btn btn-primary">Save changes</button></div>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (!$students): ?>
        <tr><td colspan="5" class="text-center text-muted py-4">No students yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add modal -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog">
    <form action="students.php" method="post" class="modal-content">
      <div class="modal-header"><h6 class="modal-title">Add Student</h6><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="add">
        <div class="mb-3"><label class="form-label">Register No.</label><input class="form-control" name="register_no" required></div>
        <div class="mb-3"><label class="form-label">Name</label><input class="form-control" name="name" required></div>
        <div class="mb-3">
          <label class="form-label">Department</label>
          <select class="form-select" name="department_id" required>
            <option value="">-- select --</option>
            <?php foreach ($departments as $d): ?>
              <option value="<?= $d['id'] ?>"><?= h($d['dept_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3"><label class="form-label">Year of Study</label><input type="number" min="1" max="5" class="form-control" name="year_of_study" value="1"></div>
      </div>
      <div class="modal-footer"><button class="btn btn-primary">Add</button></div>
    </form>
  </div>
</div>

<!-- Bulk import modal -->
<div class="modal fade" id="bulkModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form action="students.php" method="post" class="modal-content">
      <div class="modal-header"><h6 class="modal-title">Bulk Import Students</h6><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="bulk_import">
        <p class="text-muted small">Paste one student per line as <code>register_no, name</code>. All rows will be assigned to the department and year selected below.</p>
        <div class="row g-2 mb-2">
          <div class="col-md-8">
            <label class="form-label">Department</label>
            <select class="form-select" name="bulk_department_id" required>
              <option value="">-- select --</option>
              <?php foreach ($departments as $d): ?>
                <option value="<?= $d['id'] ?>"><?= h($d['dept_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Year</label>
            <input type="number" min="1" max="5" class="form-control" name="bulk_year" value="1">
          </div>
        </div>
        <textarea class="form-control" name="bulk_data" rows="8" placeholder="CSE009, Rahul Kumar&#10;CSE010, Sneha Patil" required></textarea>
      </div>
      <div class="modal-footer"><button class="btn btn-primary">Import</button></div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
