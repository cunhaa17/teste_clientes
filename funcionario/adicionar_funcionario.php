<?php
session_start();
// Verifica se a sessão está iniciada corretamente
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
    // Process the form data and add the employee to the database
    // ...

    $_SESSION['success'] = "Funcionário adicionado com sucesso!";
    header("Location: funcionario.php");
    exit();
}    

$title = 'Adicionar Funcionário';

// Start output buffering
ob_start();
?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="funcionario.php" class="btn btn-secondary">Voltar</a>
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
            <form action="guardar_funcionario.php" method="POST">
                <div class="mb-3">
                    <label class="form-label">Nome:</label>
                    <input type="text" name="nome" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email:</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Morada:</label>
                    <input type="text" name="morada" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Localidade:</label>
                    <input type="text" name="localidade" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Telefone 1:</label>
                    <input type="tel" name="telefone1" class="form-control" pattern="[0-9]{9}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Telefone 2:</label>
                    <input type="tel" name="telefone2" class="form-control" pattern="[0-9]{9}">
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
