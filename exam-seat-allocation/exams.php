<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_login();
$pageTitle = 'Exams';

$departments = $pdo->query('SELECT * FROM departments ORDER BY dept_name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $code = trim($_POST['subject_code']);
        $name = trim($_POST['subject_name']);
        $dept = (int)$_POST['department_id'];
        $date = $_POST['exam_date'];
        $slot = $_POST['session_slot'] === 'AN' ? 'AN' : 'FN';
        if ($code === '' || $name === '' || !$dept || !$date) {
            set_flash('danger', 'All fields are required.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO exams (subject_code, subject_name, department_id, exam_date, session_slot) VALUES (?,?,?,?,?)');
            $stmt->execute([$code, $name, $dept, $date, $slot]);
            set_flash('success', 'Exam scheduled.');
        }
    } elseif ($action === 'edit') {
        $id = (int)$_POST['id'];
        $code = trim($_POST['subject_code']);
        $name = trim($_POST['subject_name']);
        $dept = (int)$_POST['department_id'];
        $date = $_POST['exam_date'];
        $slot = $_POST['session_slot'] === 'AN' ? 'AN' : 'FN';
        $stmt = $pdo->prepare('UPDATE exams SET subject_code=?, subject_name=?, department_id=?, exam_date=?, session_slot=? WHERE id=?');
        $stmt->execute([$code, $name, $dept, $date, $slot, $id]);
        set_flash('success', 'Exam updated.');
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare('DELETE FROM exams WHERE id=?');
        $stmt->execute([$id]);
        set_flash('success', 'Exam deleted.');
    }
    header('Location: exams.php');
    exit;
}

$exams = $pdo->query(
    "SELECT e.*, d.dept_name, d.dept_code,
     (SELECT COUNT(*) FROM students s WHERE s.department_id = e.department_id) as candidate_count
     FROM exams e JOIN departments d ON d.id = e.department_id
     ORDER BY e.exam_date, e.session_slot, d.dept_code"
)->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="fw-bold mb-0">Exam Schedule</h5>
  <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-lg me-1"></i>Schedule Exam</button>
</div>

<div class="alert alert-info small">
  <i class="bi bi-info-circle me-1"></i>
  Exams sharing the same <strong>date + session</strong> are treated as one seating session — their students are mixed together across the halls you choose during allocation.
</div>

<div class="card p-3">
  <input type="text" id="tableFilter" class="form-control mb-3 w-auto" placeholder="Search exams...">
  <div class="table-responsive">
    <table class="table table-hover align-middle" id="dataTable">
      <thead><tr><th>Date</th><th>Slot</th><th>Subject</th><th>Department</th><th>Candidates</th><th class="no-print">Actions</th></tr></thead>
      <tbody>
      <?php foreach ($exams as $e): ?>
        <tr>
          <td><?= h($e['exam_date']) ?></td>
          <td><span class="badge bg-secondary"><?= h($e['session_slot']) ?></span></td>
          <td><?= h($e['subject_code']) ?> — <?= h($e['subject_name']) ?></td>
          <td><?= h($e['dept_name']) ?></td>
          <td><?= (int)$e['candidate_count'] ?></td>
          <td class="no-print">
            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $e['id'] ?>"><i class="bi bi-pencil"></i></button>
            <form action="exams.php" method="post" class="d-inline">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= $e['id'] ?>">
              <button class="btn btn-sm btn-outline-danger confirm-delete"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>

        <div class="modal fade" id="editModal<?= $e['id'] ?>" tabindex="-1">
          <div class="modal-dialog">
            <form action="exams.php" method="post" class="modal-content">
              <div class="modal-header"><h6 class="modal-title">Edit Exam</h6><button class="btn-close" data-bs-dismiss="modal"></button></div>
              <div class="modal-body">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?= $e['id'] ?>">
                <div class="mb-3"><label class="form-label">Subject Code</label><input class="form-control" name="subject_code" value="<?= h($e['subject_code']) ?>" required></div>
                <div class="mb-3"><label class="form-label">Subject Name</label><input class="form-control" name="subject_name" value="<?= h($e['subject_name']) ?>" required></div>
                <div class="mb-3">
                  <label class="form-label">Department</label>
                  <select class="form-select" name="department_id" required>
                    <?php foreach ($departments as $d): ?>
                      <option value="<?= $d['id'] ?>" <?= $d['id']==$e['department_id']?'selected':'' ?>><?= h($d['dept_name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="row g-2">
                  <div class="col-7"><label class="form-label">Date</label><input type="date" class="form-control" name="exam_date" value="<?= h($e['exam_date']) ?>" required></div>
                  <div class="col-5">
                    <label class="form-label">Session</label>
                    <select class="form-select" name="session_slot">
                      <option value="FN" <?= $e['session_slot']=='FN'?'selected':'' ?>>Forenoon</option>
                      <option value="AN" <?= $e['session_slot']=='AN'?'selected':'' ?>>Afternoon</option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="modal-footer"><button class="btn btn-primary">Save changes</button></div>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (!$exams): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">No exams scheduled yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog">
    <form action="exams.php" method="post" class="modal-content">
      <div class="modal-header"><h6 class="modal-title">Schedule Exam</h6><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="add">
        <div class="mb-3"><label class="form-label">Subject Code</label><input class="form-control" name="subject_code" placeholder="e.g. CS301" required></div>
        <div class="mb-3"><label class="form-label">Subject Name</label><input class="form-control" name="subject_name" required></div>
        <div class="mb-3">
          <label class="form-label">Department</label>
          <select class="form-select" name="department_id" required>
            <option value="">-- select --</option>
            <?php foreach ($departments as $d): ?>
              <option value="<?= $d['id'] ?>"><?= h($d['dept_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="row g-2">
          <div class="col-7"><label class="form-label">Date</label><input type="date" class="form-control" name="exam_date" required></div>
          <div class="col-5">
            <label class="form-label">Session</label>
            <select class="form-select" name="session_slot">
              <option value="FN">Forenoon</option>
              <option value="AN">Afternoon</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer"><button class="btn btn-primary">Schedule</button></div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
