<?php
// Inclui o ficheiro de conexão à base de dados
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

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $telefone = trim($_POST['telefone']);

    // Verifica se os campos obrigatórios foram preenchidos
    if (empty($nome) || empty($email) || empty($telefone)) {
        $_SESSION['error'] = 'Por favor, preencha todos os campos obrigatórios.';
        header('Location: adicionar_cliente.php');
        exit();
    }

    // Verifica se o email ou telefone já existem no banco de dados
    $query = "SELECT * FROM cliente WHERE email = ? OR telefone = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $email, $telefone);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['error'] = 'O email ou telefone já estão registados.';
        header('Location: adicionar_cliente.php');
        exit();
    }

    // Gera a data atual no formato YYYY-MM-DD
    $data_registo = date('Y-m-d');

    // Insere o novo cliente no banco de dados
    $query = "INSERT INTO cliente (nome, email, telefone, data_registo) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssss", $nome, $email, $telefone, $data_registo);

    if ($stmt->execute()) {
        $_SESSION['success'] = 'Cliente adicionado com sucesso!';
        header("Location: clientes.php");
        exit();
    } else {
        $_SESSION['error'] = 'Erro ao adicionar cliente. Tente novamente.';
    }

    // Redireciona para a página de adicionar cliente
    header('Location: clientes.php');
    exit();
}
?>
