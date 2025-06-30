<?php
require_once __DIR__ . '/../verificar_sessao.php';
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
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?php echo $base_url; ?>assets/js/style.js"></script>
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
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
        }

        /* Loading Overlay Styles */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.5s ease-out;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .loading-text {
            position: absolute;
            margin-top: 80px;
            color: var(--dark);
            font-size: 1.2rem;
            font-weight: 500;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .fade-out {
            opacity: 0;
            pointer-events: none;
        }

        body {
            background-color: var(--secondary);
            font-family: 'Poppins', sans-serif;
        }

        .sidebar {
            width: 300px;
            background: linear-gradient(180deg, var(--dark) 0%, #1a0f0a 100%);
            color: var(--light);
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            z-index: 1000;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
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
            overflow-y: auto;
            padding-right: 10px;
        }

        .sidebar-nav::-webkit-scrollbar {
            width: 5px;
        }

        .sidebar-nav::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }

        .sidebar-nav::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 10px;
        }

        .sidebar-footer {
            margin-top: auto;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            list-style: none;
        }

        .sidebar-header .logo {
            height: 40px;
            transition: transform 0.3s ease;
        }

        .sidebar-header .logo:hover {
            transform: scale(1.05);
        }

        .sidebar-header .greeting {
            color: var(--primary-light);
            font-size: 1.1rem;
            font-weight: 500;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .sidebar .nav-link {
            font-size: 1.1rem;
            padding: 12px 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
            color: var(--light);
            margin-bottom: 0.5rem;
            position: relative;
            overflow: hidden;
        }

        .sidebar .nav-link::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 0;
            background: var(--primary);
            transition: width 0.3s ease;
            z-index: -1;
        }

        .sidebar .nav-link:hover::before {
            width: 100%;
        }

        .sidebar .nav-link:hover {
            color: var(--light);
            transform: translateX(5px);
        }

        .sidebar .nav-link i {
            font-size: 1.3rem;
            margin-right: 10px;
            color: var(--primary-light);
            transition: transform 0.3s ease;
        }

        .sidebar .nav-link:hover i {
            transform: scale(1.1);
        }

        .sidebar .nav-link.active {
            background: var(--primary) !important;
            color: var(--primary-light) !important;
            font-weight: 600 !important;
        }

        .sidebar .logout {
            color: var(--primary-light);
            margin-top: auto;
            transition: all 0.3s ease;
        }

        .sidebar .logout:hover {
            background: rgba(255, 0, 0, 0.1);
            color: #ff4444;
            transform: translateX(5px);
        }

        .main {
            margin-left: 300px;
            background: var(--secondary);
            min-height: 100vh;
            padding: 2rem;
        }

        .main h1 {
            color: var(--dark);
            font-weight: 600;
            margin-bottom: 2rem;
            position: relative;
            padding-bottom: 0.5rem;
        }

        .main h1::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 3px;
            background: var(--primary);
            border-radius: 3px;
        }

        /* Estilos para cards */
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        /* Estilos para botões */
        .btn {
            border-radius: 8px;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--primary);
            border: none;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(163, 106, 7, 0.3);
        }

        /* Estilos para tabelas */
        .table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
        }

        .table thead th {
            background: var(--dark);
            color: var(--light);
            font-weight: 500;
            border: none;
            padding: 1rem;
        }

        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
        }

        /* Estilos para modais */
        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            background: var(--dark);
            color: var(--light);
            border-radius: 15px 15px 0 0;
            border: none;
        }

        .modal-footer {
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }

        /* Ajuste do backdrop do modal */
        .modal-backdrop {
            background-color: rgba(0, 0, 0, 0.5) !important;
            z-index: 1050 !important;
        }

        .modal {
            z-index: 1060 !important;
        }

        /* Estilos para toasts */
        .toast {
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        /* Animações */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .sidebar {
                width: 250px;
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main {
                margin-left: 0;
            }

            .main.active {
                margin-left: 250px;
            }
        }

        /* Estilos para botões de ação */
        .btn-group .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .btn-group .btn i {
            font-size: 1rem;
            transition: transform 0.3s ease;
        }

        .btn-group .btn:hover i {
            transform: scale(1.1);
        }

        .btn-warning {
            background: linear-gradient(45deg, #ffc107, #ffb300);
            border: none;
            color: #000;
        }

        .btn-warning:hover {
            background: linear-gradient(45deg, #ffb300, #ffa000);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 193, 7, 0.3);
        }

        .btn-danger {
            background: linear-gradient(45deg, #dc3545, #c82333);
            border: none;
        }

        .btn-danger:hover {
            background: linear-gradient(45deg, #c82333, #bd2130);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }

        /* Animação para botões */
        .btn {
            position: relative;
            overflow: hidden;
        }

        .btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.3s ease, height 0.3s ease;
        }

        .btn:hover::after {
            width: 200%;
            height: 200%;
        }

        /* Estilo para botões em tabelas */
        .table .btn-group {
            display: flex;
            gap: 0.5rem;
        }

        .table .btn {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 0.375rem;
        }

        /* Efeito de hover para linhas da tabela */
        .table tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }

        .table tbody tr:hover .btn {
            opacity: 1;
        }

        .table tbody tr .btn {
            opacity: 0.8;
            transition: opacity 0.3s ease;
        }

        /* Força as setas de ordenação das colunas (Simple DataTables) a ficarem brancas em todas as tabelas */
        th[data-sortable="true"] .datatable-sorter::before,
        th[data-sortable="true"] .datatable-sorter::after,
        th.datatable-ascending .datatable-sorter::before,
        th.datatable-descending .datatable-sorter::after,
        a.datatable-sorter::before,
        a.datatable-sorter::after {
            color: #fff !important;
            opacity: 1 !important;
        }
    </style>
</head>
<body class="d-flex vh-100">
    <!-- Loading Overlay -->
    <div id="toast-global-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="<?php echo $base_url; ?>../goncalo_pap/site_pap/images/logo.svg" alt="LotusSPA Logo" class="logo">
            <span class="greeting">Olá, <?php echo isset($_SESSION['utilizador_nome']) ? htmlspecialchars($_SESSION['utilizador_nome']) : 'Usuário'; ?></span>
        </div>
        <ul class="nav flex-column sidebar-nav">
            <li class="nav-item">
                <a href="<?php echo $base_url; ?>index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                    <i class="bi bi-house-door"></i>Início
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo $base_url; ?>cliente/clientes.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'cliente') !== false ? 'active' : ''; ?>">
                    <i class="bi bi-people-fill"></i>Clientes
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo $base_url; ?>reservas/reservas.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'reservas') !== false ? 'active' : ''; ?>">
                    <i class="bi bi-calendar3"></i>Reservas
                </a>
            </li>
            <?php if ($_SESSION['utilizador_tipo'] == 'admin') { ?>
            <li class="nav-item">
                <a href="<?php echo $base_url; ?>funcionario/funcionario.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'funcionario.php') !== false ? 'active' : ''; ?>">
                    <i class="bi bi-person-badge"></i>Funcionários
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo $base_url; ?>funcionario/funcionario_servicos.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'funcionario_servicos.php') !== false ? 'active' : ''; ?>">
                    <i class="bi bi-person-gear"></i>Serviços dos Funcionários
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo $base_url; ?>servico/servico.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'servico.php') !== false ? 'active' : ''; ?>">
                    <i class="bi bi-tools"></i>Serviços
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo $base_url; ?>horarios/horarios.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'horarios.php') !== false ? 'active' : ''; ?>">
                    <i class="bi bi-calendar-check"></i>Horários
                </a>
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
        <h1 class="animate__animated animate__fadeIn"><?php echo isset($title) ? $title : ''; ?></h1>
        <div class="animate__animated animate__fadeIn">
            <?php echo isset($content) ? $content : ''; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Adiciona classe active ao link atual
        document.addEventListener('DOMContentLoaded', function() {
            const currentPath = window.location.pathname;
            const navLinks = document.querySelectorAll('.nav-link');
            
            navLinks.forEach(link => {
                if (currentPath.includes(link.getAttribute('href'))) {
                    link.classList.add('active');
                }
            });

            // Adiciona animação aos cards
            const cards = document.querySelectorAll('.card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                card.classList.add('animate__animated', 'animate__fadeIn');
            });
        });
    </script>

    <!-- Script para remover o backdrop do modal -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Quando um modal é mostrado
        document.body.addEventListener('shown.bs.modal', function (event) {
            // Encontra O backdrop (Bootstrap ou outro script pode criá-lo)
            const backdrop = document.querySelector('.modal-backdrop');
            
            // Se encontrar um backdrop, remove-o
            if (backdrop) {
                backdrop.remove();
                // console.log('Modal backdrop removed.'); // Opcional para debug
            }
        });
    });
    </script>
</body>
</html>