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

    error_log("Recebido pedido para eliminar ID: " . $id);

    if ($id > 0) {
        $id = $conn->real_escape_string($id);
        $query = "DELETE FROM Cliente WHERE id = '$id'";
        
        if ($conn->query($query)) {
            echo json_encode(["status" => "success", "message" => "Cliente eliminado com sucesso!"]);
        } else {
            error_log("Erro ao executar DELETE: " . $conn->error);
            echo json_encode(["status" => "error", "message" => "Erro ao eliminar cliente."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "ID inválido."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Requisição inválida."]);
}
?>
