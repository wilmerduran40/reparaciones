<?php
// Reporte de errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

$host = getenv('PGHOST');
$db   = getenv('PGDATABASE');
$user = getenv('PGUSER');
$pass = getenv('PGPASSWORD');

$registros = [];
$error_db = null;

try {
    if ($host) {
        $pdo = new PDO("pgsql:host=$host;dbname=$db", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        
        // --- ACTUALIZACIÓN DE TABLA AUTOMÁTICA ---
        $pdo->exec("CREATE TABLE IF NOT EXISTS reparaciones (id SERIAL PRIMARY KEY, fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP, equipo VARCHAR(255) NOT NULL, detalle TEXT, estado VARCHAR(50) DEFAULT 'Revision')");
        
        // Intentar agregar columnas nuevas si no existen
        try { $pdo->exec("ALTER TABLE reparaciones ADD COLUMN cliente VARCHAR(255)"); } catch (Exception $e) { /* Ya existe */ }
        try { $pdo->exec("ALTER TABLE reparaciones ADD COLUMN costo DECIMAL(10,2) DEFAULT 0"); } catch (Exception $e) { /* Ya existe */ }

        // --- LÓGICA DE GUARDADO ---
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $stmt = $pdo->prepare("INSERT INTO reparaciones (cliente, equipo, detalle, estado) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_POST['cliente'], $_POST['equipo'], $_POST['detalle'], $_POST['estado']]);
            header("Location: /"); exit;
        }

        // --- LÓGICA DE CAMBIO DE ESTADO ---
        if (isset($_GET['update_id']) && isset($_GET['new_status'])) {
            $stmt = $pdo->prepare("UPDATE reparaciones SET estado = ? WHERE id = ?");
            $stmt->execute([$_GET['new_status'], $_GET['update_id']]);
            header("Location: /"); exit;
        }

        // --- CONSULTA ---
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
    <title>Tecnet | Gestión</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css">
    <style>
        .badge { padding: 3px 10px; border-radius: 5px; font-size: 0.8rem; font-weight: bold; }
        .Revision { background: #ffd180; }
        .Listo { background: #b9f6ca; }
        .Entregado { background: #81d4fa; }
        nav { background: #111; padding: 10px; margin-bottom: 20px; }
    </style>
</head>
<body class="container">
    <nav><ul><li><strong style="color:white">TECNET SERVICE 🛠️</strong></li></ul></nav>

    <?php if ($error_db): ?>
        <mark>Error: <?= htmlspecialchars($error_db) ?></mark>
    <?php endif; ?>

    <article>
        <form method="POST">
            <input type="hidden" name="action" value="1">
            <div class="grid">
                <input type="text" name="cliente" placeholder="Cliente" required>
                <input type="text" name="equipo" placeholder="Equipo" required>
                <select name="estado">
                    <option value="Revision">Revisión</option>
                    <option value="Listo">Listo</option>
                    <option value="Entregado">Entregado</option>
                </select>
            </div>
            <textarea name="detalle" placeholder="Falla..."></textarea>
            <button type="submit">Guardar Ticket</button>
        </form>
    </article>

    <form method="GET"><input type="search" name="q" placeholder="Buscar..." value="<?= htmlspecialchars($buscar) ?>"></form>

    <table role="grid">
        <thead><tr><th>Equipo</th><th>Cliente</th><th>Estado</th><th>Acción</th></tr></thead>
        <tbody>
            <?php foreach ($registros as $r): ?>
            <tr>
                <td><strong><?= htmlspecialchars($r['equipo']) ?></strong></td>
                <td><?= htmlspecialchars($r['cliente']) ?></td>
                <td><span class="badge <?= $r['estado'] ?>"><?= $r['estado'] ?></span></td>
                <td>
                    <select onchange="window.location.href='?update_id=<?= $r['id'] ?>&new_status='+this.value" style="margin:0;">
                        <option value="">Cambiar...</option>
                        <option value="Revision">Revisión</option>
                        <option value="Listo">Listo</option>
                        <option value="Entregado">Entregado</
