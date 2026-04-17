<?php
// 1. FORZAR REPORTE DE ERRORES (Si falla, nos dirá por qué)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
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
        $pdo = new PDO("pgsql:host=$host;dbname=$db", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        
        // Sincronización básica
        $pdo->exec("CREATE TABLE IF NOT EXISTS reparaciones (id SERIAL PRIMARY KEY, fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP, cliente VARCHAR(255) DEFAULT 'Sin nombre', equipo VARCHAR(255) NOT NULL, detalle TEXT, estado VARCHAR(50) DEFAULT 'Revision')");

        // Lógica de Guardar/Editar
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['equipo'])) {
            if (!empty($_POST['id'])) {
                $stmt = $pdo->prepare("UPDATE reparaciones SET cliente=?, equipo=?, detalle=?, estado=? WHERE id=?");
                $stmt->execute([$_POST['cliente'], $_POST['equipo'], $_POST['detalle'], $_POST['estado'], $_POST['id']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO reparaciones (cliente, equipo, detalle, estado) VALUES (?, ?, ?, ?)");
                $stmt->execute([$_POST['cliente'] ?: 'Anónimo', $_POST['equipo'], $_POST['detalle'], $_POST['estado']]);
            }
            header("Location: /"); exit;
        }

        // Lógica de Eliminar
        if (isset($_GET['delete_id'])) {
            $stmt = $pdo->prepare("DELETE FROM reparaciones WHERE id = ?");
            $stmt->execute([$_GET['delete_id']]);
            header("Location: /"); exit;
        }

        // Cargar datos para editar
        if (isset($_GET['edit_id'])) {
            $stmt = $pdo->prepare("SELECT * FROM reparaciones WHERE id = ?");
            $stmt->execute([$_GET['edit_id']]);
            $edit_data = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        // Cargar lista
        $stmt = $pdo->query("SELECT * FROM reparaciones ORDER BY fecha DESC");
        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $error_db = "Faltan variables de entorno (Base de datos no conectada).";
    }
} catch (Exception $e) { 
    $error_db = "Error de conexión: " . $e->getMessage(); 
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tecnet Service | Panel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css">
    <style>
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; color: #000; }
        .Revision { background: #ffd180; } .Reparado { background: #b9f6ca; } 
        .Dañado { background: #ff8a80; } .Repuesto { background: #cfd8dc; }
        nav { background: #111; padding: 10px; margin-bottom: 20px; }
        .action-btns { display: flex; gap: 15px; font-size: 1.2rem; }
    </style>
</head>
<body class="container">
    <nav><ul><li><strong style="color:white">TECNET SERVICE 🛠️</strong></li></ul></nav>

    <?php if ($error_db): ?>
        <article style="background:#fee; color:#b71c1c; padding:10px;"><?= $error_db ?></article>
    <?php endif; ?>

    <article id="formulario">
        <header><strong><?= $edit_data ? '📝 Editando Registro' : '📥 Ingreso de Equipo' ?></strong></header>
        <form method="POST">
            <input type="hidden" name="id" value="<?= $edit_data['id'] ?? '' ?>">
            <div class="grid">
                <input type="text" name="cliente" placeholder="Cliente" value="<?= htmlspecialchars($edit_data['cliente'] ?? '') ?>">
                <input type="text" name="equipo" placeholder="Equipo" required value="<?= htmlspecialchars($edit_data['equipo'] ?? '') ?>">
                <select name="estado">
                    <?php $est = $edit_data['estado'] ?? 'Revision'; ?>
                    <option value="Revision" <?= $est=='Revision'?'selected':'' ?>>En Revisión</option>
                    <option value="Reparado" <?= $est=='Reparado'?'selected':'' ?>>✅ Reparado</option>
                    <option value="Dañado" <?= $est=='Dañado'?'selected':'' ?>>❌ Dañado</option>
                    <option value="Repuesto" <?= $est=='Repuesto'?'selected':'' ?>>⚙️ Repuesto</option>
                </select>
            </div>
            <textarea name="detalle" placeholder="Detalles técnicos..."><?= htmlspecialchars($edit_data['detalle'] ?? '') ?></textarea>
            <button type="submit" class="contrast"><?= $edit_data ? 'Guardar Cambios' : 'Registrar Equipo' ?></button>
            <?php if($edit_data): ?><a href="/" style="display:block; text-align:center; margin-top:10px;">Cancelar Edición</a><?php endif; ?>
        </form>
    </article>

    <figure>
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Equipo
