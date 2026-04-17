<?php
$host = getenv('PGHOST');
$db   = getenv('PGDATABASE');
$user = getenv('PGUSER');
$pass = getenv('PGPASSWORD');

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$db", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec("CREATE TABLE IF NOT EXISTS reparaciones (id SERIAL PRIMARY KEY, fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP, cliente VARCHAR(255), equipo VARCHAR(255), detalle TEXT, estado VARCHAR(50) DEFAULT 'Revision')");

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $stmt = $pdo->prepare("INSERT INTO reparaciones (cliente, equipo, detalle, estado) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_POST['cliente'], $_POST['equipo'], $_POST['detalle'], $_POST['estado']]);
        header("Location: /"); exit;
    }
    $stmt = $pdo->query("SELECT * FROM reparaciones ORDER BY fecha DESC");
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $error = $e->getMessage(); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tecnet</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css">
</head>
<body class="container">
    <h1>TECNET SERVICE 🛠️</h1>
    <article>
        <form method="POST">
            <input type="text" name="cliente" placeholder="Cliente" required>
            <input type="text" name="equipo" placeholder="Equipo" required>
            <select name="estado">
                <option value="Revision">Revision</option>
                <option value="Listo">Listo</option>
            </select>
            <textarea name="detalle" placeholder="Falla"></textarea>
            <button type="submit">Guardar</button>
        </form>
    </article>
    <table>
        <?php foreach($registros as $r): ?>
            <tr>
                <td><?= $r['fecha'] ?></td>
                <td><?= $r['equipo'] ?> (<?= $r['cliente'] ?>)</td>
                <td><?= $r['estado'] ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
