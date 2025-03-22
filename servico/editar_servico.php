<?php
session_start();
$mensagem = $_SESSION['mensagem'] ?? '';
$success_message = $_SESSION['success'] ?? '';
unset($_SESSION['mensagem'], $_SESSION['success']);

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

require_once '../includes/db_conexao.php';
$title = 'Editar Serviço'; 

if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    $query = "SELECT * FROM servico_subtipo WHERE id = '$id'";
    $result = mysqli_query($conn, $query);
    $servico = mysqli_fetch_assoc($result);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $nome = mysqli_real_escape_string($conn, $_POST['nome']);
    $descricao = mysqli_real_escape_string($conn, $_POST['descricao']); 
    $preco = mysqli_real_escape_string($conn, $_POST['preco']);
    $duracao = mysqli_real_escape_string($conn, $_POST['duracao']);

    // Check for duplicates
    $query = "SELECT * FROM servico_subtipo WHERE (nome = ?) AND id != ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $nome, $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['error'] = 'O serviço ja existe!';
    } else {
        $query = "UPDATE servico_subtipo SET nome = '$nome', descricao = '$descricao', preco = '$preco' , duracao = '$duracao'  WHERE id = '$id'";
        
        if(mysqli_query($conn, $query)) {
            $_SESSION['success'] = 'Serviço atualizado com sucesso!';
            header('Location: servico.php');
            exit();
        } else {
            $erro = "Erro ao atualizar: " . mysqli_error($conn);
        }
    }
}

// Start output buffering
ob_start();
?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="fs-3 mb-3"><?php echo isset($title) ? $title : 'Serviço'; ?></h1>
        <a href="servico.php" class="btn btn-secondary">Voltar</a>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="id" value="<?php echo $servico['id']; ?>">
                
                <div class="mb-3">
                    <label class="form-label">Nome</label>
                    <input type="text" name="nome" class="form-control" value="<?php echo $servico['nome']; ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Descrição</label>
                    <input type="text" name="descricao" class="form-control" value="<?php echo $servico['descricao']; ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Preço</label>
                    <input type="float" name="preco" class="form-control" value="<?php echo $servico['preco']; ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Duração</label>
                    <input type="int" name="duracao" class="form-control" value="<?php echo $servico['duracao']; ?>" required>
                </div>

                <div class="text-end">
                    <a href="servico.php" class="btn btn-secondary me-2">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../includes/layout.php';
?>
