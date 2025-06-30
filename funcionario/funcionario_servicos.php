<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

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

include_once '../includes/db_conexao.php';
$title = 'Associar Funcionários a Serviços';

// Mensagens de feedback
$mensagem = isset($_SESSION['mensagem']) ? $_SESSION['mensagem'] : '';
$success_message = isset($_SESSION['success']) ? $_SESSION['success'] : '';
unset($_SESSION['mensagem']);
unset($_SESSION['success']);

// Processar formulário de associação/desassociação
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['associar']) && isset($_POST['funcionario_id']) && isset($_POST['servico_subtipo_id'])) {
        // Associar funcionário a serviço
        $funcionario_id = $conn->real_escape_string($_POST['funcionario_id']);
        $servico_subtipo_id = $conn->real_escape_string($_POST['servico_subtipo_id']);
        
        // Verificar se já existe a associação
        $check_sql = "SELECT * FROM funcionario_subtipo WHERE funcionario_id = '$funcionario_id' AND servico_subtipo_id = '$servico_subtipo_id'";
        $check_result = $conn->query($check_sql);
        
        if ($check_result->num_rows > 0) {
            $_SESSION['mensagem'] = "Esta associação já existe!";
        } else {
            $insert_sql = "INSERT INTO funcionario_subtipo (funcionario_id, servico_subtipo_id) VALUES ('$funcionario_id', '$servico_subtipo_id')";
            
            if ($conn->query($insert_sql)) {
                $_SESSION['success'] = "Funcionário associado ao serviço com sucesso!";
            } else {
                $_SESSION['mensagem'] = "Erro ao associar: " . $conn->error;
            }
        }
        
        header("Location: funcionario_servicos.php?funcionario_id=" . $funcionario_id);
        exit();
    }
    
    if (isset($_POST['remover']) && isset($_POST['funcionario_id']) && isset($_POST['servico_subtipo_id'])) {
        // Remover associação
        $funcionario_id = $conn->real_escape_string($_POST['funcionario_id']);
        $servico_subtipo_id = $conn->real_escape_string($_POST['servico_subtipo_id']);
        
        $delete_sql = "DELETE FROM funcionario_subtipo WHERE funcionario_id = '$funcionario_id' AND servico_subtipo_id = '$servico_subtipo_id'";
        
        if ($conn->query($delete_sql)) {
            $_SESSION['success'] = "Associação removida com sucesso!";
        } else {
            $_SESSION['mensagem'] = "Erro ao remover associação: " . $conn->error;
        }
        
        header("Location: funcionario_servicos.php?funcionario_id=" . $funcionario_id);
        exit();
    }
}

// Selecionar todos os funcionários
$func_sql = "SELECT id, nome FROM funcionario ORDER BY nome";
$func_result = $conn->query($func_sql);
$funcionarios = $func_result->fetch_all(MYSQLI_ASSOC);

// Se um funcionário foi selecionado
$funcionario_selecionado = null;
$servicos_associados = [];
$servicos_disponiveis = [];

if (isset($_GET['funcionario_id']) && !empty($_GET['funcionario_id'])) {
    $funcionario_id = $conn->real_escape_string($_GET['funcionario_id']);
    
    // Obter dados do funcionário selecionado
    $func_sql = "SELECT id, nome FROM funcionario WHERE id = '$funcionario_id'";
    $func_result = $conn->query($func_sql);
    $funcionario_selecionado = $func_result->fetch_assoc();
    
    if ($funcionario_selecionado) {
        // Obter serviços já associados ao funcionário
        $serv_assoc_sql = "SELECT ss.id, ss.nome, s.nome as categoria, ss.preco, ss.duracao 
                           FROM servico_subtipo ss
                           JOIN servico s ON ss.servico_id = s.id
                           JOIN funcionario_subtipo fs ON ss.id = fs.servico_subtipo_id
                           WHERE fs.funcionario_id = '$funcionario_id'
                           ORDER BY s.nome, ss.nome";
        $serv_assoc_result = $conn->query($serv_assoc_sql);
        $servicos_associados = $serv_assoc_result->fetch_all(MYSQLI_ASSOC);
        
        // Obter serviços disponíveis (não associados)
        $serv_disp_sql = "SELECT ss.id, ss.nome, s.nome as categoria, ss.preco, ss.duracao 
                          FROM servico_subtipo ss
                          JOIN servico s ON ss.servico_id = s.id
                          WHERE ss.id NOT IN (
                              SELECT servico_subtipo_id FROM funcionario_subtipo WHERE funcionario_id = '$funcionario_id'
                          )
                          ORDER BY s.nome, ss.nome";
        $serv_disp_result = $conn->query($serv_disp_sql);
        $servicos_disponiveis = $serv_disp_result->fetch_all(MYSQLI_ASSOC);
    }
}

$conn->close();

ob_start();
?>

<div class="container-fluid py-4">
    
    <?php if ($mensagem): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($mensagem); ?></div>
    <?php endif; ?>
    
    <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <h3>Selecione um Funcionário</h3>
        </div>
        <div class="card-body">
            <form method="GET" action="funcionario_servicos.php" class="mb-3">
                <div class="row">
                    <div class="col-md-8">
                        <select name="funcionario_id" class="form-select form-select-lg">
                            <option value="">-- Selecione um Funcionário --</option>
                            <?php foreach ($funcionarios as $funcionario): ?>
                                <option value="<?php echo $funcionario['id']; ?>" <?php echo (isset($_GET['funcionario_id']) && $_GET['funcionario_id'] == $funcionario['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($funcionario['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary btn-lg">Selecionar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <?php if ($funcionario_selecionado): ?>
        <div class="row">
            <!-- Serviços já associados -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h3>Serviços Associados a <?php echo htmlspecialchars($funcionario_selecionado['nome']); ?></h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($servicos_associados)): ?>
                            <p class="text-muted">Não há serviços associados a este funcionário.</p>
                        <?php else: ?>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Categoria</th>
                                        <th>Serviço</th>
                                        <th>Preço</th>
                                        <th>Duração</th>
                                        <th>Ação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($servicos_associados as $servico): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($servico['categoria']); ?></td>
                                            <td><?php echo htmlspecialchars($servico['nome']); ?></td>
                                            <td><?php echo htmlspecialchars($servico['preco']); ?> €</td>
                                            <td><?php echo htmlspecialchars($servico['duracao']); ?> min</td>
                                            <td>
                                                <form method="POST" onsubmit="return confirm('Tem certeza que deseja remover esta associação?');">
                                                    <input type="hidden" name="funcionario_id" value="<?php echo $funcionario_selecionado['id']; ?>">
                                                    <input type="hidden" name="servico_subtipo_id" value="<?php echo $servico['id']; ?>">
                                                    <button type="submit" name="remover" class="btn btn-danger btn-sm">Remover</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Serviços disponíveis para associar -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3>Serviços Disponíveis para Associar</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($servicos_disponiveis)): ?>
                            <p class="text-muted">Não há mais serviços disponíveis para associar.</p>
                        <?php else: ?>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Categoria</th>
                                        <th>Serviço</th>
                                        <th>Preço</th>
                                        <th>Duração</th>
                                        <th>Ação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($servicos_disponiveis as $servico): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($servico['categoria']); ?></td>
                                            <td><?php echo htmlspecialchars($servico['nome']); ?></td>
                                            <td><?php echo htmlspecialchars($servico['preco']); ?> €</td>
                                            <td><?php echo htmlspecialchars($servico['duracao']); ?> min</td>
                                            <td>
                                                <form method="POST">
                                                    <input type="hidden" name="funcionario_id" value="<?php echo $funcionario_selecionado['id']; ?>">
                                                    <input type="hidden" name="servico_subtipo_id" value="<?php echo $servico['id']; ?>">
                                                    <button type="submit" name="associar" class="btn btn-success btn-sm">Associar</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="mt-4">
        <a href="funcionario.php" class="btn btn-secondary">Voltar para Lista de Funcionários</a>
    </div>
</div>

<script>
    // Auto-exibir toast de sucesso, se existir
    document.addEventListener('DOMContentLoaded', function() {
        var successToast = document.getElementById('successToast');
        if (successToast) {
            var toast = new bootstrap.Toast(successToast);
            toast.show();
        }
    });
</script>

<?php
$content = ob_get_clean();
include '../includes/layout.php';
?>