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
    $id = intval($_POST['id']);

    if ($id > 0) {
        // Verificar se o subtipo existe
        $check_query = "SELECT id FROM servico_subtipo WHERE id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("i", $id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows === 0) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Subtipo de serviço não encontrado']);
            exit();
        }

        // Eliminar o subtipo
        $delete_query = "DELETE FROM servico_subtipo WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("i", $id);

        if ($delete_stmt->execute()) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Subtipo de serviço eliminado com sucesso']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Erro ao eliminar subtipo de serviço']);
        }

        $delete_stmt->close();
    } else {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'ID inválido']);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Requisição inválida']);
}

$conn->close();
?> 