<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$title = "Clientes";
include_once '../includes/db_conexao.php';

if (isset($_GET['clear'])) {
    header("Location: clientes.php");
    exit();
}

// Mensagens de sucesso/erro
if (isset($_SESSION['success'])) {
    $success_message = $_SESSION['success'];
    unset($_SESSION['success']);
} else {
    $success_message = '';
}

if (isset($_SESSION['mensagem'])) {
    $error_message = $_SESSION['mensagem'];
    unset($_SESSION['mensagem']);
} else {
    $error_message = '';
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$colunas_selecionadas = isset($_GET['colunas']) ? explode(',', $_GET['colunas']) : ['nome', 'email', 'telefone'];
$colunas_permitidas = ['id', 'nome', 'email', 'telefone'];
$colunas_selecionadas = array_intersect($colunas_selecionadas, $colunas_permitidas);

if (empty($colunas_selecionadas)) {
    $colunas_selecionadas = ['nome', 'email', 'telefone'];
}

$colunas_sql = implode(", ", array_unique(array_merge($colunas_selecionadas, ['id'])));
$ordenar_por = isset($_GET['ordenar_por']) ? $_GET['ordenar_por'] : 'id';
$ordem = isset($_GET['ordem']) ? $_GET['ordem'] : 'DESC';
$colunas_permitidas_ordenacao = ['id', 'nome', 'email', 'telefone'];

if (!in_array($ordenar_por, $colunas_permitidas_ordenacao)) {
    $ordenar_por = 'id';
}

$ordenar_por = $_GET['ordenar_por'] ?? 'id';
$ordem = $_GET['ordem'] ?? 'ASC';
$colunas_permitidas_ordenacao = ['nome', 'email', 'telefone'];

if (!in_array($ordenar_por, $colunas_permitidas_ordenacao)) {
    $ordenar_por = 'nome';
}

$ordem = ($ordem === 'ASC') ? 'ASC' : 'DESC';
$sql = "SELECT id, " . implode(", ", $colunas_selecionadas) . " FROM Cliente WHERE 1=1";

if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $sql .= " AND (nome LIKE '%$search%' OR email LIKE '%$search%' OR telefone LIKE '%$search%')";
}

$sql .= " ORDER BY $ordenar_por $ordem";
$resultado = $conn->query($sql);
$clientes = $resultado->fetch_all(MYSQLI_ASSOC);
$conn->close();

ob_start();
?>

<div class="container py-4">
    <?php if ($success_message): ?>
        <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1050;">
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
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1050;">
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
        </div>
    <?php endif; ?>

    <!-- Modal de Confirmação -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteLabel" aria-hidden="true" data-bs-backdrop="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0" style="border-radius: 15px; box-shadow: 0 0 20px rgba(0,0,0,0.2);">
                <div class="modal-header border-0 bg-danger text-white" style="border-radius: 15px 15px 0 0;">
                    <h5 class="modal-title" id="confirmDeleteLabel">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        Atenção: Eliminação de Cliente
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="bi bi-exclamation-circle text-danger" style="font-size: 4rem;"></i>
                    <h4 class="mt-3 mb-3">Tem certeza que deseja eliminar este cliente?</h4>
                    <p class="text-muted">Esta ação não pode ser desfeita e todos os dados associados serão permanentemente removidos.</p>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal" style="border-radius: 8px;">
                        <i class="bi bi-x-circle me-2"></i>Cancelar
                    </button>
                    <form id="deleteForm" method="POST" action="eliminar_cliente.php" style="display: inline;">
                        <input type="hidden" name="id" id="deleteId">
                        <button type="submit" class="btn btn-danger px-4" style="border-radius: 8px;">
                            <i class="bi bi-trash me-2"></i>Eliminar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card shadow-sm mb-4">
        <div class="card-header py-3">
            <h5 class="mb-0">Filtros</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="clientes.php" id="filterForm" class="row g-3">
                <!-- Pesquisa -->
                <div class="col-md-4">
                    <label for="searchInput" class="form-label fs-5">Pesquisar</label>
                    <input type="text" name="search" id="searchInput" class="form-control form-control-lg" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Nome, email, telefone...">
                </div>
                
                <!-- Botões -->
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" class="btn btn-primary btn-lg w-100" onclick="document.getElementById('filterForm').submit()">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
                
                <div class="col-12 mt-3">
                    <div class="d-flex gap-2">
                        <a href="clientes.php?clear=1" class="btn btn-secondary btn-lg">
                            <i class="bi bi-x-circle me-2"></i>Limpar Filtros
                        </a>
                        <?php if ($_SESSION['utilizador_tipo'] == 'admin') { ?>
                            <a href="adicionar_cliente.php" class="btn btn-success btn-lg">
                                <i class="bi bi-plus-lg me-2"></i>Adicionar Cliente
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
                                        <input type="checkbox" class="form-check-input me-2" id="checkNome" name="colunas[]" data-column="nome" <?php echo in_array('nome', $colunas_selecionadas) ? 'checked' : ''; ?>> Nome
                                    </label>
                                </li>
                                <li>
                                    <label class="dropdown-item fs-5">
                                        <input type="checkbox" class="form-check-input me-2" id="checkEmail" name="colunas[]" data-column="email" <?php echo in_array('email', $colunas_selecionadas) ? 'checked' : ''; ?>> Email
                                    </label>
                                </li>
                                <li>
                                    <label class="dropdown-item fs-5">
                                        <input type="checkbox" class="form-check-input me-2" id="checkTelefone" name="colunas[]" data-column="telefone" <?php echo in_array('telefone', $colunas_selecionadas) ? 'checked' : ''; ?>> Telefone
                                    </label>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header py-3">
            <h5 class="mb-0">Clientes</h5>
        </div>
        <div class="card-body p-4">
            <table id="datatablesSimple" class="table table-hover fs-5">
                <thead class="table-dark">
                    <?php foreach ($colunas_selecionadas as $coluna): ?>
                        <th class="py-3"><?php echo ucfirst($coluna); ?></th>
                    <?php endforeach; ?> 
                    <th class="py-3">Ações</th>
                </thead>
                <tfoot>
                    <?php foreach ($colunas_selecionadas as $coluna): ?>
                        <th class="py-3"><?php echo ucfirst($coluna); ?></th>
                    <?php endforeach; ?>
                    <th class="py-3">Ações</th>
                </tfoot>
                <tbody>
                    <?php foreach ($clientes as $cliente): ?>
                        <tr>
                            <?php foreach ($colunas_selecionadas as $coluna): ?>
                                <td class="py-3"><?php echo htmlspecialchars($cliente[$coluna]); ?></td>
                            <?php endforeach; ?>
                            <?php if ($_SESSION['utilizador_tipo'] == 'admin') { ?>
                                <td class="py-3">
                                    <a href="editar_cliente.php?id=<?php echo urlencode($cliente['id']); ?>" class="btn btn-warning btn-sm">
                                        <i class="bi bi-pencil-square me-1"></i>Editar
                                    </a>
                                    <button class="btn btn-danger btn-sm btn-eliminar" data-id="<?php echo $cliente['id']; ?>" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal">
                                        <i class="bi bi-trash me-1"></i>Eliminar
                                    </button>
                                </td>
                            <?php } ?>
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
        const datatablesSimple = document.getElementById('datatablesSimple');
        if (datatablesSimple) {
            new simpleDatatables.DataTable(datatablesSimple, {
                searchable: false,
                perPage: 10,
                perPageSelect: [10, 25, 50, 100],
                labels: {
                    placeholder: "Pesquisar...",
                    perPage: "Itens por página",
                    noRows: "Nenhum cliente encontrado",
                    info: "Mostrando {start} até {end} de {rows} clientes",
                    noResults: "Nenhum resultado encontrado para {query}"
                }
            });
        }

        // Add event listeners for delete buttons
        document.querySelectorAll('.btn-eliminar').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                confirmDelete(id);
            });
        });

        // Inicializar toasts
        const successToast = document.getElementById('successToast');
        const errorToast = document.getElementById('errorToast');
        
        if (successToast) {
            new bootstrap.Toast(successToast, {
                animation: true,
                autohide: true,
                delay: 3000
            }).show();
        }
        
        if (errorToast) {
            new bootstrap.Toast(errorToast, {
                animation: true,
                autohide: true,
                delay: 3000
            }).show();
        }
    });

    function confirmDelete(id) {
        if (!id) {
            console.error('ID inválido');
            return;
        }
        document.getElementById('deleteId').value = id;
        const modal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
        modal.show();
    }

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
$content = ob_get_clean();
include '../includes/layout.php';
?>