<?php
session_start();
include_once '../includes/db_conexao.php';

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

// AJAX Handler para buscar horários disponíveis
if (isset($_GET['ajax']) && $_GET['ajax'] === 'horarios_disponiveis') {
    $funcionario_id = $conn->real_escape_string($_GET['funcionario_id']);
    $data = $conn->real_escape_string($_GET['data']);
    $servico_subtipo_id = $conn->real_escape_string($_GET['servico_subtipo_id']);
    $reserva_id = isset($_GET['reserva_id']) ? intval($_GET['reserva_id']) : 0;
    
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
    
    // 3. Buscar reservas existentes do funcionário para o dia (excluindo a própria reserva se estiver editando)
    $stmt = $conn->prepare("
        SELECT r.data_reserva, s.duracao
        FROM reserva r
        JOIN servico_subtipo s ON r.servico_subtipo_id = s.id
        WHERE r.funcionario_id = ? AND DATE(r.data_reserva) = ? AND r.status != 'cancelada' AND r.id != ?
    ");
    $stmt->bind_param("isi", $funcionario_id, $data, $reserva_id);
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

// Get reservation ID
if (!isset($_GET['id'])) {
    echo "Reserva não encontrada.";
    exit();
}
$reserva_id = intval($_GET['id']);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $data = $conn->real_escape_string($_POST['data']);
    $hora = $conn->real_escape_string($_POST['hora']);
    $status = $conn->real_escape_string($_POST['status']);
    $cliente_id = $conn->real_escape_string($_POST['cliente_id']);
    $servico_id = $conn->real_escape_string($_POST['servico_id']);
    $servico_subtipo_id = $conn->real_escape_string($_POST['servico_subtipo_id']);
    $funcionario_id = $conn->real_escape_string($_POST['funcionario_id']);
    $observacao = $conn->real_escape_string($_POST['observacao']);

    // Validações baseadas nas regras do site_pap
    $errors = [];
    
    // Verificar se a data é passada
    $hoje = new DateTime();
    $dataSelecionada = new DateTime($data);
    
    if ($dataSelecionada < $hoje->setTime(0, 0)) {
        $errors[] = "Não é possível reservar datas passadas";
    }
    
    // Verificar se o funcionário trabalha no dia
    $data_dia = $dataSelecionada->format('Y-m-d');
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM agenda_funcionario WHERE funcionario_id = ? AND DATE(data_inicio) = ?");
    $stmt->bind_param("is", $funcionario_id, $data_dia);
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
        $stmt->bind_param("is", $funcionario_id, $data_dia);
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
    
    // Verificar conflitos com outras reservas (excluindo a própria reserva)
    $stmt = $conn->prepare("SELECT duracao FROM servico_subtipo WHERE id = ?");
    $stmt->bind_param("i", $servico_subtipo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $duracao = intval($result->fetch_assoc()['duracao']);
    $stmt->close();
    
    // Calcular fim da reserva
    $data_hora_reserva = $data . ' ' . $hora;
    $inicio_reserva = new DateTime($data_hora_reserva);
    $fim_reserva = clone $inicio_reserva;
    $fim_reserva->modify("+$duracao minutes");
    
    // Buscar reservas conflitantes (excluindo a própria)
    $stmt = $conn->prepare("
        SELECT r.data_reserva, s.duracao
        FROM reserva r
        JOIN servico_subtipo s ON r.servico_subtipo_id = s.id
        WHERE r.funcionario_id = ? AND DATE(r.data_reserva) = ? AND r.status != 'cancelada' AND r.id != ?
    ");
    $stmt->bind_param("isi", $funcionario_id, $data_dia, $reserva_id);
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
    
    // Se não houver erros, atualiza no banco
    if (empty($errors)) {
        $data_reserva = $data . ' ' . $hora;
        
        $sql = "UPDATE reserva SET data_reserva='$data_reserva', status='$status', cliente_id='$cliente_id', servico_id='$servico_id', servico_subtipo_id='$servico_subtipo_id', funcionario_id='$funcionario_id', observacao='$observacao' WHERE id='$reserva_id'";
        
        if ($conn->query($sql)) {
            $_SESSION['success'] = "Reserva atualizada com sucesso!";
            header("Location: reservas.php");
            exit();
        } else {
            $erro = "Erro ao atualizar reserva: " . $conn->error;
        }
    } else {
        $erro = implode("<br>", $errors);
    }
}

// Load reservation data
$sql = "SELECT * FROM reserva WHERE id='$reserva_id'";
$result = $conn->query($sql);
$reserva = $result->fetch_assoc();

if (!$reserva) {
    echo "Reserva não encontrada.";
    exit();
}

// Load lists from the database
$clientes = $conn->query("SELECT id, nome FROM cliente")->fetch_all(MYSQLI_ASSOC);
$servicos = $conn->query("SELECT id, nome FROM servico")->fetch_all(MYSQLI_ASSOC);
$subtipos = $conn->query("SELECT id, nome FROM servico_subtipo")->fetch_all(MYSQLI_ASSOC);
$funcionarios = $conn->query("SELECT id, nome FROM funcionario")->fetch_all(MYSQLI_ASSOC);

// After your PHP logic and before any HTML:
$title = "Editar Reserva";
ob_start();
?>
<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header">
            <h2>Editar Reserva</h2>
        </div>
        <div class="card-body">
            <?php if (isset($erro)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="row">
                    <!-- Cliente -->
                    <div class="col-md-6 mb-3">
                        <label for="cliente_id" class="form-label">Cliente *</label>
                        <select class="form-select" id="cliente_id" name="cliente_id" required>
                            <option value="">Selecione um cliente</option>
                            <?php foreach ($clientes as $cliente): ?>
                                <option value="<?= $cliente['id'] ?>" <?= $reserva['cliente_id'] == $cliente['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cliente['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Categoria e Serviço -->
                    <div class="col-md-6 mb-3">
                        <label for="servico_id" class="form-label">Categoria de Serviço *</label>
                        <select class="form-select" id="servico_id" name="servico_id" required onchange="carregarSubtipos()">
                            <option value="">Selecione uma categoria</option>
                            <?php foreach ($servicos as $servico): ?>
                                <option value="<?= $servico['id'] ?>" <?= $reserva['servico_id'] == $servico['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($servico['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="servico_subtipo_id" class="form-label">Serviço Específico *</label>
                        <select class="form-select" id="servico_subtipo_id" name="servico_subtipo_id" required onchange="carregarFuncionarios()">
                            <option value="">Selecione primeiro uma categoria</option>
                        </select>
                    </div>
                    
                    <!-- Funcionário -->
                    <div class="col-md-6 mb-3">
                        <label for="funcionario_id" class="form-label">Funcionário *</label>
                        <select class="form-select" id="funcionario_id" name="funcionario_id" required>
                            <option value="">Selecione primeiro um serviço</option>
                        </select>
                    </div>
                    
                    <!-- Data e Hora -->
                    <div class="col-md-3 mb-3">
                        <label for="data" class="form-label">Data *</label>
                        <input type="date" class="form-control" id="data" name="data" 
                               value="<?= date('Y-m-d', strtotime($reserva['data_reserva'])) ?>" required>
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
                        <select class="form-select" id="status" name="status" required>
                            <option value="pendente" <?= $reserva['status'] == 'pendente' ? 'selected' : '' ?>>Pendente</option>
                            <option value="confirmada" <?= $reserva['status'] == 'confirmada' ? 'selected' : '' ?>>Confirmada</option>
                            <option value="cancelada" <?= $reserva['status'] == 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
                            <option value="concluída" <?= $reserva['status'] == 'concluída' ? 'selected' : '' ?>>Concluída</option>
                        </select>
                    </div>
                    
                    <!-- Observação -->
                    <div class="col-md-12 mb-3">
                        <label for="observacao" class="form-label">Observação</label>
                        <textarea class="form-control" id="observacao" name="observacao" rows="3"><?= htmlspecialchars($reserva['observacao']) ?></textarea>
                    </div>
                    
                    <!-- Botões -->
                    <div class="col-12 mt-3">
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
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
            xhr.open('GET', 'editar_reserva.php?ajax=subtipos&servico_id=' + categoriaId, true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    subtipoSelect.innerHTML = xhr.responseText;
                    // Selecionar o subtipo da reserva se existir
                    const subtipoReserva = '<?= $reserva['servico_subtipo_id'] ?>';
                    if (subtipoReserva) {
                        setTimeout(function() {
                            document.getElementById('servico_subtipo_id').value = subtipoReserva;
                            carregarFuncionarios();
                        }, 100);
                    }
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
            xhr.open('GET', 'editar_reserva.php?ajax=funcionarios&servico_subtipo_id=' + subtipoId, true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    funcionarioSelect.innerHTML = xhr.responseText;
                    // Selecionar o funcionário da reserva se existir
                    const funcionarioReserva = '<?= $reserva['funcionario_id'] ?>';
                    if (funcionarioReserva) {
                        setTimeout(function() {
                            document.getElementById('funcionario_id').value = funcionarioReserva;
                            carregarHorariosDisponiveis();
                        }, 100);
                    }
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
        const reservaId = '<?= $reserva_id ?>';
        
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
        xhr.open('GET', `editar_reserva.php?ajax=horarios_disponiveis&funcionario_id=${funcionarioId}&data=${data}&servico_subtipo_id=${servicoSubtipoId}&reserva_id=${reservaId}`, true);
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
                        
                        // Selecionar o horário da reserva se existir
                        const horaReserva = '<?= date('H:i', strtotime($reserva['data_reserva'])) ?>';
                        if (horaReserva) {
                            setTimeout(function() {
                                document.getElementById('hora').value = horaReserva;
                            }, 100);
                        }
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
    
    // Inicializar os selects quando a página carrega
    document.addEventListener('DOMContentLoaded', function() {
        // Definir data mínima como hoje
        const dataInput = document.getElementById('data');
        const hoje = new Date().toISOString().split('T')[0];
        dataInput.min = hoje;
        
        // Adicionar validação de data
        dataInput.addEventListener('change', validarDataMinima);
        
        // Carregar subtipos se já houver um serviço selecionado
        const servicoId = '<?= $reserva['servico_id'] ?>';
        if (servicoId) {
            carregarSubtipos();
        }
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
