<?php
session_start();

// --- CONFIGURACIÓN DE SEGURIDAD ---
$password_maestra = "duran1612"; // CAMBIA TU CONTRASEÑA AQUÍ

// Lógica de Login/Logout
if (isset($_POST['login'])) {
    if ($_POST['pass'] === $password_maestra) {
        $_SESSION['admin'] = true;
    }
}
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

$es_admin = isset($_SESSION['admin']);

// --- CONEXIÓN A BASE DE DATOS ---
$host = getenv('PGHOST');
$db   = getenv('PGDATABASE');
$user = getenv('PGUSER');
$pass = getenv('PGPASSWORD');
$port = getenv('PGPORT') ?: '5432';

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // 1. Lógica: Registrar nuevo (Solo Admin)
    if ($es_admin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'nuevo') {
        $stmt = $pdo->prepare("INSERT INTO reparaciones (cliente, telefono, equipo, detalle, costo, estado) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['cliente'], $_POST['telefono'], $_POST['equipo'], $_POST['detalle'], $_POST['costo'] ?: 0, $_POST['estado']]);
        header("Location: index.php"); exit;
    }

    // 2. Lógica: Actualizar Estado (Solo Admin)
    if ($es_admin && isset($_GET['update_id']) && isset($_GET['new_status'])) {
        $stmt = $pdo->prepare("UPDATE reparaciones SET estado = ? WHERE id = ?");
        $stmt->execute([$_GET['new_status'], $_GET['update_id']]);
        header("Location: index.php"); exit;
    }

    // 3. Consulta de tickets (Público)
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
    <title>Tecnet | Estado de Tickets</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css">
    <style>
        .badge { padding: 4px 12px; border-radius: 50px; font-size: 0.8rem; font-weight: bold; display: inline-block; }
        .Revision { background: #fff3e0; color: #ef6c00; }
        .Listo { background: #e8f5e9; color: #2e7d32; }
        .Entregado { background: #e3f2fd; color: #1565c0; }
        nav { background: #1a1a1a; padding: 10px 20px; margin-bottom: 30px; }
        nav a, nav strong { color: white; }
        .admin-section { background: #f4f4f4; padding: 20px; border-radius: 10px; border: 2px dashed #ccc; margin-bottom: 30px; }
    </style>
</head>
<body>

    <nav>
        <ul>
            <li><strong>TECNET SERVICE 🛠️</strong></li>
        </ul>
        <ul>
            <?php if (!$es_admin): ?>
                <li>
                    <form method="POST" style="display:flex; margin:0; gap:10px;">
                        <input type="password" name="pass" placeholder="Contraseña Admin" required style="margin:0; height:40px;">
                        <button type="submit" name="login" class="outline" style="margin:0; height:40px; padding: 0 20px;">Entrar</button>
                    </form>
                </li>
            <?php else: ?>
                <li><mark>Modo Administrador Activo</mark></li>
                <li><a href="?logout=1" class="secondary">Cerrar Sesión</a></li>
            <?php Kalbi; endif; ?>
        </ul>
    </nav>

    <main class="container">
        
        <?php if ($es_admin): ?>
            <section class="admin-section">
                <h3>+ Registrar Nuevo Ticket</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="nuevo">
                    <div class="grid">
                        <input type="text" name="cliente" placeholder="Cliente" required>
                        <input type="text" name="telefono" placeholder="WhatsApp">
                        <input type="text" name="equipo" placeholder="Equipo/Modelo" required>
                    </div>
                    <div class="grid">
                        <input type="number" step="0.01" name="costo" placeholder="Costo ($)">
                        <select name="estado">
                            <option value="Revision">En Revisión</option>
                            <option value="Listo">Listo</option>
                            <option value="Entregado">Entregado</option>
                        </select>
                        <button type="submit">Guardar Registro</button>
                    </div>
                    <textarea name="detalle" placeholder="Falla y detalles técnicos..."></textarea>
                </form>
            </section>
        <?php endif; ?>

        <section>
            <div style="display:flex; justify-content: space-between; align-items: center;">
                <h2>Estado de Reparaciones</h2>
                <form method="GET" style="width: 300px;">
                    <input type="search" name="q" placeholder="Buscar ticket..." value="<?= htmlspecialchars($buscar) ?>" style="margin:0;">
                </form>
            </div>

            <figure>
                <table role="grid">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Equipo / Cliente</th>
                            <th>Estado</th>
                            <?php if ($es_admin): ?> <th>Acciones</th> <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registros as $r): ?>
                        <tr>
                            <td><?= date('d/m/y', strtotime($r['fecha'])) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($r['equipo']) ?></strong><br>
                                <small><?= htmlspecialchars($r['cliente']) ?></small>
                            </td>
                            <td><span class="badge <?= $r['estado'] ?>"><?= $r['estado'] ?></span></td>
                            <?php if ($es_admin): ?>
                            <td>
                                <select onchange="window.location.href='?update_id=<?= $r['id'] ?>&new_status='+this.value" style="font-size:0.7rem; padding: 5px; margin:0;">
                                    <option value="">Cambiar...</option>
                                    <option value="Revision">Revisión</option>
                                    <option value="Listo">Listo</option>
                                    <option value="Entregado">Entregado</option>
                                </select>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </figure>
        </section>
    </main>
</body>
</html>
