<?php
/**
 * SISTEMA DE REGISTRO DE REPARACIONES - TECNET
 * Conexión: Neon Postgres (Vercel Storage)
 */

// 1. Obtener variables de entorno de Vercel/Neon
$host = getenv('PGHOST');
$db   = getenv('PGDATABASE');
$user = getenv('PGUSER');
$pass = getenv('PGPASSWORD');
$port = getenv('PGPORT') ?: '5432';

$registros = [];
$error_db = false;

try {
    // Verificar si las variables existen
    if (!$host || !$db || !$user) {
        throw new Exception("Faltan las variables de entorno de la base de datos. Asegúrate de haber conectado Neon en la pestaña 'Storage'.");
    }

    // 2. Conexión PDO
    $dsn = "pgsql:host=$host;port=$port;dbname=$db";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 5
    ]);

    // 3. Crear tabla si no existe
    $pdo->exec("CREATE TABLE IF NOT EXISTS reparaciones (
        id SERIAL PRIMARY KEY,
        fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        equipo VARCHAR(255) NOT NULL,
        detalle TEXT,
        estado VARCHAR(50)
    )");

    // 4. Procesar Formulario (POST)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['equipo'])) {
        $stmt = $pdo->prepare("INSERT INTO reparaciones (equipo, detalle, estado) VALUES (?, ?, ?)");
        $stmt->execute([
            strip_tags($_POST['equipo']),
            strip_tags($_POST['detalle']),
            $_POST['estado']
        ]);
        // Redirigir para limpiar el envío del formulario
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // 5. Consultar Registros
    $stmt = $pdo->query("SELECT * FROM reparaciones ORDER BY fecha DESC LIMIT 50");
    $registros = $stmt->fetchAll();

} catch (Exception $e) {
    $error_db = $e->getMessage();
}

// Función para limpiar texto en el HTML
function h($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tecnet | Control de Equipos</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css">
    <style>
        :root { --primary: #00897b; }
        .status { padding: 4px 10px; border-radius: 20px; font-size: 0.85rem; font-weight: bold; }
        .Revision { background: #fff9c4; color: #f57f17; }
        .Listo { background: #c8e6c9; color: #2e7d32; }
        .Entregado { background: #e1f5fe; color: #0288d1; }
        .error-msg { background: #ffebee; color: #c62828; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 5px solid #b71c1c; }
    </style>
</head>
<body class="container">
    <header style="padding: 20px 0;">
        <h1>🛠 Gestión de Reparaciones</h1>
        <p>Registro de servicio técnico y revisiones</p>
    </header>

    <?php if ($error_db): ?>
        <div class="error-msg">
            <strong>⚠️ Error de Base de Datos:</strong><br>
            <?= h($error_db) ?>
        </div>
    <?php endif; ?>

    <main>
        <article>
            <header><strong>Nueva Entrada de Equipo</strong></header>
            <form method="POST">
                <div class="grid">
                    <label>
                        Equipo / Modelo
                        <input type="text" name="equipo" placeholder="Ej: Laptop HP, Router" required>
                    </label>
                    <label>
                        Estado
                        <select name="estado">
                            <option value="Revision">En Revisión</option>
                            <option value="Listo">Listo para entrega</option>
                            <option value="Entregado">Entregado</option>
                        </select>
                    </label>
                </div>
                <label>Descripción de la falla / Trabajo realizado</label>
                <textarea name="detalle" rows="3" placeholder="Detalle técnico..."></textarea>
                <button type="submit" <?= $error_db ? 'disabled' : '' ?>>Guardar en Base de Datos</button>
            </form>
        </article>

        <hr>

        <h3>Historial de Revisiones</h3>
        <figure>
            <table role="grid">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Equipo</th>
                        <th>Detalle Técnico</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($registros)): ?>
                        <tr><td colspan="4" style="text-align:center;">No hay equipos registrados aún.</td></tr>
                    <?php else: ?>
                        <?php foreach ($registros as $r): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($r['fecha'])) ?></td>
                            <td><strong><?= h($r['equipo']) ?></strong></td>
                            <td><?= h($r['detalle']) ?></td>
                            <td><span class="status <?= $r['estado'] ?>"><?= $r['estado'] ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </figure>
    </main>
</body>
</html>
