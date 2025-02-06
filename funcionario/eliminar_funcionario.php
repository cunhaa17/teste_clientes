<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
include_once '../includes/db_conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);

    if ($id > 0) {
        $query = "DELETE FROM funcionario WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Funcionário eliminado com sucesso!"]);
        } else {
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
