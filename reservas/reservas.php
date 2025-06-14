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

// CAPTURAR E LIMPAR AS MENSAGENS DE SESSÃO IMEDIATAMENTE
$mensagem = '';
$success_message = '';

if (isset($_SESSION['mensagem'])) {
    $mensagem = $_SESSION['mensagem'];
    unset($_SESSION['mensagem']); // Limpar imediatamente após capturar
}

if (isset($_SESSION['success'])) {
    $success_message = $_SESSION['success'];
    unset($_SESSION['success']); // Limpar imediatamente após capturar
}

// Debug logging
error_log("DEBUG: reservas.php - Mensagem de erro: " . $mensagem);
error_log("DEBUG: reservas.php - Mensagem de sucesso: " . $success_message);

// Configurações de filtro e paginação
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : '';
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : '';

// Colunas e ordenação
$colunas_selecionadas = isset($_GET['colunas']) ? explode(',', $_GET['colunas']) : 
    ['data_reserva', 'status', 'cliente', 'servico', 'subtipo', 'funcionario'];
$colunas_permitidas = ['id', 'data_reserva', 'status', 'cliente', 'servico', 'subtipo', 'funcionario', 'observacao'];
$colunas_selecionadas = array_intersect($colunas_selecionadas, $colunas_permitidas);

// Ensure 'data_reserva' is always included and is the first column
if (!in_array('data_reserva', $colunas_selecionadas)) {
    array_unshift($colunas_selecionadas, 'data_reserva');
} else {
    // If already present, ensure it's the first element
    $colunas_selecionadas = array('data_reserva') + array_diff($colunas_selecionadas, array('data_reserva'));
}

if (empty($colunas_selecionadas)) {
    // Fallback to default if somehow empty, still ensuring data_reserva is first
    $colunas_selecionadas = ['data_reserva', 'status', 'cliente', 'servico', 'subtipo', 'funcionario'];
} else if ($colunas_selecionadas[0] !== 'data_reserva') {
     // Ensure data_reserva is the first element if not already
     $colunas_selecionadas = array('data_reserva') + array_diff($colunas_selecionadas, array('data_reserva'));
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

/* Melhorar o estilo dos toasts */
.toast-container {
    z-index: 1055 !important;
}

.toast {
    min-width: 350px;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
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
                    <form id="deleteForm" method="POST" action="eliminar_reserva.php" style="display: inline;">
                        <input type="hidden" name="id" id="deleteId">
                        <button type="submit" class="btn btn-danger px-4" style="border-radius: 8px;">
                            <i class="bi bi-trash me-2"></i>Eliminar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Alertas de Sucesso/Erro -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1050;">
        <?php if ($mensagem): ?>
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
                <div class="progress" style="height: 4px;">
                    <div class="progress-bar bg-danger" role="progressbar" style="width: 100%" id="toastProgressBar"></div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
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
                <div class="progress" style="height: 4px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: 100%" id="toastProgressBar"></div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Filtros -->
    <div class="card shadow-sm mb-4">
        <div class="card-header py-3">
            <h5 class="mb-0">Filtros</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="reservas.php" id="filterForm" class="row g-3">
                <!-- Pesquisa -->
                <div class="col-md-4">
                    <label for="searchInput" class="form-label fs-5">Pesquisar</label>
                    <input type="text" name="search" id="searchInput" class="form-control form-control-lg" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Cliente, funcionário, serviço...">
                </div>

                <!-- Filtro de Status -->
                <div class="col-md-3">
                    <label for="status" class="form-label fs-5">Status</label>
                    <select name="status" id="status" class="form-select form-select-lg">
                        <option value="">Todos</option>
                        <option value="pendente" <?php echo $status_filter === 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                        <option value="confirmada" <?php echo $status_filter === 'confirmada' ? 'selected' : ''; ?>>Confirmada</option>
                        <option value="cancelada" <?php echo $status_filter === 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                        <option value="concluída" <?php echo $status_filter === 'concluída' ? 'selected' : ''; ?>>Concluída</option>
                    </select>
                </div>
                
                <!-- Filtro de Data -->
                <div class="col-md-2">
                    <label for="data_inicio" class="form-label fs-5">Data Início</label>
                    <input type="date" name="data_inicio" id="data_inicio" class="form-control form-control-lg"
                           value="<?php echo htmlspecialchars($data_inicio); ?>">
                </div>
                
                <div class="col-md-2">
                    <label for="data_fim" class="form-label fs-5">Data Fim</label>
                    <input type="date" name="data_fim" id="data_fim" class="form-control form-control-lg"
                           value="<?php echo htmlspecialchars($data_fim); ?>">
                </div>
                
                <!-- Botões -->
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" class="btn btn-primary btn-lg w-100" onclick="document.getElementById('filterForm').submit()">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
                
                <div class="col-12 mt-3">
                    <div class="d-flex gap-2">
                        <a href="reservas.php?clear=1" class="btn btn-secondary btn-lg">
                            <i class="bi bi-x-circle me-2"></i>Limpar Filtros
                        </a>
                        <?php if ($_SESSION['utilizador_tipo'] == 'admin') { ?>
                            <a href="adicionar_reserva.php" class="btn btn-success btn-lg">
                                <i class="bi bi-plus-lg me-2"></i>Nova Reserva
                            </a>
                        <?php } ?>
                        <button class="btn btn-primary btn-lg" onclick="window.print()">
                            <i class="bi bi-printer me-2"></i>Imprimir
                        </button>
                        <!-- Dropdown com filtros -->
                        <div class="dropdown">
                            <button class="btn btn-outline-dark btn-lg dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-columns-gap me-2"></i>Selecionar Colunas
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
                                <li>
                                    <label class="dropdown-item fs-5">
                                        <input type="checkbox" class="form-check-input me-2" id="checkData" name="colunas[]" data-column="data_reserva" <?php echo in_array('data_reserva', $colunas_selecionadas) ? 'checked' : ''; ?>> Data
                                    </label>
                                </li>
                                <li>
                                    <label class="dropdown-item fs-5">
                                        <input type="checkbox" class="form-check-input me-2" id="checkStatus" name="colunas[]" data-column="status" <?php echo in_array('status', $colunas_selecionadas) ? 'checked' : ''; ?>> Status
                                    </label>
                                </li>
                                <li>
                                    <label class="dropdown-item fs-5">
                                        <input type="checkbox" class="form-check-input me-2" id="checkCliente" name="colunas[]" data-column="cliente" <?php echo in_array('cliente', $colunas_selecionadas) ? 'checked' : ''; ?>> Cliente
                                    </label>
                                </li>
                                <li>
                                    <label class="dropdown-item fs-5">
                                        <input type="checkbox" class="form-check-input me-2" id="checkServico" name="colunas[]" data-column="servico" <?php echo in_array('servico', $colunas_selecionadas) ? 'checked' : ''; ?>> Serviço
                                    </label>
                                </li>
                                <li>
                                    <label class="dropdown-item fs-5">
                                        <input type="checkbox" class="form-check-input me-2" id="checkSubtipo" name="colunas[]" data-column="subtipo" <?php echo in_array('subtipo', $colunas_selecionadas) ? 'checked' : ''; ?>> Subtipo
                                    </label>
                                </li>
                                <li>
                                    <label class="dropdown-item fs-5">
                                        <input type="checkbox" class="form-check-input me-2" id="checkFuncionario" name="colunas[]" data-column="funcionario" <?php echo in_array('funcionario', $colunas_selecionadas) ? 'checked' : ''; ?>> Funcionário
                                    </label>
                                </li>
                                <?php if ($_SESSION['utilizador_tipo'] == 'admin') { ?>
                                <li>
                                    <label class="dropdown-item fs-5">
                                        <input type="checkbox" class="form-check-input me-2" id="checkObservacao" name="colunas[]" data-column="observacao" <?php echo in_array('observacao', $colunas_selecionadas) ? 'checked' : ''; ?>> Observação
                                    </label>
                                </li>
                                <?php } ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header py-3">
            <h5 class="mb-0">Reservas</h5>
        </div>
        <div class="card-body p-4">
            <table id="datatablesSimple" class="table table-hover fs-5">
                <thead class="table-dark">
                    <?php foreach ($colunas_selecionadas as $coluna): ?>
                        <th class="py-3">
                            <?php 
                                if ($coluna === 'data_reserva') {
                                    echo 'Data';
                                } else {
                                    echo ucfirst(str_replace('_', ' ', $coluna)); 
                                }
                            ?>
                        </th>
                    <?php endforeach; ?> 
                    <th class="py-3">Ações</th>
                </thead>
                <tfoot>
                    <?php foreach ($colunas_selecionadas as $coluna): ?>
                        <th class="py-3">
                            <?php 
                                if ($coluna === 'data_reserva') {
                                    echo 'Data';
                                } else {
                                    echo ucfirst(str_replace('_', ' ', $coluna)); 
                                }
                            ?>
                        </th>
                    <?php endforeach; ?>
                    <th class="py-3">Ações</th>
                </tfoot>
                <tbody>
                    <?php foreach ($reservas as $reserva): ?>
                        <tr>
                            <?php foreach ($colunas_selecionadas as $coluna): ?>
                                <td class="py-3">
                                    <?php 
                                    switch($coluna) {
                                        case 'data_reserva':
                                            echo date('d/m/Y H:i', strtotime($reserva['data_reserva']));
                                            break;
                                        case 'cliente':
                                            echo htmlspecialchars($reserva['cliente_nome'] ?? '');
                                            break;
                                        case 'servico':
                                            echo htmlspecialchars($reserva['servico_nome'] ?? '');
                                            break;
                                        case 'subtipo':
                                            echo htmlspecialchars($reserva['subtipo_nome'] ?? '');
                                            break;
                                        case 'funcionario':
                                            echo htmlspecialchars($reserva['funcionario_nome'] ?? '');
                                            break;
                                        case 'observacao':
                                            echo htmlspecialchars($reserva['observacao'] ?? '');
                                            break;
                                        default:
                                            echo htmlspecialchars($reserva[$coluna] ?? '');
                                    }
                                    ?>
                                </td>
                            <?php endforeach; ?>
                            <td class="py-3">
                                <a href="editar_reserva.php?id=<?php echo urlencode($reserva['id']); ?>" class="btn btn-warning btn-sm">
                                    <i class="bi bi-pencil-square me-1"></i>Editar
                                </a>
                                <?php if ($_SESSION['utilizador_tipo'] == 'admin') { ?>
                                <button class="btn btn-danger btn-sm btn-eliminar" data-id="<?php echo $reserva['id']; ?>" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal">
                                    <i class="bi bi-trash me-1"></i>Eliminar
                                </button>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="../assets/js/style.js"></script>
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>

<script>
    window.addEventListener('DOMContentLoaded', event => {
        // Initialize DataTable
        const datatablesSimple = document.getElementById('datatablesSimple');
        if (datatablesSimple) {
            new simpleDatatables.DataTable(datatablesSimple, {
                searchable: false,
                perPage: 10,
                perPageSelect: [10, 25, 50, 100],
                labels: {
                    placeholder: "Pesquisar...",
                    perPage: "Itens por página",
                    noRows: "Nenhuma reserva encontrada",
                    info: "Mostrando {start} até {end} de {rows} reservas",
                    noResults: "Nenhum resultado encontrado para {query}"
                }
            });
        }

        // Delete button click handler
        document.querySelectorAll('.btn-eliminar').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                document.getElementById('deleteId').value = id;
            });
        });

        // Initialize toasts
        function initializeToast(toastElement, type) {
            if (toastElement && typeof bootstrap !== 'undefined') {
                const toast = new bootstrap.Toast(toastElement, {
                    animation: true,
                    autohide: true,
                    delay: 5000
                });
                toast.show();
                console.log(type + ' toast initialized and shown');
            } else {
                console.error('Bootstrap not available or toast element not found');
            }
        }

        // Initialize success and error toasts
        const successToast = document.getElementById('successToast');
        const errorToast = document.getElementById('errorToast');

        if (successToast) {
            initializeToast(successToast, 'Success');
        }
        
        if (errorToast) {
            initializeToast(errorToast, 'Error');
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        const toastEl = document.getElementById('successToast') || document.getElementById('errorToast');
        const progressBar = document.getElementById('toastProgressBar');
        if (toastEl && progressBar) {
            let width = 100;
            const duration = 3000; // 3 segundos
            const intervalTime = 30;

            // Mostra o toast
            const toast = new bootstrap.Toast(toastEl, { autohide: false });
            toast.show();

            // Anima a barra
            const interval = setInterval(() => {
                width -= (intervalTime / duration) * 100;
                progressBar.style.width = width + "%";
                if (width <= 0) {
                    clearInterval(interval);
                    toast.hide();
                }
            }, intervalTime);
        }
    });
</script>

<?php
$conn->close(); // Close the database connection after the query and fetching
$content = ob_get_clean();
include '../includes/layout.php';
?>