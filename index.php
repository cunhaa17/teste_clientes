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
$result_status_reservas = mysqli_query($conn, $query_status_reservas); // Executa a consulta de status

$total_clientes = mysqli_fetch_assoc($result_clientes)['total'];
$total_servicos = mysqli_fetch_assoc($result_servicos)['total'];
$taxa_ocupacao = mysqli_fetch_assoc($result_ocupacao)['taxa'];

// Dados do gráfico de faturamento
$meses = [];
$valores = [];
while ($row = mysqli_fetch_assoc($result_faturamento)) {
    $meses[] = $row['mes'];
    $valores[] = $row['total'];
}

// Processamento dos dados do gráfico de status das reservas
$meses_labels = range(1, 12); // Janeiro a Dezembro
$dados_confirmadas = array_fill(0, 12, 0);
$dados_pendentes = array_fill(0, 12, 0);
$dados_concluidas = array_fill(0, 12, 0);
$dados_canceladas = array_fill(0, 12, 0);

while ($row = mysqli_fetch_assoc($result_status_reservas)) {
    $mes = $row['mes'] - 1; // Ajuste do índice para array (0-based)
    $status = strtolower($row['status']);
    $total = $row['total'];
    
    switch ($status) {
        case 'confirmada':
            $dados_confirmadas[$mes] = $total;
            break;
        case 'pendente':
            $dados_pendentes[$mes] = $total;
            break;
        case 'concluida':
            $dados_concluidas[$mes] = $total;
            break;
        case 'cancelada':
            $dados_canceladas[$mes] = $total;
            break;
    }
}

ob_start();
?>

<head>
    <!-- CSS do Datepicker -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">

    <!-- jQuery (necessário para o Datepicker) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- JS do Datepicker -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</head>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<div class="container py-4">
    <div class="row">
        <!-- Card Clientes -->
        <div class="col-md-3">
            <div class="card bg-primary text-white shadow">
                <div class="card-body">
                    <h5 class="card-title">Total de Clientes</h5>
                    <p class="card-text fs-2"><?php echo $total_clientes; ?></p>
                </div>
            </div>
        </div>

        <?php if ($_SESSION['utilizador_tipo'] == 'admin') { ?>
        <!-- Card Serviços Ativos -->
        <div class="col-md-3">
            <div class="card bg-success text-white shadow">
                <div class="card-body">
                    <h5 class="card-title">Serviços Ativos</h5>
                    <p class="card-text fs-2"><?php echo $total_servicos; ?></p>
                </div>
            </div>
        </div>
        <?php } ?>
    </div>

    <!-- Gráfico e Reservas na mesma linha -->
    <div class="row mt-4">
        <div class="col-md-12">
            <h4>Próximas Reservas</h4>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Serviço</th>
                            <th id="dateHeader" style="cursor: pointer;">
                                Data <i class="fas fa-search" title="Filtrar por Data"></i>
                            </th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="reservasTableBody">
                        <?php while ($reserva = mysqli_fetch_assoc($result_reservas)) { ?>
                            <tr>
                                <td><?php echo $reserva['cliente']; ?></td>
                                <td><?php echo $reserva['servico']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($reserva['data'])); ?></td>
                                <td><?php echo ucfirst($reserva['status']); ?></td>
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
                <h5 class="modal-title" id="datePickerModalLabel">Selecionar Data</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Fechar">&times;</button>
            </div>
            <div class="modal-body">
                <label for="datepicker">Escolha uma data:</label>
                <input type="text" id="datepicker" class="form-control">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" id="applyDate" class="btn btn-primary">Aplicar</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#datepicker').datepicker({
            format: 'dd/mm/yyyy', // Set the desired date format
            autoclose: true // Close the datepicker after selection
        });

        // When clicking the date header, show the modal
        $('#dateHeader').on('click', function() {
            $('#datePickerModal').modal('show');
        });

        // Apply date filter
        $('#applyDate').on('click', function() {
            const selectedDate = $('#datepicker').val();
            const rows = $('#reservasTableBody tr');

            rows.each(function() {
                const dateCell = $(this).find('td:nth-child(3)').text().trim(); // Get the date cell
                $(this).toggle(dateCell === selectedDate);
            });

            $('#datePickerModal').modal('hide');
        });
    });
</script>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>