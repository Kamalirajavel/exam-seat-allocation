<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_login();

$pageTitle = 'Dashboard';

$deptCount = $pdo->query('SELECT COUNT(*) FROM departments')->fetchColumn();
$studentCount = $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
$hallCount = $pdo->query('SELECT COUNT(*) FROM halls')->fetchColumn();
$totalCapacity = $pdo->query('SELECT COALESCE(SUM(rows_count*cols_count),0) FROM halls')->fetchColumn();
$examCount = $pdo->query('SELECT COUNT(*) FROM exams')->fetchColumn();

$upcoming = $pdo->query(
    "SELECT e.*, d.dept_name FROM exams e
     JOIN departments d ON d.id = e.department_id
     ORDER BY e.exam_date ASC, e.session_slot ASC LIMIT 8"
)->fetchAll();

$recentSessions = $pdo->query(
    "SELECT session_key, COUNT(*) as seated, COUNT(DISTINCT hall_id) as halls_used, MAX(created_at) as generated_at
     FROM exam_allocations GROUP BY session_key ORDER BY generated_at DESC LIMIT 5"
)->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div class="row g-3 mb-4">
  <div class="col-md-3 col-6">
    <div class="stat-card bg-a">
      <div class="stat-num"><?= (int)$deptCount ?></div>
      <div>Departments</div>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="stat-card bg-b">
      <div class="stat-num"><?= (int)$studentCount ?></div>
      <div>Students</div>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="stat-card bg-c">
      <div class="stat-num"><?= (int)$hallCount ?></div>
      <div>Halls (<?= (int)$totalCapacity ?> seats)</div>
    </div>
  </div>
  <div class="col-md-3 col-6">
    <div class="stat-card bg-d">
      <div class="stat-num"><?= (int)$examCount ?></div>
      <div>Scheduled Exams</div>
    </div>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-7">
    <div class="card p-3">
      <h6 class="fw-bold mb-3"><i class="bi bi-calendar-event me-2"></i>Upcoming Exams</h6>
      <?php if (!$upcoming): ?>
        <p class="text-muted mb-0">No exams scheduled yet. <a href="exams.php">Add one</a>.</p>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead><tr><th>Date</th><th>Slot</th><th>Subject</th><th>Department</th></tr></thead>
          <tbody>
          <?php foreach ($upcoming as $e): ?>
            <tr>
              <td><?= h($e['exam_date']) ?></td>
              <td><span class="badge bg-secondary"><?= h($e['session_slot']) ?></span></td>
              <td><?= h($e['subject_code']) ?> — <?= h($e['subject_name']) ?></td>
              <td><?= h($e['dept_name']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card p-3">
      <h6 class="fw-bold mb-3"><i class="bi bi-clock-history me-2"></i>Recent Allocations</h6>
      <?php if (!$recentSessions): ?>
        <p class="text-muted mb-0">No seating generated yet. <a href="allocate.php">Run allocation</a>.</p>
      <?php else: ?>
        <ul class="list-group list-group-flush">
        <?php foreach ($recentSessions as $s): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <div>
              <div class="fw-semibold"><?= h($s['session_key']) ?></div>
              <small class="text-muted"><?= (int)$s['seated'] ?> students · <?= (int)$s['halls_used'] ?> halls</small>
            </div>
            <a href="view_allocation.php?session_key=<?= urlencode($s['session_key']) ?>" class="btn btn-sm btn-outline-primary">View</a>
          </li>
        <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
