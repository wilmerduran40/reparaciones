<?php
// Reporte de errores para depuración rápida
ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- CONEXIÓN BASE DE DATOS ---
$host = getenv('PGHOST');
$db   = getenv('PGDATABASE');
$user = getenv('PGUSER');
$pass = getenv('PGPASSWORD');

$registros = [];
$error_db = null;

try {
    if ($host) {
        $pdo = new PDO("pgsql:host=$host;dbname=$db", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        
        // 1. Crear tabla si no existe
        $pdo->exec("CREATE TABLE IF NOT EXISTS reparaciones (
            id SERIAL PRIMARY KEY,
            fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            cliente VARCHAR(255),
            equipo VARCHAR(255) NOT NULL,
            detalle TEXT,
            estado VARCHAR(50) DEFAULT 'Revision'
        )");

        // 2. Lógica: Registrar Nuevo Ticket
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $stmt = $pdo->prepare("INSERT INTO reparaciones (cliente, equipo, detalle, estado) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_POST['cliente'], $_POST['equipo'], $_POST['detalle'], $_POST['estado']]);
            header("Location: /"); 
            exit;
        }

        // 3. Lógica: Cambiar Estado
        if (isset($_GET['update_id']) && isset($_GET['new_status'])) {
            $stmt = $pdo->prepare("UPDATE reparaciones SET estado = ? WHERE id = ?");
            $stmt->execute([$_GET['new_status'], $_GET['update_id']]);
            header("Location: /"); 
            exit;
        }

        // 4. Cargar Datos
        $buscar = isset($_GET['q']) ? $_GET['q'] : '';
        $stmt = $pdo->prepare("SELECT * FROM reparaciones WHERE equipo ILIKE ? OR cliente ILIKE ? ORDER BY fecha DESC");
        $stmt->execute(["%$buscar%", "%$buscar%"]);
        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $error_db = "Variables de entorno no encontradas. Verifica la conexión con Neon.";
    }
} catch (Exception $e) {
    $error_db = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tecnet | Gestión de Equipos</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css">
    <style>
        .badge { padding: 3px 10px; border-radius: 5px; font-size: 0.8rem; font-weight: bold; }
        .Revision { background: #ffd180; color: #000; }
        .Listo { background: #b9f6ca; color: #000; }
        .Entregado { background: #81d4fa; color: #000; }
        nav { background: #111; padding: 15px; margin-bottom: 20px; }
        nav strong { color: white; font-size: 1.5rem; }
    </style>
</head>
<body class="container">
    <nav>
        <ul><li><strong>TECNET SERVICE 🛠️</strong></li></ul>
    </nav>

    <?php if ($error_db): ?>
        <article style="background: #fee; color: #b71c1c;">
            <strong>Error de conexión:</strong> <?= htmlspecialchars($error_db) ?>
        </article>
    <?php endif; ?>

    <article>
        <header><strong>Registrar Entrada</strong></header>
        <form method="POST">
            <input type="hidden" name="action" value="1">
            <div class="grid">
                <input type="text" name="cliente" placeholder="Nombre del Cliente" required>
                <input type="text" name="equipo" placeholder="Equipo / Modelo" required>
                <select name="estado">
                    <option value="Revision">En Revisión</option>
                    <option value="Listo">Listo para entrega</option>
                    <option value="Entregado">Entregado</option>
                </select>
            </div>
            <textarea name="detalle" placeholder="Falla reportada y detalles técnicos..."></textarea>
            <button type="submit" class="contrast">Guardar Ticket</button>
        </form>
    </article>

    <form method="GET">
        <input type="search" name
