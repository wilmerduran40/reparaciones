<?php
require_once __DIR__ . '/../config.php'; // El .. sube un nivel a la raíz$message = '';
$message_type = '';

$stmt = $pdo->query("SELECT COALESCE(MAX(ticket), 0) + 1 as next_ticket FROM reparaciones");
$next_ticket = $stmt->fetch()['next_ticket'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'crear') {
    $cliente = trim($_POST['cliente']);
    $dispositivo = trim($_POST['dispositivo']);
    $problema = trim($_POST['problema']);
    
    if ($cliente && $dispositivo && $problema) {
        $stmt = $pdo->prepare("INSERT INTO reparaciones (ticket, cliente, dispositivo, problema, estatus) VALUES (?, ?, ?, ?, 'en_revision')");
        $stmt->execute([$next_ticket, $cliente, $dispositivo, $problema]);
        $message = "Reparación registrada. Ticket #$next_ticket";
        $message_type = 'success';
        $next_ticket++;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'actualizar_estatus') {
    $id = $_POST['id'];
    $estatus = $_POST['estatus'];
    $observaciones = trim($_POST['observaciones']);
    
    $stmt = $pdo->prepare("UPDATE reparaciones SET estatus = ?, observaciones = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$estatus, $observaciones, $id]);
    $message = "Estatus actualizado";
    $message_type = 'success';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'eliminar') {
    $id = $_POST['id'];
    $stmt = $pdo->prepare("DELETE FROM reparaciones WHERE id = ?");
    $stmt->execute([$id]);
    $message = "Reparación eliminada";
    $message_type = 'success';
}

$stmt = $pdo->query("SELECT * FROM reparaciones ORDER BY ticket DESC");
$reparaciones = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Reparaciones</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: Arial, sans-serif; }
        body { background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #333; margin-bottom: 20px; }
        
        .card { background: white; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        textarea { height: 80px; resize: vertical; }
        
        .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }
        .btn-primary { background: #007bff; color: white; }
        .btn-primary:hover { background: #0056b3; }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; }
        .btn-warning { background: #ffc107; color: #333; }
        .btn-warning:hover { background: #e0a800; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        
        .message { padding: 10px 15px; border-radius: 4px; margin-bottom: 20px; }
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f8f8; font-weight: bold; }
        tr:hover { background: #f9f9f9; }
        
        .ticket { font-weight: bold; color: #007bff; font-size: 18px; }
        .estatus { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; display: inline-block; }
        .estatus.en_revision { background: #fff3cd; color: #856404; }
        .estatus.reparado { background: #d4edda; color: #155724; }
        .estatus.para_repuesto { background: #f8d7da; color: #721c24; }
        
        .actions { display: flex; gap: 5px; flex-wrap: wrap; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; }
        .modal.active { display: flex; align-items: center; justify-content: center; }
        .modal-content { background: white; padding: 20px; border-radius: 8px; max-width: 400px; width: 90%; }
        
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        
        @media (max-width: 768px) {
            .grid-2 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Sistema de Reparaciones</h1>
        
        <?php if ($message): ?>
            <div class="message <?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <div class="grid-2">
            <div class="card">
                <h2 style="margin-bottom: 15px;">Nueva Reparación</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="crear">
                    <div class="form-group">
                        <label>Ticket #</label>
                        <input type="text" value="<?= $next_ticket ?>" disabled style="background: #f5f5f5;">
                    </div>
                    <div class="form-group">
                        <label>Cliente *</label>
                        <input type="text" name="cliente" required placeholder="Nombre del cliente">
                    </div>
                    <div class="form-group">
                        <label>Dispositivo *</label>
                        <input type="text" name="dispositivo" required placeholder="Tipo de dispositivo">
                    </div>
                    <div class="form-group">
                        <label>Problema *</label>
                        <textarea name="problema" required placeholder="Describe el problema"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Registrar Reparación</button>
                </form>
            </div>
            
            <div class="card">
                <h2 style="margin-bottom: 15px;">Resumen</h2>
                <?php
                $total = count($reparaciones);
                $en_revision = count(array_filter($reparaciones, fn($r) => $r['estatus'] === 'en_revision'));
                $reparado = count(array_filter($reparaciones, fn($r) => $r['estatus'] === 'reparado'));
                $para_repuesto = count(array_filter($reparaciones, fn($r) => $r['estatus'] === 'para_repuesto'));
                ?>
                <p><strong>Total:</strong> <?= $total ?></p>
                <p><strong>En Revisión:</strong> <?= $en_revision ?></p>
                <p><strong>Reparado:</strong> <?= $reparado ?></p>
                <p><strong>Para Repuesto:</strong> <?= $para_repuesto ?></p>
            </div>
        </div>
        
        <div class="card">
            <h2 style="margin-bottom: 15px;">Lista de Reparaciones</h2>
            <?php if (empty($reparaciones)): ?>
                <p>No hay reparaciones registradas.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Ticket</th>
                            <th>Cliente</th>
                            <th>Dispositivo</th>
                            <th>Problema</th>
                            <th>Estatus</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reparaciones as $r): ?>
                            <tr>
                                <td class="ticket">#<?= htmlspecialchars($r['ticket']) ?></td>
                                <td><?= htmlspecialchars($r['cliente']) ?></td>
                                <td><?= htmlspecialchars($r['dispositivo']) ?></td>
                                <td><?= htmlspecialchars($r['problema']) ?></td>
                                <td><span class="estatus <?= htmlspecialchars($r['estatus']) ?>"><?= htmlspecialchars($r['estatus']) ?></span></td>
                                <td><?= htmlspecialchars($r['fecha_entrada']) ?></td>
                                <td>
                                    <div class="actions">
                                        <button class="btn btn-warning" onclick="openModal(<?= $r['id'] ?>, '<?= htmlspecialchars($r['estatus']) ?>', '<?= htmlspecialchars($r['observaciones'] ?? '') ?>')">Editar</button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar esta reparación?');">
                                            <input type="hidden" name="action" value="eliminar">
                                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                            <button type="submit" class="btn btn-danger">Eliminar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h3 style="margin-bottom: 15px;">Editar Estatus</h3>
            <form method="POST">
                <input type="hidden" name="action" value="actualizar_estatus">
                <input type="hidden" name="id" id="modalId">
                <div class="form-group">
                    <label>Nuevo Estatus</label>
                    <select name="estatus" id="modalEstatus">
                        <option value="en_revision">En Revisión</option>
                        <option value="reparado">Reparado</option>
                        <option value="para_repuesto">Para Repuesto</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Observaciones</label>
                    <textarea name="observaciones" id="modalObservaciones" placeholder="Notas adicionales"></textarea>
                </div>
                <button type="submit" class="btn btn-success">Guardar</button>
                <button type="button" class="btn" onclick="closeModal()">Cancelar</button>
            </form>
        </div>
    </div>
    
    <script>
        function openModal(id, estatus, observaciones) {
            document.getElementById('modalId').value = id;
            document.getElementById('modalEstatus').value = estatus;
            document.getElementById('modalObservaciones').value = observaciones || '';
            document.getElementById('editModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('editModal').classList.remove('active');
        }
        
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    </script>
</body>
</html>
