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
    $servico_id = $conn->real_escape_string($_GET['servico_id']);
    
    // Buscar subtipos do serviço selecionado
    $sql = "SELECT id, nome, preco, duracao FROM servico_subtipo WHERE servico_id = '$servico_id' ORDER BY nome";
    $result = $conn->query($sql);
    
    echo '<option value="">Selecione um serviço</option>';
    
    while ($row = $result->fetch_assoc()) {
        $preco = number_format($row['preco'], 2, ',', '.');
        echo '<option value="' . $row['id'] . '">' . 
             htmlspecialchars($row['nome']) . ' - ' . 
             $preco . '€ (' . 
             $row['duracao'] . ' min)</option>';
    }
    
    $conn->close();
    exit;
}

// AJAX Handler para funcionários
if (isset($_GET['ajax']) && $_GET['ajax'] === 'funcionarios' && isset($_GET['servico_subtipo_id'])) {
    $servico_subtipo_id = $conn->real_escape_string($_GET['servico_subtipo_id']);
    
    // Buscar funcionários associados ao subtipo de serviço
    $sql = "SELECT f.id, f.nome 
            FROM funcionario f
            JOIN funcionario_subtipo fs ON f.id = fs.funcionario_id
            WHERE fs.servico_subtipo_id = '$servico_subtipo_id'
            ORDER BY f.nome";
    
    $result = $conn->query($sql);
    
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
    
    $conn->close();
    exit;
}

// AJAX Handler para verificar disponibilidade
if (isset($_GET['ajax']) && $_GET['ajax'] === 'verificar_disponibilidade') {
    $funcionario_id = $conn->real_escape_string($_GET['funcionario_id']);
    $data = $conn->real_escape_string($_GET['data']);
    $hora = $conn->real_escape_string($_GET['hora']);
    
    // Buscar reservas existentes para o funcionário na data/hora especificada
    $sql = "SELECT r.id, r.data_reserva, ss.duracao 
            FROM reserva r 
            JOIN servico_subtipo ss ON r.servico_subtipo_id = ss.id 
            WHERE r.funcionario_id = '$funcionario_id' 
            AND DATE(r.data_reserva) = '$data' 
            AND r.status != 'cancelada'";
    
    $result = $conn->query($sql);
    
    $reservas = [];
    while ($row = $result->fetch_assoc()) {
        $reservas[] = [
            'hora_inicio' => date('H:i', strtotime($row['data_reserva'])),
            'duracao' => $row['duracao']
        ];
    }
    
    // Verificar se o horário solicitado está disponível
    $hora_solicitada = strtotime($hora);
    $disponivel = true;
    
    foreach ($reservas as $reserva) {
        $hora_inicio = strtotime($reserva['hora_inicio']);
        $hora_fim = strtotime('+' . $reserva['duracao'] . ' minutes', $hora_inicio);
        
        if ($hora_solicitada >= $hora_inicio && $hora_solicitada < $hora_fim) {
            $disponivel = false;
            break;
        }
    }
    
    echo json_encode(['disponivel' => $disponivel]);
    $conn->close();
    exit;
}

// AJAX Handler para buscar horários disponíveis
if (isset($_GET['ajax']) && $_GET['ajax'] === 'horarios_disponiveis') {
    $funcionario_id = $conn->real_escape_string($_GET['funcionario_id']);
    $data = $conn->real_escape_string($_GET['data']);
    $servico_subtipo_id = $conn->real_escape_string($_GET['servico_subtipo_id']);
    
    // Verificar se a data é passada
    $hoje = new DateTime();
    $dataSelecionada = new DateTime($data);
    
    if ($dataSelecionada < $hoje->setTime(0, 0)) {
        echo json_encode([
            'disponivel' => false,
            'mensagem' => 'Não é possível reservar datas passadas',
            'horarios' => []
        ]);
        exit;
    }
    
    // 1. Buscar duração do serviço
    $stmt = $conn->prepare("SELECT duracao FROM servico_subtipo WHERE id = ?");
    $stmt->bind_param("i", $servico_subtipo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        echo json_encode([
            'disponivel' => false,
            'mensagem' => 'Serviço não encontrado',
            'horarios' => []
        ]);
        exit;
    }
    $duracao = intval($result->fetch_assoc()['duracao']); // duração em minutos
    $stmt->close();
    
    // 2. Buscar agenda do funcionário para o dia
    $stmt = $conn->prepare("SELECT data_inicio, data_fim FROM agenda_funcionario WHERE funcionario_id = ? AND DATE(data_inicio) = ?");
    $stmt->bind_param("is", $funcionario_id, $data);
    $stmt->execute();
    $agendas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    if (empty($agendas)) {
        echo json_encode([
            'disponivel' => false,
            'mensagem' => 'Funcionário não trabalha neste dia',
            'horarios' => []
        ]);
        exit;
    }
    
    // 3. Buscar reservas existentes do funcionário para o dia
    $stmt = $conn->prepare("
        SELECT r.data_reserva, s.duracao
        FROM reserva r
        JOIN servico_subtipo s ON r.servico_subtipo_id = s.id
        WHERE r.funcionario_id = ? AND DATE(r.data_reserva) = ? AND r.status != 'cancelada'
    ");
    $stmt->bind_param("is", $funcionario_id, $data);
    $stmt->execute();
    $reservas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // 4. Função para verificar conflito com reservas
    function conflito($inicio, $fim, $reservas) {
        foreach ($reservas as $reserva) {
            $r_inicio = new DateTime($reserva['data_reserva']);
            $r_fim = clone $r_inicio;
            $r_fim->modify("+" . intval($reserva['duracao']) . " minutes");
            
            if ($inicio < $r_fim && $fim > $r_inicio) {
                return true;
            }
        }
        return false;
    }
    
    // 5. Gerar horários disponíveis
    $horarios_disponiveis = [];
    $hora_atual = new DateTime();
    
    foreach ($agendas as $agenda) {
        $inicio = new DateTime($agenda['data_inicio']);
        $fim = new DateTime($agenda['data_fim']);
        
        // Se for o dia atual, ajustar o início para a hora atual + 30 minutos
        if ($dataSelecionada->format('Y-m-d') === $hora_atual->format('Y-m-d')) {
            $inicio_ajustado = clone $hora_atual;
            $inicio_ajustado->modify('+30 minutes');
            $inicio_ajustado->setTime($inicio_ajustado->format('H'), ceil($inicio_ajustado->format('i') / 30) * 30, 0);
            
            if ($inicio_ajustado > $inicio) {
                $inicio = $inicio_ajustado;
            }
        }
        
        while ($inicio < $fim) {
            $slot_inicio = clone $inicio;
            $slot_fim = clone $inicio;
            $slot_fim->modify("+$duracao minutes");
            
            if ($slot_fim > $fim) break;
            
            if (!conflito($slot_inicio, $slot_fim, $reservas)) {
                $horarios_disponiveis[] = $slot_inicio->format("H:i");
            }
            
            // Avançar de bloco em bloco com base na duração do serviço
            $inicio->modify("+$duracao minutes");
        }
    }
    
    // 6. Exibir horários disponíveis
    if (empty($horarios_disponiveis)) {
        echo json_encode([
            'disponivel' => false,
            'mensagem' => 'Sem horários disponíveis',
            'horarios' => []
        ]);
    } else {
        echo json_encode([
            'disponivel' => true,
            'mensagem' => 'Horários disponíveis encontrados',
            'horarios' => $horarios_disponiveis
        ]);
    }
    
    $conn->close();
    exit;
}

$title = "Reserva";

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
    $cliente_id = $conn->real_escape_string($_POST['cliente_id']);
    $servico_id = $conn->real_escape_string($_POST['servico_id']);
    $servico_subtipo_id = $conn->real_escape_string($_POST['servico_subtipo_id']);
    $funcionario_id = $conn->real_escape_string($_POST['funcionario_id']);
    $data = $conn->real_escape_string($_POST['data']);
    $hora = $conn->real_escape_string($_POST['hora']);
    $status = $conn->real_escape_string($_POST['status']);
    $observacao = $conn->real_escape_string($_POST['observacao']);
    
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
    
    // Validações adicionais baseadas nas regras do site_pap
    if (empty($errors)) {
        // Verificar se a data é passada
        $hoje = new DateTime();
        $dataSelecionada = new DateTime($data);
        
        if ($dataSelecionada < $hoje->setTime(0, 0)) {
            $errors[] = "Não é possível reservar datas passadas";
        }
        
        // Verificar se o horário é válido para o dia atual
        if ($dataSelecionada->format('Y-m-d') === $hoje->format('Y-m-d')) {
            $hora_atual = new DateTime();
            $hora_reserva = new DateTime($data . ' ' . $hora);
            $hora_minima = clone $hora_atual;
            $hora_minima->modify('+30 minutes');
            
            if ($hora_reserva < $hora_minima) {
                $errors[] = "Para o dia atual, só é possível reservar horários a partir de 30 minutos após a hora atual";
            }
        }
        
        // Verificar se o funcionário trabalha no dia
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM agenda_funcionario WHERE funcionario_id = ? AND DATE(data_inicio) = ?");
        $stmt->bind_param("is", $funcionario_id, $data);
        $stmt->execute();
        $result = $stmt->get_result();
        $agenda_count = $result->fetch_assoc()['count'];
        $stmt->close();
        
        if ($agenda_count == 0) {
            $errors[] = "O funcionário não trabalha neste dia";
        }
        
        // Verificar se o horário está dentro do período de trabalho
        if ($agenda_count > 0) {
            $stmt = $conn->prepare("SELECT data_inicio, data_fim FROM agenda_funcionario WHERE funcionario_id = ? AND DATE(data_inicio) = ?");
            $stmt->bind_param("is", $funcionario_id, $data);
            $stmt->execute();
            $agendas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            $hora_reserva = new DateTime($data . ' ' . $hora);
            $dentro_periodo = false;
            
            foreach ($agendas as $agenda) {
                $inicio = new DateTime($agenda['data_inicio']);
                $fim = new DateTime($agenda['data_fim']);
                
                if ($hora_reserva >= $inicio && $hora_reserva < $fim) {
                    $dentro_periodo = true;
                    break;
                }
            }
            
            if (!$dentro_periodo) {
                $errors[] = "O horário selecionado está fora do período de trabalho do funcionário";
            }
        }
        
        // Verificar conflitos com outras reservas
        $data_hora_reserva = $data . ' ' . $hora;
        
        // Buscar duração do serviço
        $stmt = $conn->prepare("SELECT duracao FROM servico_subtipo WHERE id = ?");
        $stmt->bind_param("i", $servico_subtipo_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $duracao = intval($result->fetch_assoc()['duracao']);
        $stmt->close();
        
        // Calcular fim da reserva
        $inicio_reserva = new DateTime($data_hora_reserva);
        $fim_reserva = clone $inicio_reserva;
        $fim_reserva->modify("+$duracao minutes");
        
        // Buscar reservas conflitantes
        $stmt = $conn->prepare("
            SELECT r.data_reserva, s.duracao
            FROM reserva r
            JOIN servico_subtipo s ON r.servico_subtipo_id = s.id
            WHERE r.funcionario_id = ? AND DATE(r.data_reserva) = ? AND r.status != 'cancelada'
        ");
        $stmt->bind_param("is", $funcionario_id, $data);
        $stmt->execute();
        $reservas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        foreach ($reservas as $reserva) {
            $r_inicio = new DateTime($reserva['data_reserva']);
            $r_fim = clone $r_inicio;
            $r_fim->modify("+" . intval($reserva['duracao']) . " minutes");
            
            if ($inicio_reserva < $r_fim && $fim_reserva > $r_inicio) {
                $errors[] = "O horário selecionado conflita com uma reserva existente";
                break;
            }
        }
    }
    
    // Se não houver erros, insere no banco
    if (empty($errors)) {
        $data_reserva = $data . ' ' . $hora;
        
        $sql = "INSERT INTO reserva (data_reserva, status, cliente_id, servico_id, servico_subtipo_id, funcionario_id, observacao) 
                VALUES ('$data_reserva', '$status', '$cliente_id', '$servico_id', '$servico_subtipo_id', '$funcionario_id', '$observacao')";
        
        if ($conn->query($sql)) {
            $reserva_id = $conn->insert_id;
            
            // Opcional: Inserir na tabela reserva_funcionario se necessário
            $sql_reserva_func = "INSERT INTO reserva_funcionario (r_id, f_id) VALUES ('$reserva_id', '$funcionario_id')";
            $conn->query($sql_reserva_func);
            
            $_SESSION['success'] = "Reserva adicionada com sucesso!";
            header("Location: reservas.php");
            exit();
        } else {
            $errors[] = "Erro ao adicionar reserva: " . $conn->error;
        }
    }
}

ob_start();
?>

<div class="container-fluid py-4">
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
                    
                    <!-- Funcionário -->
                    <div class="col-md-6 mb-3">
                        <label for="funcionario_id" class="form-label">Funcionário *</label>
                        <select name="funcionario_id" id="funcionario_id" class="form-select" required>
                            <option value="">Selecione primeiro um serviço</option>
                        </select>
                    </div>
                    
                    <!-- Data e Hora -->
                    <div class="col-md-3 mb-3">
                        <label for="data" class="form-label">Data *</label>
                        <input type="date" name="data" id="data" class="form-control" required 
                               value="<?php echo isset($_POST['data']) ? htmlspecialchars($_POST['data']) : date('Y-m-d'); ?>"
                               min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="hora" class="form-label">Hora *</label>
                        <select name="hora" id="hora" class="form-select" required>
                            <option value="">Selecione um horário</option>
                        </select>
                        <div id="disponibilidade-mensagem" class="mt-2"></div>
                    </div>
                    
                    <!-- Status -->
                    <div class="col-md-6 mb-3">
                        <label for="status" class="form-label">Status *</label>
                        <select name="status" id="status" class="form-select" required>
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
        
        // Limpar o select de horários
        document.getElementById('hora').innerHTML = '<option value="">Selecione um horário</option>';
        document.getElementById('disponibilidade-mensagem').innerHTML = '';
        
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
        
        // Limpar o select de horários
        document.getElementById('hora').innerHTML = '<option value="">Selecione um horário</option>';
        document.getElementById('disponibilidade-mensagem').innerHTML = '';
        
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
    
    // Função para carregar horários disponíveis
    function carregarHorariosDisponiveis() {
        const funcionarioId = document.getElementById('funcionario_id').value;
        const data = document.getElementById('data').value;
        const servicoSubtipoId = document.getElementById('servico_subtipo_id').value;
        const horaSelect = document.getElementById('hora');
        const mensagemDiv = document.getElementById('disponibilidade-mensagem');
        
        // Limpar o select de horários
        horaSelect.innerHTML = '<option value="">Selecione um horário</option>';
        
        if (!funcionarioId || !data || !servicoSubtipoId) {
            mensagemDiv.innerHTML = '';
            return;
        }
        
        // Mostrar loading
        mensagemDiv.innerHTML = '<span class="text-info">Carregando horários disponíveis...</span>';
        mensagemDiv.className = 'mt-2 text-info';
        
        const xhr = new XMLHttpRequest();
        xhr.open('GET', `adicionar_reserva.php?ajax=horarios_disponiveis&funcionario_id=${funcionarioId}&data=${data}&servico_subtipo_id=${servicoSubtipoId}`, true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.disponivel) {
                        response.horarios.forEach(horario => {
                            const option = document.createElement('option');
                            option.value = horario;
                            option.textContent = horario;
                            horaSelect.appendChild(option);
                        });
                        mensagemDiv.innerHTML = `<span class="text-success">${response.mensagem}</span>`;
                        mensagemDiv.className = 'mt-2 text-success';
                    } else {
                        mensagemDiv.innerHTML = `<span class="text-danger">${response.mensagem}</span>`;
                        mensagemDiv.className = 'mt-2 text-danger';
                    }
                } catch (e) {
                    mensagemDiv.innerHTML = '<span class="text-danger">Erro ao processar resposta do servidor</span>';
                    mensagemDiv.className = 'mt-2 text-danger';
                }
            } else {
                mensagemDiv.innerHTML = '<span class="text-danger">Erro ao carregar horários</span>';
                mensagemDiv.className = 'mt-2 text-danger';
            }
        };
        xhr.onerror = function() {
            mensagemDiv.innerHTML = '<span class="text-danger">Erro de conexão</span>';
            mensagemDiv.className = 'mt-2 text-danger';
        };
        xhr.send();
    }
    
    // Função para validar data mínima
    function validarDataMinima() {
        const dataInput = document.getElementById('data');
        const hoje = new Date().toISOString().split('T')[0];
        
        if (dataInput.value < hoje) {
            dataInput.setCustomValidity('Não é possível selecionar datas passadas');
        } else {
            dataInput.setCustomValidity('');
        }
    }
    
    // Inicializar os selects se houver valores salvos
    document.addEventListener('DOMContentLoaded', function() {
        // Definir data mínima como hoje
        const dataInput = document.getElementById('data');
        const hoje = new Date().toISOString().split('T')[0];
        dataInput.min = hoje;
        
        // Adicionar validação de data
        dataInput.addEventListener('change', validarDataMinima);
        
        <?php if (isset($_POST['servico_id']) && !empty($_POST['servico_id'])): ?>
            carregarSubtipos();
            <?php if (isset($_POST['servico_subtipo_id']) && !empty($_POST['servico_subtipo_id'])): ?>
                setTimeout(function() {
                    document.getElementById('servico_subtipo_id').value = '<?php echo $_POST['servico_subtipo_id']; ?>';
                    carregarFuncionarios();
                    <?php if (isset($_POST['funcionario_id']) && !empty($_POST['funcionario_id'])): ?>
                        setTimeout(function() {
                            document.getElementById('funcionario_id').value = '<?php echo $_POST['funcionario_id']; ?>';
                            <?php if (isset($_POST['data']) && !empty($_POST['data'])): ?>
                                setTimeout(function() {
                                    document.getElementById('data').value = '<?php echo $_POST['data']; ?>';
                                    carregarHorariosDisponiveis();
                                    <?php if (isset($_POST['hora']) && !empty($_POST['hora'])): ?>
                                        setTimeout(function() {
                                            document.getElementById('hora').value = '<?php echo $_POST['hora']; ?>';
                                        }, 1000);
                                    <?php endif; ?>
                                }, 500);
                            <?php endif; ?>
                        }, 500);
                    <?php endif; ?>
                }, 500);
            <?php endif; ?>
        <?php endif; ?>
    });

    // Adicionar event listeners para carregar horários
    document.getElementById('data').addEventListener('change', carregarHorariosDisponiveis);
    document.getElementById('funcionario_id').addEventListener('change', carregarHorariosDisponiveis);
    document.getElementById('servico_subtipo_id').addEventListener('change', carregarHorariosDisponiveis);
    
    // Adicionar validação do formulário antes do envio
    document.querySelector('form').addEventListener('submit', function(e) {
        const data = document.getElementById('data').value;
        const hora = document.getElementById('hora').value;
        const funcionario = document.getElementById('funcionario_id').value;
        const servicoSubtipo = document.getElementById('servico_subtipo_id').value;
        
        if (!data || !hora || !funcionario || !servicoSubtipo) {
            e.preventDefault();
            alert('Por favor, preencha todos os campos obrigatórios e selecione um horário disponível.');
            return false;
        }
        
        // Verificar se um horário foi selecionado
        if (hora === '') {
            e.preventDefault();
            alert('Por favor, selecione um horário disponível.');
            return false;
        }
        
        return true;
    });
</script>

<?php
$content = ob_get_clean();
include '../includes/layout.php';
?>