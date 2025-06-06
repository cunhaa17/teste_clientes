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

$title = "Atualizar Serviço";

// Verifica se o ID foi fornecido via GET
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $_SESSION['mensagem'] = "ID inválido.";
    header("Location: servico.php");
    exit();
}

// Obtém o ID do serviço
$id = $conn->real_escape_string($_GET['id']);

// Cria uma query SQL para selecionar os dados do serviço com o ID fornecido
$query = "SELECT s.*, ss.nome as servico_nome 
          FROM servico_subtipo s 
          JOIN servico ss ON s.servico_id = ss.id 
          WHERE s.id = '$id'";
$result = $conn->query($query);
$servico = $result->fetch_assoc();

// Verifica se o serviço foi encontrado na base de dados
if (!$servico) {
    $_SESSION['mensagem'] = "Serviço não encontrado.";
    header("Location: servico.php");
    exit();
}

// Verifica se já existe um CSRF token na sessão, e gera um novo caso não exista
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

ob_start();
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Atualizar Serviço</h1>
        <a href="servico.php" class="btn btn-secondary">Voltar</a>
    </div>

    <?php if (isset($_SESSION['mensagem'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['mensagem'];
            unset($_SESSION['mensagem']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form action="atualizar_servico_process.php" method="POST" id="updateForm">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($servico['id']); ?>">
                <input type="hidden" name="servico_id" value="<?php echo htmlspecialchars($servico['servico_id']); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <div class="mb-3">
                    <label class="form-label">Serviço Principal</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($servico['servico_nome']); ?>" readonly>
                </div>

                <div class="mb-3">
                    <label for="nome" class="form-label">Nome do Subtipo *</label>
                    <input type="text" class="form-control" id="nome" name="nome" 
                           value="<?php echo htmlspecialchars($servico['nome']); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="descricao" class="form-label">Descrição *</label>
                    <textarea class="form-control" id="descricao" name="descricao" rows="3" required><?php echo htmlspecialchars($servico['descricao']); ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="preco" class="form-label">Preço (€) *</label>
                    <input type="text" class="form-control" id="preco" name="preco" 
                           value="<?php echo number_format($servico['preco'], 2, ',', ''); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="duracao" class="form-label">Duração (minutos) *</label>
                    <input type="number" min="1" class="form-control" id="duracao" name="duracao" 
                           value="<?php echo intval($servico['duracao']); ?>" required>
                </div>

                <button type="submit" class="btn btn-primary">Atualizar Serviço</button>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('updateForm').addEventListener('submit', function(e) {
    // Garantir que o preço está no formato correto
    var preco = document.getElementById('preco').value;
    if (preco) {
        // Substituir vírgula por ponto
        preco = preco.replace(',', '.');
        // Garantir que tem 2 casas decimais
        preco = parseFloat(preco).toFixed(2);
        document.getElementById('preco').value = preco;
    }
    
    // Garantir que a duração é um número inteiro
    var duracao = document.getElementById('duracao').value;
    if (duracao) {
        document.getElementById('duracao').value = parseInt(duracao);
    }
});
</script>

<?php
$content = ob_get_clean();
include '../includes/layout.php';
?>
