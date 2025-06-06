<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Coleta e sanitiza os dados do formulário
    $nome = $conn->real_escape_string($_POST['nome'] ?? '');
    $email = $conn->real_escape_string($_POST['email'] ?? '');
    $morada = $conn->real_escape_string($_POST['morada'] ?? '');
    $localidade = $conn->real_escape_string($_POST['localidade'] ?? '');
    $telefone1 = $conn->real_escape_string($_POST['telefone1'] ?? '');
    $telefone2 = $conn->real_escape_string($_POST['telefone2'] ?? '');

    // Validação básica (pode adicionar mais validações conforme necessário)
    if (empty($nome) || empty($email) || empty($morada) || empty($localidade) || empty($telefone1)) {
        $_SESSION['mensagem'] = "Por favor, preencha todos os campos obrigatórios (Nome, Email, Morada, Localidade, Telefone 1).";
    } else {
        // Prepara e executa a query SQL para inserir o novo funcionário
        $sql = "INSERT INTO funcionario (nome, email, morada, localidade, telefone1, telefone2) VALUES ('$nome', '$email', '$morada', '$localidade', '$telefone1', '$telefone2')";

        if ($conn->query($sql) === TRUE) {
            $_SESSION['success'] = "Funcionário adicionado com sucesso!";
        } else {
            $_SESSION['mensagem'] = "Erro ao adicionar funcionário: " . $conn->error;
            error_log("Erro ao inserir funcionário: " . $conn->error);
        }
    }

    $conn->close();

    // Redireciona de volta para a página de listagem de funcionários
    header("Location: funcionario.php");
    exit();
} else {
    // Se a requisição não for POST, redireciona de volta para o formulário ou outra página
    header("Location: adicionar_funcionario.php");
    exit();
}
?>
