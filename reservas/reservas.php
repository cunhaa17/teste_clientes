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

$title = "Reservas - Gestão";
include_once '../includes/db_conexao.php';

if (isset($_GET['clear'])) {
    header("Location: reservas.php");
    exit();
}

// Processar exclusão de reserva
if (isset($_POST['delete_reserva'])) {
    $reserva_id = $_POST['reserva_id'];
    
    try {
        // Excluir a reserva
        $delete_sql = "DELETE FROM Reserva WHERE id = $reserva_id";
        $delete_result = $conn->query($delete_sql);
        
        if ($delete_result === false) {
            throw new Exception("Erro ao excluir reserva: " . $conn->error);
        }
        
        if ($conn->affected_rows > 0) {
            $_SESSION['success'] = "Reserva excluída com sucesso!";
        } else {
            $_SESSION['error'] = "Erro ao excluir reserva.";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Erro ao excluir reserva: " . $e->getMessage();
    }
    
    header('Location: reservas.php');
    exit();
}

// Mensagens de sucesso/erro
if (isset($_SESSION['success'])) {
    $success_message = $_SESSION['success'];
    unset($_SESSION['success']);
} else {
    $success_message = '';
}

if (isset($_SESSION['error'])) {
    $error_message = $_SESSION['error'];
    unset($_SESSION['error']);
} else {
    $error_message = '';
}

// Debug logging
error_log("DEBUG: reservas.php - Mensagem de erro: " . $error_message);
error_log("DEBUG: reservas.php - Mensagem de sucesso: " . $success_message);

// Configurações de filtro e paginação
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : '';
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : '';

$view = isset($_GET['view']) ? $_GET['view'] : 'tabela';

// Colunas e ordenação
$colunas_selecionadas = isset($_GET['colunas']) 
    ? (is_array($_GET['colunas']) ? $_GET['colunas'] : explode(',', $_GET['colunas'])) 
    : ['data_reserva', 'status', 'cliente', 'servico', 'subtipo', 'funcionario'];
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
/* No longer needed as dropend handles position */
/* .dropdown-menu-end {
    right: 0;
    left: auto;
} */
/* No longer needed as dropend handles position */
/* .dropdown-menu {
    margin-top: 0;
} */
/* Custom: Dropdown opens to the right - no longer needed with Bootstrap's dropend */
/* .dropdown-menu-side {
    left: 100% !important;
    top: 0 !important;
    right: auto !important;
    margin-top: 0 !important;
    margin-left: 0.1rem;
    min-width: 10rem;
    position: absolute;
} */
.btn-group {
    position: relative;
}

/* Force dropend dropdown to open to the right */
.dropdown.dropend .dropdown-item {
    position: relative;
}

.dropdown.dropend .dropdown-menu {
    top: 0;
    left: 100%;
    margin-left: 0.5rem; /* Space between button and menu */
}

/* Ensure labels inside dropdown items wrap text and have enough space */
.dropdown-item .form-check-label {
    white-space: normal;
    word-wrap: break-word; /* Ensure long words break */
}

/* Melhorar o estilo dos toasts */
.toast-container {
    z-index: 1055 !important;
}

.toast {
    min-width: 350px;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

@media print {
    /* Ocultar elementos que não devem aparecer na impressão */
    body > nav,                   /* Assumindo que o seu layout.php tem uma navbar no topo */
    #layoutSidenav_nav,           /* Assumindo que o seu layout usa um menu lateral com este ID */
    .card.shadow-sm.mb-4,         /* Card dos Filtros */
    .modal,
    .toast-container,
    .btn,                         /* Oculta todos os botões */
    .dataTables_wrapper .dataTables_length, /* Oculta o seletor de itens por página da tabela */
    .dataTables_wrapper .dataTables_filter, /* Oculta a pesquisa do datatables */
    .dataTables_wrapper .dataTables_info,   /* Oculta a informação de paginação */
    .dataTables_wrapper .dataTables_paginate, /* Oculta a paginação */
    #datatablesSimple tfoot,      /* Oculta o rodapé da tabela */
    #datatablesSimple th:last-child, /* Oculta o cabeçalho da coluna "Ações" */
    #datatablesSimple td:last-child { /* Oculta a célula da coluna "Ações" */
        display: none !important;
    }

    /* Ajustar o corpo da página para impressão */
    body {
        background-color: #fff !important;
    }

    /* Fazer com que o conteúdo principal ocupe toda a largura */
    #layoutSidenav_content, .container-fluid {
        padding: 0 !important;
        margin: 0 !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
    }

    .card-header, .card-body {
        padding: 10px 0 !important;
    }
    
    .card-header h5 {
        text-align: center;
        font-size: 1.5rem;
        width: 100%;
    }

    /* Mostrar apenas a visualização ativa */
    body.view-calendario #tabelaContainer {
        display: none !important;
    }
    body.view-tabela #calendarContainer {
        display: none !important;
    }
    
    /* Garantir que o container visível é de fato visível */
    body.view-tabela #tabelaContainer,
    body.view-calendario #calendarContainer {
        display: block !important;
    }

    /* Título para a impressão */
    .card-header::before {
        content: "Relatório de Reservas";
        display: block;
        text-align: center;
        font-size: 24px;
        margin-bottom: 20px;
        font-weight: bold;
    }
    .card-header h5 {
        display: none; /* Oculta o título original "Reservas" */
    }
}

#calendarContainer, #fullcalendar {
    height: 100% !important;
    min-height: 600px;
}
.card-body.p-4 {
    height: 80vh;
    min-height: 600px;
    display: flex;
    flex-direction: column;
}
</style>

<div class="container-fluid py-4">
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
        <?php if ($error_message): ?>
            <div id="errorToast" class="toast align-items-center text-white border-0" role="alert" aria-live="assertive" aria-atomic="true" style="background: linear-gradient(45deg, #dc3545, #c82333); border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                <div class="d-flex align-items-center p-3">
                    <div class="toast-icon me-3">
                        <i class="bi bi-exclamation-circle-fill fs-4"></i>
                    </div>
                    <div class="toast-body fs-5">
                        <?php echo htmlspecialchars($error_message); ?>
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
                <input type="hidden" name="view" id="viewInput" value="tabela">
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
                
                <!-- Data Range Melhorado -->
                <div class="col-md-3" id="filtro-daterange-container">
                    <label for="daterange" class="form-label fs-5">Intervalo de Datas</label>
                    <input type="text" name="daterange" id="daterange" class="form-control form-control-lg" value="<?php echo htmlspecialchars(isset($_GET['daterange']) ? $_GET['daterange'] : ''); ?>" autocomplete="off" placeholder="Escolha o intervalo">
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
                        <a href="gerar_pdf.php" id="pdfLink" class="btn btn-primary btn-lg" target="_blank">
                            <i class="bi bi-file-earmark-pdf-fill me-2"></i>Gerar PDF
                        </a>
                        <!-- Dropdown com filtros -->
                        <div class="dropdown dropend">
                            <button class="btn btn-outline-dark btn-lg dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-columns-gap me-2"></i>Selecionar Colunas
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
                                <li>
                                    <label class="dropdown-item fs-5">
                                        <input type="checkbox" class="form-check-input me-2" id="checkData" name="colunas[]" value="data_reserva" <?php echo in_array('data_reserva', $colunas_selecionadas) ? 'checked' : ''; ?>> Data
                                    </label>
                                </li>
                                <li>
                                    <label class="dropdown-item fs-5">
                                        <input type="checkbox" class="form-check-input me-2" id="checkStatus" name="colunas[]" value="status" <?php echo in_array('status', $colunas_selecionadas) ? 'checked' : ''; ?>> Status
                                    </label>
                                </li>
                                <li>
                                    <label class="dropdown-item fs-5">
                                        <input type="checkbox" class="form-check-input me-2" id="checkCliente" name="colunas[]" value="cliente" <?php echo in_array('cliente', $colunas_selecionadas) ? 'checked' : ''; ?>> Cliente
                                    </label>
                                </li>
                                <li>
                                    <label class="dropdown-item fs-5">
                                        <input type="checkbox" class="form-check-input me-2" id="checkServico" name="colunas[]" value="servico" <?php echo in_array('servico', $colunas_selecionadas) ? 'checked' : ''; ?>> Serviço
                                    </label>
                                </li>
                                <li>
                                    <label class="dropdown-item fs-5">
                                        <input type="checkbox" class="form-check-input me-2" id="checkSubtipo" name="colunas[]" value="subtipo" <?php echo in_array('subtipo', $colunas_selecionadas) ? 'checked' : ''; ?>> Subtipo
                                    </label>
                                </li>
                                <li>
                                    <label class="dropdown-item fs-5">
                                        <input type="checkbox" class="form-check-input me-2" id="checkFuncionario" name="colunas[]" value="funcionario" <?php echo in_array('funcionario', $colunas_selecionadas) ? 'checked' : ''; ?>> Funcionário
                                    </label>
                                </li>
                                <?php if ($_SESSION['utilizador_tipo'] == 'admin') { ?>
                                <li>
                                    <label class="dropdown-item fs-5">
                                        <input type="checkbox" class="form-check-input me-2" id="checkObservacao" name="colunas[]" value="observacao" <?php echo in_array('observacao', $colunas_selecionadas) ? 'checked' : ''; ?>> Observação
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
        <div class="card-header d-flex justify-content-between align-items-center py-3">
            <h5 class="mb-0">Reservas</h5>
            <div class="btn-group" role="group">
                <button id="btnTabela" class="btn btn-outline-primary active" onclick="showTabela()">Tabela</button>
                <button id="btnCalendario" class="btn btn-outline-primary" onclick="showCalendario()">Calendário</button>
            </div>
        </div>
        <div class="card-body p-4">
            <div id="tabelaContainer">
                <?php if (empty($reservas)) : ?>
                    <div class="alert alert-warning text-center fs-5 my-4">Nenhuma reserva encontrada para os filtros selecionados.</div>
                <?php else : ?>
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
                                        <button class="btn btn-danger btn-sm delete-btn" data-id="<?php echo $reserva['id']; ?>">
                                            <i class="bi bi-trash me-1"></i>Eliminar
                                        </button>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            <div id="calendarContainer" style="display:none;">
                <div id="fullcalendar"></div>
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/style.js"></script>
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>

<!-- FullCalendar CSS -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet" />
<!-- FullCalendar JS -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<!-- FullCalendar PT Locale -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/locales/pt.js"></script>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" rel="stylesheet" />

<script>
    // =================================================
    // 1. VARIÁVEIS GLOBAIS E DADOS INICIAIS
    // =================================================
    const todasAsReservas = <?php echo json_encode($reservas); ?>;
    let calendar;
let calendarRendered = false;
    let calendarStartDate = null;
    let calendarEndDate = null;

    // =================================================
    // 2. FUNÇÕES AUXILIARES
    // =================================================
function showTabela() {
        document.body.classList.remove('view-calendario');
        document.body.classList.add('view-tabela');
  document.getElementById('calendarContainer').style.display = 'none';
        document.getElementById('tabelaContainer').style.display = 'block';
  document.getElementById('btnTabela').classList.add('active');
  document.getElementById('btnCalendario').classList.remove('active');
        document.getElementById('filtro-daterange-container').style.display = 'block';
  document.getElementById('viewInput').value = 'tabela';
}

function showCalendario() {
        document.body.classList.remove('view-tabela');
        document.body.classList.add('view-calendario');
        document.getElementById('calendarContainer').style.display = 'block';
  document.getElementById('tabelaContainer').style.display = 'none';
  document.getElementById('btnTabela').classList.remove('active');
  document.getElementById('btnCalendario').classList.add('active');
  document.getElementById('filtro-daterange-container').style.display = 'none';
        document.getElementById('viewInput').value = 'calendario';
        
  setTimeout(function() {
    if (!calendarRendered) {
      calendar.render();
      calendarRendered = true;
    } else {
      calendar.updateSize();
    }
  }, 10);
}

function mapReservasToEvents(reservas) {
        // Filtrar apenas reservas confirmadas e concluídas para o calendário
        const reservasAtivas = reservas.filter(r => r.status === 'confirmada' || r.status === 'concluída');
        
        return reservasAtivas.map(r => {
            // Adiciona 30 minutos à data/hora de início para o 'end'
            const startDate = new Date(r.data_reserva);
            const endDate = new Date(startDate.getTime() + 30 * 60000); // 30 minutos

            // Definir cor baseada no serviço
            let color;
            switch(r.servico_nome.toLowerCase()) {
                case 'massagem':
                    color = '#28a745'; // Verde
                    break;
                case 'barbearia':
                    color = '#007bff'; // Azul
                    break;
                case 'cabeleireiro':
                    color = '#ffc107'; // Amarelo
                    break;
                case 'depilação':
                    color = '#dc3545'; // Vermelho
                    break;
                case 'estética avançada':
                    color = '#6f42c1'; // Roxo
                    break;
                case 'manicure/pedicure':
                    color = '#fd7e14'; // Laranja
                    break;
                case 'microblading/shading':
                    color = '#e83e8c'; // Rosa
                    break;
                default:
                    color = '#6c757d'; // Cinza para outros serviços
            }

            return {
    id: r.id,
    title: r.cliente_nome + ' - ' + r.servico_nome,
    start: r.data_reserva,
                end: endDate.toISOString().slice(0, 19), // formato 'YYYY-MM-DDTHH:MM:SS'
    extendedProps: {
      status: r.status,
      funcionario: r.funcionario_nome,
      subtipo: r.subtipo_nome,
      observacao: r.observacao
    },
    color: color
            };
        });
}

function filterReservasBySearch(reservas, searchTerm) {
    if (!searchTerm) {
        // Se não há termo de pesquisa, retorna apenas reservas ativas (confirmadas e concluídas)
        return reservas.filter(r => r.status === 'confirmada' || r.status === 'concluída');
    }
    
    searchTerm = searchTerm.toLowerCase();
    return reservas.filter(r =>
        // Aplicar filtro de status primeiro (apenas confirmadas e concluídas)
        (r.status === 'confirmada' || r.status === 'concluída') &&
        // Depois aplicar filtro de pesquisa
        ((r.cliente_nome && r.cliente_nome.toLowerCase().includes(searchTerm)) ||
        (r.servico_nome && r.servico_nome.toLowerCase().includes(searchTerm)) ||
        (r.subtipo_nome && r.subtipo_nome.toLowerCase().includes(searchTerm)) ||
        (r.funcionario_nome && r.funcionario_nome.toLowerCase().includes(searchTerm)))
    );
}

function updateCalendarEvents(filteredReservas) {
        if (calendar) {
    calendar.removeAllEvents();
            calendar.addEventSource(mapReservasToEvents(filteredReservas));
        }
    }

    function atualizarLinkPDF() {
        const search = document.getElementById('searchInput').value;
        const status = document.getElementById('status').value;
        const view = document.getElementById('viewInput').value;
        let data_inicio = '';
        let data_fim = '';

        if (view === 'calendario') {
            data_inicio = calendarStartDate;
            data_fim = calendarEndDate;

            // Se for vista de dia, força as datas a serem iguais
            if (calendar && calendar.view && calendar.view.type === 'timeGridDay') {
                data_inicio = calendarStartDate;
                data_fim = calendarStartDate;
            }
        } else {
            const daterange = document.getElementById('daterange').value;
            if (daterange) {
                const dates = daterange.split(' até ');
                if (dates.length === 2) {
                    data_inicio = dates[0].trim();
                    data_fim = dates[1].trim();
                }
            }
        }

        // Troca as datas se estiverem invertidas
        if (data_inicio && data_fim && data_inicio > data_fim) {
            const temp = data_inicio;
            data_inicio = data_fim;
            data_fim = temp;
        }

        const params = new URLSearchParams({
            view: view,
            search: search,
            status: status,
            data_inicio: data_inicio || '',
            data_fim: data_fim || ''
        });

        const pdfLink = document.getElementById('pdfLink');
        if (pdfLink) {
            pdfLink.href = 'gerar_pdf.php?' + params.toString();
}
    }

    // ===============================================================
    // 3. PONTO DE ENTRADA PRINCIPAL - DOMContentLoaded
    // ===============================================================
    document.addEventListener('DOMContentLoaded', function() {
        // --- INICIALIZAÇÃO DE ALERTAS E TOASTS ---
        <?php if ($success_message): ?>
        Swal.fire({
            icon: 'success', title: 'Sucesso!', text: '<?php echo addslashes($success_message); ?>',
            timer: 3000, timerProgressBar: true
        });
        <?php endif; ?>
        <?php if ($error_message): ?>
        Swal.fire({
            icon: 'error', title: 'Erro!', text: '<?php echo addslashes($error_message); ?>',
            timer: 3000, timerProgressBar: true
        });
        <?php endif; ?>

        // --- INICIALIZAÇÃO DA TABELA ---
        const datatablesSimple = document.getElementById('datatablesSimple');
        if (datatablesSimple) {
            new simpleDatatables.DataTable(datatablesSimple, {
                searchable: false, perPage: 10,
                labels: {
                    placeholder: "Pesquisar...", perPage: "Itens por página",
                    noRows: "Nenhuma reserva encontrada", info: "Mostrando {start} até {end} de {rows} reservas"
                }
            });
        }

        // --- INICIALIZAÇÃO DO FILTRO DE DATAS (DATERANGEPICKER) ---
  $('#daterange').daterangepicker({
    locale: {
                format: 'YYYY-MM-DD', separator: ' até ', applyLabel: 'Aplicar', cancelLabel: 'Cancelar',
      monthNames: ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'],
    },
    autoUpdateInput: false
        }).on('apply.daterangepicker', function(ev, picker) {
    $(this).val(picker.startDate.format('YYYY-MM-DD') + ' até ' + picker.endDate.format('YYYY-MM-DD'));
            atualizarLinkPDF(); // Atualiza o link quando a data é aplicada
        }).on('cancel.daterangepicker', function(ev, picker) {
    $(this).val('');
  });

        // --- INICIALIZAÇÃO DO CALENDÁRIO (FULLCALENDAR) ---
        const calendarEl = document.getElementById('fullcalendar');
        calendar = new FullCalendar.Calendar(calendarEl, {
            locale: 'pt',
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            buttonText: { today: 'Hoje', month: 'Mês', week: 'Semana', day: 'Dia' },
            height: '100%',
            contentHeight: 'auto',
            expandRows: true,
            allDaySlot: false,
            slotMinTime: '09:00:00', // Start at 9:00
            slotMaxTime: '18:30:00', // End at 18:30
            events: mapReservasToEvents(todasAsReservas),
            datesSet: function(dateInfo) {
                calendarStartDate = dateInfo.startStr.substring(0, 10);
                let endDate = new Date(dateInfo.endStr);
                endDate.setDate(endDate.getDate() - 1);
                calendarEndDate = endDate.toISOString().substring(0, 10);
                atualizarLinkPDF();
            },
            eventClick: function(info) {
                const props = info.event.extendedProps;
                Swal.fire({
                    title: info.event.title,
                    html: `<b>Status:</b> ${props.status}<br><b>Funcionário:</b> ${props.funcionario}<br><b>Observação:</b> ${props.observacao || 'N/A'}`,
                    icon: 'info'
                });
            }
        });

        // --- CONFIGURAÇÃO DOS EVENT LISTENERS ---
        document.getElementById('searchInput').addEventListener('input', function() {
            if (document.getElementById('viewInput').value === 'calendario') {
                const filtered = filterReservasBySearch(todasAsReservas, this.value);
                updateCalendarEvents(filtered);
            }
            // A pesquisa da tabela é tratada pelo simple-datatables e pelo submit do form
            atualizarLinkPDF();
    });

        document.getElementById('status').addEventListener('change', atualizarLinkPDF);
        document.getElementById('btnTabela').addEventListener('click', () => { showTabela(); atualizarLinkPDF(); });
        document.getElementById('btnCalendario').addEventListener('click', () => { showCalendario(); atualizarLinkPDF(); });

        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const reservaId = this.dataset.id;
                Swal.fire({
                    title: 'Tem certeza?', text: "Esta ação não poderá ser revertida!", icon: 'warning',
                    showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sim, excluir!', cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = 'reservas.php';
                        const input = document.createElement('input');
                        input.type = 'hidden'; input.name = 'reserva_id'; input.value = reservaId;
                        const deleteInput = document.createElement('input');
                        deleteInput.type = 'hidden'; deleteInput.name = 'delete_reserva'; deleteInput.value = '1';
                        form.appendChild(input);
                        form.appendChild(deleteInput);
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
        });

        document.querySelectorAll('.dropdown-item input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                document.getElementById('filterForm').submit();
            });
        });

        // --- ESTADO INICIAL DA PÁGINA ---
        const urlParams = new URLSearchParams(window.location.search);
        const view = urlParams.get('view') || 'tabela';
        if (view === 'calendario') {
            showCalendario();
        } else {
            showTabela();
  }
        atualizarLinkPDF(); // Garante que o link está correto no carregamento inicial
});
</script>

<?php
$conn->close();
$content = ob_get_clean();
include '../includes/layout.php';
?>