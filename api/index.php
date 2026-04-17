<?php
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
        
        // Asegurar que la tabla existe
        $pdo->exec("CREATE TABLE IF NOT EXISTS reparaciones (
            id SERIAL PRIMARY KEY,
            fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            cliente VARCHAR(255) DEFAULT 'Sin nombre',
            equipo VARCHAR(255) NOT NULL,
            detalle TEXT,
            estado VARCHAR(50) DEFAULT 'Revision'
        )");

        // Guardar nuevo registro
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['equipo'])) {
            $stmt = $pdo->prepare("INSERT INTO reparaciones (cliente, equipo, detalle, estado) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $_POST['cliente'] ?: 'Anónimo', 
                $_POST['equipo'], 
                $_POST['detalle'] ?: '', 
                $_POST['estado']
            ]);
            header("Location: /"); exit;
        }

        // Cambio de estado rápido vía URL
        if (isset($_GET['update_id']) && isset($_GET['new_status'])) {
            $stmt = $pdo->prepare("UPDATE reparaciones SET estado = ? WHERE id = ?");
            $stmt->execute([$_GET['new_status'], $_GET['update_id']]);
            header("Location: /"); exit;
        }

        // Cargar datos
        $stmt = $pdo->query("SELECT * FROM reparaciones ORDER BY fecha DESC");
        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) { $error_db = $e->getMessage(); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tecnet Service | Gestión</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css">
    <style>
        .badge { padding: 4px 10px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; color: #000; }
        .Revision { background: #ffd180; } /* Naranja */
        .Reparado { background: #b9f6ca; } /* Verde */
        .Dañado { background: #ff8a80; }   /* Rojo */
        .Repuesto { background: #cfd8dc; } /* Gris */
        nav { background: #111; padding: 10px; margin-bottom: 20px; }
        nav strong { color: #fff; }
    </style>
</head>
<body class="container">
    <nav><ul><li><strong>TECNET SERVICE 🛠️</strong></li></ul></nav>

    <?php if ($error_db): ?><mark>Error: <?= htmlspecialchars($error_db) ?></mark><?php endif; ?>

    <article>
        <header><strong>Ingreso de Equipo</strong></header>
        <form method="POST">
            <div class="grid">
                <input type="text" name="cliente" placeholder="Nombre del Cliente">
                <input type="text" name="equipo" placeholder="Equipo (Ej: Router, TV, Laptop)" required>
                <select name="estado">
                    <option value="Revision">En Revisión</option>
                    <option value="Reparado">✅ Reparado</option>
                    <option value="Dañado">❌ Dañado (Sin Arreglo)</option>
                    <option value="Repuesto">⚙️ Para Repuesto</option>
                </select>
            </div>
            <textarea name="detalle" placeholder="¿Qué falla tiene o qué piezas se usaron?"></textarea>
            <button type="submit" class="contrast">Registrar en Sistema</button>
        </form>
    </article>

    <figure>
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Equipo (Cliente)</th>
                    <th>Estado Actual</th>
                    <th>Actualizar</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registros as $r): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($r['fecha'])) ?></td>
                    <td>
                        <strong><?= htmlspecialchars($r['equipo']) ?></strong><br>
                        <small><?= htmlspecialchars($r['cliente']) ?></small>
                    </td>
                    <td><span class="badge <?= $r['estado'] ?>"><?= htmlspecialchars($r['estado']) ?></span></td>
                    <td>
                        <select onchange="window.location.href='?update_id=<?= $r['id'] ?>&new_status='+this.value" style="margin:0; font-size:0.8rem;">
                            <option value="">Cambiar a...</option>
                            <option value="Revision">Revisión</option>
                            <option value="Reparado">Reparado</option>
                            <option value="Dañado">Dañado</option>
                            <option value="Repuesto">Repuesto</option>
                        </select>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </figure>
</body>
</html>
