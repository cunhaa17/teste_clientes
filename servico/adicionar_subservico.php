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

    <?php 
    if (isset($_SESSION['error'])) {
        echo '
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Erro:</strong> ' . htmlspecialchars($_SESSION['error']) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
        unset($_SESSION['error']);
    }

    if (isset($_SESSION['success'])) {
        echo '
        <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1050;">
            <div id="successToast" class="toast align-items-center text-white border-0" role="alert" aria-live="assertive" aria-atomic="true" style="background: linear-gradient(45deg, #28a745, #20c997); border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                <div class="d-flex align-items-center p-3">
                    <div class="toast-icon me-3">
                        <i class="bi bi-check-circle-fill fs-4"></i>
                    </div>
                    <div class="toast-body fs-5">
                        ' . htmlspecialchars($_SESSION['success']) . '
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="progress" style="height: 4px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: 100%" id="toastProgressBar"></div>
                </div>
            </div>
        </div>';

        unset($_SESSION['success']);
    }

    if (isset($_SESSION['mensagem'])) {
        echo '
        <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1050;">
            <div id="errorToast" class="toast align-items-center text-white border-0" role="alert" aria-live="assertive" aria-atomic="true" style="background: linear-gradient(45deg, #dc3545, #c82333); border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                <div class="d-flex align-items-center p-3">
                    <div class="toast-icon me-3">
                        <i class="bi bi-exclamation-circle-fill fs-4"></i>
                    </div>
                    <div class="toast-body fs-5">
                        ' . htmlspecialchars($_SESSION['mensagem']) . '
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="progress" style="height: 4px;">
                    <div class="progress-bar bg-danger" role="progressbar" style="width: 100%" id="toastProgressBar"></div>
                </div>
            </div>
        </div>';
        unset($_SESSION['mensagem']);
    }
    ?>

    <form method="POST">
        <input type="hidden" name="servico_id" value="<?php echo htmlspecialchars($servico_id); ?>">
        
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

        <button type="submit" class="btn btn-success">Adicionar</button>
        <a href="servico.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const toastEl = document.getElementById('successToast') || document.getElementById('errorToast');
  const progressBar = document.getElementById('toastProgressBar');
  if (toastEl && progressBar) {
    let width = 100;
    const duration = 3000; // 3 segundos
    const intervalTime = 30;

    // Mostra o toast
    const toast = new bootstrap.Toast(toastEl, { autohide: false });
    toast.show();

    // Anima a barra
    const interval = setInterval(() => {
      width -= (intervalTime / duration) * 100;
      progressBar.style.width = width + "%";
      if (width <= 0) {
        clearInterval(interval);
        toast.hide();
      }
    }, intervalTime);
  }
});
</script>

<?php
$content = ob_get_clean();
include '../includes/layout.php';
?> 