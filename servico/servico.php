<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

include_once '../includes/db_conexao.php';

// Initial setup and redirects
if (isset($_GET['clear'])) {
    header("Location: servico.php");
    exit();
}

// Session message handling
$mensagem = $_SESSION['mensagem'] ?? '';
$success_message = $_SESSION['success'] ?? '';
unset($_SESSION['mensagem'], $_SESSION['success']);

// Filter and column settings
$search = trim($_GET['search'] ?? '');
$colunas_selecionadas = isset($_GET['colunas']) ? explode(',', $_GET['colunas']) : ['nome', 'descricao', 'preco', 'duracao', 'categoria'];
$colunas_permitidas = ['id', 'nome', 'descricao', 'preco', 'duracao', 'categoria'];
$colunas_selecionadas = array_intersect($colunas_selecionadas, $colunas_permitidas);

if (empty($colunas_selecionadas)) {
    $colunas_selecionadas = ['nome', 'descricao', 'preco', 'duracao', 'categoria'];
}

// Sorting settings
$ordenar_por = $_GET['ordenar_por'] ?? 'id';
$ordem = $_GET['ordem'] ?? 'ASC';
$colunas_permitidas_ordenacao = ['id', 'nome', 'descricao', 'preco', 'duracao', 'categoria'];

if (!in_array($ordenar_por, $colunas_permitidas_ordenacao)) {
    $ordenar_por = 'nome';
}
$ordem = ($ordem === 'ASC') ? 'ASC' : 'DESC';

// Database query
$sql = "SELECT ss.id, ss.nome, ss.descricao, ss.preco, ss.duracao, s.nome AS categoria 
        FROM servico_subtipo ss
        LEFT JOIN servico s ON ss.servico_id = s.id
        WHERE 1=1";

if (!empty($search)) {
    $sql .= " AND (ss.nome LIKE ? OR ss.descricao LIKE ? OR ss.preco LIKE ? OR ss.duracao LIKE ? OR s.nome LIKE ? )";
    $search_param = "%$search%";
    $stmt = $conn->prepare($sql . " ORDER BY $ordenar_por $ordem");
    $stmt->bind_param("sssss", $search_param, $search_param, $search_param, $search_param, $search_param);
} else {
    $stmt = $conn->prepare($sql . " ORDER BY $ordenar_por $ordem");
}

$stmt->execute();
$resultado = $stmt->get_result();
$servico = $resultado->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

ob_start();
?>

<!-- Main Container -->
<div class="container py-4">
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmDeleteLabel">Confirmar Eliminação</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Tem certeza que deseja eliminar este servico?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Eliminar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Controls Section -->
    <div class="mb-4 d-flex align-items-center">
        <form method="GET" action="servico.php" class="d-flex align-items-center flex-grow-1" id="searchForm">
            <input type="text" name="search" class="form-control me-2" placeholder="Pesquisar servico..." 
                   value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" 
                   style="width: 100%;" id="searchInput">
            <a href="servico.php?clear=1" class="btn btn-secondary ms-2">Limpar</a>
        </form>

        <!-- Column Selection -->
        <div class="dropdown ms-2">
            <button class="btn btn-outline-dark dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                Selecionar Colunas
            </button>
            <a href="adicionar_servico.php" class="btn btn-success ms-2">Adicionar Serviço</a>
            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                <li>
                    <label class="dropdown-item">
                        <input type="checkbox" class="form-check-input me-2" id="checkNome" <?php echo in_array('nome', $colunas_selecionadas) ? 'checked' : ''; ?>> Nome
                    </label>
                </li>
                <li>
                    <label class="dropdown-item">
                        <input type="checkbox" class="form-check-input me-2" id="checkDescricao" <?php echo in_array('descricao', $colunas_selecionadas) ? 'checked' : ''; ?>> Descrição
                    </label>
                </li>
                <li>
                    <label class="dropdown-item">
                        <input type="checkbox" class="form-check-input me-2" id="checkPreco" <?php echo in_array('preco', $colunas_selecionadas) ? 'checked' : ''; ?>> Preço
                    </label>
                </li>
                <li>
                    <label class="dropdown-item">
                        <input type="checkbox" class="form-check-input me-2" id="checkDuracao" <?php echo in_array('duracao', $colunas_selecionadas) ? 'checked' : ''; ?>> Duração
                    </label>
                </li>
                <li>
                    <label class="dropdown-item">
                        <input type="checkbox" class="form-check-input me-2" id="checkCategoria" <?php echo in_array('categoria', $colunas_selecionadas) ? 'checked' : ''; ?>> Categoria
                    </label>
                </li>
            </ul>
        </div>
    </div>

    <!-- Success Toast -->
    <?php if ($success_message): ?>
        <div class="toast-container position-fixed bottom-0 end-0 p-3">
            <div id="successToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Services Table -->
    <table class="table table-striped">
        <thead class="table-dark">
            <tr>
                <?php foreach ($colunas_selecionadas as $coluna): ?>
                    <th data-column="<?php echo $coluna; ?>">
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['ordenar_por' => $coluna, 'ordem' => ($ordem == 'ASC' ? 'DESC' : 'ASC')])); ?>">
                            <?php echo ucfirst($coluna); ?>
                            <?php if (isset($_GET['ordenar_por']) && $_GET['ordenar_por'] == $coluna): ?>
                                <?php echo ($ordem == 'ASC') ? '▲' : '▼'; ?>
                            <?php endif; ?>
                        </a>
                    </th>
                <?php endforeach; ?>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($servico as $servicos): ?>
                <tr>
                    <?php foreach ($colunas_selecionadas as $coluna): ?>
                        <td data-column="<?php echo $coluna; ?>"><?php echo htmlspecialchars($servicos[$coluna] ?? ''); ?></td>
                    <?php endforeach; ?>
                    <td>
                        <a href="editar_servico.php?id=<?php echo urlencode($servicos['id']); ?>" class="btn btn-warning btn-sm">Editar</a>
                        <button class="btn btn-danger btn-sm btn-eliminar" data-id="<?php echo $servicos['id']; ?>">Eliminar</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Scripts -->
<script src="../assets/js/style.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var toastEl = document.getElementById('successToast');
        if (toastEl) {
            var toast = new bootstrap.Toast(toastEl);
            toast.show();
        }
    });
</script>

<?php
$content = ob_get_clean();
include '../includes/layout.php';
?>