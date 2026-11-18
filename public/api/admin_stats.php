<?php
declare(strict_types=1);
require __DIR__ . '/../../includes/bootstrap.php';

// Admin-only JSON feed that the dashboard polls every few seconds.
$user = current_user();
if ($user === null || $user['role'] !== 'admin') {
    json_response(['error' => 'forbidden'], 403);
}

try {
    $pdo = db();
    $overall = '(e.teaching_quality + e.clarity + e.engagement + e.punctuality + e.materials) / 5';

    $totals = $pdo->query(
        "SELECT COUNT(*) AS evaluations, ROUND(AVG($overall), 2) AS average FROM evaluations e"
    )->fetch();

    $students = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
    $units    = (int)$pdo->query('SELECT COUNT(*) FROM units')->fetchColumn();

    $perUnit = $pdo->query(
        "SELECT u.id, u.code, u.title, l.full_name AS lecturer,
                COUNT(e.id) AS responses,
                ROUND(AVG($overall), 2) AS average
         FROM units u
         JOIN lecturers l ON l.id = u.lecturer_id
         LEFT JOIN evaluations e ON e.unit_id = u.id
         GROUP BY u.id, u.code, u.title, l.full_name
         ORDER BY u.code"
    )->fetchAll();

    // How many evaluations fall in each overall star bucket (1..5)
    $dist = array_fill(1, 5, 0);
    $rows = $pdo->query(
        "SELECT ROUND($overall) AS bucket, COUNT(*) AS n FROM evaluations e GROUP BY bucket"
    )->fetchAll();
    foreach ($rows as $r) {
        $b = max(1, min(5, (int)$r['bucket']));
        $dist[$b] += (int)$r['n'];
    }

    // Average per question (for the radar-style bars)
    $q = $pdo->query(
        'SELECT ROUND(AVG(teaching_quality),2) AS teaching_quality, ROUND(AVG(clarity),2) AS clarity,
                ROUND(AVG(engagement),2) AS engagement, ROUND(AVG(punctuality),2) AS punctuality,
                ROUND(AVG(materials),2) AS materials
         FROM evaluations'
    )->fetch();

    $comments = $pdo->query(
        "SELECT e.comment, e.submitted_on, u.code
         FROM evaluations e JOIN units u ON u.id = e.unit_id
         WHERE e.comment IS NOT NULL AND e.comment <> ''
         ORDER BY e.id DESC LIMIT 10"
    )->fetchAll();

    json_response([
        'totals'   => [
            'students'    => $students,
            'units'       => $units,
            'evaluations' => (int)$totals['evaluations'],
            'average'     => $totals['average'] !== null ? (float)$totals['average'] : null,
        ],
        'per_unit' => array_map(static fn($r) => [
            'id' => (int)$r['id'], 'code' => $r['code'], 'title' => $r['title'],
            'lecturer' => $r['lecturer'], 'responses' => (int)$r['responses'],
            'average' => $r['average'] !== null ? (float)$r['average'] : null,
        ], $perUnit),
        'distribution' => array_values($dist),
        'questions'    => array_map(static fn($v) => $v !== null ? (float)$v : 0.0, $q ?: []),
        'comments'     => $comments,
        'updated'      => date('H:i:s'),
    ]);
} catch (Throwable $ex) {
    error_log('admin stats api: ' . $ex->getMessage());
    json_response(['error' => 'unavailable'], 503);
}
