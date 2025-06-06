<?php
session_start();
// Verifica se a sessão está iniciada corretamente
if (!isset($_SESSION['utilizador_id'])) {
    // Redireciona para a página de login, usando o caminho correto
    header("Location: ../login.php");
    exit();
}


// Verifica se o usuário é do tipo admin
if ($_SESSION['utilizador_tipo'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

include_once '../includes/db_conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $conn->real_escape_string($_POST['nome']);
    
    // Verificar se já existe um serviço com o mesmo nome
    $sql_check = "SELECT id FROM servico WHERE nome = '$nome'";
    $result_check = $conn->query($sql_check);
    
    if ($result_check->num_rows > 0) {
        $_SESSION['mensagem'] = "Já existe um serviço com este nome.";
        header("Location: adicionar_servico.php");
        exit();
    }
    
    // Inserir o novo serviço
    $sql = "INSERT INTO servico (nome) VALUES ('$nome')";
    
    if ($conn->query($sql)) {
        $_SESSION['success'] = "Serviço adicionado com sucesso!";
        header("Location: servico.php");
        exit();
    } else {
        $_SESSION['mensagem'] = "Erro ao adicionar serviço: " . $conn->error;
        header("Location: adicionar_servico.php");
        exit();
    }
}

$title = 'Adicionar Serviço';

// Your existing code...

// Start output buffering
ob_start();
?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Adicionar Serviço</h1>
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

    if (!empty($error)) {
        echo '
        <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1050;">
            <div id="errorToast" class="toast align-items-center text-white border-0" role="alert" aria-live="assertive" aria-atomic="true" style="background: linear-gradient(45deg, #dc3545, #c82333); border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                <div class="d-flex align-items-center p-3">
                    <div class="toast-icon me-3">
                        <i class="bi bi-exclamation-circle-fill fs-4"></i>
                    </div>
                    <div class="toast-body fs-5">
                        ' . htmlspecialchars($error) . '
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="progress" style="height: 4px;">
                    <div class="progress-bar bg-danger" role="progressbar" style="width: 100%" id="toastProgressBar"></div>
                </div>
            </div>
        </div>';
    }
    ?>

    <form method="POST">
        <div class="mb-3">
            <label for="nome" class="form-label">Nome do Serviço</label>
            <input type="text" class="form-control" id="nome" name="nome" required>
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
$content = ob_get_clean(); // Capture the output and store it in $content

// Include the layout file
include '../includes/layout.php';
?>
