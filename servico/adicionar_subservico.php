<?php
session_start();

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

include_once '../includes/db_conexao.php';

$servico_id = isset($_GET['servico_id']) ? intval($_GET['servico_id']) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $servico_id = trim($_POST['servico_id']);
    $nome = trim($_POST['nome']);
    $descricao = trim($_POST['descricao']);
    $preco = trim($_POST['preco']);
    $duracao = trim($_POST['duracao']);

    // Verifica se os campos obrigatórios foram preenchidos
    if (empty($servico_id) || empty($nome) || empty($preco) || empty($duracao)) {
        $_SESSION['error'] = 'Por favor, preencha todos os campos obrigatórios.';
        header('Location: adicionar_subservico.php?servico_id=' . $servico_id);
        exit();
    }

    // Verifica se o ID do serviço existe
    $query = "SELECT id FROM servico WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $servico_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['error'] = 'O serviço selecionado não existe.';
        header('Location: adicionar_subservico.php?servico_id=' . $servico_id);
        exit();
    }

    // Insere o subtipo de serviço
    $query = "INSERT INTO servico_subtipo (servico_id, nome, descricao, preco, duracao) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("issss", $servico_id, $nome, $descricao, $preco, $duracao);

    if ($stmt->execute()) {
        $_SESSION['success'] = 'Subtipo de serviço adicionado com sucesso!';
        header("Location: servico.php");
        exit();
    } else {
        $_SESSION['error'] = 'Erro ao adicionar subtipo de serviço. Tente novamente.';
    }
}

$title = 'Adicionar Subtipo de Serviço';

// Buscar informações do serviço principal
$query = "SELECT nome FROM servico WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $servico_id);
$stmt->execute();
$result = $stmt->get_result();
$servico = $result->fetch_assoc();

ob_start();
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Adicionar Subtipo de Serviço</h1>
        <a href="servico.php" class="btn btn-secondary">Voltar</a>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="adicionar_subservico.php">
                <input type="hidden" name="servico_id" value="<?php echo $servico_id; ?>">
                
                <div class="mb-3">
                    <label class="form-label">Serviço Principal</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($servico['nome'] ?? ''); ?>" readonly>
                </div>

                <div class="mb-3">
                    <label for="nome" class="form-label">Nome do Subtipo *</label>
                    <input type="text" class="form-control" id="nome" name="nome" required>
                </div>

                <div class="mb-3">
                    <label for="descricao" class="form-label">Descrição</label>
                    <textarea class="form-control" id="descricao" name="descricao" rows="3"></textarea>
                </div>

                <div class="mb-3">
                    <label for="preco" class="form-label">Preço (€) *</label>
                    <input type="number" step="0.01" class="form-control" id="preco" name="preco" required>
                </div>

                <div class="mb-3">
                    <label for="duracao" class="form-label">Duração (minutos) *</label>
                    <input type="number" class="form-control" id="duracao" name="duracao" required>
                </div>

                <button type="submit" class="btn btn-primary">Adicionar Subtipo</button>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../includes/layout.php';
?> 