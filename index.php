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

        <!-- Card Taxa de Ocupação -->
        <div class="col-md-3">
            <div class="card bg-info text-white shadow">
                <div class="card-body">
                    <h5 class="card-title">Taxa de Ocupação</h5>
                    <div class="progress">
                        <div class="progress-bar bg-dark" role="progressbar" style="width: <?php echo $taxa_ocupacao; ?>%;">
                            <?php echo round($taxa_ocupacao, 1); ?>%
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php } ?>
    </div>

    <!-- Gráfico e Reservas na mesma linha -->
    <div class="row mt-4">
        <!-- Próximas Reservas -->
        <div class="col-md-6">
            <h4>Próximas Reservas</h4>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Serviço</th>
                        <th>Data</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
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

        <?php if ($_SESSION['utilizador_tipo'] == 'admin') { ?>
        <!-- Gráfico de Reservas por Status -->
        <div class="col-md-6 mt-4">
            <canvas id="graficoReservasStatus"></canvas>
        </div>
        <?php } ?>
    </div>
</div>

<?php if ($_SESSION['utilizador_tipo'] == 'admin') { ?>
<script>
    var ctxStatus = document.getElementById('graficoReservasStatus').getContext('2d');
    var graficoStatus = new Chart(ctxStatus, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($meses_labels); ?>,
            datasets: [
                {
                    label: 'Confirmadas',
                    data: <?php echo json_encode($dados_confirmadas); ?>,
                    backgroundColor: 'rgba(40, 167, 69, 0.2)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Pendentes',
                    data: <?php echo json_encode($dados_pendentes); ?>,
                    backgroundColor: 'rgba(255, 193, 7, 0.2)',
                    borderColor: 'rgba(255, 193, 7, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Concluídas',
                    data: <?php echo json_encode($dados_concluidas); ?>,
                    backgroundColor: 'rgba(23, 162, 184, 0.2)',
                    borderColor: 'rgba(23, 162, 184, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Canceladas',
                    data: <?php echo json_encode($dados_canceladas); ?>,
                    backgroundColor: 'rgba(220, 53, 69, 0.2)',
                    borderColor: 'rgba(220, 53, 69, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
</script>
<?php } ?>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>