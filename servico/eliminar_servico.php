<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verifica se a sessão está iniciada corretamente
if (!isset($_SESSION['utilizador_id'])) {
    header("Location: ../login.php");
    exit();
}

// Verifica se o usuário é do tipo admin
if ($_SESSION['utilizador_tipo'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

header('Content-Type: application/json');
include_once '../includes/db_conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = $conn->real_escape_string($_POST['id']);

    // Verificar se o serviço existe
    $sql_check = "SELECT id FROM servico WHERE id = '$id'";
    $result_check = $conn->query($sql_check);

    if ($result_check->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Serviço não encontrado']);
        exit();
    }

    // Excluir o serviço
    $sql_delete = "DELETE FROM servico WHERE id = '$id'";
    
    if ($conn->query($sql_delete)) {
        echo json_encode(['status' => 'success', 'message' => 'Serviço excluído com sucesso']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Erro ao excluir serviço: ' . $conn->error]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Requisição inválida']);
}

$conn->close();
?>
