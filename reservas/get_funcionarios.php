<?php
session_start();

// Verifica se a sessão está iniciada corretamente
if (!isset($_SESSION['utilizador_id'])) {
    exit('Acesso negado');
}

require_once '../includes/db_conexao.php';

// Verificar se o ID do subtipo foi enviado
if (!isset($_GET['servico_subtipo_id']) || empty($_GET['servico_subtipo_id'])) {
    echo '<option value="">Selecione um serviço primeiro</option>';
    exit;
}

$servico_subtipo_id = intval($_GET['servico_subtipo_id']);

// Buscar funcionários associados ao subtipo de serviço
$sql = "SELECT f.id, f.nome 
        FROM funcionario f
        JOIN funcionario_subtipo fs ON f.id = fs.funcionario_id
        WHERE fs.servico_subtipo_id = ?
        ORDER BY f.nome";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $servico_subtipo_id);
$stmt->execute();
$result = $stmt->get_result();

// Verificar se encontrou funcionários
if ($result->num_rows > 0) {
    echo '<option value="">Selecione um funcionário</option>';
    while ($row = $result->fetch_assoc()) {
        echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['nome']) . '</option>';
    }
} else {
    // Se não houver funcionários associados, mostrar todos os funcionários
    echo '<option value="">Nenhum funcionário específico - Selecione manualmente:</option>';
    
    // Buscar todos os funcionários
    $sql_all = "SELECT id, nome FROM funcionario ORDER BY nome";
    $result_all = $conn->query($sql_all);
    
    while ($row = $result_all->fetch_assoc()) {
        echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['nome']) . '</option>';
    }
}

$stmt->close();
$conn->close();
?>