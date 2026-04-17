<?php
// 1. Iniciar sesión ANTES de cualquier HTML
session_start();

// --- CONFIGURACIÓN DE SEGURIDAD ---
$password_maestra = "admin123"; 

// Inicializar variables
$registros = [];
$es_admin = isset($_SESSION['admin']);
$error_db = null;
$buscar = isset($_GET['q']) ? $_GET['q'] : '';

// Lógica de Login/Logout
if (isset($_POST['login'])) {
    if (isset($_POST['pass']) && $_POST['pass'] === $password_maestra) {
        $_SESSION['admin'] = true;
        $es_admin = true;
    }
}
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// --- CONEXIÓN A BASE DE DATOS ---
$host = getenv('PGHOST');
$db   = getenv('PGDATABASE');
$user = getenv('PGUSER');
$pass = getenv('PGPASSWORD');
$port = getenv('PGPORT') ?: '5432';

try {
    if ($host) {
        $dsn = "pgsql:host=$host;port=$port;dbname=$db";
        $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        // Crear tabla
        $pdo->exec("CREATE TABLE IF NOT EXISTS reparaciones (
            id SERIAL PRIMARY KEY,
            fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            cliente VARCHAR(255),
            telefono VARCHAR(50),
            equipo VARCHAR(255) NOT NULL,
            detalle TEXT,
            costo DECIMAL(10,2) DEFAULT 0,
            estado VARCHAR(50) DEFAULT 'Revision'
        )");

        // Registrar nuevo (Solo Admin)
        if ($es_admin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'nuevo') {
            $stmt = $pdo->prepare("INSERT INTO reparaciones (cliente, telefono, equipo, detalle, costo, estado) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_POST['cliente'], $_POST['telefono'], $_POST['equipo'], $_POST['detalle'], $_POST['costo'] ?: 0, $_POST['estado']]);
            header("Location: index.php"); 
            exit;
        }

        // Actualizar Estado (Solo Admin)
        if ($es_admin && isset($_GET['update_id']) && isset($_GET['new_status'])) {
            $stmt = $pdo->prepare("UPDATE reparaciones SET estado = ? WHERE id = ?");
            $stmt->execute([$_GET['new_status'], $_GET['update_id']]);
            header("Location: index.php"); 
            exit;
        }

        // Consulta de tickets
        $stmt = $pdo->prepare("SELECT * FROM reparaciones WHERE equipo ILIKE ? OR cliente ILIKE ? ORDER BY fecha DESC");
        $stmt->execute(["%$buscar%", "%$buscar%"]);
        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $error_db = "Conexión pendiente: Conecta Neon en la pestaña Storage de Vercel.";
    }
} catch (Exception $e) { 
    $error_db = "Error técnico: " . $e->getMessage(); 
}
?>
<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tecnet | Gestión de Tickets</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css">
    <style>
        .badge { padding: 4px 12px; border-radius: 50px; font-size: 0.8rem; font-weight: bold; }
        .Revision { background: #fff3e0; color: #ef6c00; }
        .Listo { background: #e8f5e9; color: #2e7d32; }
        .Entregado { background: #e3f2fd; color: #1565c0; }
        nav { background: #1a1a1a; padding: 10px 20px; color: white; margin-bottom: 20px; }
        .admin-box { background: #f9f9f9; padding: 20px; border-radius: 8px; border: 1px dashed #999; margin-bottom: 30px; }
    </style>
</head>
<body>
    <nav class="container-fluid">
        <ul><li><strong>TE
