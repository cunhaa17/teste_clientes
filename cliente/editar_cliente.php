<?php
session_start(); // Ensure session is started
require_once '../includes/db_conexao.php';
$title = 'Editar Cliente'; 

// Simplified connection check
if (!$conn) {
    die("Erro: Conexão com o banco de dados falhou");
}

if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    $query = "SELECT * FROM cliente WHERE id = '$id'";
    $result = mysqli_query($conn, $query);
    $cliente = mysqli_fetch_assoc($result);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $nome = mysqli_real_escape_string($conn, $_POST['nome']);
    $email = mysqli_real_escape_string($conn, $_POST['email']); 
    $telefone = mysqli_real_escape_string($conn, $_POST['telefone']);

    // Check for duplicates
    $query = "SELECT * FROM cliente WHERE (email = ? OR telefone = ?) AND id != ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssi", $email, $telefone, $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['error'] = 'O email ou telefone já estão registados.';
    } else {
        $query = "UPDATE cliente SET nome = '$nome', email = '$email', telefone = '$telefone' WHERE id = '$id'";
        
        if(mysqli_query($conn, $query)) {
            $_SESSION['success'] = 'Cliente atualizado com sucesso!';
            header('Location: clientes.php');
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
        <h1 class="fs-3 mb-3"><?php echo isset($title) ? $title : 'Cliente'; ?></h1>
        <a href="clientes.php" class="btn btn-secondary">Voltar</a>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="id" value="<?php echo $cliente['id']; ?>">
                
                <div class="mb-3">
                    <label class="form-label">Nome</label>
                    <input type="text" name="nome" class="form-control" value="<?php echo $cliente['nome']; ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?php echo $cliente['email']; ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Telefone</label>
                    <input type="text" name="telefone" class="form-control" value="<?php echo $cliente['telefone']; ?>" required>
                </div>

                <div class="text-end">
                    <a href="clientes.php" class="btn btn-secondary me-2">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean(); // Capture the output and store it in $content

// Include the layout file
include '../includes/layout.php';
?>
