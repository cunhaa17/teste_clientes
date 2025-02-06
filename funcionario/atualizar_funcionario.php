<?php
// Inclui o ficheiro que contém a conexão com a base de dados
include '../includes/db_conexao.php';
include '../includes/sidebar.php';

// Inicia uma sessão para manter informações durante o uso do sistema
session_start();

// Verifica se o ID foi fornecido no POST e se é um número inteiro válido
if (!isset($_POST['id']) || !filter_var($_POST['id'], FILTER_VALIDATE_INT)) {
    // Encerra o script com uma mensagem caso o ID seja inválido
    die("ID inválido.");
}

// Obtém o ID do funcionário enviado pelo formulário
$id = $_POST['id'];

// Cria uma query SQL segura para selecionar os dados do funcionário com o ID fornecido
$query = "SELECT * FROM funcionarios WHERE id = ?";
$stmt = $conn->prepare($query); // Prepara a query para execução
$stmt->bind_param("i", $id); // Associa o parâmetro do ID como inteiro à query
$stmt->execute(); // Executa a query preparada
$result = $stmt->get_result(); // Obtém o resultado da query executada
$funcionario = $result->fetch_assoc(); // Converte o resultado em um array associativo

// Verifica se o funcionário foi encontrado na base de dados
if (!$funcionario) {
    // Encerra o script com uma mensagem caso o funcionário não seja encontrado
    die("Funcionário não encontrado.");
}

// Verifica se já existe um CSRF token na sessão, e gera um novo caso não exista
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Gera um token seguro aleatório
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Atualizar Funcionário</title> <!-- Título da página -->
    <!-- Inclui o CSS do Bootstrap para estilos prontos -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- Container principal para centralizar o conteúdo na página -->
    <div class="container mt-4">
        <!-- Cabeçalho principal da página -->
        <h1 class="text-center">Atualizar Funcionário</h1>
        
        <!-- Formulário para atualizar os dados do funcionário -->
        <form action="guardar_funcionario.php" method="POST" class="mt-4">
            <!-- Campo oculto para enviar o ID do funcionário -->
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($funcionario['id']); ?>">
            <!-- Campo oculto para enviar o token CSRF -->
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <!-- Campo para atualizar o nome do funcionário -->
            <div class="mb-3">
                <label>Nome:</label> <!-- Rótulo para o campo de nome -->
                <input type="text" name="nome" value="<?php echo htmlspecialchars($funcionario['nome']); ?>" class="form-control" required> <!-- Entrada de texto com valor inicial preenchido -->
            </div>

            <!-- Campo para atualizar o email do funcionário -->
            <div class="mb-3">
                <label>Email:</label> <!-- Rótulo para o campo de email -->
                <input type="email" name="email" value="<?php echo htmlspecialchars($funcionario['email']); ?>" class="form-control" required> <!-- Entrada de email com validação -->
            </div>

            <!-- Campo para atualizar o telefone 1 do funcionário -->
            <div class="mb-3">
                <label>Telefone 1:</label> <!-- Rótulo para o campo de telefone -->
                <input type="tel" name="telefone1" value="<?php echo htmlspecialchars($funcionario['telefone1']); ?>" class="form-control" required pattern="[0-9]{9}"> <!-- Entrada de telefone com validação de padrão -->
            </div>

            <!-- Campo para atualizar o telefone 2 do funcionário -->
            <div class="mb-3">
                <label>Telefone 2:</label> <!-- Rótulo para o campo de telefone -->
                <input type="tel" name="telefone2" value="<?php echo htmlspecialchars($funcionario['telefone2']); ?>" class="form-control" pattern="[0-9]{9}"> <!-- Entrada de telefone com validação de padrão -->
            </div>

            <!-- Campo para atualizar o cargo do funcionário -->
            <div class="mb-3">
                <label>Cargo:</label> <!-- Rótulo para o campo de cargo -->
                <input type="text" name="cargo" value="<?php echo htmlspecialchars($funcionario['cargo']); ?>" class="form-control" required> <!-- Entrada de texto com valor inicial preenchido -->
            </div>

            <!-- Botão para enviar o formulário e atualizar os dados -->
            <button type="submit" class="btn btn-primary">Atualizar</button>
        </form>
    </div>
</body>
</html>
