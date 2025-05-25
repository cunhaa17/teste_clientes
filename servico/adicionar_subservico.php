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

if (isset($_GET['servico_id'])) {
    $servico_id = $conn->real_escape_string($_GET['servico_id']);
    
    // Verificar se o serviço existe
    $sql = "SELECT id, nome FROM servico WHERE id = '$servico_id'";
    $result = $conn->query($sql);
    
    if ($result->num_rows === 0) {
        $_SESSION['mensagem'] = "Serviço não encontrado.";
        header("Location: servico.php");
        exit();
    }
    
    $servico = $result->fetch_assoc();
} else {
    header("Location: servico.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $servico_id = $conn->real_escape_string($_POST['servico_id']);
    $nome = $conn->real_escape_string($_POST['nome']);
    $descricao = $conn->real_escape_string($_POST['descricao']);
    $preco = $conn->real_escape_string($_POST['preco']);
    $duracao = $conn->real_escape_string($_POST['duracao']);
    
    // Verificar se já existe um subtipo com o mesmo nome
    $sql_check = "SELECT id FROM servico_subtipo WHERE nome = '$nome' AND servico_id = '$servico_id'";
    $result_check = $conn->query($sql_check);
    
    if ($result_check->num_rows > 0) {
        $_SESSION['mensagem'] = "Já existe um subtipo com este nome.";
        header("Location: adicionar_subservico.php?servico_id=" . $servico_id);
        exit();
    }
    
    // Inserir o novo subtipo
    $sql = "INSERT INTO servico_subtipo (servico_id, nome, descricao, preco, duracao) VALUES ('$servico_id', '$nome', '$descricao', '$preco', '$duracao')";
    
    if ($conn->query($sql)) {
        $_SESSION['success'] = "Subtipo adicionado com sucesso!";
        header("Location: servico.php");
        exit();
    } else {
        $_SESSION['mensagem'] = "Erro ao adicionar subtipo: " . $conn->error;
        header("Location: adicionar_subservico.php?servico_id=" . $servico_id);
        exit();
    }
}

$title = 'Adicionar Subtipo de Serviço';

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