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

include_once '../includes/db_conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    error_log("Recebido pedido para eliminar funcionário ID: " . $id);

    if ($id > 0) {
        $id = $conn->real_escape_string($id);
        
        // Verificar se o funcionário existe
        $check_query = "SELECT id FROM funcionario WHERE id = '$id'";
        $check_result = $conn->query($check_query);
        
        if ($check_result->num_rows === 0) {
            error_log("Funcionário não encontrado: " . $id);
            $_SESSION['mensagem'] = "Funcionário não encontrado";
        } else {
            // Tentar eliminar o funcionário
            $query = "DELETE FROM funcionario WHERE id = '$id'";
            error_log("Executando query: " . $query);
            
            if ($conn->query($query)) {
                error_log("Funcionário eliminado com sucesso: " . $id);
                $_SESSION['success'] = "Funcionário eliminado com sucesso!";
            } else {
                error_log("Erro ao executar DELETE: " . $conn->error);
                $_SESSION['mensagem'] = "Erro ao eliminar funcionário: " . $conn->error;
            }
        }
    } else {
        error_log("ID inválido recebido: " . $id);
        $_SESSION['mensagem'] = "ID inválido";
    }
} else {
    error_log("Requisição inválida. Método: " . $_SERVER['REQUEST_METHOD'] . ", POST data: " . print_r($_POST, true));
    $_SESSION['mensagem'] = "Requisição inválida";
}

$conn->close();

// Redireciona de volta para a página de listagem de funcionários
header("Location: funcionario.php");
exit();
?>
