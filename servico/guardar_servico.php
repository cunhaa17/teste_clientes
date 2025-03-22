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
    $servico_id = trim($_POST['servico_id']); // ID da categoria (serviço)
    $nome = trim($_POST['nome']);
    $descricao = trim($_POST['descricao']);
    $preco = trim($_POST['preco']);
    $duracao = trim($_POST['duracao']);

    // Verifica se os campos obrigatórios foram preenchidos
    if (empty($servico_id) || empty($nome) || empty($descricao) || empty($preco) || empty($duracao)) {
        $_SESSION['error'] = 'Por favor, preencha todos os campos obrigatórios.';
        header('Location: adicionar_servico.php');
        exit();
    }

    // Verifica se o ID do serviço (categoria) existe na tabela servico
    $query = "SELECT id FROM servico WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $servico_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['error'] = 'O serviço selecionado não existe.';
        header('Location: adicionar_servico.php');
        exit();
    }

    // Insere na tabela servico_subtipo associando ao servico_id
    $query = "INSERT INTO servico_subtipo (servico_id, nome, descricao, preco, duracao) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("issss", $servico_id, $nome, $descricao, $preco, $duracao);

    if ($stmt->execute()) {
        $_SESSION['success'] = 'Serviço adicionado com sucesso!';
        header("Location: servico.php");
        exit();
    } else {
        $_SESSION['error'] = 'Erro ao adicionar serviço. Tente novamente.';
    }

    // Fecha a conexão
    $stmt->close();
    $conn->close();
    header('Location: servico.php');
    exit();
}
?>
