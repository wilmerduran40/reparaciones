<?php
// Reportar errores para saber qué falla (puedes quitar esto después)
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// --- CONFIGURACIÓN ---
$password_maestra = "admin123"; 
$registros = [];
$error_db = null;

// Lógica de acceso
if (isset($_POST['login']) && isset($_POST['pass'])) {
    if ($_POST['pass'] === $password_maestra) {
        $_SESSION['admin'] = true;
    }
}
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: /");
    exit;
}
$es_admin = isset($_SESSION['admin']);

// --- CONEXIÓN BASE DE DATOS ---
$host = getenv('PGHOST');
$db   = getenv('PGDATABASE');
$user = getenv('PGUSER');
$pass = getenv('PGPASSWORD');

try {
    if ($host) {
        $pdo = new PDO("pgsql:host=$host;dbname=$db", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        
        // 1. Registrar (Admin)
        if ($es_admin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $stmt = $pdo->prepare("INSERT INTO reparaciones (cliente, equipo, detalle, estado) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_POST['cliente'], $_POST['equipo'], $_POST['detalle'], $_POST['estado']]);
            header("Location: /"); exit;
        }

        // 2. Cambiar Estado (Admin)
        if ($es_admin && isset($_GET['update_id'])) {
            $stmt = $pdo->prepare("UPDATE reparaciones SET estado = ? WHERE id = ?");
            $stmt->execute([$_GET['new_status'], $_GET['update_id']]);
            header("Location: /"); exit;
        }

        // 3. Cargar Datos
        $buscar = isset($_GET['q']) ? $_GET['q'] : '';
        $stmt = $pdo->prepare("SELECT * FROM reparaciones WHERE equipo ILIKE ? OR cliente ILIKE ? ORDER BY fecha DESC");
        $stmt->execute(["%$buscar%", "%$buscar%"]);
        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $error_db = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tecnet Service</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css">
    <style>
        .badge { padding: 2px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; }
        .Revision { background: #ffd180; color: #000; }
        .Listo { background: #b9f6ca; color: #000; }
        .Entregado { background: #80d8ff; color: #000; }
        nav { margin-bottom: 20px; background: #111; padding: 10px; }
    </style>
</head>
<body class="container">
    <nav>
        <ul><li><strong style="color:white">TECNET 🛠️</strong></li></ul>
        <ul>
            <?php if (!$es_admin): ?>
                <li><form method="POST" style="margin:0; display:flex; gap:5px;">
                    <input type="password" name="pass" placeholder="Admin Key" required style="margin:0;">
                    <button type="submit" name="login" style="margin:0;">OK</button>
                </form></li>
            <?php else: ?>
                <li><mark>MODO ADMIN</mark></li>
                <li><a href="?logout=1" class="secondary">Salir</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <?php if ($error_db): ?>
        <p style="color:red">Error: <?= htmlspecialchars($error_db) ?></p>
    <?php endif; ?>

    <?php if ($es_admin): ?>
        <article>
            <form method="POST">
