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

$title = "Funcionários";
include_once '../includes/db_conexao.php';

if (isset($_GET['clear'])) {
    header("Location: funcionario.php");
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
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0" style="border-radius: 15px; box-shadow: 0 0 20px rgba(0,0,0,0.2);">
                <div class="modal-header border-0 bg-danger text-white" style="border-radius: 15px 15px 0 0;">
                    <h5 class="modal-title" id="confirmDeleteLabel">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        Atenção: Eliminação de Funcionário
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="bi bi-exclamation-circle text-danger" style="font-size: 4rem;"></i>
                    <h4 class="mt-3 mb-3">Tem certeza que deseja eliminar este funcionário?</h4>
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

    <div class="mb-4 d-flex align-items-center">
    <form method="GET" action="funcionarios.php" class="d-flex align-items-center flex-grow-1" id="searchForm">
        <input type="text" name="search" class="form-control me-2 w-100 fs-5" placeholder="Pesquisar funcionários..." 
               value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" 
               id="searchInput">
        <a href="funcionarios.php?clear=1" class="btn btn-secondary ms-2 fs-5">Limpar</a>
    </form>

    <!-- Dropdown com filtros -->
    <div class="dropdown ms-2">
        <button class="btn btn-outline-dark dropdown-toggle fs-5" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
            Selecionar Colunas
        </button>
        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
            <li>
                <label class="dropdown-item fs-5">
                    <input type="checkbox" class="form-check-input me-2" id="checkNome" <?php echo in_array('nome', $colunas_selecionadas) ? 'checked' : ''; ?>> Nome
                </label>
            </li>
            <li>
                <label class="dropdown-item fs-5">
                    <input type="checkbox" class="form-check-input me-2" id="checkEmail" <?php echo in_array('email', $colunas_selecionadas) ? 'checked' : ''; ?>> Email
                </label>
            </li>
            <li>
                <label class="dropdown-item fs-5">
                    <input type="checkbox" class="form-check-input me-2" id="checkMorada" <?php echo in_array('morada', $colunas_selecionadas) ? 'checked' : ''; ?>> Morada
                </label>
            </li>
            <li>
                <label class="dropdown-item fs-5">
                    <input type="checkbox" class="form-check-input me-2" id="checkLocalidade" <?php echo in_array('localidade', $colunas_selecionadas) ? 'checked' : ''; ?>> Localidade
                </label>
            </li>
            <li>
                <label class="dropdown-item fs-5">
                    <input type="checkbox" class="form-check-input me-2" id="checkTelefone1" <?php echo in_array('telefone1', $colunas_selecionadas) ? 'checked' : ''; ?>> Telefone 1
                </label>
            </li>
            <li>
                <label class="dropdown-item fs-5">
                    <input type="checkbox" class="form-check-input me-2" id="checkTelefone2" <?php echo in_array('telefone2', $colunas_selecionadas) ? 'checked' : ''; ?>> Telefone 2
                </label>
            </li>
            <li>
                <label class="dropdown-item fs-5">
                    <input type="checkbox" class="form-check-input me-2" id="checkCargo" <?php echo in_array('cargo', $colunas_selecionadas) ? 'checked' : ''; ?>> Cargo
                </label>
            </li>
        </ul>
    </div>

    <!-- Botão para adicionar funcionário -->
    <a href="adicionar_funcionario.php" class="btn btn-success ms-2 fs-5">Adicionar Funcionário</a>

    <!-- Botão para imprimir -->
    <button class="btn btn-primary ms-2 fs-5" onclick="window.print()">Imprimir</button>
</div>


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

    <table class="table table-striped table-hover fs-5">
    <thead class="table-dark">
        <tr>
            <?php foreach ($colunas_selecionadas as $coluna): ?>
                <th data-column="<?php echo $coluna; ?>">
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['ordenar_por' => $coluna, 'ordem' => ($ordem == 'ASC' ? 'DESC' : 'ASC')])); ?>" class="text-white text-decoration-none">
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
                    <td><?php echo htmlspecialchars($funcionario[$coluna] ?? ''); ?></td>
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


<?php
$content = ob_get_clean();
include '../includes/layout.php';
?>
