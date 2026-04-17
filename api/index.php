<?php
// Reportar errores para depuración
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Conexión segura
$host = getenv('PGHOST');
$db   = getenv('PGDATABASE');
$user = getenv('PGUSER');
$pass = getenv('PGPASSWORD');

$registros = []; // Inicializamos para evitar el error de la imagen 3
$error_db = null;

try {
    if ($host) {
        $pdo = new PDO("pgsql:host=$host;dbname=$db", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        
        // Crear tabla si no existe
        $pdo->exec("CREATE TABLE IF NOT EXISTS reparaciones (
            id SERIAL PRIMARY KEY,
            fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            cliente VARCHAR(255) DEFAULT 'Sin nombre',
            equipo VARCHAR(255) NOT NULL,
            detalle TEXT,
            estado VARCHAR(50) DEFAULT 'Revision'
        )");

        // Lógica de guardado
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['equipo'])) {
            $stmt = $pdo->prepare("INSERT INTO reparaciones (cliente, equipo, detalle, estado) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $_POST['cliente'] ?? 'Anónimo', 
                $_POST['equipo'], 
                $_POST['detalle'] ?? '', 
                $_POST['estado'] ?? 'Revision'
            ]);
            header("Location: /"); 
            exit;
        }

        // Cargar datos
        $stmt = $pdo->query("SELECT * FROM reparaciones ORDER BY fecha DESC");
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
</head>
<body class="container">
    <header><h1>TECNET SERVICE 🛠️</h1></header>
    
    <?php if ($error_db): ?>
        <mark>Error de DB: <?= htmlspecialchars($error_db) ?></mark>
    <?php endif; ?>

    <article>
        <form method="POST">
            <div class="grid">
                <input type="text" name="cliente" placeholder="Nombre Cliente">
                <input type="text" name="equipo" placeholder="Equipo / Modelo" required>
                <select name="estado">
                    <option value="Revision">En Revisión</option>
                    <option value="Listo">Listo</option>
                </select>
            </div>
            <textarea name="detalle" placeholder="Falla reportada..."></textarea>
            <button type="submit">Registrar Equipo</button>
        </form>
    </article>

    <figure>
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Equipo (Cliente)</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registros as $r): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($r['fecha'])) ?></td>
                    <td>
                        <strong><?= htmlspecialchars($r['equipo'] ?? 'N/A') ?></strong><br>
                        <small><?= htmlspecialchars($r['cliente'] ?? 'Anónimo') ?></small>
                    </td>
                    <td><?= htmlspecialchars($r['estado'] ?? 'Revision') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </figure>
</body>
</html>
