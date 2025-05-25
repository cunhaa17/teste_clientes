<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
    $id = $conn->real_escape_string($_GET['id']);
    
    // Buscar dados do serviço
    $sql = "SELECT * FROM servico WHERE id = '$id'";
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
    $id = $conn->real_escape_string($_POST['id']);
    $nome = $conn->real_escape_string($_POST['nome']);
    
    // Verificar se já existe um serviço com o mesmo nome
    $sql_check = "SELECT id FROM servico WHERE nome = '$nome' AND id != '$id'";
    $result_check = $conn->query($sql_check);
    
    if ($result_check->num_rows > 0) {
        $_SESSION['mensagem'] = "Já existe um serviço com este nome.";
        header("Location: editar_servico.php?id=" . $id);
        exit();
    }
    
    // Atualizar o serviço
    $sql = "UPDATE servico SET nome = '$nome' WHERE id = '$id'";
    
    if ($conn->query($sql)) {
        $_SESSION['success'] = "Serviço atualizado com sucesso!";
        header("Location: servico.php");
        exit();
    } else {
        $_SESSION['mensagem'] = "Erro ao atualizar serviço: " . $conn->error;
        header("Location: editar_servico.php?id=" . $id);
        exit();
    }
}

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
            <form method="POST" action="editar_servico.php?id=<?php echo $id; ?>">
                <div class="mb-3">
                    <label for="nome" class="form-label">Nome do Serviço *</label>
                    <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($servico['nome']); ?>" required>
                </div>

                <button type="submit" class="btn btn-primary">Atualizar Serviço</button>
            </form>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="successModalLabel">Sucesso!</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                <p class="mt-3 mb-0">Serviço atualizado com sucesso!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Verifica se há mensagem de sucesso na sessão
    <?php if (isset($_SESSION['success'])): ?>
        const successModal = new bootstrap.Modal(document.getElementById('successModal'));
        successModal.show();
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
});
</script>

<?php
$content = ob_get_clean();
include '../includes/layout.php';
?>
