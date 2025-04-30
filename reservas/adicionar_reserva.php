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

// Verifica se o usuário é do tipo admin ou funcionario
if ($_SESSION['utilizador_tipo'] !== 'admin' && $_SESSION['utilizador_tipo'] !== 'funcionario') {
    header("Location: ../index.php");
    exit();
}

include_once '../includes/db_conexao.php';

// AJAX Handler para subtipos
if (isset($_GET['ajax']) && $_GET['ajax'] === 'subtipos' && isset($_GET['servico_id'])) {
    $servico_id = intval($_GET['servico_id']);
    
    // Buscar subtipos do serviço selecionado
    $sql = "SELECT id, nome, preco, duracao FROM servico_subtipo WHERE servico_id = ? ORDER BY nome";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $servico_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo '<option value="">Selecione um serviço</option>';
    
    while ($row = $result->fetch_assoc()) {
        $preco = number_format($row['preco'], 2, ',', '.');
        echo '<option value="' . $row['id'] . '">' . 
             htmlspecialchars($row['nome']) . ' - ' . 
             $preco . '€ (' . 
             $row['duracao'] . ' min)</option>';
    }
    
    $stmt->close();
    $conn->close();
    exit; // Importante para parar a execução do resto do código
}

// AJAX Handler para funcionários
if (isset($_GET['ajax']) && $_GET['ajax'] === 'funcionarios' && isset($_GET['servico_subtipo_id'])) {
    $servico_subtipo_id = intval($_GET['servico_subtipo_id']);
    
    // Buscar funcionários associados ao subtipo de serviço
    $sql = "SELECT f.id, f.nome 
            FROM funcionario f
            JOIN funcionario_subtipo fs ON f.id = fs.funcionario_id
            WHERE fs.servico_subtipo_id = ?
            ORDER BY f.nome";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $servico_subtipo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Verificar se encontrou funcionários
    if ($result->num_rows > 0) {
        echo '<option value="">Selecione um funcionário</option>';
        while ($row = $result->fetch_assoc()) {
            echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['nome']) . '</option>';
        }
    } else {
        // Se não houver funcionários associados, mostrar todos os funcionários
        echo '<option value="">Nenhum funcionário específico - Selecione manualmente:</option>';
        
        // Buscar todos os funcionários
        $sql_all = "SELECT id, nome FROM funcionario ORDER BY nome";
        $result_all = $conn->query($sql_all);
        
        while ($row = $result_all->fetch_assoc()) {
            echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['nome']) . '</option>';
        }
    }
    
    $stmt->close();
    $conn->close();
    exit; // Importante para parar a execução do resto do código
}

$title = "Adicionar Reserva";

// Buscar categorias/serviços
$sql_servicos = "SELECT id, nome FROM servico ORDER BY nome";
$result_servicos = $conn->query($sql_servicos);
$servicos = $result_servicos->fetch_all(MYSQLI_ASSOC);

// Buscar clientes
$sql_clientes = "SELECT id, nome, email, telefone FROM cliente ORDER BY nome";
$result_clientes = $conn->query($sql_clientes);
$clientes = $result_clientes->fetch_all(MYSQLI_ASSOC);

// Buscar funcionários
$sql_funcionarios = "SELECT id, nome FROM funcionario ORDER BY nome";
$result_funcionarios = $conn->query($sql_funcionarios);
$funcionarios = $result_funcionarios->fetch_all(MYSQLI_ASSOC);

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente_id = $_POST['cliente_id'];
    $servico_id = $_POST['servico_id'];
    $servico_subtipo_id = $_POST['servico_subtipo_id'];
    $funcionario_id = $_POST['funcionario_id'];
    $data = $_POST['data'];
    $hora = $_POST['hora'];
    $status = $_POST['status'];
    $observacao = $_POST['observacao'];
    
    // Validações
    $errors = [];
    
    if (empty($cliente_id)) {
        $errors[] = "O cliente é obrigatório";
    }
    
    if (empty($servico_id)) {
        $errors[] = "O serviço é obrigatório";
    }
    
    if (empty($servico_subtipo_id)) {
        $errors[] = "O subtipo de serviço é obrigatório";
    }
    
    if (empty($funcionario_id)) {
        $errors[] = "O funcionário é obrigatório";
    }
    
    if (empty($data)) {
        $errors[] = "A data é obrigatória";
    }
    
    if (empty($hora)) {
        $errors[] = "A hora é obrigatória";
    }
    
    if (empty($status)) {
        $errors[] = "O status é obrigatório";
    }
    
    // Se não houver erros, insere no banco
    if (empty($errors)) {
        $data_reserva = $data . ' ' . $hora;
        
        $sql = "INSERT INTO reserva (data_reserva, status, cliente_id, servico_id, servico_subtipo_id, funcionario_id, observacao) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssiiiis", $data_reserva, $status, $cliente_id, $servico_id, $servico_subtipo_id, $funcionario_id, $observacao);
        
        if ($stmt->execute()) {
            $reserva_id = $stmt->insert_id;
            
            // Opcional: Inserir na tabela reserva_funcionario se necessário
            $sql_reserva_func = "INSERT INTO reserva_funcionario (r_id, f_id) VALUES (?, ?)";
            $stmt_reserva_func = $conn->prepare($sql_reserva_func);
            $stmt_reserva_func->bind_param("ii", $reserva_id, $funcionario_id);
            $stmt_reserva_func->execute();
            $stmt_reserva_func->close();
            
            $_SESSION['success'] = "Reserva adicionada com sucesso!";
            header("Location: reservas.php");
            exit();
        } else {
            $errors[] = "Erro ao adicionar reserva: " . $conn->error;
        }
        
        $stmt->close();
    }
}

ob_start();
?>

<div class="container py-4">
    <div class="card">
        <div class="card-header">
            <h2>Adicionar Nova Reserva</h2>
        </div>
        <div class="card-body">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="row">
                    <!-- Cliente -->
                    <div class="col-md-6 mb-3">
                        <label for="cliente_id" class="form-label">Cliente *</label>
                        <select name="cliente_id" id="cliente_id" class="form-select" required>
                            <option value="">Selecione um cliente</option>
                            <?php foreach ($clientes as $cliente): ?>
                                <option value="<?php echo $cliente['id']; ?>" <?php echo isset($_POST['cliente_id']) && $_POST['cliente_id'] == $cliente['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cliente['nome']); ?> (<?php echo htmlspecialchars($cliente['email']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Data e Hora -->
                    <div class="col-md-3 mb-3">
                        <label for="data" class="form-label">Data *</label>
                        <input type="date" name="data" id="data" class="form-control" required 
                               value="<?php echo isset($_POST['data']) ? htmlspecialchars($_POST['data']) : date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="hora" class="form-label">Hora *</label>
                        <input type="time" name="hora" id="hora" class="form-control" required 
                               value="<?php echo isset($_POST['hora']) ? htmlspecialchars($_POST['hora']) : ''; ?>">
                    </div>
                    
                    <!-- Categoria e Serviço -->
                    <div class="col-md-6 mb-3">
                        <label for="servico_id" class="form-label">Categoria de Serviço *</label>
                        <select name="servico_id" id="servico_id" class="form-select" required onchange="carregarSubtipos()">
                            <option value="">Selecione uma categoria</option>
                            <?php foreach ($servicos as $servico): ?>
                                <option value="<?php echo $servico['id']; ?>" <?php echo isset($_POST['servico_id']) && $_POST['servico_id'] == $servico['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($servico['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="servico_subtipo_id" class="form-label">Serviço Específico *</label>
                        <select name="servico_subtipo_id" id="servico_subtipo_id" class="form-select" required onchange="carregarFuncionarios()">
                            <option value="">Selecione primeiro uma categoria</option>
                        </select>
                    </div>
                    
                    <!-- Funcionário e Status -->
                    <div class="col-md-6 mb-3">
                        <label for="funcionario_id" class="form-label">Funcionário *</label>
                        <select name="funcionario_id" id="funcionario_id" class="form-select" required>
                            <option value="">Selecione primeiro um serviço</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="status" class="form-label">Status *</label>
                        <select name="status" id="status" class="form-select" required>
                            <option value="pendente" <?php echo isset($_POST['status']) && $_POST['status'] == 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                            <option value="confirmada" <?php echo isset($_POST['status']) && $_POST['status'] == 'confirmada' ? 'selected' : ''; ?>>Confirmada</option>
                            <option value="cancelada" <?php echo isset($_POST['status']) && $_POST['status'] == 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                            <option value="concluída" <?php echo isset($_POST['status']) && $_POST['status'] == 'concluída' ? 'selected' : ''; ?>>Concluída</option>
                        </select>
                    </div>
                    
                    <!-- Observação -->
                    <div class="col-md-12 mb-3">
                        <label for="observacao" class="form-label">Observação</label>
                        <textarea name="observacao" id="observacao" class="form-control" rows="3"><?php echo isset($_POST['observacao']) ? htmlspecialchars($_POST['observacao']) : ''; ?></textarea>
                    </div>
                    
                    <!-- Botões -->
                    <div class="col-12 mt-3">
                        <button type="submit" class="btn btn-success">Salvar Reserva</button>
                        <a href="reservas.php" class="btn btn-secondary">Cancelar</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Função para carregar os subtipos de serviço quando uma categoria é selecionada
    function carregarSubtipos() {
        const categoriaId = document.getElementById('servico_id').value;
        const subtipoSelect = document.getElementById('servico_subtipo_id');
        
        // Limpar o select de subtipos
        subtipoSelect.innerHTML = '<option value="">Selecione um serviço</option>';
        
        // Limpar o select de funcionários
        document.getElementById('funcionario_id').innerHTML = '<option value="">Selecione primeiro um serviço</option>';
        
        if (categoriaId) {
            // Fazer uma requisição AJAX para buscar os subtipos
            const xhr = new XMLHttpRequest();
            xhr.open('GET', 'adicionar_reserva.php?ajax=subtipos&servico_id=' + categoriaId, true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    subtipoSelect.innerHTML = xhr.responseText;
                }
            };
            xhr.send();
        }
    }
    
    // Função para carregar os funcionários disponíveis quando um subtipo é selecionado
    function carregarFuncionarios() {
        const subtipoId = document.getElementById('servico_subtipo_id').value;
        const funcionarioSelect = document.getElementById('funcionario_id');
        
        // Limpar o select de funcionários
        funcionarioSelect.innerHTML = '<option value="">Selecione um funcionário</option>';
        
        if (subtipoId) {
            // Fazer uma requisição AJAX para buscar os funcionários
            const xhr = new XMLHttpRequest();
            xhr.open('GET', 'adicionar_reserva.php?ajax=funcionarios&servico_subtipo_id=' + subtipoId, true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    funcionarioSelect.innerHTML = xhr.responseText;
                }
            };
            xhr.send();
        }
    }
    
    // Inicializar os selects se houver valores salvos
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (isset($_POST['servico_id']) && !empty($_POST['servico_id'])): ?>
            carregarSubtipos();
            <?php if (isset($_POST['servico_subtipo_id']) && !empty($_POST['servico_subtipo_id'])): ?>
                setTimeout(function() {
                    document.getElementById('servico_subtipo_id').value = '<?php echo $_POST['servico_subtipo_id']; ?>';
                    carregarFuncionarios();
                    <?php if (isset($_POST['funcionario_id']) && !empty($_POST['funcionario_id'])): ?>
                        setTimeout(function() {
                            document.getElementById('funcionario_id').value = '<?php echo $_POST['funcionario_id']; ?>';
                        }, 500);
                    <?php endif; ?>
                }, 500);
            <?php endif; ?>
        <?php endif; ?>
    });
</script>

<?php
$content = ob_get_clean();
include '../includes/layout.php';
?>