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

$title = "Adicionar Horário de Funcionário";
include_once '../includes/db_conexao.php';

// Buscar funcionários para o select
$sql_funcionarios = "SELECT id, nome FROM funcionario ORDER BY nome";
$result_funcionarios = $conn->query($sql_funcionarios);
$funcionarios = $result_funcionarios->fetch_all(MYSQLI_ASSOC);

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $funcionario_id = $_POST['funcionario_id'];
    $tipo = $_POST['tipo'];
    
    // Validações
    $errors = [];
    
    if (empty($funcionario_id)) {
        $errors[] = "O funcionário é obrigatório";
    }
    
    // Modo horário normal de trabalho (segunda a sexta)
    if ($tipo === 'normal') {
        $data_inicio = $_POST['data_inicio'];
        $data_fim = $_POST['data_fim'];
        $hora_entrada_manha = $_POST['hora_entrada_manha'];
        $hora_saida_manha = $_POST['hora_saida_manha'];
        $hora_entrada_tarde = $_POST['hora_entrada_tarde'];
        $hora_saida_tarde = $_POST['hora_saida_tarde'];
        $dias_semana = isset($_POST['dias_semana']) ? $_POST['dias_semana'] : [];
        
        if (empty($data_inicio) || empty($data_fim)) {
            $errors[] = "As datas de início e fim são obrigatórias";
        }
        
        if (empty($hora_entrada_manha) || empty($hora_saida_manha)) {
            $errors[] = "Os horários da manhã são obrigatórios";
        }
        
        if (!empty($hora_entrada_tarde) && empty($hora_saida_tarde)) {
            $errors[] = "Se definir entrada da tarde, deve definir também a saída";
        }
        
        if (empty($hora_entrada_tarde) && !empty($hora_saida_tarde)) {
            $errors[] = "Se definir saída da tarde, deve definir também a entrada";
        }
        
        if (empty($dias_semana)) {
            $errors[] = "Selecione pelo menos um dia da semana";
        }
        
        // Verificar se a data de fim é posterior à data de início
        if (strtotime($data_fim) < strtotime($data_inicio)) {
            $errors[] = "A data de fim deve ser posterior à data de início";
        }
        
        // Se não houver erros, gerar entradas para cada dia
        if (empty($errors)) {
            $inicio = new DateTime($data_inicio);
            $fim = new DateTime($data_fim);
            $fim->modify('+1 day'); // Para incluir o último dia
            
            $interval = new DateInterval('P1D'); // Intervalo de 1 dia
            $periodo = new DatePeriod($inicio, $interval, $fim);
            
            $conn->begin_transaction();
            $sucesso = true;
            
            foreach ($periodo as $dt) {
                $dia_semana = $dt->format('N'); // 1 (Segunda) a 7 (Domingo)
                
                // Verificar se o dia da semana está incluído na seleção
                if (in_array($dia_semana, $dias_semana)) {
                    $data_atual = $dt->format('Y-m-d');
                    
                    // Horário da manhã
                    $data_inicio_manha = $data_atual . ' ' . $hora_entrada_manha;
                    $data_fim_manha = $data_atual . ' ' . $hora_saida_manha;
                    
                    $sql = "INSERT INTO agenda_funcionario (funcionario_id, data_inicio, data_fim) VALUES (?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("iss", $funcionario_id, $data_inicio_manha, $data_fim_manha);
                    
                    if (!$stmt->execute()) {
                        $errors[] = "Erro ao inserir horário da manhã: " . $stmt->error;
                        $sucesso = false;
                        break;
                    }
                    
                    // Horário da tarde (se definido)
                    if (!empty($hora_entrada_tarde) && !empty($hora_saida_tarde)) {
                        $data_inicio_tarde = $data_atual . ' ' . $hora_entrada_tarde;
                        $data_fim_tarde = $data_atual . ' ' . $hora_saida_tarde;
                        
                        $sql = "INSERT INTO agenda_funcionario (funcionario_id, data_inicio, data_fim) VALUES (?, ?, ?)";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("iss", $funcionario_id, $data_inicio_tarde, $data_fim_tarde);
                        
                        if (!$stmt->execute()) {
                            $errors[] = "Erro ao inserir horário da tarde: " . $stmt->error;
                            $sucesso = false;
                            break;
                        }
                    }
                }
            }
            
            if ($sucesso) {
                $conn->commit();
                $_SESSION['success'] = "Horários adicionados com sucesso!";
                header("Location: agenda_funcionarios.php");
                exit();
            } else {
                $conn->rollback();
            }
        }
    }
    // Modo de folga/ausência (especificar início e fim específicos)
    else if ($tipo === 'folga') {
        $data_folga = $_POST['data_folga'];
        $motivo = $_POST['motivo'];
        
        if (empty($data_folga)) {
            $errors[] = "A data da folga é obrigatória";
        }
        
        // Verificar se já existem horários para este dia
        if (empty($errors)) {
            $sql_check = "SELECT id FROM agenda_funcionario 
                         WHERE funcionario_id = ? 
                         AND DATE(data_inicio) = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("is", $funcionario_id, $data_folga);
            $stmt_check->execute();
            
            if ($stmt_check->get_result()->num_rows > 0) {
                $errors[] = "Já existem horários cadastrados para esta data. Remova-os primeiro antes de adicionar uma folga.";
            }
            
            $stmt_check->close();
        }
        
        // Se não houver erros, registrar a folga (não inserindo nenhum horário)
        if (empty($errors)) {
            $_SESSION['success'] = "Folga registrada com sucesso para " . date('d/m/Y', strtotime($data_folga));
            header("Location: agenda_funcionarios.php");
            exit();
        }
    }
    // Horário específico (um período único)
    else if ($tipo === 'especifico') {
        $data_especifica = $_POST['data_especifica'];
        $hora_inicio_especifica = $_POST['hora_inicio_especifica'];
        $hora_fim_especifica = $_POST['hora_fim_especifica'];
        
        if (empty($data_especifica) || empty($hora_inicio_especifica) || empty($hora_fim_especifica)) {
            $errors[] = "A data e os horários de início e fim são obrigatórios";
        }
        
        // Verificar se o horário de fim é posterior ao de início
        $data_inicio_especifica = $data_especifica . ' ' . $hora_inicio_especifica;
        $data_fim_especifica = $data_especifica . ' ' . $hora_fim_especifica;
        
        if (strtotime($data_fim_especifica) <= strtotime($data_inicio_especifica)) {
            $errors[] = "O horário de fim deve ser posterior ao horário de início";
        }
        
        // Se não houver erros, inserir o horário específico
        if (empty($errors)) {
            $sql = "INSERT INTO agenda_funcionario (funcionario_id, data_inicio, data_fim) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iss", $funcionario_id, $data_inicio_especifica, $data_fim_especifica);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Horário específico adicionado com sucesso!";
                header("Location: agenda_funcionarios.php");
                exit();
            } else {
                $errors[] = "Erro ao adicionar horário: " . $stmt->error;
            }
            
            $stmt->close();
        }
    }
}

ob_start();
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        
        <a href="horarios.php" class="btn btn-secondary">Voltar</a>
    </div>
    
    <?php if (isset($errors) && !empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" id="horarioTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="normal-tab" data-bs-toggle="tab" data-bs-target="#normal" type="button" role="tab" aria-controls="normal" aria-selected="true">Horário Normal</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="especifico-tab" data-bs-toggle="tab" data-bs-target="#especifico" type="button" role="tab" aria-controls="especifico" aria-selected="false">Horário Específico</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="folga-tab" data-bs-toggle="tab" data-bs-target="#folga" type="button" role="tab" aria-controls="folga" aria-selected="false">Folga/Ausência</button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="horarioTabContent">
                <!-- Formulário de Horário Normal -->
                <div class="tab-pane fade show active" id="normal" role="tabpanel" aria-labelledby="normal-tab">
                    <form method="POST" action="./guardar_horario.php">
                        <input type="hidden" name="tipo" value="normal">
                        
                        <div class="mb-3">
                            <label for="funcionario_id" class="form-label">Funcionário *</label>
                            <select name="funcionario_id" id="funcionario_id" class="form-select" required>
                                <option value="">Selecione um funcionário</option>
                                <?php foreach ($funcionarios as $funcionario): ?>
                                    <option value="<?php echo $funcionario['id']; ?>">
                                        <?php echo htmlspecialchars($funcionario['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="data_inicio" class="form-label">Data de Início *</label>
                                <input type="date" name="data_inicio" id="data_inicio" class="form-control" required
                                       value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="data_fim" class="form-label">Data de Fim *</label>
                                <input type="date" name="data_fim" id="data_fim" class="form-control" required
                                       value="<?php echo date('Y-m-d', strtotime('+1 month')); ?>">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <label class="form-label">Dias da Semana *</label>
                            <div class="col">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="dias_semana[]" value="1" id="segunda" checked>
                                    <label class="form-check-label" for="segunda">Segunda</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="dias_semana[]" value="2" id="terca" checked>
                                    <label class="form-check-label" for="terca">Terça</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="dias_semana[]" value="3" id="quarta" checked>
                                    <label class="form-check-label" for="quarta">Quarta</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="dias_semana[]" value="4" id="quinta" checked>
                                    <label class="form-check-label" for="quinta">Quinta</label>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="dias_semana[]" value="5" id="sexta" checked>
                                    <label class="form-check-label" for="sexta">Sexta</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="dias_semana[]" value="6" id="sabado">
                                    <label class="form-check-label" for="sabado">Sábado</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="dias_semana[]" value="7" id="domingo">
                                    <label class="form-check-label" for="domingo">Domingo</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Período da Manhã *</label>
                            <div class="row">
                                <div class="col">
                                    <label for="hora_entrada_manha" class="form-label">Hora de Entrada</label>
                                    <input type="time" name="hora_entrada_manha" id="hora_entrada_manha" class="form-control" required value="09:00">
                                </div>
                                <div class="col">
                                    <label for="hora_saida_manha" class="form-label">Hora de Saída</label>
                                    <input type="time" name="hora_saida_manha" id="hora_saida_manha" class="form-control" required value="13:00">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Período da Tarde</label>
                            <div class="row">
                                <div class="col">
                                    <label for="hora_entrada_tarde" class="form-label">Hora de Entrada</label>
                                    <input type="time" name="hora_entrada_tarde" id="hora_entrada_tarde" class="form-control" value="14:00">
                                </div>
                                <div class="col">
                                    <label for="hora_saida_tarde" class="form-label">Hora de Saída</label>
                                    <input type="time" name="hora_saida_tarde" id="hora_saida_tarde" class="form-control" value="18:00">
                                </div>
                            </div>
                            <small class="form-text text-muted">Deixe em branco se não houver período da tarde</small>
                        </div>
                        
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary">Salvar Horários</button>
                        </div>
                    </form>
                </div>
                
                <!-- Formulário de Horário Específico -->
                <div class="tab-pane fade" id="especifico" role="tabpanel" aria-labelledby="especifico-tab">
                    <form method="POST" action="./guardar_horario.php">
                        <input type="hidden" name="tipo" value="especifico">
                        
                        <div class="mb-3">
                            <label for="funcionario_id_especifico" class="form-label">Funcionário *</label>
                            <select name="funcionario_id" id="funcionario_id_especifico" class="form-select" required>
                                <option value="">Selecione um funcionário</option>
                                <?php foreach ($funcionarios as $funcionario): ?>
                                    <option value="<?php echo $funcionario['id']; ?>">
                                        <?php echo htmlspecialchars($funcionario['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="data_especifica" class="form-label">Data *</label>
                            <input type="date" name="data_especifica" id="data_especifica" class="form-control" required
                                   value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col">
                                <label for="hora_inicio_especifica" class="form-label">Hora de Início *</label>
                                <input type="time" name="hora_inicio_especifica" id="hora_inicio_especifica" class="form-control" required>
                            </div>
                            <div class="col">
                                <label for="hora_fim_especifica" class="form-label">Hora de Fim *</label>
                                <input type="time" name="hora_fim_especifica" id="hora_fim_especifica" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary">Salvar Horário Específico</button>
                        </div>
                    </form>
                </div>
                
                <!-- Formulário de Folga/Ausência -->
                <div class="tab-pane fade" id="folga" role="tabpanel" aria-labelledby="folga-tab">
                    <form method="POST" action="./guardar_horario.php">
                        <input type="hidden" name="tipo" value="folga">
                        
                        <div class="mb-3">
                            <label for="funcionario_id_folga" class="form-label">Funcionário *</label>
                            <select name="funcionario_id" id="funcionario_id_folga" class="form-select" required>
                                <option value="">Selecione um funcionário</option>
                                <?php foreach ($funcionarios as $funcionario): ?>
                                    <option value="<?php echo $funcionario['id']; ?>">
                                        <?php echo htmlspecialchars($funcionario['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="data_folga" class="form-label">Data da Folga *</label>
                            <input type="date" name="data_folga" id="data_folga" class="form-control" required
                                   value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="motivo" class="form-label">Motivo (opcional)</label>
                            <textarea name="motivo" id="motivo" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary">Registrar Folga</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Quando alterar a tab, ativar o campo de funcionário correspondente
    const tabs = document.querySelectorAll('button[data-bs-toggle="tab"]');
    tabs.forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(event) {
            const targetId = event.target.getAttribute('data-bs-target').substring(1);
            document.getElementById(`funcionario_id_${targetId}`).focus();
        });
    });
});
</script>

<?php
$content = ob_get_clean();
include '../includes/layout.php';
?>