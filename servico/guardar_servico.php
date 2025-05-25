<?php
require_once '../includes/db_conexao.php';
session_start();

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $servico_id = $conn->real_escape_string($_POST['servico_id']);
    $nome = $conn->real_escape_string($_POST['nome']);
    $descricao = $conn->real_escape_string($_POST['descricao']);
    $preco = $conn->real_escape_string($_POST['preco']);
    $duracao = $conn->real_escape_string($_POST['duracao']);

    // Verifica se os campos obrigatórios foram preenchidos
    if (empty($servico_id) || empty($nome) || empty($descricao) || empty($preco) || empty($duracao)) {
        $_SESSION['error'] = 'Por favor, preencha todos os campos obrigatórios.';
        header('Location: adicionar_servico.php');
        exit();
    }

    // Verifica se o ID do serviço (categoria) existe na tabela servico
    $sql_check = "SELECT id FROM servico WHERE id = '$servico_id'";
    $result = $conn->query($sql_check);

    if ($result->num_rows === 0) {
        $_SESSION['error'] = 'O serviço selecionado não existe.';
        header('Location: adicionar_servico.php');
        exit();
    }

    // Verificar se já existe um serviço com o mesmo nome
    $sql_check = "SELECT id FROM servico_subtipo WHERE nome = '$nome' AND servico_id = '$servico_id'";
    $result_check = $conn->query($sql_check);

    if ($result_check->num_rows > 0) {
        $_SESSION['mensagem'] = "Já existe um serviço com este nome.";
        header("Location: adicionar_servico.php");
        exit();
    }

    // Inserir o novo serviço
    $sql = "INSERT INTO servico_subtipo (servico_id, nome, descricao, preco, duracao) VALUES ('$servico_id', '$nome', '$descricao', '$preco', '$duracao')";
    
    if ($conn->query($sql)) {
        $_SESSION['success'] = "Serviço adicionado com sucesso!";
        header("Location: servico.php");
        exit();
    } else {
        $_SESSION['mensagem'] = "Erro ao adicionar serviço: " . $conn->error;
        header("Location: adicionar_servico.php");
        exit();
    }
}
?>
