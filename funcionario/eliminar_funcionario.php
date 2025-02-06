<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
include_once '../includes/db_conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);

    error_log("Recebido pedido para eliminar ID: " . $id);

    if ($id > 0) {
        $query = "DELETE FROM funcionarios WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Funcionário eliminado com sucesso!"]);
        } else {
            error_log("Erro ao executar DELETE: " . $stmt->error);
            echo json_encode(["status" => "error", "message" => "Erro ao eliminar funcionário."]);
        }

        $stmt->close();
    } else {
        echo json_encode(["status" => "error", "message" => "ID inválido."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Requisição inválida."]);
}
?>
