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
$title = 'Editar Funcionário'; 

if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    $query = "SELECT * FROM funcionario WHERE id = '$id'";
    $result = mysqli_query($conn, $query);
    $funcionario = mysqli_fetch_assoc($result);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $conn->real_escape_string($_POST['id']);
    $nome = $conn->real_escape_string($_POST['nome']);
    $email = $conn->real_escape_string($_POST['email']); 
    $morada = $conn->real_escape_string($_POST['morada']);
    $localidade = $conn->real_escape_string($_POST['localidade']);
    $telefone1 = $conn->real_escape_string($_POST['telefone1']);
    $telefone2 = $conn->real_escape_string($_POST['telefone2']);
    $cargo = $conn->real_escape_string($_POST['cargo']);

    // Check for duplicates
    $query = "SELECT * FROM funcionario WHERE (email = '$email' OR telefone1 = '$telefone1') AND id != '$id'";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $_SESSION['error'] = 'O email ou telefone já estão registados.';
    } else {
        $query = "UPDATE funcionario SET nome = '$nome', email = '$email', morada = '$morada', localidade = '$localidade', telefone1 = '$telefone1', telefone2 = '$telefone2', cargo = '$cargo' WHERE id = '$id'";
        
        if($conn->query($query)) {
            $_SESSION['success'] = 'Funcionário atualizado com sucesso!';
            header('Location: funcionario.php');
            exit();
        } else {
            $erro = "Erro ao atualizar: " . $conn->error;
        }
    }
}

// Start output buffering
ob_start();
?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="funcionario.php" class="btn btn-secondary">Voltar</a>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="id" value="<?php echo $funcionario['id']; ?>">
                
                <div class="mb-3">
                    <label class="form-label">Nome</label>
                    <input type="text" name="nome" class="form-control" value="<?php echo $funcionario['nome']; ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?php echo $funcionario['email']; ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Morada</label>
                    <input type="text" name="morada" class="form-control" value="<?php echo $funcionario['morada']; ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Localidade</label>
                    <input type="text" name="localidade" class="form-control" value="<?php echo $funcionario['localidade']; ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Telefone 1</label>
                    <input type="text" name="telefone1" class="form-control" value="<?php echo $funcionario['telefone1']; ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Telefone 2</label>
                    <input type="text" name="telefone2" class="form-control" value="<?php echo $funcionario['telefone2']; ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Cargo</label>
                    <input type="text" name="cargo" class="form-control" value="<?php echo $funcionario['cargo']; ?>" required>
                </div>

                <div class="text-end">
                    <a href="funcionario.php" class="btn btn-secondary me-2">Cancelar</a>
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
