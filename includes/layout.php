<?php
$base_url = "/PAP/dashboard_pap/"; 
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
        :root {
            --primary: #A36A07;
            --primary-dark: #805306;
            --primary-light: #FFB52E;
            --secondary: #FFF8EC;
            --dark: #2C1810;
            --light: #FFF8EC;
            --accent: #D4973D;
            --tertiary: #62816C;
            --quaternary: #8BA893;
        }

        .sidebar {
            width: 300px;
            background: var(--dark);
            color: var(--light);
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            z-index: 1000;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-nav {
            flex-grow: 1;
        }

        .sidebar-footer {
            margin-top: auto;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            list-style: none;
        }

        .sidebar-footer .nav-item {
            list-style: none;
        }

        .sidebar-header .logo {
            height: 40px;
        }

        .sidebar-header .greeting {
            color: var(--primary-light);
            font-size: 1.1rem;
            font-weight: 500;
        }

        .sidebar .nav-link {
            font-size: 1.1rem;
            padding: 12px 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
            color: var(--light);
            margin-bottom: 0.5rem;
        }

        .sidebar .nav-link:hover {
            background: var(--primary);
            color: var(--light);
            transform: translateX(5px);
        }

        .sidebar .nav-link i {
            font-size: 1.3rem;
            margin-right: 10px;
            color: var(--primary-light);
        }

        .sidebar .nav-link.active {
            background: var(--primary);
            color: var(--light);
        }

        .sidebar .logout {
            color: var(--primary-light);
            margin-top: auto;
        }

        .sidebar .logout:hover {
            background: rgba(255, 0, 0, 0.1);
            color: #ff4444;
        }

        .sidebar .logout i {
            color: inherit;
        }

        .main {
            margin-left: 300px;
            background: var(--secondary);
        }
    </style>
</head>
<body class="d-flex vh-100">
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <!-- Aqui os toasts serão adicionados dinamicamente -->
</div>
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="<?php echo $base_url; ?>../goncalo_pap/site_pap/images/logo.svg" alt="LotusSPA Logo" class="logo">
            <span class="greeting">Olá, <?php echo isset($_SESSION['utilizador_nome']) ? htmlspecialchars($_SESSION['utilizador_nome']) : 'Usuário'; ?></span>
        </div>
        <ul class="nav flex-column sidebar-nav">
            <li class="nav-item">
                <a href="<?php echo $base_url; ?>index.php" class="nav-link">
                <i class="bi bi-house-door"></i>Início</a>
            </li>
            <li class="nav-item">
                <a href="<?php echo $base_url; ?>cliente/clientes.php" class="nav-link">
                <i class="bi bi-people-fill"></i>Clientes</a>
            </li>
            <li class="nav-item">
                <a href="<?php echo $base_url; ?>reservas/reservas.php" class="nav-link">
                <i class="bi bi-calendar3"></i>Reservas</a>
            </li>
            <?php if ($_SESSION['utilizador_tipo'] == 'admin') { ?>
            <li class="nav-item">
                <a href="<?php echo $base_url; ?>funcionario/funcionario.php" class="nav-link">
                <i class="bi bi-person-badge"></i>Funcionarios</a>
            </li>
            <li class="nav-item">
                <a href="<?php echo $base_url; ?>funcionario/funcionario_servicos.php" class="nav-link">
                <i class="bi bi-person-gear"></i>Serviços dos Funcionários</a>
            </li>
            <li class="nav-item">
                <a href="<?php echo $base_url; ?>servico/servico.php" class="nav-link">
                <i class="bi bi-tools"></i>Servico</a>
            </li>
            <li class="nav-item">
                <a href="<?php echo $base_url; ?>horarios/horarios.php" class="nav-link">
                <i class="bi bi-calendar-check"></i>Horarios</a>
            </li>
            <?php } ?>
        </ul>
        <div class="sidebar-footer">
            <li class="nav-item">
                <a href="<?php echo $base_url; ?>logout.php" class="nav-link logout">
                    <i class="bi bi-box-arrow-right"></i>Logout
                </a>
            </li>
            <p class="text-center text-muted small mt-3" style="color: var(--primary-light) !important;">
                © 2024 LotusSPA. Todos os direitos reservados.
            </p>
        </div>
    </div>
    <div class="main d-flex flex-column flex-grow-1 p-5">
        <h1><?php echo isset($title) ? $title : ''; ?></h1>
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