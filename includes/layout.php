<?php
$base_url = "/dashboard_pap/"; // Substitua pelo nome correto da pasta do projeto
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? $title : 'Dashboard'; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .sidebar {
            width: 300px; /* Aumentar a largura da sidebar */
            background: #000; /* Cor preta */
            color: white;
        }
        .sidebar .nav-link {
            font-size: 1.2rem;
            padding: 10px 15px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .sidebar .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        .sidebar .nav-link i {
            font-size: 1.5rem;
        }
    </style>
</head>
<body class="d-flex vh-100">
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <!-- Aqui os toasts serão adicionados dinamicamente -->
</div>
    <div class="sidebar p-4 overflow-y-auto">
        <h2 class="fs-4 mb-3">Sidebar</h2>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a href="<?php echo $base_url; ?>index.php" class="nav-link text-white">
                <i class="bi bi-house-door me-2"></i>Início</a>
            </li>
            <li class="nav-item">
                <a href="<?php echo $base_url; ?>cliente/clientes.php" class="nav-link text-white">
                <i class="bi bi-people-fill me-2"></i>Clientes</a>
            </li>
            <li class="nav-item">
                <a href="<?php echo $base_url; ?>funcionario/funcionario.php" class="nav-link text-white">
                <i class="bi bi-person-badge me-2"></i>Funcionarios</a>
            </li>
            <li class="nav-item">
                <a href="<?php echo $base_url; ?>servico/servico.php" class="nav-link text-white">
                <i class="bi bi-tools me-2"></i>Servico</a>
            </li>
            <li class="nav-item">
                <a href="<?php echo $base_url; ?>horarios/horarios.php" class="nav-link text-white">
                <i class="bi bi-calendar-check me-2"></i>Horarios</a>
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
