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
    $nome = trim($_POST['nome'] ?? '');

    if ($nome !== '') {
        $stmt = $conn->prepare("INSERT INTO servico (nome) VALUES (?)");
        $stmt->bind_param("s", $nome);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Serviço adicionado com sucesso!";
            header("Location: servico.php");
            exit();
        } else {
            $error = "Erro ao adicionar serviço.";
        }
        $stmt->close();
    } else {
        $error = "O nome do serviço é obrigatório.";
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
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Sucesso:</strong> ' . htmlspecialchars($_SESSION['success']) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
        unset($_SESSION['success']);
    }
    ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label for="nome" class="form-label">Nome do Serviço</label>
            <input type="text" class="form-control" id="nome" name="nome" required>
        </div>
        <button type="submit" class="btn btn-success">Adicionar</button>
        <a href="servico.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
<?php
$content = ob_get_clean(); // Capture the output and store it in $content

// Include the layout file
include '../includes/layout.php';
?>
