<?php
$host = getenv('PGHOST');
$db   = getenv('PGDATABASE');
$user = getenv('PGUSER');
$pass = getenv('PGPASSWORD');
$port = getenv('PGPORT') ?: '5432';

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // Crear tabla actualizada si no existe
    $pdo->exec("CREATE TABLE IF NOT EXISTS reparaciones (
        id SERIAL PRIMARY KEY,
        fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        cliente VARCHAR(255),
        telefono VARCHAR(50),
        equipo VARCHAR(255) NOT NULL,
        detalle TEXT,
        costo DECIMAL(10,2) DEFAULT 0,
        estado VARCHAR(50) DEFAULT 'Revision'
    )");

    // Registrar equipo
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'nuevo') {
        $stmt = $pdo->prepare("INSERT INTO reparaciones (cliente, telefono, equipo, detalle, costo, estado) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['cliente'], $_POST['telefono'], $_POST['equipo'], 
            $_POST['detalle'], $_POST['costo'] ?: 0, $_POST['estado']
        ]);
        header("Location: index.php"); exit;
    }

    // Buscador
    $buscar = $_GET['q'] ?? '';
    $sql = "SELECT * FROM reparaciones WHERE equipo ILIKE ? OR cliente ILIKE ? ORDER BY fecha DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["%$buscar%", "%$buscar%"]);
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) { $error = $e->getMessage(); }
?>

<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title>Tecnet Pro | Panel de Control</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css">
    <style>
        .badge { padding: 3px 10px; border-radius: 50px; font-size: 0.8rem; font-weight: bold; }
        .Revision { background: #fff3e0; color: #ef6c00; }
        .Listo { background: #e8f5e9; color: #2e7d32; }
        .Entregado { background: #e3f2fd; color: #1565c0; }
        .m-0 { margin: 0; }
        header { background: #1a1a1a; color: white; padding: 2rem 0; margin-bottom: 2rem; }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <hgroup>
                <h1>Tecnet Service 🛠️</h1>
                <p>Gestión de Servicio Técnico de Importaciones y Electrónica</p>
            </hgroup>
        </div>
    </header>

    <main class="container">
        <div class="grid">
            <section>
                <article>
                    <header><strong>Ingreso de Equipo</strong></header>
                    <form method="POST">
                        <input type="hidden" name="action" value="nuevo">
                        <div class="grid">
                            <input type="text" name="cliente" placeholder="Nombre del Cliente" required>
                            <input type="text" name="telefono" placeholder="WhatsApp / Teléfono">
                        </div>
                        <div class="grid">
                            <input type="text" name="equipo" placeholder="Equipo (Ej: Router 1688, Laptop)" required>
                            <input type="number" step="0.01" name="costo" placeholder="Costo Presupuestado ($)">
                        </div>
                        <select name="estado">
                            <option value="Revision">En Revisión</option>
                            <option value="Listo">Listo / Reparado</option>
                            <option value="Entregado">Entregado</option>
                        </select>
                        <textarea name="detalle" placeholder="Falla reportada o piezas cambiadas..."></textarea>
                        <button type="submit" class="contrast">Registrar en Sistema</button>
                    </form>
                </article>
            </section>

            <section>
                <form method="GET">
                    <input type="search" name="q" placeholder="Buscar por cliente o equipo..." value="<?= htmlspecialchars($buscar) ?>">
                </form>

                <div style="overflow-x:auto;">
                    <table role="grid">
                        <thead>
                            <tr>
                                <th>Equipo / Cliente</th>
                                <th>Costo</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registros as $r): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($r['equipo']) ?></strong><br>
                                    <small><?= htmlspecialchars($r['cliente']) ?> (<?= htmlspecialchars($r['telefono']) ?>)</small>
                                </td>
                                <td>$<?= number_format($r['costo'], 2) ?></td>
                                <td><span class="badge <?= $r['estado'] ?>"><?= $r['estado'] ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>
</body>
</html>
