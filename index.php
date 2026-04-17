<?php
// Conexión a Neon Postgres usando las variables que se crearon automáticamente
$host = getenv('PGHOST');
$db   = getenv('PGDATABASE');
$user = getenv('PGUSER');
$pass = getenv('PGPASSWORD');
$port = getenv('PGPORT') ?: '5432';

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Crear la tabla si no existe
    $pdo->exec("CREATE TABLE IF NOT EXISTS reparaciones (
        id SERIAL PRIMARY KEY,
        fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        equipo VARCHAR(255) NOT NULL,
        detalle TEXT,
        estado VARCHAR(50)
    )");

    // Registrar nuevo equipo
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['equipo'])) {
        $stmt = $pdo->prepare("INSERT INTO reparaciones (equipo, detalle, estado) VALUES (?, ?, ?)");
        $stmt->execute([
            strip_tags($_POST['equipo']),
            strip_tags($_POST['detalle']),
            $_POST['estado']
        ]);
        // Recargar para evitar re-envío del formulario
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // Obtener registros
    $stmt = $pdo->query("SELECT * FROM reparaciones ORDER BY fecha DESC");
    $registros = $stmt->fetchAll();

} catch (PDOException $e) {
    echo "<div style='color:red; padding:20px; border:1px solid red;'>";
    echo "<strong>Error de conexión:</strong> " . $e->getMessage();
    echo "</div>";
    $registros = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Reparaciones | Tecnet</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css">
    <style>
        .status-badge { padding: 2px 8px; border-radius: 4px; font-size: 0.8em; font-weight: bold; }
        .Revision { background: #ffe082; color: #6f4e00; }
        .Listo { background: #c8e6c9; color: #2e7d32; }
        .Entregado { background: #bbdefb; color: #1565c0; }
    </style>
</head>
<body class="container">
    <h2 style="margin-top:20px">🛠 Registro de Equipos y Revisiones</h2>
    
    <article>
        <form method="POST">
            <div class="grid">
                <input type="text" name="equipo" placeholder="Nombre del equipo (Ej: Router, Laptop)" required>
                <select name="estado">
                    <option value="Revision">En Revisión</option>
                    <option value="Listo">Listo para entrega</option>
                    <option value="Entregado">Entregado</option>
                </select>
            </div>
            <textarea name="detalle" placeholder="Detalles de la falla o reparación realizada..."></textarea>
            <button type="submit" class="contrast">Guardar Registro</button>
        </form>
    </article>

    <h3>Historial Reciente</h3>
    <figure>
        <table role="grid">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Equipo</th>
                    <th>Detalles</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registros as $r): ?>
                <tr>
                    <td><?= date('d/m/y', strtotime($r['fecha'])) ?></td>
                    <td><strong><?= htmlspecialchars($r['equipo']) ?></strong></td>
                    <td><?= htmlspecialchars($r['detalle']) ?></td>
                    <td><span class="status-badge <?= $r['estado'] ?>"><?= $r['estado'] ?></span></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($registros)): ?>
                    <tr><td colspan="4" style="text-align:center">No hay registros aún.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </figure>
</body>
</html>
