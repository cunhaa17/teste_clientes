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

    if ($id > 0) {
        $query = "DELETE FROM funcionario WHERE id = '$id'";
        
        if ($conn->query($query)) {
            echo json_encode(["status" => "success", "message" => "Funcionário eliminado com sucesso!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Erro ao eliminar funcionário."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "ID inválido."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Requisição inválida."]);
}
?>
