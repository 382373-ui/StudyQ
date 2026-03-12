<?php
// PostgreSQL connection using DATABASE_URL
$databaseUrl = getenv('DATABASE_URL');

if ($databaseUrl) {
    // Parse DATABASE_URL (postgresql://user:password@host:port/dbname)
    $url = parse_url($databaseUrl);
    $host = $url['host'] ?? getenv('PGHOST');
    $db = ltrim($url['path'] ?? '', '/') ?: getenv('PGDATABASE');
    $user = $url['user'] ?? getenv('PGUSER');
    $pass = $url['pass'] ?? getenv('PGPASSWORD');
    $port = $url['port'] ?? getenv('PGPORT') ?? 5432;
    
    $dsn = "pgsql:host=$host;port=$port;dbname=$db";
} else {
    // Fallback to MySQL if DATABASE_URL not available
    $host = getenv('DB_HOST') ?: 'localhost';
    $db = getenv('DB_NAME');
    $user = getenv('DB_USER');
    $pass = getenv('DB_PASSWORD');
    $charset = 'utf8mb4';
    
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
}

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
    // Don't throw in db.php - let pages handle the error gracefully
    $pdo = null;
}
?>
