<?php
// api/config.php
$host = getenv('DB_HOST'); // Ej: ep-cool-darkness-123456.us-east-2.aws.neon.tech
$db   = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASSWORD');
$port = "5432";

try {
    // El formato correcto para Postgres PDO es:
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require";
    
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ATTR_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    // Esto te ayudará a ver si falta alguna variable de entorno en Vercel
    die("Error de conexión: " . $e->getMessage());
}
?>
