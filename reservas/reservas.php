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

$title = "Gestão de Reservas";
include_once '../includes/db_conexao.php';

if (isset($_GET['clear'])) {
    header("Location: reservas.php");
    exit();
}

$mensagem = isset($_SESSION['mensagem']) ? $_SESSION['mensagem'] : '';
$success_message = isset($_SESSION['success']) ? $_SESSION['success'] : '';
unset($_SESSION['mensagem']);
unset($_SESSION['success']);

// Configurações de filtro e paginação
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : '';
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : '';

// Colunas e ordenação
$colunas_selecionadas = isset($_GET['colunas']) ? explode(',', $_GET['colunas']) : 
    ['id', 'data_reserva', 'status', 'cliente', 'servico', 'subtipo', 'funcionario'];
$colunas_permitidas = ['id', 'data_reserva', 'status', 'cliente', 'servico', 'subtipo', 'funcionario', 'observacao'];
$colunas_selecionadas = array_intersect($colunas_selecionadas, $colunas_permitidas);

if (empty($colunas_selecionadas)) {
    $colunas_selecionadas = ['id', 'data_reserva', 'status', 'cliente', 'servico', 'subtipo', 'funcionario'];
}

$ordenar_por = isset($_GET['ordenar_por']) ? $_GET['ordenar_por'] : 'data_reserva';
$ordem = isset($_GET['ordem']) ? $_GET['ordem'] : 'DESC';

if (!in_array($ordenar_por, $colunas_permitidas)) {
    $ordenar_por = 'data_reserva';
}

$ordem = ($ordem === 'ASC') ? 'ASC' : 'DESC';

// Construção da consulta SQL
$sql = "SELECT r.id, r.data_reserva, r.status, r.observacao, 
               c.nome AS cliente, c.id AS cliente_id,
               s.nome AS servico, s.id AS servico_id,
               ss.nome AS subtipo, ss.id AS servico_subtipo_id,
               f.nome AS funcionario, f.id AS funcionario_id
        FROM reserva r
        JOIN cliente c ON r.cliente_id = c.id
        JOIN servico s ON r.servico_id = s.id
        JOIN servico_subtipo ss ON r.servico_subtipo_id = ss.id
        JOIN funcionario f ON r.funcionario_id = f.id
        WHERE 1=1";

$params = [];
$param_types = "";

if (!empty($search)) {
    $sql .= " AND (c.nome LIKE ? OR f.nome LIKE ? OR s.nome LIKE ? OR ss.nome LIKE ? OR r.observacao LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= "sssss";
}

if (!empty($status_filter)) {
    $sql .= " AND r.status = ?";
    $params[] = $status_filter;
    $param_types .= "s";
}

if (!empty($data_inicio)) {
    $sql .= " AND r.data_reserva >= ?";
    $params[] = $data_inicio . " 00:00:00";
    $param_types .= "s";
}

if (!empty($data_fim)) {
    $sql .= " AND r.data_reserva <= ?";
    $params[] = $data_fim . " 23:59:59";
    $param_types .= "s";
}

// Ordenação
$sql .= " ORDER BY ";
switch ($ordenar_por) {
    case 'cliente':
        $sql .= "c.nome";
        break;
    case 'servico':
        $sql .= "s.nome";
        break;
    case 'subtipo':
        $sql .= "ss.nome";
        break;
    case 'funcionario':
        $sql .= "f.nome";
        break;
    default:
        $sql .= "r.$ordenar_por";
}
$sql .= " $ordem";

// Executar a consulta
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}

$stmt->execute();
$resultado = $stmt->get_result();
$reservas = $resultado->fetch_all(MYSQLI_ASSOC);
$stmt->close();

ob_start();
?>

<div class="container py-4">
    <!-- Modal de Confirmação de Exclusão -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmDeleteLabel">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Tem certeza que deseja excluir esta reserva?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Excluir</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Alertas de Sucesso/Erro -->
    <?php if ($mensagem): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($mensagem); ?></div>
    <?php endif; ?>
    
    <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <!-- Controles e Filtros -->
    <div class="card mb-4">
        <div class="card-header">
            <h3>Filtros e Opções</h3>
        </div>
        <div class="card-body">
            <form method="GET" action="reservas.php" id="filterForm" class="row g-3">
                <!-- Pesquisa -->
                <div class="col-md-4">
                    <label for="search" class="form-label">Pesquisar</label>
                    <input type="text" name="search" id="search" class="form-control" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Cliente, funcionário, serviço...">
                </div>
                
                <!-- Filtro de Status -->
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">Todos</option>
                        <option value="pendente" <?php echo $status_filter === 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                        <option value="confirmada" <?php echo $status_filter === 'confirmada' ? 'selected' : ''; ?>>Confirmada</option>
                        <option value="cancelada" <?php echo $status_filter === 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                        <option value="concluída" <?php echo $status_filter === 'concluída' ? 'selected' : ''; ?>>Concluída</option>
                    </select>
                </div>
                
                <!-- Filtro de Data -->
                <div class="col-md-2">
                    <label for="data_inicio" class="form-label">Data Início</label>
                    <input type="date" name="data_inicio" id="data_inicio" class="form-control"
                           value="<?php echo htmlspecialchars($data_inicio); ?>">
                </div>
                
                <div class="col-md-2">
                    <label for="data_fim" class="form-label">Data Fim</label>
                    <input type="date" name="data_fim" id="data_fim" class="form-control"
                           value="<?php echo htmlspecialchars($data_fim); ?>">
                </div>
                
                <!-- Botões -->
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                </div>
                
                <div class="col-md-12 mt-3">
                    <a href="reservas.php?clear=1" class="btn btn-secondary me-2">Limpar Filtros</a>
                    <a href="adicionar_reserva.php" class="btn btn-success">Nova Reserva</a>
                    <button type="button" class="btn btn-primary ms-2" onclick="window.print()">Imprimir</button>
                    
                    <!-- Dropdown de Colunas -->
                    <div class="dropdown d-inline-block ms-2">
                        <button class="btn btn-outline-dark dropdown-toggle" type="button" id="dropdownMenuButton" 
                                data-bs-toggle="dropdown" aria-expanded="false">
                            Selecionar Colunas
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <li>
                                <label class="dropdown-item">
                                    <input type="checkbox" class="form-check-input me-2" id="checkId" 
                                           <?php echo in_array('id', $colunas_selecionadas) ? 'checked' : ''; ?>> ID
                                </label>
                            </li>
                            <li>
                                <label class="dropdown-item">
                                    <input type="checkbox" class="form-check-input me-2" id="checkData" 
                                           <?php echo in_array('data_reserva', $colunas_selecionadas) ? 'checked' : ''; ?>> Data e Hora
                                </label>
                            </li>
                            <li>
                                <label class="dropdown-item">
                                    <input type="checkbox" class="form-check-input me-2" id="checkStatus" 
                                           <?php echo in_array('status', $colunas_selecionadas) ? 'checked' : ''; ?>> Status
                                </label>
                            </li>
                            <li>
                                <label class="dropdown-item">
                                    <input type="checkbox" class="form-check-input me-2" id="checkCliente" 
                                           <?php echo in_array('cliente', $colunas_selecionadas) ? 'checked' : ''; ?>> Cliente
                                </label>
                            </li>
                            <li>
                                <label class="dropdown-item">
                                    <input type="checkbox" class="form-check-input me-2" id="checkServico" 
                                           <?php echo in_array('servico', $colunas_selecionadas) ? 'checked' : ''; ?>> Serviço
                                </label>
                            </li>
                            <li>
                                <label class="dropdown-item">
                                    <input type="checkbox" class="form-check-input me-2" id="checkSubtipo" 
                                           <?php echo in_array('subtipo', $colunas_selecionadas) ? 'checked' : ''; ?>> Subtipo
                                </label>
                            </li>
                            <li>
                                <label class="dropdown-item">
                                    <input type="checkbox" class="form-check-input me-2" id="checkFuncionario" 
                                           <?php echo in_array('funcionario', $colunas_selecionadas) ? 'checked' : ''; ?>> Funcionário
                                </label>
                            </li>
                            <li>
                                <label class="dropdown-item">
                                    <input type="checkbox" class="form-check-input me-2" id="checkObservacao" 
                                           <?php echo in_array('observacao', $colunas_selecionadas) ? 'checked' : ''; ?>> Observação
                                </label>
                            </li>
                        </ul>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabela de Reservas -->
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <?php if (in_array('id', $colunas_selecionadas)): ?>
                        <th>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['ordenar_por' => 'id', 'ordem' => ($ordem == 'ASC' ? 'DESC' : 'ASC')])); ?>" class="text-white text-decoration-none">
                                ID <?php echo ($ordenar_por == 'id') ? ($ordem == 'ASC' ? '▲' : '▼') : ''; ?>
                            </a>
                        </th>
                    <?php endif; ?>
                    
                    <?php if (in_array('data_reserva', $colunas_selecionadas)): ?>
                        <th>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['ordenar_por' => 'data_reserva', 'ordem' => ($ordem == 'ASC' ? 'DESC' : 'ASC')])); ?>" class="text-white text-decoration-none">
                                Data e Hora <?php echo ($ordenar_por == 'data_reserva') ? ($ordem == 'ASC' ? '▲' : '▼') : ''; ?>
                            </a>
                        </th>
                    <?php endif; ?>
                    
                    <?php if (in_array('status', $colunas_selecionadas)): ?>
                        <th>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['ordenar_por' => 'status', 'ordem' => ($ordem == 'ASC' ? 'DESC' : 'ASC')])); ?>" class="text-white text-decoration-none">
                                Status <?php echo ($ordenar_por == 'status') ? ($ordem == 'ASC' ? '▲' : '▼') : ''; ?>
                            </a>
                        </th>
                    <?php endif; ?>
                    
                    <?php if (in_array('cliente', $colunas_selecionadas)): ?>
                        <th>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['ordenar_por' => 'cliente', 'ordem' => ($ordem == 'ASC' ? 'DESC' : 'ASC')])); ?>" class="text-white text-decoration-none">
                                Cliente <?php echo ($ordenar_por == 'cliente') ? ($ordem == 'ASC' ? '▲' : '▼') : ''; ?>
                            </a>
                        </th>
                    <?php endif; ?>
                    
                    <?php if (in_array('servico', $colunas_selecionadas)): ?>
                        <th>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['ordenar_por' => 'servico', 'ordem' => ($ordem == 'ASC' ? 'DESC' : 'ASC')])); ?>" class="text-white text-decoration-none">
                                Serviço <?php echo ($ordenar_por == 'servico') ? ($ordem == 'ASC' ? '▲' : '▼') : ''; ?>
                            </a>
                        </th>
                    <?php endif; ?>
                    
                    <?php if (in_array('subtipo', $colunas_selecionadas)): ?>
                        <th>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['ordenar_por' => 'subtipo', 'ordem' => ($ordem == 'ASC' ? 'DESC' : 'ASC')])); ?>" class="text-white text-decoration-none">
                                Subtipo <?php echo ($ordenar_por == 'subtipo') ? ($ordem == 'ASC' ? '▲' : '▼') : ''; ?>
                            </a>
                        </th>
                    <?php endif; ?>
                    
                    <?php if (in_array('funcionario', $colunas_selecionadas)): ?>
                        <th>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['ordenar_por' => 'funcionario', 'ordem' => ($ordem == 'ASC' ? 'DESC' : 'ASC')])); ?>" class="text-white text-decoration-none">
                                Funcionário <?php echo ($ordenar_por == 'funcionario') ? ($ordem == 'ASC' ? '▲' : '▼') : ''; ?>
                            </a>
                        </th>
                    <?php endif; ?>
                    
                    <?php if (in_array('observacao', $colunas_selecionadas)): ?>
                        <th>Observação</th>
                    <?php endif; ?>
                    
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($reservas) > 0): ?>
                    <?php foreach ($reservas as $reserva): ?>
                        <tr>
                            <?php if (in_array('id', $colunas_selecionadas)): ?>
                                <td><?php echo htmlspecialchars($reserva['id']); ?></td>
                            <?php endif; ?>
                            
                            <?php if (in_array('data_reserva', $colunas_selecionadas)): ?>
                                <td><?php echo date('d/m/Y H:i', strtotime($reserva['data_reserva'])); ?></td>
                            <?php endif; ?>
                            
                            <?php if (in_array('status', $colunas_selecionadas)): ?>
                                <td>
                                    <?php 
                                    $status_class = '';
                                    switch ($reserva['status']) {
                                        case 'pendente':
                                            $status_class = 'bg-warning text-dark';
                                            break;
                                        case 'confirmada':
                                            $status_class = 'bg-info text-dark';
                                            break;
                                        case 'cancelada':
                                            $status_class = 'bg-danger text-white';
                                            break;
                                        case 'concluída':
                                            $status_class = 'bg-success text-white';
                                            break;
                                    }
                                    ?>
                                    <span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars(ucfirst($reserva['status'])); ?></span>
                                </td>
                            <?php endif; ?>
                            
                            <?php if (in_array('cliente', $colunas_selecionadas)): ?>
                                <td><?php echo htmlspecialchars($reserva['cliente']); ?></td>
                            <?php endif; ?>
                            
                            <?php if (in_array('servico', $colunas_selecionadas)): ?>
                                <td><?php echo htmlspecialchars($reserva['servico']); ?></td>
                            <?php endif; ?>
                            
                            <?php if (in_array('subtipo', $colunas_selecionadas)): ?>
                                <td><?php echo htmlspecialchars($reserva['subtipo']); ?></td>
                            <?php endif; ?>
                            
                            <?php if (in_array('funcionario', $colunas_selecionadas)): ?>
                                <td><?php echo htmlspecialchars($reserva['funcionario']); ?></td>
                            <?php endif; ?>
                            
                            <?php if (in_array('observacao', $colunas_selecionadas)): ?>
                                <td><?php echo htmlspecialchars($reserva['observacao'] ?: '-'); ?></td>
                            <?php endif; ?>
                            
                            <td>
                                <!-- Ações -->
                                <div class="btn-group">
                                    <a href="editar_reserva.php?id=<?php echo $reserva['id']; ?>" class="btn btn-warning btn-sm">Editar</a>
                                    <button type="button" class="btn btn-danger btn-sm btn-eliminar" data-id="<?php echo $reserva['id']; ?>">Excluir</button>
                                    
                                    <!-- Dropdown para alteração de status -->
                                    <button type="button" class="btn btn-info btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                        Status
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item alter-status" data-id="<?php echo $reserva['id']; ?>" data-status="pendente" href="#">Pendente</a></li>
                                        <li><a class="dropdown-item alter-status" data-id="<?php echo $reserva['id']; ?>" data-status="confirmada" href="#">Confirmada</a></li>
                                        <li><a class="dropdown-item alter-status" data-id="<?php echo $reserva['id']; ?>" data-status="cancelada" href="#">Cancelada</a></li>
                                        <li><a class="dropdown-item alter-status" data-id="<?php echo $reserva['id']; ?>" data-status="concluída" href="#">Concluída</a></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?php echo count($colunas_selecionadas) + 1; ?>" class="text-center">Nenhuma reserva encontrada</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Manipulação da seleção de colunas
    const checkboxes = document.querySelectorAll('.dropdown-menu input[type="checkbox"]');
    checkboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const selectedColumns = [];
            checkboxes.forEach(function(cb) {
                if (cb.checked) {
                    selectedColumns.push(cb.id.replace('check', '').toLowerCase());
                }
            });
            if (selectedColumns.length > 0) {
                const url = new URL(window.location.href);
                url.searchParams.set('colunas', selectedColumns.join(','));
                window.location.href = url.toString();
            }
        });
    });

    // Confirmar exclusão
    const btnEliminar = document.querySelectorAll('.btn-eliminar');
    const confirmDeleteModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    
    let reservaId;
    
    btnEliminar.forEach(function(btn) {
        btn.addEventListener('click', function() {
            reservaId = this.getAttribute('data-id');
            confirmDeleteModal.show();
        });
    });
    
    confirmDeleteBtn.addEventListener('click', function() {
        window.location.href = 'excluir_reserva.php?id=' + reservaId;
    });

    // Alterar status
    const statusLinks = document.querySelectorAll('.alter-status');
    statusLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            const status = this.getAttribute('data-status');
            window.location.href = 'alterar_status_reserva.php?id=' + id + '&status=' + status;
        });
    });
});
</script>

<?php
$content = ob_get_clean();
include '../includes/layout.php';
?>