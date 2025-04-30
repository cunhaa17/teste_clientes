<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Verifica se a sessão está iniciada corretamente
if (!isset($_SESSION['utilizador_id'])) {
    header("Location: ../login.php");
    exit();
}

// Verifica se o usuário é do tipo admin
if ($_SESSION['utilizador_tipo'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$title = "Editar Horário de Funcionário";
include_once '../includes/db_conexao.php';

// Verificar se o ID foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['mensagem'] = "ID do horário não fornecido";
    header("Location: agenda_funcionarios.php");
    exit();
}

$id = intval($_GET['id']);

// Buscar dados do horário
$sql = "SELECT id, funcionario_id, data_inicio, data_fim FROM agenda_funcionario WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['mensagem'] = "Horário não encontrado";
    header("Location: agenda_funcionarios.php");
    exit();
}

$agenda = $result->fetch_assoc();
$stmt->close();

// Formatar datas e horas para o formulário
$data_inicio = date('Y-m-d', strtotime($agenda['data_inicio']));
$hora_inicio = date('H:i', strtotime($agenda['data_inicio']));
$data_fim = date('Y-m-d', strtotime($agenda['data_fim']));
$hora_fim = date('H:i', strtotime($agenda['data_fim']));

// Buscar funcionários para o select
$sql_funcionarios = "SELECT id, nome FROM funcionario ORDER BY nome";
$result_funcionarios = $conn->query($sql_funcionarios);
$funcionarios = $result_funcionarios->fetch_all(MYSQLI_ASSOC);

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $funcionario_id = $_POST['funcionario_id'];
    $data_inicio = $_POST['data_inicio'] . ' ' . $_POST['hora_inicio'];
    $data_fim = $_POST['data_fim'] . ' ' . $_POST['hora_fim'];
    
    // Validações
    $errors = [];
    
    if (empty($funcionario_id)) {
        $errors[] = "O funcionário é obrigatório";
    }
    
    if (empty($_POST['data_inicio']) || empty($_POST['hora_inicio'])) {
        $errors[] = "A data e hora de início são obrigatórias";
    }
    
    if (empty($_POST['data_fim']) || empty($_POST['hora_fim'])) {
        $errors[] = "A data e hora de fim são obrigatórias";
    }
    
    // Verificar se a data de fim é posterior à data de início
    if (strtotime($data_fim) <= strtotime($data_inicio)) {
        $errors[] = "A data/hora de fim deve ser posterior à data/hora de início";
    }
    
    // Verificar se há sobreposição com outros horários (excluindo o atual)
    if (empty($errors)) {
        $sql_check = "SELECT id FROM agenda_funcionario 
                      WHERE funcionario_id = ? 
                      AND id != ?
                      AND ((data_inicio <= ? AND data_fim >= ?) 
                           OR (data_inicio <= ? AND data_fim >= ?)
                           OR (data_inicio >= ? AND data_fim <= ?))";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("iissssss", $funcionario_id, $id, $data_fim, $data_inicio, $data_inicio, $data_inicio, $data_inicio, $data_fim);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            $errors[] = "Há sobreposição com outro horário já cadastrado para este funcionário";
        }
        
        $stmt_check->close();
    }
    
    // Se não houver erros, atualiza no banco
    if (empty($errors)) {
        $sql = "UPDATE agenda_funcionario SET funcionario_id = ?, data_inicio = ?, data_fim = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issi", $funcionario_id, $data_inicio, $data_fim, $id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Horário atualizado com sucesso!";
            header("Location: agenda_funcionarios.php");
            exit();
        } else {
            $errors[] = "Erro ao atualizar horário: " . $conn->error;
        }
        
        $stmt->close();
    }
}

ob_start();
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Editar Horário de Trabalho</h1>
        <a href="agenda_funcionarios.php" class="btn btn-secondary">Voltar</a>
    </div>
    
    <?php if (isset($errors) && !empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <form method="POST" action="">
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="funcionario_id" class="form-label">Funcionário *</label>
                        <select name="funcionario_id" id="funcionario_id" class="form-select" required>
                            <option value="">Selecione um funcionário</option>
                            <?php foreach ($funcionarios as $funcionario): ?>
                                <option value="<?php echo $funcionario['id']; ?>" <?php echo $agenda['funcionario_id'] == $funcionario['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($funcionario['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="data_inicio" class="form-label">Data de Início *</label>
                        <input type="date" name="data_inicio" id="data_inicio" class="form-control" required
                               value="<?php echo isset($_POST['data_inicio']) ? htmlspecialchars($_POST['data_inicio']) : $data_inicio; ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="hora_inicio" class="form-label">Hora de Início *</label>
                        <input type="time" name="hora_inicio" id="hora_inicio" class="form-control" required
                               value="<?php echo isset($_POST['hora_inicio']) ? htmlspecialchars($_POST['hora_inicio']) : $hora_inicio; ?>">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="data_fim" class="form-label">Data de Fim *</label>
                        <input type="date" name="data_fim" id="data_fim" class="form-control" required
                               value="<?php echo isset($_POST['data_fim']) ? htmlspecialchars($_POST['data_fim']) : $data_fim; ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="hora_fim" class="form-label">Hora de Fim *</label>
                        <input type="time" name="hora_fim" id="hora_fim" class="form-control" required
                               value="<?php echo isset($_POST['hora_fim']) ? htmlspecialchars($_POST['hora_fim']) : $hora_fim; ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="form-text text-muted">
                        <strong>Nota:</strong> Este horário define quando o funcionário estará disponível para atender clientes.
                    </div>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Atualizar Horário</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../includes/layout.php';
?>