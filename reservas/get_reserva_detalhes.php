<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once '../includes/db_conexao.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['reserva_id'])) {
    echo json_encode(['success' => false, 'message' => 'Requisição inválida']);
    exit();
}

$reserva_id = intval($_POST['reserva_id']);

try {
    // Verificar conexão com o banco
    if ($conn->connect_error) {
        throw new Exception("Falha na conexão com o banco de dados: " . $conn->connect_error);
    }

    // Verificar se a reserva existe
    $query = "SELECT r.*, 
              c.nome as cliente_nome, 
              c.telefone as cliente_contacto,
              s.nome as servico_nome, 
              ss.nome as servico_subtipo,
              ss.preco,
              f.nome as funcionario_nome,
              DATE_FORMAT(r.data_reserva, '%d/%m/%Y') as data,
              TIME_FORMAT(r.data_reserva, '%H:%i') as hora
              FROM reserva r 
              INNER JOIN cliente c ON r.cliente_id = c.id 
              INNER JOIN servico s ON r.servico_id = s.id 
              INNER JOIN servico_subtipo ss ON r.servico_subtipo_id = ss.id 
              INNER JOIN funcionario f ON r.funcionario_id = f.id 
              WHERE r.id = ?";

    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        throw new Exception("Erro ao preparar consulta: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "i", $reserva_id);

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Erro ao executar consulta: " . mysqli_stmt_error($stmt));
    }

    $result = mysqli_stmt_get_result($stmt);
    $reserva = mysqli_fetch_assoc($result);

    if (!$reserva) {
        throw new Exception("Reserva não encontrada");
    }

    // Formatar os dados para retorno
    $response = [
        'success' => true,
        'data' => [
            'cliente_nome' => $reserva['cliente_nome'],
            'cliente_contacto' => $reserva['cliente_contacto'],
            'servico_nome' => $reserva['servico_nome'],
            'servico_subtipo' => $reserva['servico_subtipo'],
            'preco' => number_format($reserva['preco'], 2, ',', '.'),
            'funcionario_nome' => $reserva['funcionario_nome'],
            'data' => $reserva['data'],
            'hora' => $reserva['hora'],
            'status' => $reserva['status'],
            'observacoes' => $reserva['observacao']
        ]
    ];

    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Erro em get_reserva_detalhes.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao carregar detalhes da reserva: ' . $e->getMessage()]);
}
?> 