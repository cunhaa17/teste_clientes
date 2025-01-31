<?php include '../includes/sidebar.php' ?>
<!DOCTYPE html>
<html>
<head>
    <title>Adicionar Cliente</title>
    <link rel="stylesheet" href="../assets/css/style.css"> <!-- Inclui o ficheiro CSS para estilos -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"> <!-- Ícones do Material Icons -->
</head>
<body>

    <!-- Mensagens de erro ou sucesso -->
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <?php 
                session_start();
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
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="container">
                    <div class="card">
                        <!-- Cabeçalho do cartão -->
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">Adicionar Cliente</h4>
                        </div>
                        <div class="card-body">
                            <!-- Formulário para adicionar cliente -->
                            <form action="guardar_cliente.php" method="POST">
                                <div class="mb-3">
                                    <label>Nome:</label>
                                    <input type="text" name="nome" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label>Email:</label>
                                    <input type="email" name="email" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label>Telefone:</label>
                                    <input type="tel" name="telefone" class="form-control" pattern="[0-9]{9}" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Guardar</button>
                                <a href="clientes.php" class="btn btn-secondary">Voltar</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Script do Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
