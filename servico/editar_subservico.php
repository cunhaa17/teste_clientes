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

// Validar e obter ID
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])) {
    $_SESSION['mensagem'] = "ID inválido.";
    header("Location: servico.php");
    exit();
}

$id = intval($_GET['id']);

// Buscar dados do subtipo
$sql = "SELECT s.*, ss.nome as servico_nome 
        FROM servico_subtipo s 
        JOIN servico ss ON s.servico_id = ss.id 
        WHERE s.id = '" . $conn->real_escape_string($id) . "'";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
    $_SESSION['mensagem'] = "Subtipo não encontrado.";
    header("Location: servico.php");
    exit();
}

$subtipo = $result->fetch_assoc();

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar se o ID foi enviado
    if (!isset($_POST['id']) || !filter_var($_POST['id'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])) {
        $_SESSION['mensagem'] = "ID inválido no formulário.";
        header("Location: servico.php");
        exit();
    }
    
    $id_post = intval($_POST['id']);
    $nome = trim($_POST['nome']);
    $descricao = trim($_POST['descricao']);
    $preco = str_replace(',', '.', trim($_POST['preco']));
    $duracao = intval($_POST['duracao']);
    
    // Validações
    if (empty($nome)) {
        $_SESSION['mensagem'] = "O nome do subtipo é obrigatório.";
        header("Location: editar_subservico.php?id=" . $id);
        exit();
    }
    
    if (!is_numeric($preco) || $preco <= 0) {
        $_SESSION['mensagem'] = "O preço deve ser um valor positivo.";
        header("Location: editar_subservico.php?id=" . $id);
        exit();
    }
    
    if ($duracao <= 0) {
        $_SESSION['mensagem'] = "A duração deve ser um valor positivo.";
        header("Location: editar_subservico.php?id=" . $id);
        exit();
    }
    
    // Verificar se já existe um subtipo com o mesmo nome (excluindo o atual)
    $sql_check = "SELECT id FROM servico_subtipo WHERE nome = '" . $conn->real_escape_string($nome) . "' 
                  AND servico_id = '" . $conn->real_escape_string($subtipo['servico_id']) . "' 
                  AND id != '" . $conn->real_escape_string($id_post) . "'";
    $result_check = $conn->query($sql_check);
    
    if ($result_check->num_rows > 0) {
        $_SESSION['mensagem'] = "Já existe um subtipo com este nome neste serviço.";
        header("Location: editar_subservico.php?id=" . $id);
        exit();
    }
    
    // Atualizar o subtipo
    $sql_update = "UPDATE servico_subtipo SET 
                   nome = '" . $conn->real_escape_string($nome) . "', 
                   descricao = '" . $conn->real_escape_string($descricao) . "', 
                   preco = '" . $conn->real_escape_string($preco) . "', 
                   duracao = '" . $conn->real_escape_string($duracao) . "' 
                   WHERE id = '" . $conn->real_escape_string($id_post) . "'";
    
    if ($conn->query($sql_update)) {
        $_SESSION['success'] = "Subtipo atualizado com sucesso!";
        header("Location: servico.php");
        exit();
    } else {
        $_SESSION['mensagem'] = "Erro ao atualizar subtipo: " . $conn->error;
        header("Location: editar_subservico.php?id=" . $id);
        exit();
    }
}

$title = 'Editar Subtipo de Serviço';

ob_start();
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Editar Subtipo de Serviço</h1>
        <a href="servico.php" class="btn btn-secondary">Voltar</a>
    </div>

    <?php if (isset($_SESSION['mensagem'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php 
            echo htmlspecialchars($_SESSION['mensagem']);
            unset($_SESSION['mensagem']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="editar_subservico.php?id=<?php echo $id; ?>">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
                
                <div class="mb-3">
                    <label class="form-label">Serviço Principal</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($subtipo['servico_nome']); ?>" readonly>
                </div>

                <div class="mb-3">
                    <label for="nome" class="form-label">Nome do Subtipo *</label>
                    <input type="text" class="form-control" id="nome" name="nome" 
                           value="<?php echo htmlspecialchars($subtipo['nome']); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="descricao" class="form-label">Descrição</label>
                    <textarea class="form-control" id="descricao" name="descricao" rows="3"><?php echo htmlspecialchars($subtipo['descricao']); ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="preco" class="form-label">Preço (€) *</label>
                    <input type="number" step="0.01" class="form-control" id="preco" name="preco" 
                           value="<?php echo number_format($subtipo['preco'], 2, '.', ''); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="duracao" class="form-label">Duração (minutos) *</label>
                    <input type="number" class="form-control" id="duracao" name="duracao" 
                           value="<?php echo intval($subtipo['duracao']); ?>" required>
                </div>

                <button type="submit" class="btn btn-primary">Atualizar Subtipo</button>
                <a href="servico.php" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../includes/layout.php';
?> 