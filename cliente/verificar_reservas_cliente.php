<?php
session_start();
if (!isset($_SESSION['utilizador_id']) || $_SESSION['utilizador_tipo'] !== 'admin') {
    http_response_code(403);
    exit('Acesso negado');
}
require_once '../includes/db_conexao.php';
if (!isset($_GET['cliente_id']) || empty($_GET['cliente_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID do cliente não fornecido']);
    exit;
}
$cliente_id = $conn->real_escape_string($_GET['cliente_id']);
$sql = "SELECT COUNT(*) as count FROM Reserva WHERE cliente_id = '$cliente_id'";
$result = $conn->query($sql);
if ($result === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao verificar reservas']);
    exit;
}
$row = $result->fetch_assoc();
$count = $row['count'];
header('Content-Type: application/json');
echo json_encode([
    'has_reservas' => $count > 0,
    'count' => $count
]);
$conn->close(); 