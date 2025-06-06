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
$id = $conn->real_escape_string($_POST['id']);
$servico_id = $conn->real_escape_string($_POST['servico_id']);
$nome = trim($conn->real_escape_string($_POST['nome']));
$descricao = trim($conn->real_escape_string($_POST['descricao']));
$preco = str_replace(',', '.', $conn->real_escape_string($_POST['preco']));
$duracao = intval($_POST['duracao']);

// Log dos valores recebidos
error_log("POST data: " . print_r($_POST, true));
error_log("Processed values - ID: $id, Nome: $nome, Descrição: $descricao, Preço: $preco, Duração: $duracao");

// Validação dos campos obrigatórios
if (empty($nome) || empty($descricao) || empty($preco) || empty($duracao)) {
    $_SESSION['mensagem'] = "Todos os campos são obrigatórios.";
    header("Location: atualizar_servico.php?id=" . $id);
    exit();
}

// Validação do preço
if (!is_numeric($preco) || $preco <= 0) {
    $_SESSION['mensagem'] = "O preço deve ser um valor positivo.";
    header("Location: atualizar_servico.php?id=" . $id);
    exit();
}

// Validação da duração
if ($duracao <= 0) {
    $_SESSION['mensagem'] = "A duração deve ser um valor positivo.";
    header("Location: atualizar_servico.php?id=" . $id);
    exit();
}

// Verifica se o serviço existe
$check_query = "SELECT * FROM servico_subtipo WHERE id = '$id'";
$result = $conn->query($check_query);

if ($result->num_rows === 0) {
    $_SESSION['mensagem'] = "Serviço não encontrado.";
    header("Location: servico.php");
    exit();
}

// Obtém os valores atuais do serviço
$servico_atual = $result->fetch_assoc();
error_log("Current values - Nome: {$servico_atual['nome']}, Descrição: {$servico_atual['descricao']}, Preço: {$servico_atual['preco']}, Duração: {$servico_atual['duracao']}");

// Verifica se já existe outro serviço com o mesmo nome no mesmo tipo
$check_nome_query = "SELECT id FROM servico_subtipo WHERE nome = '$nome' AND servico_id = '$servico_id' AND id != '$id'";
$result = $conn->query($check_nome_query);

if ($result->num_rows > 0) {
    $_SESSION['mensagem'] = "Já existe um serviço com este nome neste tipo.";
    header("Location: atualizar_servico.php?id=" . $id);
    exit();
}

// Atualiza o serviço
$update_query = "UPDATE servico_subtipo 
                SET nome = '$nome', 
                    descricao = '$descricao', 
                    preco = $preco, 
                    duracao = $duracao 
                WHERE id = '$id'";

error_log("Update query: " . $update_query);

if ($conn->query($update_query)) {
    $_SESSION['mensagem'] = "Serviço atualizado com sucesso!";
    header("Location: servico.php");
} else {
    error_log("Database error: " . $conn->error);
    $_SESSION['mensagem'] = "Erro ao atualizar serviço: " . $conn->error;
    header("Location: atualizar_servico.php?id=" . $id);
}

$conn->close();
?> 