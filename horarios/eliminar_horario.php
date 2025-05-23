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
    $id = intval($_POST['id']);

    error_log("Recebido pedido para eliminar horário ID: " . $id);

    if ($id > 0) {
        $query = "DELETE FROM agenda_funcionario WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Horário eliminado com sucesso!";
            echo json_encode(["status" => "success", "message" => "Horário eliminado com sucesso!"]);
        } else {
            error_log("Erro ao executar DELETE: " . $stmt->error);
            $_SESSION['mensagem'] = "Erro ao eliminar horário.";
            echo json_encode(["status" => "error", "message" => "Erro ao eliminar horário."]);
        }

        $stmt->close();
    } else {
        $_SESSION['mensagem'] = "ID inválido.";
        echo json_encode(["status" => "error", "message" => "ID inválido."]);
    }
} else {
    $_SESSION['mensagem'] = "Requisição inválida.";
    echo json_encode(["status" => "error", "message" => "Requisição inválida."]);
}
?> 