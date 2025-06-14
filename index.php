<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['utilizador_id'])) {
    header("Location: login.php");
    exit();
}

$title = "Início";
include_once 'includes/db_conexao.php';

// Consultas ao banco de dados
$query_clientes = "SELECT COUNT(*) AS total FROM cliente";
$query_servicos = "SELECT COUNT(*) AS total FROM servico";
$query_reservas = "SELECT cliente.nome AS cliente, servico.nome AS servico, reserva.data_reserva AS data, reserva.status 
                   FROM reserva 
                   JOIN cliente ON reserva.cliente_id = cliente.id 
                   JOIN servico_subtipo ON reserva.servico_subtipo_id = servico_subtipo.id
                   JOIN servico ON servico_subtipo.servico_id = servico.id 
                   ORDER BY reserva.data_reserva ASC LIMIT 5";
$query_funcionarios_online = "SELECT nome FROM funcionario WHERE id IN (SELECT f_id FROM reserva_funcionario)";
$query_faturamento = "SELECT MONTH(data_reserva) AS mes, SUM(servico_subtipo.preco) AS total FROM reserva 
                      JOIN servico_subtipo ON reserva.servico_subtipo_id = servico_subtipo.id 
                      GROUP BY MONTH(data_reserva)";
$query_ocupacao = "SELECT 
IFNULL((COUNT(*) / NULLIF((SELECT COUNT(*) FROM reserva), 0)) * 100, 0) AS taxa 
FROM reserva";

// Query para estimativa mensal
$query_estimativa_mensal = "SELECT SUM(servico_subtipo.preco) as total_mensal 
                           FROM reserva 
                           JOIN servico_subtipo ON reserva.servico_subtipo_id = servico_subtipo.id 
                           WHERE MONTH(data_reserva) = MONTH(CURRENT_DATE()) 
                           AND YEAR(data_reserva) = YEAR(CURRENT_DATE())";

// Consultando status das reservas
$query_status_reservas = "SELECT 
    MONTH(reserva.data_reserva) AS mes, 
    reserva.status, 
    COUNT(*) AS total 
FROM reserva 
GROUP BY MONTH(reserva.data_reserva), reserva.status";

$result_clientes = mysqli_query($conn, $query_clientes);
$result_servicos = mysqli_query($conn, $query_servicos);
$result_reservas = mysqli_query($conn, $query_reservas);
$result_funcionarios = mysqli_query($conn, $query_funcionarios_online);
$result_faturamento = mysqli_query($conn, $query_faturamento);
$result_ocupacao = mysqli_query($conn, $query_ocupacao);
$result_estimativa_mensal = mysqli_query($conn, $query_estimativa_mensal);
$result_status_reservas = mysqli_query($conn, $query_status_reservas); // Executa a consulta de status

$total_clientes = mysqli_fetch_assoc($result_clientes)['total'];
$total_servicos = mysqli_fetch_assoc($result_servicos)['total'];
$taxa_ocupacao = mysqli_fetch_assoc($result_ocupacao)['taxa'];
$estimativa_mensal = mysqli_fetch_assoc($result_estimativa_mensal)['total_mensal'] ?? 0;


ob_start();
?>

<head>
    <!-- CSS do Datepicker -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- jQuery (necessário para o Datepicker) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- JS do Datepicker -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .card-title {
            font-weight: 500;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }
        
        .card-text {
            font-weight: 600;
            font-size: 2rem !important;
        }
        
        .bg-primary {
            background: linear-gradient(45deg, #4e73df, #224abe) !important;
        }
        
        .bg-success {
            background: linear-gradient(45deg, #1cc88a, #13855c) !important;
        }
        
        .table {
            background: white;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
        }
        
        .table thead th {
            border-top: none;
            background: #f8f9fa;
            font-weight: 600;
            padding: 1rem;
        }
        
        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
        }
        
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0,0,0,.02);
        }
        
        .table tbody tr:hover {
            background-color: rgba(0,0,0,.04);
            transition: background-color 0.2s ease;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-pendente { background-color: #ffeeba; color: #856404; }
        .status-confirmada { background-color: #d4edda; color: #155724; }
        .status-cancelada { background-color: #f8d7da; color: #721c24; }
        
        .icon-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }
        
        .modal-content {
            border-radius: 15px;
            border: none;
        }
        
        .modal-header {
            border-bottom: 1px solid rgba(0,0,0,.1);
            background: #f8f9fa;
            border-radius: 15px 15px 0 0;
        }
        
        .btn {
            border-radius: 50px;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #4e73df, #224abe);
            border: none;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(78,115,223,0.3);
        }
    </style>
</head>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<div class="container py-4">
    <div class="row">
        <!-- Card Clientes -->
        <div class="col-md-3">
            <div class="card bg-primary text-white shadow animate__animated animate__fadeIn">
                <div class="card-body">
                    <div class="icon-circle">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                    <h5 class="card-title">Total de Clientes</h5>
                    <p class="card-text fs-2"><?php echo $total_clientes; ?></p>
                </div>
            </div>
        </div>

        <?php if ($_SESSION['utilizador_tipo'] == 'admin') { ?>
        <!-- Card Serviços Ativos -->
        <div class="col-md-3">
            <div class="card bg-success text-white shadow animate__animated animate__fadeIn" style="animation-delay: 0.2s">
                <div class="card-body">
                    <div class="icon-circle">
                        <i class="fas fa-concierge-bell fa-2x"></i>
                    </div>
                    <h5 class="card-title">Serviços Ativos</h5>
                    <p class="card-text fs-2"><?php echo $total_servicos; ?></p>
                </div>
            </div>
        </div>

        <!-- Card Estimativa Mensal -->
        <div class="col-md-3">
            <div class="card bg-info text-white shadow animate__animated animate__fadeIn" style="animation-delay: 0.4s">
                <div class="card-body">
                    <div class="icon-circle">
                        <i class="fas fa-chart-line fa-2x"></i>
                    </div>
                    <h5 class="card-title">Estimativa Mensal</h5>
                    <p class="card-text fs-2"><?php echo number_format($estimativa_mensal, 2, ',', '.'); ?> MZN</p>
                </div>
            </div>
        </div>
        <?php } ?>
    </div>

    <!-- Gráfico e Reservas na mesma linha -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">Próximas Reservas</h4>
                <button class="btn btn-primary" id="refreshReservas">
                    <i class="fas fa-sync-alt mr-2"></i>Atualizar
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th><i class="fas fa-user mr-2"></i>Cliente</th>
                            <th><i class="fas fa-concierge-bell mr-2"></i>Serviço</th>
                            <th id="dateHeader" style="cursor: pointer;">
                                <i class="fas fa-calendar-alt mr-2"></i>Data
                                <i class="fas fa-search ml-2" title="Filtrar por Data"></i>
                            </th>
                            <th><i class="fas fa-info-circle mr-2"></i>Status</th>
                        </tr>
                    </thead>
                    <tbody id="reservasTableBody">
                        <?php while ($reserva = mysqli_fetch_assoc($result_reservas)) { ?>
                            <tr class="animate__animated animate__fadeIn">
                                <td><?php echo $reserva['cliente']; ?></td>
                                <td><?php echo $reserva['servico']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($reserva['data'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($reserva['status']); ?>">
                                        <?php echo ucfirst($reserva['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal para seleção de data -->
<div class="modal fade" id="datePickerModal" tabindex="-1" aria-labelledby="datePickerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="datePickerModalLabel">
                    <i class="fas fa-calendar-alt mr-2"></i>Selecionar Data
                </h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Fechar">&times;</button>
            </div>
            <div class="modal-body">
                <label for="datepicker">Escolha uma data:</label>
                <input type="text" id="datepicker" class="form-control">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" id="applyDate" class="btn btn-primary">
                    <i class="fas fa-check mr-2"></i>Aplicar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#datepicker').datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,
            todayHighlight: true
        });

        $('#dateHeader').on('click', function() {
            $('#datePickerModal').modal('show');
        });

        $('#applyDate').on('click', function() {
            const selectedDate = $('#datepicker').val();
            const rows = $('#reservasTableBody tr');

            rows.each(function() {
                const dateCell = $(this).find('td:nth-child(3)').text().trim();
                $(this).toggle(dateCell === selectedDate);
            });

            $('#datePickerModal').modal('hide');
        });

        // Adiciona efeito de hover nos cards
        $('.card').hover(
            function() { $(this).addClass('animate__animated animate__pulse'); },
            function() { $(this).removeClass('animate__animated animate__pulse'); }
        );

        // Adiciona efeito de loading ao atualizar
        $('#refreshReservas').on('click', function() {
            const $btn = $(this);
            $btn.prop('disabled', true);
            $btn.html('<i class="fas fa-spinner fa-spin mr-2"></i>Atualizando...');
            
            setTimeout(function() {
                location.reload();
            }, 1000);
        });
    });
</script>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>