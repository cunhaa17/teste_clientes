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

    // Verifica se o email ou telefone já existem no banco de dados
    $query = "SELECT * FROM cliente WHERE email = ? OR telefone = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $email, $telefone);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['error'] = 'O email ou telefone já estão registados.';
        header('Location: adicionar_cliente.php');
        exit();
    }

    // Insere o cliente na base de dados
    $sql = "INSERT INTO cliente (nome, email, telefone) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $nome, $email, $telefone);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Cliente adicionado com sucesso!";
    } else {
        $_SESSION['error'] = "Erro ao adicionar cliente: " . $stmt->error;
    }

    $stmt->close();
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
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Erro:</strong> ' . htmlspecialchars($_SESSION['error']) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
        unset($_SESSION['error']);
    }

    if (isset($_SESSION['success'])) {
        echo '
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Sucesso:</strong> ' . htmlspecialchars($_SESSION['success']) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
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