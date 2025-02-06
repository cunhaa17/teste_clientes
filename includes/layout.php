<?php
$base_url = "../"; // Replace with your actual domain
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? $title : 'Dashboard'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex vh-100">
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <!-- Aqui os toasts serão adicionados dinamicamente -->
</div>
    <div class="sidebar bg-dark text-white p-4 overflow-y-auto" style="width: 250px;">
        <h2 class="fs-4 mb-3">Sidebar</h2>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a href="<?php echo $base_url; ?>" class="nav-link text-white">Início</a>
            </li>
            <li class="nav-item">
                <a href="<?php echo $base_url; ?>cliente/clientes.php" class="nav-link text-white">Clientes</a>
            </li>
            <li class="nav-item">
                <a href="<?php echo $base_url; ?>funcionario/funcionario.php" class="nav-link text-white">Funcionarios</a>
            </li>
        </ul>
    </div>
    <div class="main d-flex flex-column flex-grow-1 p-5">
        <?php echo isset($content) ? $content : ''; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Toast de Notificação -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1050;">
    <div id="deleteToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                Cliente eliminado com sucesso!
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Fechar"></button>
        </div>
    </div>
</div>


</body>
</html>     