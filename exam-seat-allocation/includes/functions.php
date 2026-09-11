<?php
// =====================================================================
// Helper / utility functions + Seat Allocation Algorithm
// =====================================================================

function h(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

function session_key_for(string $date, string $slot): string {
    return $date . '_' . $slot;
}

/**
 * Build a department-interleaved sequence of students so that students
 * from the same department are spread out as evenly as possible before
 * the grid "repair" pass runs.
 *
 * $studentsByDept: [ department_id => [ student rows... ] ]
 * returns: flat array of student rows, interleaved round-robin.
 */
function build_interleaved_sequence(array $studentsByDept): array {
    // Sort each department's students by register number for readability.
    foreach ($studentsByDept as &$list) {
        usort($list, fn($a, $b) => strcmp($a['register_no'], $b['register_no']));
    }
    unset($list);

    $queues = array_values($studentsByDept);
    $sequence = [];
    $remaining = true;

    while ($remaining) {
        $remaining = false;
        foreach ($queues as &$queue) {
            if (!empty($queue)) {
                $sequence[] = array_shift($queue);
                if (!empty($queue)) {
                    $remaining = true;
                }
            }
        }
        unset($queue);
    }

    return $sequence;
}

/**
 * Allocate students into the given halls (in the order provided), filling
 * each hall row by row, and running a "repair" pass that swaps students
 * forward in the sequence whenever two students from the same department
 * would end up seated directly next to each other (left/right) or directly
 * in front/behind each other, within the same hall.
 *
 * $halls: array of ['id'=>, 'rows_count'=>, 'cols_count'=>]
 * $sequence: flat array of student rows (already interleaved)
 *
 * Returns: [
 *   'assignments' => [ ['student'=>row, 'hall_id'=>, 'row'=>, 'col'=>, 'seat_label'=>], ... ],
 *   'unseated'    => [ student rows that didn't fit in available capacity ]
 * ]
 */
function allocate_seats(array $halls, array $sequence): array {
    // Build the ordered list of physical seat "cells" across all halls.
    $cells = [];
    foreach ($halls as $hall) {
        for ($r = 1; $r <= (int)$hall['rows_count']; $r++) {
            for ($c = 1; $c <= (int)$hall['cols_count']; $c++) {
                $cells[] = ['hall_id' => (int)$hall['id'], 'row' => $r, 'col' => $c];
            }
        }
    }

    $totalCapacity = count($cells);
    $unseated = [];
    if (count($sequence) > $totalCapacity) {
        // Trim the overflow; caller should warn the user about this.
        $unseated = array_slice($sequence, $totalCapacity);
        $sequence = array_slice($sequence, 0, $totalCapacity);
    }

    // Pad sequence with nulls up to cell count so index math lines up.
    $n = count($sequence);

    // Quick lookup: cell index -> department id (once assigned)
    $deptAt = array_fill(0, $n, null);
    for ($i = 0; $i < $n; $i++) {
        $deptAt[$i] = $sequence[$i]['department_id'];
    }

    // Helper: index of left neighbour / top neighbour within same hall & grid,
    // or null if none (start of row / first row / different hall).
    $leftIndex = function (int $i) use ($cells): ?int {
        if ($i <= 0) return null;
        $cur = $cells[$i];
        $prev = $cells[$i - 1];
        if ($prev['hall_id'] === $cur['hall_id'] && $prev['row'] === $cur['row'] && $prev['col'] === $cur['col'] - 1) {
            return $i - 1;
        }
        return null;
    };
    $topIndex = function (int $i) use ($cells): ?int {
        $cur = $cells[$i];
        for ($j = $i - 1; $j >= 0; $j--) {
            if ($cells[$j]['hall_id'] !== $cur['hall_id']) return null;
            if ($cells[$j]['col'] === $cur['col'] && $cells[$j]['row'] === $cur['row'] - 1) return $j;
            if ($cells[$j]['row'] < $cur['row'] - 1) return null;
        }
        return null;
    };

    // Repair pass: for each cell, if it conflicts with left/top neighbour,
    // find a later student (further in the sequence) whose department
    // differs from both neighbours and swap.
    for ($i = 0; $i < $n; $i++) {
        $li = $leftIndex($i);
        $ti = $topIndex($i);
        $leftDept = $li !== null ? $deptAt[$li] : null;
        $topDept  = $ti !== null ? $deptAt[$ti] : null;

        if ($deptAt[$i] === $leftDept || $deptAt[$i] === $topDept) {
            for ($j = $i + 1; $j < $n; $j++) {
                if ($deptAt[$j] !== $leftDept && $deptAt[$j] !== $topDept) {
                    // swap sequence[i] and sequence[j]
                    [$sequence[$i], $sequence[$j]] = [$sequence[$j], $sequence[$i]];
                    [$deptAt[$i], $deptAt[$j]] = [$deptAt[$j], $deptAt[$i]];
                    break;
                }
            }
        }
    }

    $assignments = [];
    for ($i = 0; $i < $n; $i++) {
        $cell = $cells[$i];
        $seatLabel = 'R' . $cell['row'] . 'C' . $cell['col'];
        $assignments[] = [
            'student'    => $sequence[$i],
            'hall_id'    => $cell['hall_id'],
            'row'        => $cell['row'],
            'col'        => $cell['col'],
            'seat_label' => $seatLabel,
        ];
    }

    return ['assignments' => $assignments, 'unseated' => $unseated];
}
