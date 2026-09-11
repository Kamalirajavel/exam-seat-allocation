<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_login();
$pageTitle = 'Allocate Seats';

// Distinct date + session combos that have exams scheduled
$sessions = $pdo->query(
    "SELECT exam_date, session_slot, COUNT(*) as exam_count,
     GROUP_CONCAT(subject_code SEPARATOR ', ') as subjects
     FROM exams GROUP BY exam_date, session_slot ORDER BY exam_date, session_slot"
)->fetchAll();

$halls = $pdo->query('SELECT * FROM halls ORDER BY hall_name')->fetchAll();

$result = null;
$selectedDate = $_POST['exam_date'] ?? ($_GET['exam_date'] ?? '');
$selectedSlot = $_POST['session_slot'] ?? ($_GET['session_slot'] ?? '');
$selectedHallIds = $_POST['hall_ids'] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'run') {
    $selectedDate = $_POST['exam_date'];
    $selectedSlot = $_POST['session_slot'];
    $selectedHallIds = array_map('intval', $_POST['hall_ids'] ?? []);

    if (!$selectedDate || !$selectedSlot || empty($selectedHallIds)) {
        set_flash('danger', 'Please choose an exam session and at least one hall.');
        header('Location: allocate.php');
        exit;
    }

    // Get exams in this session
    $stmt = $pdo->prepare('SELECT * FROM exams WHERE exam_date = ? AND session_slot = ?');
    $stmt->execute([$selectedDate, $selectedSlot]);
    $examsInSession = $stmt->fetchAll();

    if (!$examsInSession) {
        set_flash('danger', 'No exams found for the selected date/session.');
        header('Location: allocate.php');
        exit;
    }

    // Gather students for each exam's department (dedup by student id in case
    // of overlapping departments across exams in same session)
    $studentsByDept = [];
    $studentToExam = [];
    foreach ($examsInSession as $exam) {
        $stmt = $pdo->prepare('SELECT * FROM students WHERE department_id = ?');
        $stmt->execute([$exam['department_id']]);
        $rows = $stmt->fetchAll();
        if (!isset($studentsByDept[$exam['department_id']])) {
            $studentsByDept[$exam['department_id']] = [];
        }
        foreach ($rows as $r) {
            $studentsByDept[$exam['department_id']][$r['id']] = $r;
            $studentToExam[$r['id']] = $exam['id'];
        }
    }
    foreach ($studentsByDept as &$grp) { $grp = array_values($grp); }
    unset($grp);

    // Fetch selected halls in the order chosen, sorted by name for stability
    $in = implode(',', array_fill(0, count($selectedHallIds), '?'));
    $stmt = $pdo->prepare("SELECT * FROM halls WHERE id IN ($in) ORDER BY hall_name");
    $stmt->execute($selectedHallIds);
    $chosenHalls = $stmt->fetchAll();

    $sequence = build_interleaved_sequence($studentsByDept);
    $alloc = allocate_seats($chosenHalls, $sequence);

    $sessionKey = session_key_for($selectedDate, $selectedSlot);

    // Clear any previous allocation for this exact session before saving new one
    $del = $pdo->prepare('DELETE FROM exam_allocations WHERE session_key = ?');
    $del->execute([$sessionKey]);

    $ins = $pdo->prepare(
        'INSERT INTO exam_allocations (session_key, exam_id, student_id, hall_id, seat_row, seat_col, seat_label)
         VALUES (?,?,?,?,?,?,?)'
    );
    foreach ($alloc['assignments'] as $a) {
        $studentId = $a['student']['id'];
        $examId = $studentToExam[$studentId];
        $ins->execute([$sessionKey, $examId, $studentId, $a['hall_id'], $a['row'], $a['col'], $a['seat_label']]);
    }

    if ($alloc['unseated']) {
        set_flash('danger', count($alloc['unseated']) . ' student(s) could not be seated — selected halls do not have enough capacity. Please add more halls/capacity and re-run.');
    } else {
        set_flash('success', 'Seating arrangement generated for ' . count($alloc['assignments']) . ' students across ' . count($chosenHalls) . ' hall(s).');
    }

    header('Location: view_allocation.php?session_key=' . urlencode($sessionKey));
    exit;
}

require __DIR__ . '/includes/header.php';
?>

<h5 class="fw-bold mb-3">Allocate Seats</h5>

<?php if (!$sessions): ?>
  <div class="alert alert-warning">No exams scheduled yet. <a href="exams.php">Schedule an exam</a> first.</div>
<?php elseif (!$halls): ?>
  <div class="alert alert-warning">No halls added yet. <a href="halls.php">Add a hall</a> first.</div>
<?php else: ?>

<div class="card p-4">
  <form action="allocate.php" method="post">
    <input type="hidden" name="action" value="run">

    <label class="form-label fw-semibold">1. Choose Exam Session</label>
    <div class="list-group mb-4">
      <?php foreach ($sessions as $s): ?>
        <?php $checked = ($s['exam_date'] === $selectedDate && $s['session_slot'] === $selectedSlot); ?>
        <label class="list-group-item d-flex gap-2 align-items-start">
          <input class="form-check-input mt-1" type="radio" name="session_choice"
                 value="<?= h($s['exam_date']) ?>|<?= h($s['session_slot']) ?>"
                 onchange="document.getElementById('exam_date_input').value='<?= h($s['exam_date']) ?>'; document.getElementById('session_slot_input').value='<?= h($s['session_slot']) ?>';"
                 <?= $checked ? 'checked' : '' ?> required>
          <span>
            <strong><?= h($s['exam_date']) ?></strong> — <?= $s['session_slot']=='FN' ? 'Forenoon' : 'Afternoon' ?>
            <br><small class="text-muted"><?= (int)$s['exam_count'] ?> exam(s): <?= h($s['subjects']) ?></small>
          </span>
        </label>
      <?php endforeach; ?>
    </div>
    <input type="hidden" id="exam_date_input" name="exam_date" value="<?= h($selectedDate) ?>">
    <input type="hidden" id="session_slot_input" name="session_slot" value="<?= h($selectedSlot) ?>">

    <label class="form-label fw-semibold">2. Choose Halls to Use</label>
    <div class="row g-2 mb-4">
      <?php foreach ($halls as $hl): ?>
        <div class="col-md-4">
          <label class="card p-2 d-flex flex-row align-items-center gap-2" style="cursor:pointer;">
            <input type="checkbox" class="form-check-input" name="hall_ids[]" value="<?= $hl['id'] ?>"
              <?= in_array($hl['id'], $selectedHallIds) ? 'checked' : '' ?>>
            <span><strong><?= h($hl['hall_name']) ?></strong><br><small class="text-muted"><?= (int)$hl['capacity'] ?> seats</small></span>
          </label>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="alert alert-secondary small">
      Running this will generate a fresh seating arrangement for the selected session (department-mixed, no two same-department students adjacent where avoidable). Re-running for the same date/session replaces the previous arrangement.
    </div>

    <button type="submit" class="btn btn-primary"><i class="bi bi-shuffle me-1"></i>Generate Seating Arrangement</button>
  </form>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
