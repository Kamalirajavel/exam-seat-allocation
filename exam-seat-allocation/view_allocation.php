<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_login();
$pageTitle = 'View Allocation';

$sessionKeys = $pdo->query(
    "SELECT session_key, COUNT(*) as seated, MIN(created_at) as generated_at
     FROM exam_allocations GROUP BY session_key ORDER BY session_key DESC"
)->fetchAll();

$sessionKey = $_GET['session_key'] ?? ($sessionKeys[0]['session_key'] ?? '');

$hallsUsed = [];
$deptColorMap = [];
if ($sessionKey) {
    $stmt = $pdo->prepare(
        "SELECT a.*, s.register_no, s.name as student_name, s.department_id,
                d.dept_code, d.dept_name, h.hall_name, h.rows_count, h.cols_count,
                e.subject_code, e.subject_name
         FROM exam_allocations a
         JOIN students s ON s.id = a.student_id
         JOIN departments d ON d.id = s.department_id
         JOIN halls h ON h.id = a.hall_id
         JOIN exams e ON e.id = a.exam_id
         WHERE a.session_key = ?
         ORDER BY h.hall_name, a.seat_row, a.seat_col"
    );
    $stmt->execute([$sessionKey]);
    $rows = $stmt->fetchAll();

    $colorIdx = 0;
    foreach ($rows as $r) {
        if (!isset($hallsUsed[$r['hall_id']])) {
            $hallsUsed[$r['hall_id']] = [
                'hall_name' => $r['hall_name'],
                'rows_count' => $r['rows_count'],
                'cols_count' => $r['cols_count'],
                'seats' => [],
            ];
        }
        $hallsUsed[$r['hall_id']]['seats'][$r['seat_row']][$r['seat_col']] = $r;

        if (!isset($deptColorMap[$r['dept_code']])) {
            $deptColorMap[$r['dept_code']] = $colorIdx % 8;
            $colorIdx++;
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 no-print">
  <h5 class="fw-bold mb-0">View Seating Allocation</h5>
  <div class="d-flex gap-2">
    <form class="d-flex gap-2" method="get">
      <select class="form-select form-select-sm" name="session_key" onchange="this.form.submit()">
        <?php foreach ($sessionKeys as $sk): ?>
          <option value="<?= h($sk['session_key']) ?>" <?= $sk['session_key']===$sessionKey?'selected':'' ?>>
            <?= h($sk['session_key']) ?> (<?= (int)$sk['seated'] ?> seated)
          </option>
        <?php endforeach; ?>
      </select>
    </form>
    <?php if ($sessionKey): ?>
      <a href="reports/room_wise.php?session_key=<?= urlencode($sessionKey) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-building me-1"></i>Room-wise Report</a>
      <a href="reports/student_wise.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-person-lines-fill me-1"></i>Find Student Seat</a>
      <button onclick="printSection()" class="btn btn-sm btn-primary"><i class="bi bi-printer me-1"></i>Print</button>
    <?php endif; ?>
  </div>
</div>

<?php if (!$sessionKeys): ?>
  <div class="alert alert-warning">No seating has been generated yet. Go to <a href="allocate.php">Allocate Seats</a> to generate one.</div>
<?php else: ?>

<div class="mb-4 no-print">
  <strong class="small">Legend: </strong>
  <?php foreach ($deptColorMap as $code => $idx): ?>
    <span class="badge dept-color-<?= $idx ?> me-1"><?= h($code) ?></span>
  <?php endforeach; ?>
</div>

<?php foreach ($hallsUsed as $hallId => $hall): ?>
  <div class="hall-block card p-3">
    <h6 class="fw-bold mb-3"><i class="bi bi-building me-1"></i><?= h($hall['hall_name']) ?>
      <span class="text-muted small fw-normal">(<?= (int)$hall['rows_count'] ?> × <?= (int)$hall['cols_count'] ?>)</span>
    </h6>
    <div class="seat-grid" style="grid-template-columns: repeat(<?= (int)$hall['cols_count'] ?>, 1fr);">
      <?php for ($r = 1; $r <= $hall['rows_count']; $r++): ?>
        <?php for ($c = 1; $c <= $hall['cols_count']; $c++): ?>
          <?php $seat = $hall['seats'][$r][$c] ?? null; ?>
          <?php if ($seat): ?>
            <div class="seat dept-color-<?= $deptColorMap[$seat['dept_code']] ?>" title="<?= h($seat['student_name']) ?> — <?= h($seat['subject_code']) ?>">
              <div class="seat-reg"><?= h($seat['register_no']) ?></div>
              <div class="seat-dept"><?= h($seat['dept_code']) ?></div>
              <div class="seat-dept">Seat <?= h($seat['seat_label']) ?></div>
            </div>
          <?php else: ?>
            <div class="seat empty">—</div>
          <?php endif; ?>
        <?php endfor; ?>
      <?php endfor; ?>
    </div>
  </div>
<?php endforeach; ?>

<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
