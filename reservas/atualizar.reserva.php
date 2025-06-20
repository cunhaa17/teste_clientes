<?php
session_start();
include_once '../includes/db_conexao.php';

if (!isset($_SESSION['utilizador_id']) || ($_SESSION['utilizador_tipo'] !== 'admin' && $_SESSION['utilizador_tipo'] !== 'funcionario')) {
    header("Location: ../login.php");
    exit();
}

// Se for uma requisição AJAX para atualizar status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && isset($_POST['status'])) {
    header('Content-Type: application/json');
    
    $id = $conn->real_escape_string($_POST['id']);
    $status = $conn->real_escape_string($_POST['status']);
    
    // Validar status
    $status_permitidos = ['confirmada', 'cancelada', 'concluída'];
    if (!in_array($status, $status_permitidos)) {
        echo json_encode(['status' => 'error', 'message' => 'Status inválido']);
        exit();
    }
    
    // Atualizar o status da reserva
    $sql = "UPDATE reserva SET status = '$status' WHERE id = '$id'";
    
    if ($conn->query($sql)) {
        echo json_encode(['status' => 'success', 'message' => 'Status atualizado com sucesso']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Erro ao atualizar status: ' . $conn->error]);
    }
    exit();
}

// Se for uma requisição normal para editar reserva
if (!isset($_GET['id'])) {
    echo "Reserva não encontrada.";
    exit();
}
$reserva_id = $conn->real_escape_string($_GET['id']);

// Buscar dados da reserva
$sql = "SELECT * FROM reserva WHERE id = '$reserva_id'";
$result = $conn->query($sql);
$reserva = $result->fetch_assoc();

if (!$reserva) {
    echo "Reserva não encontrada.";
    exit();
}

// Carregar listas para selects
$clientes = $conn->query("SELECT id, nome FROM cliente")->fetch_all(MYSQLI_ASSOC);
$servicos = $conn->query("SELECT id, nome FROM servico")->fetch_all(MYSQLI_ASSOC);
$subtipos = $conn->query("SELECT id, nome FROM servico_subtipo")->fetch_all(MYSQLI_ASSOC);
$funcionarios = $conn->query("SELECT id, nome FROM funcionario")->fetch_all(MYSQLI_ASSOC);

$title = "Atualizar Reserva";
ob_start();
?>
<div class="container mt-4">
    <h2>Atualizar Reserva</h2>
    <form method="post" action="guardar_reserva.php">
        <input type="hidden" name="id" value="<?= $reserva['id'] ?>">
        <div class="mb-3">
            <label for="data_reserva" class="form-label">Data e Hora</label>
            <input type="datetime-local" class="form-control" id="data_reserva" name="data_reserva" value="<?= date('Y-m-d\TH:i', strtotime($reserva['data_reserva'])) ?>" required>
        </div>
        <div class="mb-3">
            <label for="status" class="form-label">Status</label>
            <select class="form-select" id="status" name="status" required>
                <option value="confirmada" <?= $reserva['status'] == 'confirmada' ? 'selected' : '' ?>>Confirmada</option>
                <option value="cancelada" <?= $reserva['status'] == 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
                <option value="concluída" <?= $reserva['status'] == 'concluída' ? 'selected' : '' ?>>Concluída</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="cliente_id" class="form-label">Cliente</label>
            <select class="form-select" id="cliente_id" name="cliente_id" required>
                <?php foreach ($clientes as $cliente): ?>
                    <option value="<?= $cliente['id'] ?>" <?= $reserva['cliente_id'] == $cliente['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cliente['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="servico_id" class="form-label">Serviço</label>
            <select class="form-select" id="servico_id" name="servico_id" required>
                <?php foreach ($servicos as $servico): ?>
                    <option value="<?= $servico['id'] ?>" <?= $reserva['servico_id'] == $servico['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($servico['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="servico_subtipo_id" class="form-label">Subtipo</label>
            <select class="form-select" id="servico_subtipo_id" name="servico_subtipo_id" required>
                <?php foreach ($subtipos as $subtipo): ?>
                    <option value="<?= $subtipo['id'] ?>" <?= $reserva['servico_subtipo_id'] == $subtipo['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($subtipo['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="funcionario_id" class="form-label">Funcionário</label>
            <select class="form-select" id="funcionario_id" name="funcionario_id" required>
                <?php foreach ($funcionarios as $funcionario): ?>
                    <option value="<?= $funcionario['id'] ?>" <?= $reserva['funcionario_id'] == $funcionario['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($funcionario['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="observacao" class="form-label">Observação</label>
            <textarea class="form-control" id="observacao" name="observacao" rows="3"><?= htmlspecialchars($reserva['observacao'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Salvar</button>
        <a href="reservas.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
<?php
$content = ob_get_clean();
include '../includes/layout.php';
?> 