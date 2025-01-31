<?php
require_once '../includes/db_conexao.php';
include '../includes/sidebar.php';

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

    $query = "UPDATE cliente SET nome = '$nome', email = '$email', telefone = '$telefone' WHERE id = '$id'";
    
    if(mysqli_query($conn, $query)) {
        header('Location: clientes.php');
        exit();
    } else {
        $erro = "Erro ao atualizar: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Editar Cliente</title>
    <!-- Add Bootstrap CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">Editar Cliente</h1>
            <a href="listar_clientes.php" class="btn btn-secondary">Voltar</a>
        </div>

        <?php if (isset($erro)): ?>
            <div class="alert alert-danger"><?php echo $erro; ?></div>
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
                        <a href="listar_clientes.php" class="btn btn-secondary me-2">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Bootstrap JS and its dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
