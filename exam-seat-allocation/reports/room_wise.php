<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();
$pageTitle = 'Room-wise Report';

$sessionKey = $_GET['session_key'] ?? '';
$hallsData = [];

if ($sessionKey) {
    $stmt = $pdo->prepare(
        "SELECT a.*, s.register_no, s.name as student_name, d.dept_code, d.dept_name,
                h.hall_name, e.subject_code, e.subject_name, e.exam_date, e.session_slot
         FROM exam_allocations a
         JOIN students s ON s.id = a.student_id
         JOIN departments d ON d.id = s.department_id
         JOIN halls h ON h.id = a.hall_id
         JOIN exams e ON e.id = a.exam_id
         WHERE a.session_key = ?
         ORDER BY h.hall_name, a.seat_row, a.seat_col"
    );
    $stmt->execute([$sessionKey]);
    foreach ($stmt->fetchAll() as $r) {
        $hallsData[$r['hall_name']][] = $r;
    }
}

require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
  <h5 class="fw-bold mb-0">Room-wise Allocation Report — <?= h($sessionKey) ?></h5>
  <div>
    <a href="../view_allocation.php?session_key=<?= urlencode($sessionKey) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
    <button onclick="printSection()" class="btn btn-sm btn-primary"><i class="bi bi-printer me-1"></i>Print</button>
  </div>
</div>

<?php if (!$hallsData): ?>
  <div class="alert alert-warning">No allocation data found for this session.</div>
<?php endif; ?>

<?php foreach ($hallsData as $hallName => $rows): ?>
  <div class="card p-3 mb-4">
    <h6 class="fw-bold">Hall: <?= h($hallName) ?></h6>
    <p class="text-muted small mb-3">
      <?= h($rows[0]['exam_date']) ?> — <?= $rows[0]['session_slot']=='FN'?'Forenoon':'Afternoon' ?> · Total candidates: <?= count($rows) ?>
    </p>
    <div class="table-responsive">
      <table class="table table-sm table-bordered">
        <thead class="table-light">
          <tr><th>Seat</th><th>Register No.</th><th>Name</th><th>Department</th><th>Subject</th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= h($r['seat_label']) ?></td>
            <td><?= h($r['register_no']) ?></td>
            <td><?= h($r['student_name']) ?></td>
            <td><?= h($r['dept_code']) ?></td>
            <td><?= h($r['subject_code']) ?> — <?= h($r['subject_name']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endforeach; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
