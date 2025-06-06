<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$title = "Serviços";
include_once '../includes/db_conexao.php';

// Initial setup and redirects
if (isset($_GET['clear'])) {
    header("Location: servico.php");
    exit();
}

// Session message handling
$mensagem = $_SESSION['mensagem'] ?? '';
$success_message = $_SESSION['success'] ?? '';
$error_message = $_SESSION['error'] ?? '';
unset($_SESSION['mensagem'], $_SESSION['success'], $_SESSION['error']);

// Filter and column settings
$search = trim($_GET['search'] ?? '');
$colunas_permitidas = ['servico'];
$colunas_selecionadas = ['servico'];

// Sorting settings
$ordenar_por = isset($_GET['ordenar_por']) ? $_GET['ordenar_por'] : 'servico';
$ordem = isset($_GET['ordem']) ? $_GET['ordem'] : 'ASC';
$colunas_permitidas_ordenacao = ['servico'];

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

// Database query
$sql = "SELECT id, nome as servico FROM servico ORDER BY nome";
$result = $conn->query($sql);
$servico = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();

ob_start();
?>

<!-- Main Container -->
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="adicionar_servico.php" class="btn btn-primary">Adicionar Serviço</a>
        <div class="d-flex gap-2">
            <form method="GET" class="d-flex gap-2">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" placeholder="Pesquisar..." value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($mensagem): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <?php echo $mensagem; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

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

    <!-- Services Table -->
    <div class="table-responsive">
        <table class="table table-striped table-hover fs-5">
            <thead class="table-dark">
                <tr>
                    <th data-column="servico">
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['ordenar_por' => 'servico', 'ordem' => ($ordem == 'ASC' ? 'DESC' : 'ASC')])); ?>" class="text-white text-decoration-none">
                            Serviço
                            <?php if (isset($_GET['ordenar_por']) && $_GET['ordenar_por'] == 'servico'): ?>
                                <?php echo ($ordem == 'ASC') ? '▲' : '▼'; ?>
                            <?php endif; ?>
                        </a>
                    </th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($servico as $servicos): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($servicos['servico'] ?? ''); ?></td>
                        <td>
                            <button class="btn btn-primary btn-sm btn-expand" data-servico-id="<?php echo $servicos['id']; ?>">+</button>
                            <a href="editar_servico.php?id=<?php echo urlencode($servicos['id']); ?>" class="btn btn-warning btn-sm">
                                <i class="bi bi-pencil-square me-1"></i>Editar
                            </a>
                            <button class="btn btn-danger btn-sm btn-eliminar" data-id="<?php echo $servicos['id']; ?>" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal">
                                <i class="bi bi-trash me-1"></i>Eliminar
                            </button>
                        </td>
                    </tr>
                    <tr class="subservicos-row" id="subservicos-<?php echo $servicos['id']; ?>" style="display: none;">
                        <td colspan="2">
                            <div class="subservicos-content"></div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Scripts -->
<script src="../assets/js/style.js"></script>

<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 9999">
  <div id="subservicoToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body"></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>

<!-- Toast Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="updateToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                ✅ Serviço atualizado com sucesso!
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Delete modal functionality
    const confirmDeleteModalElement = document.getElementById('confirmDeleteModal');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    let serviceToDeleteId = null;

    // Remove the event listener on the modal
    // confirmDeleteModal.addEventListener('show.bs.modal', function (event) {
    //     const button = event.relatedTarget;
    //     serviceToDeleteId = button.getAttribute('data-id');
    // });

    // Add event listener to delete buttons
    document.querySelectorAll('.btn-eliminar').forEach(button => {
        button.addEventListener('click', function() {
            serviceToDeleteId = this.getAttribute('data-id'); // Get ID from clicked button
            if (!serviceToDeleteId) {
                console.error('ID do serviço a eliminar não encontrado.');
                return;
            }
            // Initialize and show the modal
            const modal = new bootstrap.Modal(confirmDeleteModalElement);
            modal.show();
        });
    });

    // Add event listener to the modal's confirm button
    confirmDeleteBtn.addEventListener('click', function () {
        if (serviceToDeleteId) {
            // Redirect to deletion script with the captured ID
            window.location.href = `eliminar_servico.php?id=${serviceToDeleteId}`;
        } else {
             console.error('Nenhum ID de serviço para eliminar.');
        }
    });

    // Inicializar toasts
    const successToastElement = document.getElementById('successToast');
    if (successToastElement) {
        const successToast = new bootstrap.Toast(successToastElement, {
            animation: true,
            autohide: true,
            delay: 3000
        });
        successToast.show();
    }

    // Verifica se há mensagem de sucesso na URL for update toast
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('success')) {
        const updateToastElement = document.getElementById('updateToast');
        if(updateToastElement) {
             const updateToast = new bootstrap.Toast(updateToastElement, {
                autohide: true,
                delay: 5000
            });
            updateToast.show();
        }
    }

     // Toast for delete success (if needed, depends on eliminar_servico.php redirect)
     const deleteToastElement = document.getElementById('deleteToast');
     if (deleteToastElement) {
        // You would need to set the toast body content and potentially background color
        // based on the response from eliminar_servico.php
        // For example, if eliminar_servico.php redirects back with a query param like ?deleted=success
        // Check for that param here and set toast content/style before showing
     }

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

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0" style="border-radius: 15px; box-shadow: 0 0 20px rgba(0,0,0,0.2);">
            <div class="modal-header border-0 bg-danger text-white" style="border-radius: 15px 15px 0 0;">
                <h5 class="modal-title" id="confirmDeleteModalLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Atenção: Eliminação de Serviço
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="bi bi-exclamation-circle text-danger" style="font-size: 4rem;"></i>
                <h4 class="mt-3 mb-3">Tem certeza que deseja eliminar este serviço?</h4>
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

<!-- Toast for Success Messages -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1050;">
    <div id="deleteToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <strong class="me-auto">Notificação</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body"></div>
    </div>
</div>

<!-- Toast Container for subservicoToast -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 9999">
  <div id="subservicoToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body"></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>

<!-- Toast Container for updateToast -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="updateToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                ✅ Serviço atualizado com sucesso!
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>