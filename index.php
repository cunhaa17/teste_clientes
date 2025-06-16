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

$base_url = "/PAP/dashboard_pap/";
$title = "Início";
include_once 'includes/db_conexao.php';

// Consultas ao banco de dados
$query_clientes = "SELECT COUNT(*) AS total FROM cliente";
$query_funcionarios = "SELECT COUNT(*) AS total FROM funcionario";
$query_servicos = "SELECT COUNT(*) AS total FROM servico";
$query_reservas = "SELECT cliente.nome AS cliente, 
                   servico.nome AS servico, 
                   servico_subtipo.nome AS servico_subtipo,
                   servico_subtipo.preco AS preco,
                   servico_subtipo.duracao AS duracao,
                   reserva.data_reserva AS data, 
                   TIME_FORMAT(reserva.data_reserva, '%H:%i') AS hora,
                   reserva.status, 
                   reserva.id,
                   funcionario.nome AS funcionario_nome
                   FROM reserva 
                   JOIN cliente ON reserva.cliente_id = cliente.id 
                   JOIN servico_subtipo ON reserva.servico_subtipo_id = servico_subtipo.id
                   JOIN servico ON servico_subtipo.servico_id = servico.id 
                   LEFT JOIN reserva_funcionario ON reserva.id = reserva_funcionario.r_id
                   LEFT JOIN funcionario ON reserva_funcionario.f_id = funcionario.id
                   WHERE reserva.data_reserva >= CURRENT_DATE()
                   ORDER BY reserva.data_reserva ASC LIMIT 5";
$query_funcionarios_online = "SELECT nome FROM funcionario WHERE id IN (SELECT f_id FROM reserva_funcionario)";
$query_faturamento = "SELECT 
    MONTH(data_reserva) AS mes,
    YEAR(data_reserva) AS ano,
    SUM(servico_subtipo.preco) AS total 
FROM reserva 
JOIN servico_subtipo ON reserva.servico_subtipo_id = servico_subtipo.id 
WHERE YEAR(data_reserva) = YEAR(CURRENT_DATE())
GROUP BY YEAR(data_reserva), MONTH(data_reserva)
ORDER BY ano, mes";
$query_faturamento_diario = "SELECT 
    DATE(data_reserva) AS data,
    SUM(servico_subtipo.preco) AS total 
FROM reserva 
JOIN servico_subtipo ON reserva.servico_subtipo_id = servico_subtipo.id 
WHERE data_reserva >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)
GROUP BY DATE(data_reserva)
ORDER BY data";
$query_reservas_hoje = "SELECT COUNT(*) as total_hoje 
                       FROM reserva 
                       WHERE DATE(data_reserva) = CURRENT_DATE()";
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

// Query para faturamento do mês atual
$query_faturamento_mes_atual = "SELECT 
    SUM(servico_subtipo.preco) AS total 
FROM reserva 
JOIN servico_subtipo ON reserva.servico_subtipo_id = servico_subtipo.id 
WHERE MONTH(data_reserva) = MONTH(CURRENT_DATE()) 
AND YEAR(data_reserva) = YEAR(CURRENT_DATE())";

// Query para faturamento dos últimos 7 dias
$query_faturamento_7dias = "SELECT 
    SUM(servico_subtipo.preco) AS total 
FROM reserva 
JOIN servico_subtipo ON reserva.servico_subtipo_id = servico_subtipo.id 
WHERE data_reserva >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)";

// Query para média diária do mês
$query_media_diaria = "SELECT 
    AVG(daily_total) as media_diaria
FROM (
    SELECT DATE(data_reserva) as data, SUM(servico_subtipo.preco) as daily_total
    FROM reserva 
    JOIN servico_subtipo ON reserva.servico_subtipo_id = servico_subtipo.id 
    WHERE MONTH(data_reserva) = MONTH(CURRENT_DATE()) 
    AND YEAR(data_reserva) = YEAR(CURRENT_DATE())
    GROUP BY DATE(data_reserva)
) as daily_totals";

// Query para serviços mais reservados (Radar Chart - agora por serviço, não subtipo)
$query_servicos_total_reservas = "SELECT
    servico.nome AS servico_nome,
    COUNT(reserva.id) AS total_reservas
FROM servico
LEFT JOIN servico_subtipo ON servico.id = servico_subtipo.servico_id
LEFT JOIN reserva ON servico_subtipo.id = reserva.servico_subtipo_id
GROUP BY servico.nome
ORDER BY total_reservas DESC";

$result_clientes = mysqli_query($conn, $query_clientes);
$result_funcionarios = mysqli_query($conn, $query_funcionarios);
$result_servicos = mysqli_query($conn, $query_servicos);
$result_reservas = mysqli_query($conn, $query_reservas);
if (!$result_reservas) {
    die("Erro na consulta: " . mysqli_error($conn));
}
$result_funcionarios_online = mysqli_query($conn, $query_funcionarios_online);
$result_faturamento = mysqli_query($conn, $query_faturamento);
$result_faturamento_diario = mysqli_query($conn, $query_faturamento_diario);
$result_ocupacao = mysqli_query($conn, $query_ocupacao);
$result_reservas_hoje = mysqli_query($conn, $query_reservas_hoje);
$result_estimativa_mensal = mysqli_query($conn, $query_estimativa_mensal);
$result_status_reservas = mysqli_query($conn, $query_status_reservas); // Executa a consulta de status
$result_faturamento_mes = mysqli_query($conn, $query_faturamento_mes_atual);
$result_faturamento_7dias = mysqli_query($conn, $query_faturamento_7dias);
$result_media_diaria = mysqli_query($conn, $query_media_diaria);
$result_servicos_total_reservas = mysqli_query($conn, $query_servicos_total_reservas); // Nova consulta

$total_clientes = mysqli_fetch_assoc($result_clientes)['total'];
$total_funcionarios = mysqli_fetch_assoc($result_funcionarios)['total'];
$total_servicos = mysqli_fetch_assoc($result_servicos)['total'];
$taxa_ocupacao = mysqli_fetch_assoc($result_ocupacao)['taxa'];
$estimativa_mensal = mysqli_fetch_assoc($result_estimativa_mensal)['total_mensal'] ?? 0;
$faturamento_mes = mysqli_fetch_assoc($result_faturamento_mes)['total'] ?? 0;
$faturamento_7dias = mysqli_fetch_assoc($result_faturamento_7dias)['total'] ?? 0;
$media_diaria = mysqli_fetch_assoc($result_media_diaria)['media_diaria'] ?? 0;
$total_reservas_hoje = mysqli_fetch_assoc($result_reservas_hoje)['total_hoje'] ?? 0;

// Preparar dados para os gráficos
$meses = [];
$valores_mensais = [];
$datas = [];
$valores_diarios = [];
$servicos_nomes_radar = [];
$servicos_reservas_radar = [];

while ($row = mysqli_fetch_assoc($result_faturamento)) {
    $meses[] = date('M Y', mktime(0, 0, 0, $row['mes'], 1, $row['ano']));
    $valores_mensais[] = $row['total'];
}

while ($row = mysqli_fetch_assoc($result_faturamento_diario)) {
    $datas[] = date('d/m', strtotime($row['data']));
    $valores_diarios[] = $row['total'];
}

while ($row = mysqli_fetch_assoc($result_servicos_total_reservas)) {
    $servicos_nomes_radar[] = $row['servico_nome'];
    $servicos_reservas_radar[] = (int) $row['total_reservas'];
}

ob_start();
?>

<head>
    <!-- CSS do Datepicker -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- jQuery (necessário para o Datepicker) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #FDF5E6;
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
        .status-concluída { background-color: #e3f2fd; color: #0d47a1; }
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
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            background: #fff;
        }
        
        .modal-header {
            background: linear-gradient(45deg, #8B4513, #A0522D);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 1.5rem;
            border: none;
        }
        
        .modal-title {
            font-weight: 600;
            font-size: 1.25rem;
            letter-spacing: 0.5px;
        }
        
        .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.8;
            transition: opacity 0.3s ease;
        }
        
        .btn-close:hover {
            opacity: 1;
        }
        
        .modal-body {
            padding: 2rem;
            background: #FDF5E6;
        }
        
        .modal-footer {
            border-top: 1px solid rgba(139,69,19,0.1);
            padding: 1.5rem;
            background: #fff;
        }
        
        .modal-section {
            background: #fff;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid rgba(139,69,19,0.1);
        }
        
        .modal-section h6 {
            color: #8B4513;
            font-weight: 600;
            margin-bottom: 1rem;
            font-size: 1.1rem;
            border-bottom: 2px solid #A0522D;
            padding-bottom: 0.5rem;
        }
        
        .modal-section p {
            margin-bottom: 0.75rem;
            color: #2d3436;
            font-size: 0.95rem;
        }
        
        .modal-section strong {
            color: #8B4513;
            font-weight: 500;
        }
        
        .modal-section span {
            color: #2d3436;
            font-weight: 500;
        }
        
        .status-badge-modal {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-left: 0.5rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .status-pendente { 
            background-color: #fff3cd; 
            color: #856404;
            border: 1px solid #ffeeba;
        }
        
        .status-confirmada { 
            background-color: #d4edda; 
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-concluída { 
            background-color: #e3f2fd; 
            color: #0d47a1;
            border: 1px solid #bbdefb;
        }
        
        .status-cancelada { 
            background-color: #f8d7da; 
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .btn-secondary {
            background: #8B4513;
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            background: #A0522D;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(139,69,19,0.3);
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
        
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
            height: 400px; /* Aumenta a altura para dar mais espaço ao gráfico */
        }
        
        .chart-title {
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 1rem;
            color: #2c3e50;
        }

        canvas {
            max-height: 250px !important; /* Altura máxima para o canvas */
        }

        /* Estilos adicionais para a tabela */
        .table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #495057;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
            transition: background-color 0.2s ease;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-block;
            min-width: 100px;
            text-align: center;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            margin: 0 2px;
        }

        .gap-2 {
            gap: 0.5rem;
        }

        #statusFilter {
            border-radius: 50px;
            padding: 0.375rem 1rem;
            border: 1px solid #ced4da;
        }

        #justificativaCancelamento {
            background: #fff;
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid rgba(0,0,0,0.1);
        }

        #justificativaCancelamento .form-label {
            color: #636e72;
            font-weight: 500;
        }

        #justificativaCancelamento .form-control {
            border: 1px solid #dfe6e9;
            border-radius: 8px;
            padding: 0.75rem;
        }

        #justificativaCancelamento .form-control:focus {
            border-color: #6C5CE7;
            box-shadow: 0 0 0 0.2rem rgba(108,92,231,0.25);
        }

        #btnCancelarReserva {
            background: #e74c3c;
            border: none;
            padding: 0.5rem 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        #btnCancelarReserva:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231,76,60,0.3);
        }

        #btnConfirmarCancelamento {
            background: #e74c3c;
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        #btnConfirmarCancelamento:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231,76,60,0.3);
        }

        #confirmarCancelamentoModal .modal-header {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
            border: none;
        }

        #confirmarCancelamentoModal .modal-content {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        #confirmarCancelamentoModal .alert {
            border-radius: 10px;
            border: none;
            background-color: #fff3cd;
            color: #856404;
        }

        #confirmarCancelamentoModal .alert-heading {
            color: #856404;
            font-weight: 600;
        }

        #confirmarCancelamentoModal .form-control {
            border: 1px solid #dfe6e9;
            border-radius: 8px;
            padding: 0.75rem;
        }

        #confirmarCancelamentoModal .form-control:focus {
            border-color: #6C5CE7;
            box-shadow: 0 0 0 0.2rem rgba(108,92,231,0.25);
        }

        #confirmarCancelamentoModal .btn-danger {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        #confirmarCancelamentoModal .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231,76,60,0.3);
        }

        #confirmarCancelamentoModal .btn-secondary {
            background: #6C5CE7;
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        #confirmarCancelamentoModal .btn-secondary:hover {
            background: #5b4bc4;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(108,92,231,0.3);
        }

        .info-item {
            background: #FDF5E6;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid rgba(139,69,19,0.1);
        }
        
        .info-item label {
            font-size: 0.85rem;
            font-weight: 500;
            color: #8B4513;
            margin-bottom: 0.25rem;
            display: block;
        }
        
        .info-item p {
            font-size: 1rem;
            color: #2d3436;
            margin: 0;
            font-weight: 500;
        }
        
        #reservaDataHora {
            font-size: 1.1rem;
            font-weight: 500;
            color: #2d3436;
        }
        
        #reservaStatus {
            font-size: 1.1rem;
            font-weight: 500;
        }

        .btn-success {
            background: #00B894;
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-success:hover {
            background: #00A884;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,184,148,0.3);
        }
        
        .btn-danger {
            background: #FF6B6B;
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-danger:hover {
            background: #FF5252;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,107,107,0.3);
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
            border-top: 5px solid #D4B996;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .loading-text {
            position: absolute;
            margin-top: 80px;
            color: #8B7355;
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

        /* Estilos para a tabela de reservas */
        .table-reservas {
            font-size: 0.9rem;
        }

        .table-reservas thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #495057;
            padding: 1rem;
            white-space: nowrap;
        }

        .table-reservas tbody td {
            padding: 1rem;
            vertical-align: middle;
        }

        .table-reservas .cliente-info {
            font-weight: 500;
            color: #2d3436;
        }

        .table-reservas .servico-info {
            margin-bottom: 0.25rem;
        }

        .table-reservas .servico-detalhes {
            font-size: 0.8rem;
            color: #636e72;
        }

        .table-reservas .servico-detalhes i {
            width: 16px;
            text-align: center;
            margin-right: 4px;
        }

        .table-reservas .data-hora {
            font-weight: 500;
            color: #2d3436;
        }

        .table-reservas .funcionario-info {
            font-size: 0.8rem;
            color: #636e72;
            margin-top: 0.25rem;
        }

        .table-reservas .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-block;
            min-width: 90px;
            text-align: center;
        }

        .table-reservas .btn-group {
            display: flex;
            gap: 0.25rem;
        }

        .table-reservas .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }

        .table-reservas .badge-hoje {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
            border-radius: 50px;
            margin-left: 0.5rem;
        }

        /* Layout Improvements */
        .dashboard-container {
            padding: 2rem 0;
        }

        .dashboard-header {
            margin-bottom: 2rem;
        }

        .dashboard-title {
            color: #2d3436;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .dashboard-subtitle {
            color: #636e72;
            font-size: 1.1rem;
        }

        /* Card Grid Improvements */
        .card-grid {
            display: grid;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .card-grid-3 {
            grid-template-columns: repeat(3, 1fr);
        }

        .card-grid-2 {
            grid-template-columns: repeat(2, 1fr);
        }

        @media (max-width: 992px) {
            .card-grid-3 {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .card-grid-3, .card-grid-2 {
                grid-template-columns: 1fr;
            }
        }

        /* Card Improvements */
        .dashboard-card {
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .dashboard-card .card-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .dashboard-card .card-title {
            font-size: 1.1rem;
            margin-bottom: 1rem;
            color: rgba(255, 255, 255, 0.9);
        }

        .dashboard-card .card-text {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0;
        }

        .dashboard-card .icon-circle {
            margin-bottom: 1.5rem;
        }

        /* Chart and Table Section */
        .dashboard-main {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        @media (max-width: 992px) {
            .dashboard-main {
                grid-template-columns: 1fr;
            }
        }

        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
            height: 100%;
        }

        .chart-title {
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 1.5rem;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .chart-title i {
            color: #6C5CE7;
        }

        /* Quick Stats Section */
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        @media (max-width: 992px) {
            .quick-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 576px) {
            .quick-stats {
                grid-template-columns: 1fr;
            }
        }

        .quick-stat-card {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }

        .quick-stat-card:hover {
            transform: translateY(-3px);
        }

        .quick-stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .quick-stat-info {
            flex: 1;
        }

        .quick-stat-label {
            font-size: 0.9rem;
            color: #636e72;
            margin-bottom: 0.25rem;
        }

        .quick-stat-value {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2d3436;
        }
    </style>
</head>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<div class="dashboard-container">
    <!-- Header Section -->
    <div class="dashboard-header">
        <h1 class="dashboard-title">Painel de Controle</h1>
        <p class="dashboard-subtitle">Bem-vindo ao seu painel de gerenciamento</p>
    </div>

    <!-- Quick Stats Section -->
    <div class="quick-stats">
        <div class="quick-stat-card">
            <div class="quick-stat-icon" style="background: rgba(108,92,231,0.1); color: #6C5CE7;">
                <i class="fas fa-users"></i>
            </div>
            <div class="quick-stat-info">
                <div class="quick-stat-label">Total de Clientes</div>
                <div class="quick-stat-value"><?php echo $total_clientes; ?></div>
            </div>
        </div>
        <div class="quick-stat-card">
            <div class="quick-stat-icon" style="background: rgba(0,184,148,0.1); color: #00B894;">
                <i class="fas fa-user-tie"></i>
            </div>
            <div class="quick-stat-info">
                <div class="quick-stat-label">Total de Funcionários</div>
                <div class="quick-stat-value"><?php echo $total_funcionarios; ?></div>
            </div>
        </div>
        <div class="quick-stat-card">
            <div class="quick-stat-icon" style="background: rgba(255,107,107,0.1); color: #FF6B6B;">
                <i class="fas fa-concierge-bell"></i>
            </div>
            <div class="quick-stat-info">
                <div class="quick-stat-label">Serviços Ativos</div>
                <div class="quick-stat-value"><?php echo $total_servicos; ?></div>
            </div>
        </div>
        <div class="quick-stat-card">
            <div class="quick-stat-icon" style="background: rgba(255,217,61,0.1); color: #FFD93D;">
                <i class="fas fa-calendar-day"></i>
            </div>
            <div class="quick-stat-info">
                <div class="quick-stat-label">Reservas Hoje</div>
                <div class="quick-stat-value"><?php echo $total_reservas_hoje; ?></div>
            </div>
        </div>
    </div>

    <!-- Main Cards Section -->
    <div class="card-grid card-grid-3">
        <!-- Card Faturamento do Mês -->
        <div class="card text-white shadow animate__animated animate__fadeIn dashboard-card" style="background: linear-gradient(45deg, #6C5CE7, #8C7AE6)">
            <div class="card-body">
                <div class="icon-circle">
                    <i class="fas fa-calendar-alt fa-2x"></i>
                </div>
                <h5 class="card-title">Faturamento do Mês</h5>
                <p class="card-text"><?php echo number_format($faturamento_mes, 2, ',', '.'); ?> MZN</p>
            </div>
        </div>

        <!-- Card Últimos 7 Dias -->
        <div class="card text-white shadow animate__animated animate__fadeIn dashboard-card" style="background: linear-gradient(45deg, #00B894, #00A884)">
            <div class="card-body">
                <div class="icon-circle">
                    <i class="fas fa-chart-line fa-2x"></i>
                </div>
                <h5 class="card-title">Últimos 7 Dias</h5>
                <p class="card-text"><?php echo number_format($faturamento_7dias, 2, ',', '.'); ?> MZN</p>
            </div>
        </div>

        <!-- Card Reservas Hoje -->
        <div class="card text-white shadow animate__animated animate__fadeIn dashboard-card" style="background: linear-gradient(45deg, #FFD93D, #FFC107)">
            <div class="card-body">
                <div class="icon-circle">
                    <i class="fas fa-calendar-day fa-2x"></i>
                </div>
                <h5 class="card-title">Reservas Hoje</h5>
                <p class="card-text"><?php echo $total_reservas_hoje; ?></p>
            </div>
        </div>
    </div>

    <!-- Main Content Section -->
    <div class="dashboard-main">
        <!-- Chart Section -->
        <div class="chart-container">
            <h5 class="chart-title">
                <i class="fas fa-chart-pie"></i>
                Total de Reservas por Serviço
            </h5>
            <canvas id="servicosReservasRadarChart"></canvas>
        </div>

        <!-- Reservas Table Section -->
        <div class="card shadow">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Próximas Reservas</h4>
                    <div class="d-flex gap-2">
                        <div class="input-group" style="width: auto;">
                            <input type="text" class="form-control" id="searchReserva" placeholder="Buscar cliente...">
                            <button class="btn btn-outline-secondary" type="button">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        <select class="form-control" id="statusFilter" style="width: auto;">
                            <option value="">Todos os Estados</option>
                            <option value="confirmada">Confirmada</option>
                            <option value="cancelada">Cancelada</option>
                            <option value="concluída">Concluída</option>
                        </select>
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-download"></i>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                                <li><a class="dropdown-item" href="#" id="exportPDF"><i class="fas fa-file-pdf"></i> Exportar PDF</a></li>
                                <li><a class="dropdown-item" href="#" id="exportExcel"><i class="fas fa-file-excel"></i> Exportar Excel</a></li>
                            </ul>
                        </div>
                        <button class="btn btn-primary" id="refreshReservas">
                            <i class="fas fa-sync-alt mr-2"></i>Atualizar
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-reservas">
                        <thead>
                            <tr>
                                <th><i class="fas fa-user mr-2"></i>Cliente</th>
                                <th><i class="fas fa-concierge-bell mr-2"></i>Serviço</th>
                                <th><i class="fas fa-calendar-alt mr-2"></i>Data/Hora</th>
                                <th><i class="fas fa-info-circle mr-2"></i>Status</th>
                                <th><i class="fas fa-cog mr-2"></i>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="reservasTableBody">
                            <?php while ($reserva = mysqli_fetch_assoc($result_reservas)) { 
                                $isToday = date('Y-m-d') === date('Y-m-d', strtotime($reserva['data']));
                                $rowClass = $isToday ? 'table-warning' : '';
                            ?>
                                <tr class="animate__animated animate__fadeIn <?php echo $rowClass; ?>" 
                                    data-status="<?php echo strtolower($reserva['status']); ?>" 
                                    data-reserva-id="<?php echo $reserva['id']; ?>"
                                    data-cliente="<?php echo strtolower($reserva['cliente']); ?>">
                                    <td>
                                        <div class="cliente-info">
                                            <?php echo $reserva['cliente']; ?>
                                            <?php if ($isToday) { ?>
                                                <span class="badge bg-warning text-dark badge-hoje">Hoje</span>
                                            <?php } ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="servico-info"><?php echo $reserva['servico'] . ' - ' . $reserva['servico_subtipo']; ?></div>
                                        <div class="servico-detalhes">
                                            <div><i class="fas fa-clock"></i><?php echo $reserva['duracao']; ?> min</div>
                                            <div><i class="fas fa-money-bill-wave"></i><?php echo number_format($reserva['preco'], 2, ',', '.'); ?> MZN</div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="data-hora">
                                            <?php 
                                            $dataFormatada = date('d/m/Y', strtotime($reserva['data']));
                                            $horaFormatada = $reserva['hora'];
                                            if ($isToday) {
                                                echo "Hoje às " . $horaFormatada;
                                            } else {
                                                echo $dataFormatada . " " . $horaFormatada;
                                            }
                                            ?>
                                        </div>
                                        <?php if ($reserva['funcionario_nome']) { ?>
                                            <div class="funcionario-info">
                                                <i class="fas fa-user-tie"></i><?php echo $reserva['funcionario_nome']; ?>
                                            </div>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($reserva['status']); ?>">
                                            <?php echo ucfirst($reserva['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-info" title="Ver Detalhes">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if (strtolower($reserva['status']) == 'pendente') { ?>
                                                <button class="btn btn-sm btn-success" title="Confirmar">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" title="Cancelar">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <?php } ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Radar Chart – Total de Reservas por Serviço
        const servicosNomesRadar = <?php echo json_encode($servicos_nomes_radar); ?>;
        const servicosReservasRadar = <?php echo json_encode($servicos_reservas_radar); ?>;

        const ctxServicosReservasRadar = document.getElementById('servicosReservasRadarChart').getContext('2d');

        new Chart(ctxServicosReservasRadar, {
            type: 'radar',
            data: {
                labels: servicosNomesRadar,
                datasets: [
                    {
                        label: 'Número de Reservas',
                        data: servicosReservasRadar,
                        backgroundColor: 'rgba(75,192,192,0.2)',
                        borderColor: 'rgba(75,192,192,1)',
                        borderWidth: 2,
                        pointBackgroundColor: 'rgba(75,192,192,1)',
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: 'rgba(75,192,192,1)'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        angleLines: { display: true, color: 'rgba(0, 0, 0, 0.1)' },
                        grid: { color: 'rgba(0, 0, 0, 0.1)' },
                        pointLabels: {
                            font: {
                                size: 12
                            },
                            color: '#333'
                        },
                        ticks: {
                            beginAtZero: true,
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            color: '#333',
                            font: {
                                size: 14
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.raw;
                            }
                        }
                    }
                }
            }
        });

        // Filtro por status
        $('#statusFilter').on('change', function() {
            const selectedStatus = $(this).val().toLowerCase();
            const rows = $('#reservasTableBody tr');
            
            if (selectedStatus === '') {
                rows.show();
            } else {
                rows.each(function() {
                    const rowStatus = $(this).data('status');
                    $(this).toggle(rowStatus === selectedStatus);
                });
            }
        });

        // Atualizar tabela
        $('#refreshReservas').on('click', function() {
            const $btn = $(this);
            $btn.prop('disabled', true);
            $btn.html('<i class="fas fa-spinner fa-spin mr-2"></i>Atualizando...');
            
            setTimeout(function() {
                location.reload();
            }, 1000);
        });

        // Adiciona efeito de hover nos cards
        $('.card').hover(
            function() { $(this).addClass('animate__animated animate__pulse'); },
            function() { $(this).removeClass('animate__animated animate__pulse'); }
        );

        // Função para carregar detalhes da reserva
        function carregarDetalhesReserva(reservaId) {
            $.ajax({
                url: 'reservas/get_reserva_detalhes.php',
                type: 'POST',
                data: { reserva_id: reservaId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const reserva = response.data;
                        
                        // Preencher data e hora
                        $('#reservaDataHora').text(reserva.data + ' ' + reserva.hora);
                        
                        // Preencher status
                        $('#reservaStatus').html('<span class="status-badge-modal status-' + reserva.status.toLowerCase() + '">' + 
                            reserva.status + '</span>');
                        
                        // Preencher informações do cliente
                        $('#clienteNome').text(reserva.cliente_nome);
                        $('#clienteContacto').text(reserva.cliente_contacto);
                        
                        // Preencher detalhes do serviço
                        $('#servicoNome').text(reserva.servico_nome);
                        $('#servicoSubtipo').text(reserva.servico_subtipo);
                        $('#servicoPreco').text(reserva.preco + ' MZN');
                        $('#funcionarioNome').text(reserva.funcionario_nome || 'Não atribuído');
                        
                        // Preencher observações
                        $('#reservaObservacoes').text(reserva.observacoes || 'Sem observações');
                        
                        // Mostrar/ocultar seção de ações
                        if (reserva.status.toLowerCase() === 'confirmada') {
                            $('#acoesReservaSection').show();
                        } else {
                            $('#acoesReservaSection').hide();
                        }
                        
                        // Preencher informações para o modal de confirmação de cancelamento
                        $('#confirmClienteNome').text(reserva.cliente_nome);
                        $('#confirmServicoNome').text(reserva.servico_nome + ' - ' + reserva.servico_subtipo);
                        $('#confirmReservaData').text(reserva.data);
                        $('#confirmReservaHora').text(reserva.hora);
                        
                        // Armazenar o ID da reserva no modal
                        $('#reservaDetalhesModal').data('reserva-id', reservaId);
                        
                        // Abrir o modal
                        $('#reservaDetalhesModal').modal('show');
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro',
                            text: response.message || 'Não foi possível carregar os detalhes da reserva.'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Erro AJAX:', {xhr, status, error});
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: 'Ocorreu um erro ao carregar os detalhes da reserva. Por favor, tente novamente.'
                    });
                }
            });
        }

        // Adicionar evento de clique no botão de visualizar
        $(document).on('click', '.btn-info', function() {
            const reservaId = $(this).closest('tr').data('reserva-id');
            carregarDetalhesReserva(reservaId);
        });

        // Função para atualizar o status da reserva
        function atualizarStatusReserva(reservaId, novoStatus) {
            console.log('Enviando dados:', { reserva_id: reservaId, status: novoStatus }); // Debug
            
            $.ajax({
                url: 'reservas/atualizar_status_reserva.php',
                type: 'POST',
                data: { 
                    reserva_id: reservaId,
                    status: novoStatus
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Resposta:', response); // Debug
                    if (response.success) {
                        // Mostrar o efeito de carregamento
                        $('.loading-overlay').removeClass('fade-out').show();
                        
                        setTimeout(function() {
                            // Após o carregamento, mostrar mensagem de sucesso
                            Swal.fire({
                                icon: 'success',
                                title: 'Sucesso!',
                                text: 'Status da reserva atualizado com sucesso.',
                                showConfirmButton: false,
                                timer: 1500
                            }).then(() => {
                                location.reload();
                            });
                        }, 1000);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro',
                            text: response.message || 'Não foi possível atualizar o status da reserva.'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Erro AJAX:', {xhr, status, error});
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: 'Ocorreu um erro ao atualizar o status da reserva. Por favor, tente novamente.'
                    });
                }
            });
        }

        // Evento para concluir reserva
        $(document).on('click', '#btnConcluirReserva', function() {
            const reservaId = $('#reservaDetalhesModal').data('reserva-id');
            console.log('ID da reserva:', reservaId); // Debug
            
            Swal.fire({
                title: 'Confirmar Conclusão',
                text: 'Deseja marcar esta reserva como concluída?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#00B894',
                cancelButtonColor: '#8B7355',
                confirmButtonText: 'Sim, concluir',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    atualizarStatusReserva(reservaId, 'concluída');
                }
            });
        });

        // Evento para cancelar reserva (mantendo o modal de confirmação existente)
        $(document).on('click', '#btnCancelarReserva', function() {
            $('#confirmarCancelamentoModal').modal('show');
        });

        // Evento para confirmar o cancelamento final
        $(document).on('click', '#btnConfirmarCancelamentoFinal', function() {
            const reservaId = $('#reservaDetalhesModal').data('reserva-id');
            const justificativa = $('#confirmJustificativa').val().trim();
            
            if (!justificativa) {
                $('#confirmJustificativa').addClass('is-invalid');
                return;
            }
            
            $('#confirmJustificativa').removeClass('is-invalid');
            
            $.ajax({
                url: 'reservas/atualizar_status_reserva.php',
                type: 'POST',
                data: { 
                    reserva_id: reservaId,
                    status: 'cancelada',
                    justificativa: justificativa
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#confirmarCancelamentoModal').modal('hide');
                        $('#reservaDetalhesModal').modal('hide');
                        
                        // Mostrar o efeito de carregamento
                        $('.loading-overlay').removeClass('fade-out').show();
                        
                        setTimeout(function() {
                            // Após o carregamento, mostrar mensagem de sucesso
                            Swal.fire({
                                icon: 'success',
                                title: 'Sucesso!',
                                text: 'Reserva cancelada com sucesso.',
                                showConfirmButton: false,
                                timer: 1500
                            }).then(() => {
                                location.reload();
                            });
                        }, 1000);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro',
                            text: response.message || 'Não foi possível cancelar a reserva.'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Erro AJAX:', {xhr, status, error});
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: 'Ocorreu um erro ao cancelar a reserva. Por favor, tente novamente.'
                    });
                }
            });
        });

        // Busca rápida por cliente
        $('#searchReserva').on('keyup', function() {
            const searchText = $(this).val().toLowerCase();
            $('#reservasTableBody tr').each(function() {
                const cliente = $(this).data('cliente');
                $(this).toggle(cliente.includes(searchText));
            });
        });

        // Exportar para PDF
        $('#exportPDF').on('click', function(e) {
            e.preventDefault();
            // Implementar exportação para PDF
            Swal.fire({
                icon: 'info',
                title: 'Em desenvolvimento',
                text: 'A exportação para PDF será implementada em breve.'
            });
        });

        // Exportar para Excel
        $('#exportExcel').on('click', function(e) {
            e.preventDefault();
            // Implementar exportação para Excel
            Swal.fire({
                icon: 'info',
                title: 'Em desenvolvimento',
                text: 'A exportação para Excel será implementada em breve.'
            });
        });
    });
</script>

<!-- Modal de Detalhes da Reserva -->
<div class="modal fade" id="reservaDetalhesModal" tabindex="-1" aria-labelledby="reservaDetalhesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reservaDetalhesModalLabel">
                    <i class="fas fa-calendar-check mr-2"></i>Detalhes da Reserva
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="modal-section">
                            <h6><i class="fas fa-calendar-alt mr-2"></i>Data e Hora</h6>
                            <div class="info-item">
                                <label>Data e Hora da Reserva:</label>
                                <p id="reservaDataHora"></p>
                            </div>
                            <div class="info-item">
                                <label>Status:</label>
                                <p id="reservaStatus"></p>
                            </div>
                        </div>

                        <div class="modal-section">
                            <h6><i class="fas fa-user mr-2"></i>Informações do Cliente</h6>
                            <div class="info-item">
                                <label>Nome do Cliente:</label>
                                <p id="clienteNome"></p>
                            </div>
                            <div class="info-item">
                                <label>Contacto:</label>
                                <p id="clienteContacto"></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="modal-section">
                            <h6><i class="fas fa-concierge-bell mr-2"></i>Detalhes do Serviço</h6>
                            <div class="info-item">
                                <label>Serviço:</label>
                                <p id="servicoNome"></p>
                            </div>
                            <div class="info-item">
                                <label>Subtipo:</label>
                                <p id="servicoSubtipo"></p>
                            </div>
                            <div class="info-item">
                                <label>Preço:</label>
                                <p id="servicoPreco"></p>
                            </div>
                            <div class="info-item">
                                <label>Duração:</label>
                                <p id="servicoDuracao"></p>
                            </div>
                            <div class="info-item">
                                <label>Funcionário Responsável:</label>
                                <p id="funcionarioNome"></p>
                            </div>
                        </div>

                        <div class="modal-section">
                            <h6><i class="fas fa-clipboard-list mr-2"></i>Observações</h6>
                            <div class="info-item">
                                <p id="reservaObservacoes"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-section" id="acoesReservaSection" style="display: none;">
                    <h6><i class="fas fa-tasks mr-2"></i>Ações</h6>
                    <div class="d-flex gap-2">
                        <button class="btn btn-success" id="btnConcluirReserva">
                            <i class="fas fa-check mr-2"></i>Concluir Reserva
                        </button>
                        <button class="btn btn-danger" id="btnCancelarReserva">
                            <i class="fas fa-times mr-2"></i>Cancelar Reserva
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmação de Cancelamento -->
<div class="modal fade" id="confirmarCancelamentoModal" tabindex="-1" aria-labelledby="confirmarCancelamentoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmarCancelamentoModalLabel">Confirmar Cancelamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <h6 class="alert-heading">Atenção!</h6>
                    <p>Você está prestes a cancelar a reserva do cliente <strong id="confirmClienteNome"></strong>.</p>
                    <p>Serviço: <strong id="confirmServicoNome"></strong></p>
                    <p>Data: <strong id="confirmReservaData"></strong></p>
                    <p>Hora: <strong id="confirmReservaHora"></strong></p>
                </div>
                <div class="mb-3">
                    <label for="confirmJustificativa" class="form-label">Justificativa do Cancelamento:</label>
                    <textarea class="form-control" id="confirmJustificativa" rows="3" required></textarea>
                    <div class="invalid-feedback">
                        Por favor, forneça uma justificativa para o cancelamento.
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Voltar</button>
                <button type="button" class="btn btn-danger" id="btnConfirmarCancelamentoFinal">Confirmar Cancelamento</button>
            </div>
        </div>
    </div>
</div>

</body>
</html>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>