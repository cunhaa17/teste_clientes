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
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $telefone = trim($_POST['telefone']);

    if (empty($nome) || empty($email) || empty($telefone)) {
        $_SESSION['error'] = "Todos os campos são obrigatórios.";
        header("Location: adicionar_cliente.php");
        exit();
    }

    // Escapa os valores para evitar SQL injection
    $email = $conn->real_escape_string($email);
    $telefone = $conn->real_escape_string($telefone);

    // Monta a query diretamente com os valores escapados
    $query = "SELECT * FROM cliente WHERE email = '$email' OR telefone = '$telefone'";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $_SESSION['error'] = 'O email ou telefone já estão registados.';
        header('Location: adicionar_cliente.php');
        exit();
    }

    // Insere o cliente na base de dados
    $nome = $conn->real_escape_string($nome);
    $sql = "INSERT INTO cliente (nome, email, telefone) VALUES ('$nome', '$email', '$telefone')";
    
    if ($conn->query($sql)) {
        $_SESSION['success'] = "Cliente adicionado com sucesso!";
    } else {
        $_SESSION['error'] = "Erro ao adicionar cliente: " . $conn->error;
    }

    $conn->close();

    header("Location: clientes.php");
    exit();
}

$title = 'Adicionar Cliente';

// Start output buffering
ob_start();
?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="clientes.php" class="btn btn-secondary">Voltar</a>
    </div>

    <?php 
    if (isset($_SESSION['error'])) {
        echo '
        <div class="toast-container position-fixed bottom-0 end-0 p-3">
            <div id="errorToast" class="toast align-items-center text-white border-0" role="alert" aria-live="assertive" aria-atomic="true" style="background: linear-gradient(45deg, #dc3545, #c82333); border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                <div class="d-flex align-items-center p-3">
                    <div class="toast-icon me-3">
                        <i class="bi bi-exclamation-circle-fill fs-4"></i>
                    </div>
                    <div class="toast-body fs-5">
                        ' . htmlspecialchars($_SESSION['error']) . '
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                var toastEl = document.getElementById("errorToast");
                var toast = new bootstrap.Toast(toastEl, {
                    animation: true,
                    autohide: true,
                    delay: 3000
                });
                toast.show();
            });
        </script>';
        unset($_SESSION['error']);
    }

    if (isset($_SESSION['success'])) {
        echo '
        <div class="toast-container position-fixed bottom-0 end-0 p-3">
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
            </div>
        </div>

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                var toastEl = document.getElementById("successToast");
                var toast = new bootstrap.Toast(toastEl, {
                    animation: true,
                    autohide: true,
                    delay: 3000
                });
                toast.show();
            });
        </script>';
        unset($_SESSION['success']);
    }
    ?>

    <div class="card">
        <div class="card-body">
            <form action="adicionar_cliente.php" method="POST">
                <div class="mb-3">
                    <label class="form-label">Nome:</label>
                    <input type="text" name="nome" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email:</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Telefone:</label>
                    <input type="tel" name="telefone" class="form-control" pattern="[0-9]{9}" required>
                </div>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </form>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean(); // Capture the output and store it in $content

// Include the layout file
include '../includes/layout.php';
?>