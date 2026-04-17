<?php
// api/config.php
$host = getenv('DB_HOST');
$db   = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASSWORD');

try {
    // IMPORTANTE: Sin espacios extra y asegurando que $host no esté vacío
    if (!$host) {
        throw new Exception("La variable DB_HOST no está definida en Vercel");
    }

    $dsn = "pgsql:host=$host;port=5432;dbname=$db;sslmode=require";
    
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ATTR_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 5 // Tiempo de espera para evitar bloqueos
    ]);
    
} catch (Exception $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>
