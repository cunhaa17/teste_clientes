<?php
// Inclui o ficheiro que contém a conexão com a base de dados
include '../includes/db_conexao.php';
include '../includes/sidebar.php';

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

// Verifica se o ID foi fornecido no POST e se é um número inteiro válido
if (!isset($_POST['id']) || !filter_var($_POST['id'], FILTER_VALIDATE_INT)) {
    // Encerra o script com uma mensagem caso o ID seja inválido
    die("ID inválido.");
}

// Obtém o ID do cliente enviado pelo formulário
$id = $_POST['id'];

// Escapa o ID para evitar SQL injection
$id = $conn->real_escape_string($id);

// Cria uma query SQL segura para selecionar os dados do cliente com o ID fornecido
$query = "SELECT * FROM cliente WHERE id = '$id'";
$result = $conn->query($query);
$cliente = $result->fetch_assoc(); // Converte o resultado em um array associativo

// Verifica se o cliente foi encontrado na base de dados
if (!$cliente) {
    // Encerra o script com uma mensagem caso o cliente não seja encontrado
    die("Cliente não encontrado.");
}

// Processa o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome'])) {
    // Escapa os valores para evitar SQL injection
    $nome = $conn->real_escape_string($_POST['nome']);
    $email = $conn->real_escape_string($_POST['email']);
    $telefone = $conn->real_escape_string($_POST['telefone']);

    // Verifica se o email ou telefone já existem para outro cliente
    $check_query = "SELECT * FROM cliente WHERE (email = '$email' OR telefone = '$telefone') AND id != '$id'";
    $check_result = $conn->query($check_query);

    if ($check_result->num_rows > 0) {
        $_SESSION['error'] = 'O email ou telefone já estão registados para outro cliente.';
    } else {
        // Atualiza os dados do cliente
        $update_query = "UPDATE cliente SET nome = '$nome', email = '$email', telefone = '$telefone' WHERE id = '$id'";
        
        if ($conn->query($update_query)) {
            $_SESSION['success'] = 'Cliente atualizado com sucesso!';
            header('Location: clientes.php');
            exit();
        } else {
            $_SESSION['error'] = 'Erro ao atualizar cliente: ' . $conn->error;
        }
    }
}

// Verifica se já existe um CSRF token na sessão, e gera um novo caso não exista
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Gera um token seguro aleatório
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Atualizar Cliente</title> <!-- Título da página -->
    <!-- Inclui o CSS do Bootstrap para estilos prontos -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- Container principal para centralizar o conteúdo na página -->
    <div class="container mt-4">
        <!-- Cabeçalho principal da página -->
        <h1 class="text-center">Atualizar Cliente</h1>
        
        <!-- Formulário para atualizar os dados do cliente -->
        <form action="guardar_cliente.php" method="POST" class="mt-4">
            <!-- Campo oculto para enviar o ID do cliente -->
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($cliente['id']); ?>">
            <!-- Campo oculto para enviar o token CSRF -->
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <!-- Campo para atualizar o nome do cliente -->
            <div class="mb-3">
                <label>Nome:</label> <!-- Rótulo para o campo de nome -->
                <input type="text" name="nome" value="<?php echo htmlspecialchars($cliente['Nome']); ?>" class="form-control" required> <!-- Entrada de texto com valor inicial preenchido -->
            </div>

            <!-- Campo para atualizar o email do cliente -->
            <div class="mb-3">
                <label>Email:</label> <!-- Rótulo para o campo de email -->
                <input type="email" name="email" value="<?php echo htmlspecialchars($cliente['Email']); ?>" class="form-control" required> <!-- Entrada de email com validação -->
            </div>

            <!-- Campo para atualizar o telefone do cliente -->
            <div class="mb-3">
                <label>Telefone:</label> <!-- Rótulo para o campo de telefone -->
                <input type="tel" name="telefone" value="<?php echo htmlspecialchars($cliente['Telefone']); ?>" class="form-control" required pattern="[0-9]{9}"> <!-- Entrada de telefone com validação de padrão -->
            </div>

            <!-- Botão para enviar o formulário e atualizar os dados -->
            <button type="submit" class="btn btn-primary">Atualizar</button>
        </form>
    </div>
</body>
</html>
