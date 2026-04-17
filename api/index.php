<?php
session_start();

// --- CONFIGURACIÓN DE SEGURIDAD ---
$password_maestra = "duran1612."; 

// Inicializar variables para evitar el error de "Undefined variable"
$registros = [];
$es_admin = isset($_SESSION['admin']);
$error_db = null;

// Lógica de Login/Logout
if (isset($_POST['login'])) {
    if ($_POST['pass'] === $password_maestra) {
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
    if (!$host) throw new Exception("Base de datos no conectada en Vercel.");

    $dsn = "pgsql:host=$host;port=$port;dbname=$db";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // Crear tabla si no existe (con nuevos campos)
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

    // 1. Registrar nuevo (Solo Admin)
    if ($es_admin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'nuevo') {
        $stmt = $pdo->prepare("INSERT INTO reparaciones (cliente, telefono, equipo, detalle, costo, estado) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['cliente'], $_POST['telefono'], $_POST['equipo'], $_POST['detalle'], $_POST['costo'] ?: 0, $_POST['estado']]);
        header("Location: index.php"); exit;
    }

    // 2. Actualizar Estado (Solo Admin)
    if ($es_admin && isset($_GET['update_id']) && isset($_GET['new_status'])) {
        $stmt = $pdo->prepare("UPDATE reparaciones SET estado = ? WHERE id = ?");
        $stmt->execute([$_GET['new_status'], $_GET['update_id']]);
        header("Location: index.php"); exit;
    }

    // 3. Consulta de tickets (Público)
    $buscar = $_GET['q'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM reparaciones WHERE equipo ILIKE ? OR cliente ILIKE ? ORDER BY fecha DESC");
    $stmt->execute(["%$buscar%", "%$buscar%"]);
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) { 
    $error_db = $e->getMessage(); 
}
?>

<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tecnet | Estado de Tickets</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css">
    <style>
        .badge { padding: 4px 12px; border-radius: 50px; font-size: 0.8rem; font-weight: bold; display: inline-block; }
        .Revision { background: #fff3e0; color: #ef6c00; }
        .Listo { background: #e8f5e9; color: #2e7d32; }
        .Entregado { background: #e3f2fd; color: #1565c0; }
        nav { background: #1a1a1a; padding: 10px 20px; color: white; }
        .admin-box { background: #f9f9f9; padding: 20px; border-radius: 8px; border: 1px solid #ddd; margin-bottom: 30px; }
    </style>
</head>
<body>

    <nav class="container-fluid">
        <ul><li><strong>TECNET SERVICE 🛠️</strong></li></ul>
        <ul>
            <?php if (!$es_admin): ?>
                <li>
                    <form method="POST" style="display:flex; margin:0; gap:5px;">
                        <input type="password" name="pass" placeholder="Password Admin" required style="margin:0;">
                        <button type="submit" name="login" style="margin:0;">Entrar</button>
                    </form>
                </li>
            <?php else: ?>
                <li><mark>ADMIN</mark></li>
                <li><a href="?logout=1" class="secondary">Salir</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <main class="container" style="margin-top:20px;">
        
        <?php if ($error_db): ?>
            <div style="color:red; background:#fee; padding:10px;"><?= $error_db ?></div>
        <?php endif; ?>

        <?php if ($es_admin): ?>
            <section class="admin-box">
                <h3>+ Nuevo Ticket</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="nuevo">
                    <div class="grid">
                        <input type="text" name="cliente" placeholder="Cliente" required>
                        <input type="text" name="equipo" placeholder="Equipo" required>
                        <select name="estado">
                            <option value="Revision">Revisión</option>
                            <option value="Listo">Listo</option>
                            <option value="Entregado">Entregado</option>
                        </
