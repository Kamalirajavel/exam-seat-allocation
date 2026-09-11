<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();
$pageTitle = 'Find Student Seat';

$query = trim($_GET['q'] ?? '');
$results = [];

if ($query !== '') {
    $stmt = $pdo->prepare(
        "SELECT a.*, s.register_no, s.name as student_name, d.dept_code, d.dept_name,
                h.hall_name, e.subject_code, e.subject_name, e.exam_date, e.session_slot
         FROM exam_allocations a
         JOIN students s ON s.id = a.student_id
         JOIN departments d ON d.id = s.department_id
         JOIN halls h ON h.id = a.hall_id
         JOIN exams e ON e.id = a.exam_id
         WHERE s.register_no LIKE ? OR s.name LIKE ?
         ORDER BY e.exam_date DESC, e.session_slot"
    );
    $like = "%$query%";
    $stmt->execute([$like, $like]);
    $results = $stmt->fetchAll();
}

require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
  <h5 class="fw-bold mb-0">Find Student Seat</h5>
  <a href="../view_allocation.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<div class="card p-3 mb-4">
  <form method="get" class="d-flex gap-2">
    <input type="text" class="form-control" name="q" value="<?= h($query) ?>" placeholder="Enter register number or student name..." autofocus>
    <button class="btn btn-primary"><i class="bi bi-search me-1"></i>Search</button>
  </form>
</div>

<?php if ($query !== ''): ?>
  <?php if (!$results): ?>
    <div class="alert alert-warning">No seating record found for "<?= h($query) ?>". Either the name/register number is wrong, or seating hasn't been generated for that student's exam yet.</div>
  <?php else: ?>
    <?php foreach ($results as $r): ?>
      <div class="card p-3 mb-3">
        <div class="row">
          <div class="col-md-3"><strong>Register No.</strong><br><?= h($r['register_no']) ?></div>
          <div class="col-md-3"><strong>Name</strong><br><?= h($r['student_name']) ?></div>
          <div class="col-md-3"><strong>Department</strong><br><?= h($r['dept_name']) ?></div>
          <div class="col-md-3"><strong>Subject</strong><br><?= h($r['subject_code']) ?> — <?= h($r['subject_name']) ?></div>
        </div>
        <hr>
        <div class="row">
          <div class="col-md-3"><strong>Date</strong><br><?= h($r['exam_date']) ?></div>
          <div class="col-md-3"><strong>Session</strong><br><?= $r['session_slot']=='FN'?'Forenoon':'Afternoon' ?></div>
          <div class="col-md-3"><strong>Hall</strong><br><?= h($r['hall_name']) ?></div>
          <div class="col-md-3"><strong>Seat</strong><br><span class="badge bg-primary fs-6"><?= h($r['seat_label']) ?></span></div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
