<?php

function getEnvVar($name, $default = null) {
    $value = getenv($name);
    if ($value === false) {
        $value = $_ENV[$name] ?? $default;
    }
    return $value;
}

$host = getEnvVar('NEON_HOST', '');
$dbname = getEnvVar('NEON_DBNAME', 'reparaciones');
$user = getEnvVar('NEON_USER', '');
$password = getEnvVar('NEON_PASSWORD', '');

if (empty($host) || empty($user) || empty($password)) {
    die("Configura las variables de entorno NEON_HOST, NEON_USER y NEON_PASSWORD");
}

try {
    $dsn = "pgsql:host=$host;dbname=$dbname;options='-c client_encoding=utf8'";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}