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

// Construir a query base
$sql = "SELECT r.*, c.nome as cliente_nome, s.nome as servico_nome, ss.nome as subtipo_nome, f.nome as funcionario_nome 
        FROM reserva r 
        JOIN cliente c ON r.cliente_id = c.id 
        JOIN servico s ON r.servico_id = s.id 
        JOIN servico_subtipo ss ON r.servico_subtipo_id = ss.id 
        JOIN funcionario f ON r.funcionario_id = f.id 
        WHERE 1=1";

// Adicionar condições de busca se houver
if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $sql .= " AND (c.nome LIKE '%$search%' OR s.nome LIKE '%$search%' OR ss.nome LIKE '%$search%' OR f.nome LIKE '%$search%')";
}

if (!empty($status_filter)) {
    $status_filter = $conn->real_escape_string($status_filter);
    $sql .= " AND r.status = '$status_filter'";
}

if (!empty($data_inicio)) {
    $data_inicio = $conn->real_escape_string($data_inicio);
    $sql .= " AND DATE(r.data_reserva) >= '$data_inicio'";
}

if (!empty($data_fim)) {
    $data_fim = $conn->real_escape_string($data_fim);
    $sql .= " AND DATE(r.data_reserva) <= '$data_fim'";
}

// Adicionar ordenação
$sql .= " ORDER BY r.data_reserva DESC";

// Executar a query
$result = $conn->query($sql);
$reservas = $result->fetch_all(MYSQLI_ASSOC);

ob_start();
?>

<style>
.dropdown-menu-end {
    right: 0;
    left: auto;
}
.dropdown-menu {
    margin-top: 0;
}
/* Custom: Dropdown opens to the right */
.dropdown-menu-side {
    left: 100% !important;
    top: 0 !important;
    right: auto !important;
    margin-top: 0 !important;
    margin-left: 0.1rem;
    min-width: 10rem;
    position: absolute;
}
.btn-group {
    position: relative;
}
</style>

<div class="container py-4">
    <!-- Modal de Confirmação de Exclusão -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0" style="border-radius: 15px; box-shadow: 0 0 20px rgba(0,0,0,0.2);">
                <div class="modal-header border-0 bg-danger text-white" style="border-radius: 15px 15px 0 0;">
                    <h5 class="modal-title" id="confirmDeleteLabel">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        Atenção: Eliminação de Reserva
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="bi bi-exclamation-circle text-danger" style="font-size: 4rem;"></i>
                    <h4 class="mt-3 mb-3">Tem certeza que deseja eliminar esta reserva?</h4>
                    <p class="text-muted">Esta ação não pode ser desfeita e todos os dados associados serão permanentemente removidos.</p>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal" style="border-radius: 8px;">
                        <i class="bi bi-x-circle me-2"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-danger px-4" id="confirmDeleteBtn" style="border-radius: 8px;">
                        <i class="bi bi-trash me-2"></i>Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Alertas de Sucesso/Erro -->
    <?php if ($mensagem): ?>
        <div class="toast-container position-fixed bottom-0 end-0 p-3">
            <div id="errorToast" class="toast align-items-center text-white border-0" role="alert" aria-live="assertive" aria-atomic="true" style="background: linear-gradient(45deg, #dc3545, #c82333); border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                <div class="d-flex align-items-center p-3">
                    <div class="toast-icon me-3">
                        <i class="bi bi-exclamation-circle-fill fs-4"></i>
                    </div>
                    <div class="toast-body fs-5">
                        <?php echo htmlspecialchars($mensagem); ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var toastEl = document.getElementById('errorToast');
                var toast = new bootstrap.Toast(toastEl, {
                    animation: true,
                    autohide: true,
                    delay: 3000
                });
                toast.show();
            });
        </script>
    <?php endif; ?>
    
    <?php if ($success_message): ?>
        <div class="toast-container position-fixed bottom-0 end-0 p-3">
            <div id="successToast" class="toast align-items-center text-white border-0" role="alert" aria-live="assertive" aria-atomic="true" style="background: linear-gradient(45deg, #28a745, #20c997); border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                <div class="d-flex align-items-center p-3">
                    <div class="toast-icon me-3">
                        <i class="bi bi-check-circle-fill fs-4"></i>
                    </div>
                    <div class="toast-body fs-5">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var toastEl = document.getElementById('successToast');
                var toast = new bootstrap.Toast(toastEl, {
                    animation: true,
                    autohide: true,
                    delay: 3000
                });
                toast.show();
            });
        </script>
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
                                <td><?php echo htmlspecialchars($reserva['cliente_nome']); ?></td>
                            <?php endif; ?>
                            
                            <?php if (in_array('servico', $colunas_selecionadas)): ?>
                                <td><?php echo htmlspecialchars($reserva['servico_nome']); ?></td>
                            <?php endif; ?>
                            
                            <?php if (in_array('subtipo', $colunas_selecionadas)): ?>
                                <td><?php echo htmlspecialchars($reserva['subtipo_nome']); ?></td>
                            <?php endif; ?>
                            
                            <?php if (in_array('funcionario', $colunas_selecionadas)): ?>
                                <td><?php echo htmlspecialchars($reserva['funcionario_nome']); ?></td>
                            <?php endif; ?>
                            
                            <?php if (in_array('observacao', $colunas_selecionadas)): ?>
                                <td><?php echo htmlspecialchars($reserva['observacao'] ?: '-'); ?></td>
                            <?php endif; ?>
                            
                            <td>
                                <!-- Ações -->
                                <div class="btn-group dropend">
                                    <a href="editar_reserva.php?id=<?php echo $reserva['id']; ?>" class="btn btn-warning btn-sm">Editar</a>
                                    <button type="button" class="btn btn-danger btn-sm btn-eliminar" data-id="<?php echo $reserva['id']; ?>">Eliminar</button>
                                    
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


<?php
$content = ob_get_clean();
include '../includes/layout.php';
?>