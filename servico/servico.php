<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$title = "Serviços - Gestão";
include_once '../includes/db_conexao.php';

// Initial setup and redirects
if (isset($_GET['clear'])) {
    header("Location: servico.php");
    exit();
}

// Processar exclusão de serviço
if (isset($_POST['delete_servico'])) {
    $servico_id = $_POST['servico_id'];
    
    try {
        // Primeiro, verificar se existem reservas associadas
        $check_sql = "SELECT COUNT(*) as count FROM Reserva WHERE servico_id = $servico_id";
        $result = $conn->query($check_sql);
        
        if ($result === false) {
            throw new Exception("Erro ao verificar reservas: " . $conn->error);
        }
        
        $row = $result->fetch_assoc();
        $has_reservas = $row['count'] > 0;
        
        if ($has_reservas) {
            $_SESSION['error'] = "Não é possível excluir este serviço pois existem reservas associadas a ele.";
        } else {
            // Excluir o serviço
            $delete_sql = "DELETE FROM Servico WHERE id = $servico_id";
            $delete_result = $conn->query($delete_sql);
            
            if ($delete_result === false) {
                throw new Exception("Erro ao excluir serviço: " . $conn->error);
            }
            
            if ($conn->affected_rows > 0) {
                $_SESSION['success'] = "Serviço excluído com sucesso!";
            } else {
                $_SESSION['error'] = "Erro ao excluir serviço.";
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Erro ao excluir serviço: " . $e->getMessage();
    }
    
    header('Location: servico.php');
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

<style>
    /* Loading Overlay */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.9);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }

    .loading-overlay .spinner-border {
        width: 3rem;
        height: 3rem;
    }

    .loading-overlay.fade-out {
        opacity: 0;
        transition: opacity 0.3s ease-out;
    }
</style>

<!-- Main Container -->
<div class="container py-4">
    <!-- Loading Overlay -->
    <div class="loading-overlay">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Carregando...</span>
        </div>
    </div>

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
                            <button class="btn btn-danger btn-sm delete-btn" data-id="<?php echo $servicos['id']; ?>">
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

<script src="../assets/js/style.js"></script>

<script>
    // Código para mensagens de sucesso e erro
    document.addEventListener('DOMContentLoaded', function() {
        // Mostrar o efeito de carregamento inicial
        document.querySelector('.loading-overlay').classList.remove('fade-out');
        document.querySelector('.loading-overlay').style.display = 'flex';
        
        // Esconder o overlay após 1 segundo
        setTimeout(function() {
            document.querySelector('.loading-overlay').classList.add('fade-out');
            setTimeout(function() {
                document.querySelector('.loading-overlay').style.display = 'none';
            }, 300);
        }, 1000);

        <?php if ($success_message): ?>
        // Mostrar mensagem de sucesso após o carregamento
        setTimeout(function() {
            Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                text: '<?php echo addslashes($success_message); ?>',
                showConfirmButton: true,
                confirmButtonText: 'OK',
                confirmButtonColor: '#3085d6',
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });
        }, 1000);
        <?php endif; ?>

        <?php if ($error_message): ?>
        // Mostrar mensagem de erro após o carregamento
        setTimeout(function() {
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: '<?php echo addslashes($error_message); ?>',
                showConfirmButton: true,
                confirmButtonText: 'OK',
                confirmButtonColor: '#3085d6',
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });
        }, 1000);
        <?php endif; ?>
    });
</script>

<script>
    // Código para DataTables e outras funcionalidades
    window.addEventListener('DOMContentLoaded', event => {
        const datatablesSimple = document.getElementById('datatablesSimple');
        let dataTable;
        if (datatablesSimple) {
            dataTable = new simpleDatatables.DataTable(datatablesSimple, {
                searchable: false,
                perPage: 10,
                perPageSelect: [10, 25, 50, 100],
                labels: {
                    placeholder: "Pesquisar...",
                    perPage: "Itens por página",
                    noRows: "Nenhum serviço encontrado",
                    info: "Mostrando {start} até {end} de {rows} serviços",
                    noResults: "Nenhum resultado encontrado para {query}"
                }
            });

            datatablesSimple.style.opacity = '1';
        }

        // Handle column selection checkboxes
        document.querySelectorAll('.dropdown-item input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const form = document.getElementById('filterForm');
                form.submit();
            });
        });

        // Add real-time search functionality
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    if (dataTable) {
                        dataTable.search(this.value);
                    }
                }, 300);
            });
        }

        // Handle expand buttons
        document.querySelectorAll('.btn-expand').forEach(button => {
            button.addEventListener('click', function() {
                const servicoId = this.dataset.servicoId;
                const row = document.getElementById(`subservicos-${servicoId}`);
                const content = row.querySelector('.subservicos-content');
                
                if (row.style.display === 'none') {
                    // Mostrar o overlay de carregamento
                    document.querySelector('.loading-overlay').classList.remove('fade-out');
                    document.querySelector('.loading-overlay').style.display = 'flex';
                    
                    // Fazer a requisição AJAX
                    fetch(`get_subtipos.php?servico_id=${servicoId}`)
                        .then(response => response.text())
                        .then(html => {
                            // Esconder o overlay
                            document.querySelector('.loading-overlay').classList.add('fade-out');
                            setTimeout(() => {
                                document.querySelector('.loading-overlay').style.display = 'none';
                            }, 300);
                            
                            content.innerHTML = html;
                            row.style.display = 'table-row';
                            this.textContent = '-';
                        })
                        .catch(error => {
                            // Esconder o overlay em caso de erro
                            document.querySelector('.loading-overlay').classList.add('fade-out');
                            setTimeout(() => {
                                document.querySelector('.loading-overlay').style.display = 'none';
                            }, 300);
                            
                            console.error('Erro:', error);
                            content.innerHTML = '<div class="alert alert-danger mt-2">Erro ao carregar subtipos.</div>';
                            row.style.display = 'table-row';
                        });
                } else {
                    row.style.display = 'none';
                    this.textContent = '+';
                }
            });
        });

        // Handle delete buttons
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const servicoId = this.dataset.id;
                
                Swal.fire({
                    title: 'Tem certeza?',
                    text: "Esta ação não poderá ser revertida!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sim, excluir!',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = 'servico.php';
                        
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'servico_id';
                        input.value = servicoId;
                        
                        const deleteInput = document.createElement('input');
                        deleteInput.type = 'hidden';
                        deleteInput.name = 'delete_servico';
                        deleteInput.value = '1';
                        
                        form.appendChild(input);
                        form.appendChild(deleteInput);
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
        });
    });
</script>

<?php
$content = ob_get_clean();
include '../includes/layout.php';
?>
