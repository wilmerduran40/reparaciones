<?php
// Evitar cualquier salida antes de procesar
$host = getenv('PGHOST');
$db   = getenv('PGDATABASE');
$user = getenv('PGUSER');
$pass = getenv('PGPASSWORD');

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$db", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    // Crear tabla básica
    $pdo->exec("CREATE TABLE IF NOT EXISTS reparaciones (id SERIAL PRIMARY KEY, fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP, cliente VARCHAR(255), equipo VARCHAR(255), estado VARCHAR(50) DEFAULT 'Revision')");

    $stmt = $pdo->query("SELECT * FROM reparaciones ORDER BY fecha DESC");
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { 
    $error = $e->getMessage(); 
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tecnet Test</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css">
</head>
<body class="container">
    <h1>TECNET SERVICE 🛠️</h1>
    <?php if(isset($error)): ?>
        <p style="color:red">Error de DB: <?= $error ?></p>
    <?php endif; ?>
    
    <article>
        <form method="POST">
            <input type="text" name="cliente" placeholder="Cliente" required>
            <input type="text" name="equipo" placeholder="Equipo" required>
            <button type="submit">Probar Registro</button>
        </form>
    </article>

    <table>
        <?php foreach($registros as $r): ?>
            <tr>
                <td><?= $r['fecha'] ?></td>
                <td><?= htmlspecialchars($r['equipo']) ?></td>
                <td><?= htmlspecialchars($r['cliente']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
