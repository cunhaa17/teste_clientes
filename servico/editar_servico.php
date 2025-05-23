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

if (!isset($_GET['id'])) {
    header("Location: servico.php");
    exit();
}

$id = intval($_GET['id']);

// Buscar informações do serviço
$query = "SELECT * FROM servico WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$servico = $result->fetch_assoc();

if (!$servico) {
    header("Location: servico.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);

    // Verifica se os campos obrigatórios foram preenchidos
    if (empty($nome)) {
        $_SESSION['error'] = 'Por favor, preencha o nome do serviço.';
    } else {
        // Verifica se já existe um serviço com o mesmo nome
        $check_query = "SELECT id FROM servico WHERE nome = ? AND id != ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("si", $nome, $id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $_SESSION['error'] = 'Já existe um serviço com este nome.';
        } else {
            // Atualiza o serviço
            $update_query = "UPDATE servico SET nome = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("si", $nome, $id);

            if ($update_stmt->execute()) {
                $_SESSION['success'] = 'Serviço atualizado com sucesso!';
                header("Location: servico.php");
                exit();
            } else {
                $_SESSION['error'] = '❌ Erro ao atualizar serviço. Tente novamente.';
            }
            $update_stmt->close();
        }
        $check_stmt->close();
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
