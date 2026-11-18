<?php
declare(strict_types=1);
require __DIR__ . '/../../includes/bootstrap.php';

// Public, read-only totals for the home-page live counters. No personal data.
try {
    $pdo = db();
    $row = $pdo->query(
        'SELECT
           (SELECT COUNT(*) FROM users WHERE role = \'student\') AS students,
           (SELECT COUNT(*) FROM evaluations)                    AS evaluations,
           (SELECT COUNT(*) FROM units)                          AS units,
           (SELECT ROUND(AVG((teaching_quality + clarity + engagement + punctuality + materials) / 5), 2)
              FROM evaluations)                                  AS average'
    )->fetch();

    json_response([
        'students'    => (int)$row['students'],
        'evaluations' => (int)$row['evaluations'],
        'units'       => (int)$row['units'],
        'average'     => $row['average'] !== null ? (float)$row['average'] : null,
        'updated'     => date('H:i:s'),
    ]);
} catch (Throwable $ex) {
    error_log('stats api: ' . $ex->getMessage());
    json_response(['error' => 'unavailable'], 503);
}
