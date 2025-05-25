<?php
session_start();

// Verifica se a sessão está iniciada corretamente
if (!isset($_SESSION['utilizador_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Acesso negado']);
    exit();
}

// Verifica se o usuário é do tipo admin
if ($_SESSION['utilizador_tipo'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Acesso negado']);
    exit();
}

include_once '../includes/db_conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = $conn->real_escape_string($_POST['id']);

    // Verificar se o subtipo existe
    $sql_check = "SELECT id FROM servico_subtipo WHERE id = '$id'";
    $result_check = $conn->query($sql_check);

    if ($result_check->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Subtipo não encontrado']);
        exit();
    }

    // Excluir o subtipo
    $sql_delete = "DELETE FROM servico_subtipo WHERE id = '$id'";
    
    if ($conn->query($sql_delete)) {
        echo json_encode(['status' => 'success', 'message' => 'Subtipo excluído com sucesso']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Erro ao excluir subtipo: ' . $conn->error]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Requisição inválida']);
}

$conn->close();
?> 