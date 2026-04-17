<?php
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
        $pdo = new PDO("pgsql:host=$host;dbname=$db", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        
        // 1. Sincronizar tabla con todas las columnas necesarias
        $pdo->exec("CREATE TABLE IF NOT EXISTS reparaciones (
            id SERIAL PRIMARY KEY, 
            fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
            equipo VARCHAR(255) NOT NULL, 
            detalle TEXT, 
            estado VARCHAR(50) DEFAULT 'Revision',
            cliente VARCHAR(255) DEFAULT 'Anónimo'
        )");

        // 2. Lógica: Guardar o Actualizar
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

        // 3. Lógica: Eliminar
        if (isset($_GET['delete_id'])) {
            $stmt = $pdo->prepare("DELETE FROM reparaciones WHERE id = ?");
            $stmt->execute([$_GET['delete_id']]);
            header("Location: /"); exit;
        }

        // 4. Lógica: Cargar datos para Editar
        if (isset($_GET['edit_id'])) {
            $stmt = $pdo->prepare("SELECT * FROM reparaciones WHERE id = ?");
            $stmt->execute([$_GET['edit_id']]);
            $edit_data = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        // 5. Cargar lista completa
        $stmt = $pdo->query("SELECT * FROM reparaciones ORDER BY id DESC");
        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) { $error_db = $e->getMessage(); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tecnet Service | Panel de Tickets</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css">
    <style>
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; color: #000; }
        .Revision { background: #ffd180; } .Reparado { background: #b9f6ca; } 
        .Dañado { background: #ff8a80; } .Repuesto { background: #cfd8dc; }
        nav { background: #111; padding: 10px; margin-bottom: 20px; }
        .action-btns { display: flex; gap: 12px; font-size: 1.2rem; }
        .ticket-id { font-family: monospace; font-weight: bold; color: #1565c0; background: #e3f2fd; padding: 2px 5px; border-radius: 3px; }
    </style>
</head>
<body class="container">
    <nav><ul><li><strong style="color:white">TECNET SERVICE 🛠️</strong></li></ul></nav>

    <article id="formulario">
        <header><strong><?= $edit_data ? '📝 Editar Ticket' : '📥 Nuevo Ingreso' ?></strong></header>
        <form method="POST">
            <input type="hidden" name="id" value="<?= $edit_data['id'] ?? '' ?>">
            <div class="grid">
                <input type="text" name="cliente" placeholder="Nombre del Cliente" value="<?= htmlspecialchars($edit_data['cliente'] ?? '') ?>">
                <input type="text" name="equipo" placeholder="Equipo / Modelo" required value="<?= htmlspecialchars($edit_data['equipo'] ?? '') ?>">
                <select name="estado">
                    <?php $est = $edit_data['estado'] ?? 'Revision'; ?>
                    <option value="Revision" <?= $est=='Revision'?'selected':'' ?>>En Revisión</option>
                    <option value="Reparado" <?= $est=='Reparado'?'selected':'' ?>>✅ Reparado</option>
                    <option value="Dañado" <?= $est=='Dañado'?'selected':'' ?>>❌ Dañado</option>
                    <option value="Repuesto" <?= $est=='Repuesto'?'selected':'' ?>>⚙️ Repuesto</option>
                </select>
            </div>
            <textarea name="detalle" placeholder="Notas y fallas técnicas..."><?= htmlspecialchars($edit_data['detalle'] ?? '') ?></textarea>
            <button type="submit" class="contrast"><?= $edit_data ? 'Actualizar Ticket' : 'Generar Ticket' ?></button>
            <?php if($edit_data): ?><a href="/" style="display:block; text-align:center; margin-top:10px;">Cancelar</a><?php endif; ?>
        </form>
    </article>

    <figure>
        <table role="grid">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Equipo (Cliente)</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registros as $r): ?>
                <tr>
                    <td><span class="ticket-id">TK-<?= str_pad($r['id'], 3, '0', STR_PAD_LEFT) ?></span></td>
                    <td><?= date('d/m/y', strtotime($r['fecha'])) ?></td>
                    <td>
                        <strong><?= htmlspecialchars($r['equipo']) ?></strong><br>
                        <small><?= htmlspecialchars($r['cliente'] ?? 'Anónimo') ?></small>
                    </td>
                    <td><span class="badge <?= $r['estado'] ?>"><?= $r['estado'] ?></span></td>
                    <td class="action-btns">
                        <a href="javascript:void(0)" onclick="verTicket('TK-<?= str_pad($r['id'], 3, '0', STR_PAD_LEFT) ?>', '<?= htmlspecialchars($r['equipo']) ?>', '<?= htmlspecialchars($r['detalle']) ?>')" title="Ver">👁️</a>
                        <a href="?edit_id=<?= $r['id'] ?>#formulario" title="Editar" style="color:#f57c00;">✏️</a>
                        <a href="?delete_id=<?= $r['id'] ?>" onclick="return confirm('¿Eliminar ticket?')" style="color:#d32f2f;">🗑️</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </figure>

    <dialog id="modal-ver">
        <article>
            <header>
                <a href="javascript:void(0)" onclick="cerrarModal()" aria-label="Close" class="close"></a>
                <h3 id="modal-id"></h3>
            </header>
            <h5 id="modal-equipo"></h5>
            <p id="modal-detalle" style="white-space: pre-wrap;"></p>
            <footer><button onclick="cerrarModal()">Cerrar</button></footer>
        </article>
    </dialog>

    <script>
        function verTicket(id, equipo, detalle) {
            document.getElementById('modal-id').innerText = "Detalle del Ticket: " + id;
            document.getElementById('modal-equipo').innerText = "Equipo: " + equipo;
            document.getElementById('modal-detalle').innerText = detalle || "Sin notas adicionales.";
            document.getElementById('modal-ver').setAttribute('open', true);
        }
        function cerrarModal() {
            document.getElementById('modal-ver').removeAttribute('open');
        }
    </script>
</body>
</html>
