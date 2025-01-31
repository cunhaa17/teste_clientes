<?php
    $base_url = "/teste_dashboard/"; // Ajuste conforme necessário
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
    <!-- Estrutura principal da dashboard -->
    <div class="dashboard">
        <!-- Barra lateral da aplicação -->
        <div id="sidebar" class="sidebar bg-dark text-white p-3">
    <h4 class="text-center">Meu Site</h4>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a href="<?php echo $base_url; ?>" class="nav-link text-white">
                <i class="bi bi-house-door"></i> Início
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo $base_url; ?>" class="nav-link text-white">
                <i class="bi bi-person"></i> Clientes
            </a>
        </li>
        <li class="nav-item">
            <a href="<?php echo $base_url; ?>" class="nav-link text-white">
                <i class="bi bi-gear"></i> Configurações
            </a>
        </li>
    </ul>
</div>
