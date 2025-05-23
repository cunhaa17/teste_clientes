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

if (!isset($_GET['id'])) {
    header("Location: servico.php");
    exit();
}

$id = intval($_GET['id']);

// Buscar informações do subtipo de serviço
$query = "SELECT ss.*, s.nome as servico_nome 
          FROM servico_subtipo ss 
          JOIN servico s ON ss.servico_id = s.id 
          WHERE ss.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$subservico = $result->fetch_assoc();

if (!$subservico) {
    header("Location: servico.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $descricao = trim($_POST['descricao']);
    $preco = trim($_POST['preco']);
    $duracao = trim($_POST['duracao']);

    // Verifica se os campos obrigatórios foram preenchidos
    if (empty($nome) || empty($preco) || empty($duracao)) {
        $_SESSION['error'] = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        // Atualiza o subtipo de serviço
        $query = "UPDATE servico_subtipo SET nome = ?, descricao = ?, preco = ?, duracao = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssi", $nome, $descricao, $preco, $duracao, $id);

        if ($stmt->execute()) {
            $_SESSION['success'] = 'Subtipo de serviço atualizado com sucesso!';
            header("Location: servico.php");
            exit();
        } else {
            $_SESSION['error'] = 'Erro ao atualizar subtipo de serviço. Tente novamente.';
        }
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
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($subservico['servico_nome']); ?>" readonly>
                </div>

                <div class="mb-3">
                    <label for="nome" class="form-label">Nome do Subtipo *</label>
                    <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($subservico['nome']); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="descricao" class="form-label">Descrição</label>
                    <textarea class="form-control" id="descricao" name="descricao" rows="3"><?php echo htmlspecialchars($subservico['descricao']); ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="preco" class="form-label">Preço (€) *</label>
                    <input type="number" step="0.01" class="form-control" id="preco" name="preco" value="<?php echo htmlspecialchars($subservico['preco']); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="duracao" class="form-label">Duração (minutos) *</label>
                    <input type="number" class="form-control" id="duracao" name="duracao" value="<?php echo htmlspecialchars($subservico['duracao']); ?>" required>
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