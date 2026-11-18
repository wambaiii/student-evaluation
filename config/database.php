<?php
declare(strict_types=1);

/**
 * Database connection (PDO).
 *
 * Nothing is hard-coded: DB_HOST, DB_NAME, DB_USER and DB_PASSWORD come from the
 * environment (.env locally, Apache SetEnv / .env on Azure). Moving from your laptop
 * to the Azure VM therefore needs NO code change - only different settings.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host     = env('DB_HOST', '127.0.0.1');
    $port     = env('DB_PORT', '3306');
    $database = env('DB_NAME', 'student_evaluation');
    $username = env('DB_USER');
    $password = env('DB_PASSWORD');

    if ($username === null || $password === null) {
        throw new RuntimeException('Database credentials are not configured');
    }

    $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";

    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,   // real prepared statements = SQL-injection defence
    ]);

    return $pdo;
}
