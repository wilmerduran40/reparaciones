<?php
// Reporte de errores absoluto
ini_set('display_errors', 1);
error_reporting(E_ALL);

$host = getenv('PGHOST');
$db   = getenv('PGDATABASE');
$user = getenv('PGUSER');
$pass = getenv('PGPASSWORD');

$registros = [];
$error_db = null;
$edit_data = null;

try {
    if ($host) {
        $pdo = new PDO("pgsql:host=$host;dbname=$db", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        // 1. Asegurar estructura (Sincronización silenciosa)
        $pdo->exec("CREATE TABLE IF NOT EXISTS reparaciones (
            id SERIAL PRIMARY KEY, 
            fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
            equipo VARCHAR(255) NOT NULL, 
            detalle TEXT, 
            estado VARCHAR(50) DEFAULT 'Revision',
            cliente VARCHAR(255) DEFAULT 'Anónimo'
        )");

        // 2. LÓGICA DE GUARDADO (Revisada)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['equipo'])) {
            $cliente = !empty($_POST['cliente']) ? $_POST['cliente'] : 'Anónimo';
            $equipo  = $_POST['equipo'];
            $detalle = $_POST['detalle'] ?? '';
            $estado  = $_POST['estado'] ?? 'Revision';

            if (!empty($_POST['id'])) {
                // Actualizar
                $stmt = $pdo->prepare("UPDATE reparaciones SET cliente=?, equipo=?, detalle=?, estado=? WHERE id=?");
                $stmt->execute([$cliente, $equipo, $detalle, $estado, $_POST['id']]);
            } else {
                // Insertar Nuevo
                $stmt = $pdo->prepare("INSERT INTO reparaciones (cliente, equipo, detalle, estado) VALUES (?, ?, ?, ?)");
                $stmt->execute([$cliente, $equipo, $detalle, $estado]);
            }
            // Redirección limpia para evitar re-envío de formulario
            header("Location: " . $_SERVER['PHP_SELF']); 
            exit;
        }

        // 3. Lógica de Eliminar
        if (isset($_GET['delete_id'])) {
            $stmt = $pdo->prepare("DELETE FROM reparaciones WHERE id = ?");
            $stmt->execute([$_GET['delete_id']]);
            header("Location: " . $_SERVER['PHP_SELF']); 
            exit;
        }

        // 4. Cargar datos para Editar
        if (isset($_GET['edit_id'])) {
            $stmt = $pdo->prepare("SELECT * FROM reparaciones WHERE id = ?");
            $stmt->execute([$_GET['edit_id']]);
            $edit_data = $stmt->fetch();
        }

        // 5. Cargar lista completa (Nuevos primero)
        $stmt = $pdo->query("SELECT * FROM reparaciones ORDER BY id DESC");
        $registros = $stmt->fetchAll();
    } else {
        $error_db = "Error: No se detectaron las credenciales de la base de datos en Vercel.";
    }
} catch (Exception $e) { 
    $error_db = "Error de Sistema: " . $e->getMessage(); 
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tecnet Service | Panel de Tickets</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css">
    <style>
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; color: #000; }
        .Revision { background: #ffd180; } .Reparado { background: #b9f6ca; } 
        .Dañado { background: #ff8a80; } .Repuesto { background: #cfd8dc; }
        nav { background: #111; padding: 10px; margin-bottom: 20px; }
        .ticket-id { font-family: monospace; font-weight: bold; color: #1565c0; background: #e3f2fd; padding: 2px 5px; border-radius: 3px; }
        .action-btns { display: flex; gap: 15px; font-size: 1.2rem; align-items: center; }
    </style>
</head>
<body class="container">
    <nav><ul><li><strong style="color:white">TECNET SERVICE 🛠️</strong></li></ul></nav>

    <?php if ($error_db): ?>
        <article style="background:#fee; color:#b71c1c; border: 1px solid #b71c1c;">
            <strong>⚠️ Atención:</strong> <?= htmlspecialchars($error_db) ?>
        </article>
    <?php endif; ?>

    <article id="formulario">
        <header><strong><?= $edit_data ? '📝 Editar Ticket' : '📥 Nuevo Ingreso' ?></strong></header>
        <form method="POST" action="">
            <input type="hidden" name="id" value="<?= $edit
