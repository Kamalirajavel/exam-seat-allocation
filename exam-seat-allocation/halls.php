<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_login();
$pageTitle = 'Halls';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name = trim($_POST['hall_name']);
        $rows = (int)$_POST['rows_count'];
        $cols = (int)$_POST['cols_count'];
        if ($name === '' || $rows < 1 || $cols < 1) {
            set_flash('danger', 'Please provide a valid hall name, rows and columns.');
        } else {
            try {
                $stmt = $pdo->prepare('INSERT INTO halls (hall_name, rows_count, cols_count) VALUES (?,?,?)');
                $stmt->execute([$name, $rows, $cols]);
                set_flash('success', 'Hall added.');
            } catch (PDOException $e) {
                set_flash('danger', 'Could not add hall (name may already exist).');
            }
        }
    } elseif ($action === 'edit') {
        $id = (int)$_POST['id'];
        $name = trim($_POST['hall_name']);
        $rows = (int)$_POST['rows_count'];
        $cols = (int)$_POST['cols_count'];
        $stmt = $pdo->prepare('UPDATE halls SET hall_name=?, rows_count=?, cols_count=? WHERE id=?');
        $stmt->execute([$name, $rows, $cols, $id]);
        set_flash('success', 'Hall updated.');
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        try {
            $stmt = $pdo->prepare('DELETE FROM halls WHERE id=?');
            $stmt->execute([$id]);
            set_flash('success', 'Hall deleted.');
        } catch (PDOException $e) {
            set_flash('danger', 'Cannot delete: hall has existing seating allocations.');
        }
    }
    header('Location: halls.php');
    exit;
}

$halls = $pdo->query('SELECT * FROM halls ORDER BY hall_name')->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="fw-bold mb-0">Examination Halls</h5>
  <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-lg me-1"></i>Add Hall</button>
</div>

<div class="row g-3">
<?php foreach ($halls as $hl): ?>
  <div class="col-md-4">
    <div class="card p-3 h-100">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <h6 class="fw-bold mb-1"><?= h($hl['hall_name']) ?></h6>
          <p class="text-muted small mb-1"><?= (int)$hl['rows_count'] ?> rows × <?= (int)$hl['cols_count'] ?> columns</p>
          <span class="badge bg-primary"><?= (int)$hl['capacity'] ?> seats</span>
        </div>
        <div class="no-print">
          <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $hl['id'] ?>"><i class="bi bi-pencil"></i></button>
          <form action="halls.php" method="post" class="d-inline">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= $hl['id'] ?>">
            <button class="btn btn-sm btn-outline-danger confirm-delete"><i class="bi bi-trash"></i></button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="editModal<?= $hl['id'] ?>" tabindex="-1">
    <div class="modal-dialog">
      <form action="halls.php" method="post" class="modal-content">
        <div class="modal-header"><h6 class="modal-title">Edit Hall</h6><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <input type="hidden" name="action" value="edit">
          <input type="hidden" name="id" value="<?= $hl['id'] ?>">
          <div class="mb-3"><label class="form-label">Hall Name</label><input class="form-control" name="hall_name" value="<?= h($hl['hall_name']) ?>" required></div>
          <div class="row g-2">
            <div class="col-6"><label class="form-label">Rows</label><input type="number" min="1" class="form-control" name="rows_count" value="<?= (int)$hl['rows_count'] ?>" required></div>
            <div class="col-6"><label class="form-label">Columns</label><input type="number" min="1" class="form-control" name="cols_count" value="<?= (int)$hl['cols_count'] ?>" required></div>
          </div>
        </div>
        <div class="modal-footer"><button class="btn btn-primary">Save changes</button></div>
      </form>
    </div>
  </div>
<?php endforeach; ?>
<?php if (!$halls): ?>
  <div class="col-12"><p class="text-muted text-center py-5">No halls added yet.</p></div>
<?php endif; ?>
</div>

<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog">
    <form action="halls.php" method="post" class="modal-content">
      <div class="modal-header"><h6 class="modal-title">Add Hall</h6><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="add">
        <div class="mb-3"><label class="form-label">Hall Name</label><input class="form-control" name="hall_name" placeholder="e.g. Hall D" required></div>
        <div class="row g-2">
          <div class="col-6"><label class="form-label">Rows</label><input type="number" min="1" class="form-control" name="rows_count" value="6" required></div>
          <div class="col-6"><label class="form-label">Columns</label><input type="number" min="1" class="form-control" name="cols_count" value="5" required></div>
        </div>
      </div>
      <div class="modal-footer"><button class="btn btn-primary">Add</button></div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
