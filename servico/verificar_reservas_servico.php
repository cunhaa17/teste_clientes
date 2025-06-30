<?php
session_start();

// Verifica se a sessão está iniciada corretamente
if (!isset($_SESSION['utilizador_id'])) {
    http_response_code(403);
    exit('Acesso negado');
}

// Verifica se o usuário é do tipo admin
if ($_SESSION['utilizador_tipo'] !== 'admin') {
    http_response_code(403);
    exit('Acesso negado');
}

require_once '../includes/db_conexao.php';

// Verificar se o ID do serviço foi enviado
if (!isset($_GET['servico_id']) || empty($_GET['servico_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID do serviço não fornecido']);
    exit;
}

$servico_id = $conn->real_escape_string($_GET['servico_id']);

// Verificar se existem reservas associadas ao serviço
$sql = "SELECT COUNT(*) as count FROM Reserva WHERE servico_id = '$servico_id'";
$result = $conn->query($sql);

if ($result === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao verificar reservas']);
    exit;
}

$row = $result->fetch_assoc();
$count = $row['count'];

// Retornar resultado em JSON
header('Content-Type: application/json');
echo json_encode([
    'has_reservas' => $count > 0,
    'count' => $count
]);

$conn->close(); 