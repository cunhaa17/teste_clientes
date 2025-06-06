<?php
// Inclui o ficheiro que contém a conexão com a base de dados
include '../includes/db_conexao.php';

// Inicia uma sessão para manter informações durante o uso do sistema
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

// Verifica se o formulário foi enviado via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['mensagem'] = "Método inválido.";
    header("Location: servico.php");
    exit();
}

// Verifica o token CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['mensagem'] = "Token de segurança inválido.";
    header("Location: servico.php");
    exit();
}

// Obtém e sanitiza os dados do formulário
$servico_id = $conn->real_escape_string($_POST['servico_id']);
$nome = trim($conn->real_escape_string($_POST['nome']));
$descricao = trim($conn->real_escape_string($_POST['descricao']));
$preco = str_replace(',', '.', $conn->real_escape_string($_POST['preco']));
$duracao = intval($_POST['duracao']);

// Validação dos campos obrigatórios
if (empty($nome) || empty($descricao) || empty($preco) || empty($duracao)) {
    $_SESSION['mensagem'] = "Todos os campos são obrigatórios.";
    header("Location: adicionar_servico.php");
    exit();
}

// Validação do preço
if (!is_numeric($preco) || $preco <= 0) {
    $_SESSION['mensagem'] = "O preço deve ser um valor positivo.";
    header("Location: adicionar_servico.php");
    exit();
}

// Validação da duração
if ($duracao <= 0) {
    $_SESSION['mensagem'] = "A duração deve ser um valor positivo.";
    header("Location: adicionar_servico.php");
    exit();
}

// Verifica se já existe um serviço com o mesmo nome no mesmo tipo
$check_nome_query = "SELECT id FROM servico_subtipo WHERE nome = '$nome' AND servico_id = '$servico_id'";
$result = $conn->query($check_nome_query);

if ($result->num_rows > 0) {
    $_SESSION['mensagem'] = "Já existe um serviço com este nome neste tipo.";
    header("Location: adicionar_servico.php");
    exit();
}

// Insere o novo serviço
$insert_query = "INSERT INTO servico_subtipo (servico_id, nome, descricao, preco, duracao) 
                VALUES ('$servico_id', '$nome', '$descricao', $preco, $duracao)";

if ($conn->query($insert_query)) {
    $_SESSION['mensagem'] = "Serviço adicionado com sucesso!";
    header("Location: servico.php");
} else {
    $_SESSION['mensagem'] = "Erro ao adicionar serviço: " . $conn->error;
    header("Location: adicionar_servico.php");
}

$conn->close();
?>
