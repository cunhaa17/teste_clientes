<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

include_once '../includes/db_conexao.php';

if (isset($_GET['clear'])) {
    header("Location: funcionarios.php");
    exit();
}

$mensagem = isset($_SESSION['mensagem']) ? $_SESSION['mensagem'] : '';
$success_message = isset($_SESSION['success']) ? $_SESSION['success'] : '';
unset($_SESSION['mensagem']);
unset($_SESSION['success']);

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$colunas_selecionadas = isset($_GET['colunas']) ? explode(',', $_GET['colunas']) : ['nome', 'email', 'morada', 'localidade', 'telefone1', 'telefone2', 'cargo'];
$colunas_permitidas = ['id', 'nome', 'email', 'morada', 'localidade', 'telefone1', 'telefone2', 'cargo'];
$colunas_selecionadas = array_intersect($colunas_selecionadas, $colunas_permitidas);

if (empty($colunas_selecionadas)) {
    $colunas_selecionadas = ['nome', 'email', 'morada', 'localidade', 'telefone1', 'telefone2', 'cargo'];
}

$colunas_sql = implode(", ", array_unique(array_merge($colunas_selecionadas, ['id'])));
$ordenar_por = isset($_GET['ordenar_por']) ? $_GET['ordenar_por'] : 'id';
$ordem = isset($_GET['ordem']) ? $_GET['ordem'] : 'DESC';
$colunas_permitidas_ordenacao = ['id', 'nome', 'email', 'morada', 'localidade', 'telefone1', 'telefone2', 'cargo'];

if (!in_array($ordenar_por, $colunas_permitidas_ordenacao)) {
    $ordenar_por = 'id';
}

$ordem = ($ordem === 'ASC') ? 'ASC' : 'DESC';
$sql = "SELECT id, " . $colunas_sql . " FROM funcionario WHERE 1=1";

if (!empty($search)) {
    $sql .= " AND (nome LIKE ? OR email LIKE ? OR morada LIKE ? OR localidade LIKE ? OR telefone1 LIKE ? OR telefone2 LIKE ?)";
    $search_param = "%$search%";
    $stmt = $conn->prepare($sql . " ORDER BY $ordenar_por $ordem");
    $stmt->bind_param("ssssss", $search_param, $search_param, $search_param, $search_param, $search_param, $search_param);
} else {
    $stmt = $conn->prepare($sql . " ORDER BY $ordenar_por $ordem");
}

$stmt->execute();
$resultado = $stmt->get_result();
$funcionarios = $resultado->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

ob_start();
?>

<div class="container py-4">

    <!-- Modal de Confirmação -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmDeleteLabel">Confirmar Eliminação</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Tem certeza que deseja eliminar este funcionário?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Eliminar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-4 d-flex align-items-center">
        <form method="GET" action="funcionarios.php" class="d-flex align-items-center flex-grow-1" id="searchForm">
            <input type="text" name="search" class="form-control me-2" placeholder="Pesquisar funcionários..." 
                   value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" 
                   style="width: 100%;" id="searchInput">
            <a href="funcionarios.php?clear=1" class="btn btn-secondary ms-2">Limpar</a>
        </form>
        <!-- Dropdown with Options -->
        <div class="dropdown ms-2">
            <button class="btn btn-outline-dark dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                Selecionar Colunas
            </button>

            <!-- Button to Add Employee -->
            <a href="adicionar_funcionario.php" class="btn btn-success ms-2">Adicionar Funcionário</a>
            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                <li>
                    <label class="dropdown-item">
                        <input type="checkbox" class="form-check-input me-2" id="checkNome" <?php echo in_array('nome', $colunas_selecionadas) ? 'checked' : ''; ?>> Nome
                    </label>
                </li>
                <li>
                    <label class="dropdown-item">
                        <input type="checkbox" class="form-check-input me-2" id="checkEmail" <?php echo in_array('email', $colunas_selecionadas) ? 'checked' : ''; ?>> Email
                    </label>
                </li>
                <li>
                    <label class="dropdown-item">
                        <input type="checkbox" class="form-check-input me-2" id="checkMorada" <?php echo in_array('morada', $colunas_selecionadas) ? 'checked' : ''; ?>> Morada
                    </label>
                </li>
                <li>
                    <label class="dropdown-item">
                        <input type="checkbox" class="form-check-input me-2" id="checkLocalidade" <?php echo in_array('localidade', $colunas_selecionadas) ? 'checked' : ''; ?>> Localidade
                    </label>
                </li>
                <li>
                    <label class="dropdown-item">
                        <input type="checkbox" class="form-check-input me-2" id="checkTelefone1" <?php echo in_array('telefone1', $colunas_selecionadas) ? 'checked' : ''; ?>> Telefone 1
                    </label>
                </li>
                <li>
                    <label class="dropdown-item">
                        <input type="checkbox" class="form-check-input me-2" id="checkTelefone2" <?php echo in_array('telefone2', $colunas_selecionadas) ? 'checked' : ''; ?>> Telefone 2
                    </label>
                </li>
                <li>
                    <label class="dropdown-item">
                        <input type="checkbox" class="form-check-input me-2" id="checkCargo" <?php echo in_array('cargo', $colunas_selecionadas) ? 'checked' : ''; ?>> Cargo
                    </label>
                </li>
            </ul>
        </div>
    </div>

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
            <?php foreach ($funcionarios as $funcionario): ?>
                <tr>
                    <?php foreach ($colunas_selecionadas as $coluna): ?>
                        <td data-column="<?php echo $coluna; ?>"><?php echo htmlspecialchars($funcionario[$coluna]); ?></td>
                    <?php endforeach; ?>
                    <td>
                        <a href="editar_funcionario.php?id=<?php echo urlencode($funcionario['id']); ?>" class="btn btn-warning btn-sm">Editar</a>
                        <button class="btn btn-danger btn-sm btn-eliminar" data-id="<?php echo $funcionario['id']; ?>">Eliminar</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

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
