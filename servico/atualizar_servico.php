<?php
// Inclui o ficheiro que contém a conexão com a base de dados
include '../includes/db_conexao.php';
include '../includes/layout.php';

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

// Cria uma query SQL segura para selecionar os dados do cliente com o ID fornecido
$query = "SELECT * FROM servico_subtipo WHERE id = ?";
$stmt = $conn->prepare($query); // Prepara a query para execução
$stmt->bind_param("i", $id); // Associa o parâmetro do ID como inteiro à query
$stmt->execute(); // Executa a query preparada
$result = $stmt->get_result(); // Obtém o resultado da query executada
$cliente = $result->fetch_assoc(); // Converte o resultado em um array associativo

// Verifica se o cliente foi encontrado na base de dados
if (!$cliente) {
    // Encerra o script com uma mensagem caso o cliente não seja encontrado
    die("Servico não encontrado.");
}

// Verifica se já existe um CSRF token na sessão, e gera um novo caso não exista
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Gera um token seguro aleatório
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Atualizar Serviço</title> <!-- Título da página -->
    <!-- Inclui o CSS do Bootstrap para estilos prontos -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- Container principal para centralizar o conteúdo na página -->
    <div class="container mt-4">
        <!-- Cabeçalho principal da página -->
        <h1 class="text-center">Atualizar Serviço</h1>
        
        <!-- Formulário para atualizar os dados do cliente -->
        <form action="guardar_servico.php" method="POST" class="mt-4">
            <!-- Campo oculto para enviar o ID do cliente -->
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($servico['id']); ?>">
            <!-- Campo oculto para enviar o token CSRF -->
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <!-- Campo para atualizar o nome do cliente -->
            <div class="mb-3">
                <label>Nome:</label> <!-- Rótulo para o campo de nome -->
                <input type="text" name="nome" value="<?php echo htmlspecialchars($servico['Nome']); ?>" class="form-control" required> <!-- Entrada de texto com valor inicial preenchido -->
            </div>

            <!-- Campo para atualizar o email do cliente -->
            <div class="mb-3">
                <label>Descrição</label> <!-- Rótulo para o campo de email -->
                <input type="text" name="descricao" value="<?php echo htmlspecialchars($servico['Descricao']); ?>" class="form-control" required> <!-- Entrada de email com validação -->
            </div>

            <!-- Campo para atualizar o telefone do cliente -->
            <div class="mb-3">
                <label>Preço</label> <!-- Rótulo para o campo de telefone -->
                <input type="float" name="preco" value="<?php echo htmlspecialchars($servico['Preco']); ?>" class="form-control" required> <!-- Entrada de telefone com validação de padrão -->
            </div>

            <div class="mb-3">
                <label>Duração</label> <!-- Rótulo para o campo de telefone -->
                <input type="int" name="duracao" value="<?php echo htmlspecialchars($servico['Duracao']); ?>" class="form-control" required> <!-- Entrada de telefone com validação de padrão -->
            </div>

            <!-- Botão para enviar o formulário e atualizar os dados -->
            <button type="submit" class="btn btn-primary">Atualizar</button>
        </form>
    </div>
</body>
</html>
