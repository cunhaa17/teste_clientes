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
    $morada = trim($_POST['morada']);
    $localidade = trim($_POST['localidade']);
    $telefone1 = trim($_POST['telefone1']);
    $telefone2 = trim($_POST['telefone2']);
    $cargo = trim($_POST['cargo']);

    // Verifica se os campos obrigatórios foram preenchidos
    if (empty($nome) || empty($email) || empty($telefone1)) {
        $_SESSION['error'] = 'Por favor, preencha todos os campos obrigatórios.';
        header('Location: adicionar_funcionario.php');
        exit();
    }

    // Escapa os valores para evitar SQL injection
    $nome = $conn->real_escape_string($nome);
    $email = $conn->real_escape_string($email);
    $morada = $conn->real_escape_string($morada);
    $localidade = $conn->real_escape_string($localidade);
    $telefone1 = $conn->real_escape_string($telefone1);
    $telefone2 = $conn->real_escape_string($telefone2);
    $cargo = $conn->real_escape_string($cargo);

    // Verifica se o email ou telefone já existem no banco de dados
    $query = "SELECT * FROM funcionario WHERE email = '$email' OR telefone1 = '$telefone1'";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $_SESSION['error'] = 'O email ou telefone já estão registados.';
        header('Location: adicionar_funcionario.php');
        exit();
    }

    // Insere o novo funcionario no banco de dados
    $query = "INSERT INTO funcionario (nome, email, morada, localidade, telefone1, telefone2, cargo) VALUES ('$nome', '$email', '$morada', '$localidade', '$telefone1', '$telefone2', '$cargo')";
    
    if ($conn->query($query)) {
        $_SESSION['success'] = 'Funcionário adicionado com sucesso!';
        header("Location: funcionario.php");
        exit();
    } else {
        $_SESSION['error'] = 'Erro ao adicionar funcionário. Tente novamente.';
    }

    // Redireciona para a página de adicionar funcionário
    header('Location: funcionario.php');
    exit();
}
?>
