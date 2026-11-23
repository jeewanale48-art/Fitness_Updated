<?php
declare(strict_types=1);

/** Weekly coach availability helpers (weekday numbers follow ISO-8601: 1 = Monday ... 7 = Sunday). */

function fittrackWeekdays(): array
{
    return [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];
}

/** @return array<int, array<int, array<int, array{start:string,end:string}>>> coach_id => weekday => windows */
function fittrackLoadSchedules(PDO $pdo, ?int $coachId = null): array
{
    $sql = 'SELECT coach_id, weekday, start_time, end_time FROM coach_availability';
    $params = [];
    if ($coachId !== null) {
        $sql .= ' WHERE coach_id = ?';
        $params[] = $coachId;
    }
    $sql .= ' ORDER BY coach_id, weekday, start_time';
    $query = $pdo->prepare($sql);
    $query->execute($params);

    $schedules = [];
    foreach ($query->fetchAll() as $row) {
        $schedules[(int) $row['coach_id']][(int) $row['weekday']][] = [
            'start' => substr((string) $row['start_time'], 0, 5),
            'end' => substr((string) $row['end_time'], 0, 5),
        ];
    }
    return $schedules;
}

function fittrackFormatTime(string $hhmm): string
{
    $time = DateTime::createFromFormat('H:i', substr($hhmm, 0, 5));
    return $time ? $time->format('g:i A') : $hhmm;
}

/** Reads the 7-day grid posted by fittrackScheduleGrid(). Returns day => [start,end], or null with $error. */
function fittrackParseSchedulePost(array $post, ?string &$error): ?array
{
    $avail = is_array($post['avail'] ?? null) ? $post['avail'] : [];
    $starts = is_array($post['start'] ?? null) ? $post['start'] : [];
    $ends = is_array($post['end'] ?? null) ? $post['end'] : [];

    $rows = [];
    foreach (fittrackWeekdays() as $day => $label) {
        if (empty($avail[$day])) {
            continue;
        }
        $from = trim((string) ($starts[$day] ?? ''));
        $until = trim((string) ($ends[$day] ?? ''));
        if (!preg_match('/^\d{2}:\d{2}$/', $from) || !preg_match('/^\d{2}:\d{2}$/', $until) || $from >= $until) {
            $error = $label . ': choose a start time that is earlier than the end time.';
            return null;
        }
        $rows[$day] = ['start' => $from, 'end' => $until];
    }
    return $rows;
}

/** Replaces a coach's whole weekly schedule (up to 7 days) atomically. */
function fittrackSaveSchedule(PDO $pdo, int $coachId, array $rows): void
{
    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM coach_availability WHERE coach_id = ?')->execute([$coachId]);
        $insert = $pdo->prepare('INSERT INTO coach_availability (coach_id, weekday, start_time, end_time) VALUES (?, ?, ?, ?)');
        foreach ($rows as $day => $window) {
            $insert->execute([$coachId, (int) $day, $window['start'] . ':00', $window['end'] . ':00']);
        }
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

/** "5 of 7 days" style summary. */
function fittrackScheduleSummary(array $byDay): string
{
    $count = count(array_filter($byDay));
    return $count === 0 ? 'No schedule yet' : $count . ' of 7 days';
}

/** Editable Monday-Sunday grid (admin). Field names: avail[N], start[N], end[N]. */
function fittrackScheduleGrid(array $byDay = []): void
{
    ?>
    <div class="schedule-grid" data-schedule>
        <div class="schedule-row schedule-head"><span>Day</span><span>Available</span><span>From</span><span>Until</span></div>
        <?php foreach (fittrackWeekdays() as $day => $label):
            $windows = $byDay[$day] ?? [];
            $start = $windows ? min(array_column($windows, 'start')) : '06:00';
            $end = $windows ? max(array_column($windows, 'end')) : '17:00';
            ?>
            <div class="schedule-row">
                <span class="schedule-day"><?= e($label) ?></span>
                <label class="schedule-on"><input class="sch-on" type="checkbox" name="avail[<?= $day ?>]" value="1" <?= $windows ? 'checked' : '' ?>> Available</label>
                <input class="sch-start" type="time" name="start[<?= $day ?>]" value="<?= e($start) ?>">
                <input class="sch-end" type="time" name="end[<?= $day ?>]" value="<?= e($end) ?>">
            </div>
        <?php endforeach; ?>
        <div class="schedule-tools">
            <button type="button" class="button light btn-small" data-schedule-all>Available all 7 days</button>
            <button type="button" class="button light btn-small" data-schedule-copy>Copy Monday's hours to every day</button>
            <button type="button" class="button light btn-small" data-schedule-clear>Clear all</button>
        </div>
    </div>
    <?php
}

/** Read-only 7-day list for members. */
function fittrackScheduleTable(array $byDay): void
{
    ?>
    <ul class="avail-list">
        <?php foreach (fittrackWeekdays() as $day => $label): $windows = $byDay[$day] ?? []; ?>
            <li class="<?= $windows ? 'on' : 'off' ?>">
                <span class="avail-day"><?= e(substr($label, 0, 3)) ?></span>
                <span class="avail-time"><?php
                    if (!$windows) {
                        echo 'Not available';
                    } else {
                        $parts = [];
                        foreach ($windows as $w) {
                            $parts[] = fittrackFormatTime($w['start']) . ' – ' . fittrackFormatTime($w['end']);
                        }
                        echo e(implode(', ', $parts));
                    }
                ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php
}

/**
 * Open 60-minute slots for the next $days days, grouped by date (Y-m-d => DateTimeImmutable[]).
 * $busy is a list of [start, end] DateTimeImmutable pairs for pending/approved bookings.
 */
function fittrackOpenSlots(array $byDay, array $busy, int $days = 14, int $minutes = 60): array
{
    $earliest = (new DateTimeImmutable('now'))->modify('+1 hour');
    $today = new DateTimeImmutable('today');
    $slots = [];

    for ($i = 0; $i < $days; $i++) {
        $date = $today->modify('+' . $i . ' day');
        $weekday = (int) $date->format('N');
        if (empty($byDay[$weekday])) {
            continue;
        }
        $dateKey = $date->format('Y-m-d');
        foreach ($byDay[$weekday] as $window) {
            $cursor = new DateTimeImmutable($dateKey . ' ' . $window['start']);
            $limit = new DateTimeImmutable($dateKey . ' ' . $window['end']);
            while (true) {
                $slotEnd = $cursor->modify('+' . $minutes . ' minutes');
                if ($slotEnd > $limit) {
                    break;
                }
                if ($cursor >= $earliest) {
                    $clash = false;
                    foreach ($busy as $range) {
                        if ($range[0] < $slotEnd && $range[1] > $cursor) {
                            $clash = true;
                            break;
                        }
                    }
                    if (!$clash) {
                        $slots[$dateKey][] = $cursor;
                    }
                }
                $cursor = $slotEnd;
            }
        }
    }
    return $slots;
}
