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

if (isset($_GET['id'])) {
    $id = $conn->real_escape_string($_GET['id']);
    
    // Buscar dados do subtipo
    $sql = "SELECT s.*, ss.nome as servico_nome 
            FROM servico_subtipo s 
            JOIN servico ss ON s.servico_id = ss.id 
            WHERE s.id = '$id'";
    $result = $conn->query($sql);
    
    if ($result->num_rows === 0) {
        $_SESSION['mensagem'] = "Subtipo não encontrado.";
        header("Location: servico.php");
        exit();
    }
    
    $subtipo = $result->fetch_assoc();
} else {
    header("Location: servico.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $conn->real_escape_string($_POST['id']);
    $nome = $conn->real_escape_string($_POST['nome']);
    $descricao = $conn->real_escape_string($_POST['descricao']);
    $preco = $conn->real_escape_string($_POST['preco']);
    $duracao = $conn->real_escape_string($_POST['duracao']);
    
    // Verificar se já existe um subtipo com o mesmo nome
    $sql_check = "SELECT id FROM servico_subtipo WHERE nome = '$nome' AND id != '$id'";
    $result_check = $conn->query($sql_check);
    
    if ($result_check->num_rows > 0) {
        $_SESSION['mensagem'] = "Já existe um subtipo com este nome.";
        header("Location: editar_subservico.php?id=" . $id);
        exit();
    }
    
    // Atualizar o subtipo
    $sql = "UPDATE servico_subtipo SET nome = '$nome', descricao = '$descricao', preco = '$preco', duracao = '$duracao' WHERE id = '$id'";
    
    if ($conn->query($sql)) {
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
            <form method="POST" action="editar_subservico.php?id=<?php echo $id; ?>">
                <div class="mb-3">
                    <label class="form-label">Serviço Principal</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($subtipo['servico_nome']); ?>" readonly>
                </div>

                <div class="mb-3">
                    <label for="nome" class="form-label">Nome do Subtipo *</label>
                    <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($subtipo['nome']); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="descricao" class="form-label">Descrição</label>
                    <textarea class="form-control" id="descricao" name="descricao" rows="3"><?php echo htmlspecialchars($subtipo['descricao']); ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="preco" class="form-label">Preço (€) *</label>
                    <input type="number" step="0.01" class="form-control" id="preco" name="preco" value="<?php echo htmlspecialchars($subtipo['preco']); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="duracao" class="form-label">Duração (minutos) *</label>
                    <input type="number" class="form-control" id="duracao" name="duracao" value="<?php echo htmlspecialchars($subtipo['duracao']); ?>" required>
                </div>

                <button type="submit" class="btn btn-primary">Atualizar Subtipo</button>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../includes/layout.php';
?> 