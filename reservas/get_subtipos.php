<?php
session_start();

// Verifica se a sessão está iniciada corretamente
if (!isset($_SESSION['utilizador_id'])) {
    exit('Acesso negado');
}

require_once '../includes/db_conexao.php';

// Verificar se o ID do serviço foi enviado
if (!isset($_GET['servico_id']) || empty($_GET['servico_id'])) {
    echo '<option value="">Selecione uma categoria primeiro</option>';
    exit;
}

$servico_id = intval($_GET['servico_id']);

// Buscar subtipos do serviço selecionado
$sql = "SELECT id, nome, preco, duracao FROM servico_subtipo WHERE servico_id = ? ORDER BY nome";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $servico_id);
$stmt->execute();
$result = $stmt->get_result();

echo '<option value="">Selecione um serviço</option>';

while ($row = $result->fetch_assoc()) {
    $preco = number_format($row['preco'], 2, ',', '.');
    echo '<option value="' . $row['id'] . '">' . 
         htmlspecialchars($row['nome']) . ' - ' . 
         $preco . '€ (' . 
         $row['duracao'] . ' min)</option>';
}

$stmt->close();
$conn->close();
?>