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

include_once '../includes/db_conexao.php';

// Mensagens de feedback
$mensagem = isset($_SESSION['mensagem']) ? $_SESSION['mensagem'] : '';
$success_message = isset($_SESSION['success']) ? $_SESSION['success'] : '';
unset($_SESSION['mensagem']);
unset($_SESSION['success']);

// Processar exclusão de horário
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id']) && isset($_GET['dia'])) {
    $id = $conn->real_escape_string($_GET['id']);
    $dia = $conn->real_escape_string($_GET['dia']);
    
    // Buscar os IDs dos registros apenas para o dia específico
    $query = "SELECT af.id 
              FROM agenda_funcionario af 
              INNER JOIN funcionario f ON f.id = af.funcionario_id 
              WHERE f.id = '$id' 
              AND DATE(af.data_inicio) = '$dia'";
    
    $result = $conn->query($query);
    if (!$result) {
        $_SESSION['mensagem'] = "Erro ao buscar registros: " . $conn->error;
        header("Location: horarios.php");
        exit();
    }
    
    if ($result->num_rows === 0) {
        $_SESSION['mensagem'] = "Nenhum registro encontrado para o funcionário ID: $id no dia: $dia";
        header("Location: horarios.php");
        exit();
    }
    
    $deleted_ids = array();
    $success = true;
    
    while($row = $result->fetch_assoc()) {
        $id_a = $row['id'];
        $delete_query = "DELETE FROM agenda_funcionario WHERE id = '$id_a'";
        
        if (!$conn->query($delete_query)) {
            $success = false;
            $_SESSION['mensagem'] = "Erro ao remover o horário ID $id_a: " . $conn->error;
            break;
        }
        
        // Verificar se o registro foi realmente eliminado
        $check_query = "SELECT COUNT(*) as count FROM agenda_funcionario WHERE id = '$id_a'";
        $check_result = $conn->query($check_query);
        $check_row = $check_result->fetch_assoc();
        
        if ($check_row['count'] > 0) {
            $success = false;
            $_SESSION['mensagem'] = "Falha ao verificar a eliminação do registro ID $id_a";
            break;
        }
        
        $deleted_ids[] = $id_a;
    }
    
    if ($success) {
        $_SESSION['success'] = "Horário do dia $dia removido com sucesso!";
    }
    
    header("Location: horarios.php");
    exit();
}

// Filtro de funcionário
$funcionario_filtro = isset($_GET['funcionario_id']) ? $_GET['funcionario_id'] : 0;
$data_filtro = isset($_GET['data']) ? $_GET['data'] : '';

// Buscar todos os funcionários para o dropdown de filtro
$sql_funcionarios = "SELECT id, nome FROM funcionario ORDER BY nome";
$result_funcionarios = $conn->query($sql_funcionarios);
$funcionarios = $result_funcionarios->fetch_all(MYSQLI_ASSOC);

// Consulta para buscar agenda com filtros usando stored procedures
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isset($_GET['funcionario_id']) && !isset($_GET['data'])) {
        $sql = "CALL horarios()";
    } else {
        if (($_GET['funcionario_id']==0) && ($_GET['data'])=='') {
            $sql = "CALL horarios()";
        }
        if (($_GET['funcionario_id']==0) && ($_GET['data'])!='') {
            $data_filtro = $_GET['data'];
            $sql = "CALL horarios_data('" . $data_filtro . "')";
        }
        if (($_GET['funcionario_id']!=0) && ($_GET['data'])=='') {
            $funcionario_filtro = $_GET['funcionario_id'];
            $sql = "CALL horarios_funcionario(" . $funcionario_filtro . ")";
        }
        if (($_GET['funcionario_id']!=0) && ($_GET['data'])!='') {
            $funcionario_filtro = $_GET['funcionario_id'];
            $data_filtro = $_GET['data'];
            $sql = "CALL horarios_filtro(" . $funcionario_filtro . ", '" . $data_filtro . "')";
        }
    }
}

ob_start();
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Gestão de Agenda de Funcionários</h1>
        <a href="adicionar_horario.php" class="btn btn-success">Adicionar Novo Horário</a>
    </div>
    
    <?php if ($mensagem): ?>
        <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1050;">
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

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-header">
            <h5>Filtros</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="horarios.php" class="row g-3">
                <div class="col-md-6">
                    <label for="funcionario_id" class="form-label">Funcionário</label>
                    <select name="funcionario_id" id="funcionario_id" class="form-select">
                        <option value="0">Todos os funcionários</option>
                        <?php foreach ($funcionarios as $funcionario): ?>
                            <option value="<?php echo $funcionario['id']; ?>" <?php echo ($funcionario_filtro == $funcionario['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($funcionario['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="data" class="form-label">Data</label>
                    <input type="date" name="data" id="data" class="form-control" value="<?php echo htmlspecialchars($data_filtro); ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Filtrar</button>
                    <a href="horarios.php" class="btn btn-secondary">Limpar</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabela de Agenda -->
    <div class="card">
        <div class="card-header">
            <h5>Horários Cadastrados</h5>
        </div>
        <div class="card-body overflow-auto" style="max-height: 500px;">
            <div class="accordion accordion-flush" id="accordionFlushExample">
                <?php 
                $result = $conn->query($sql);
                $i = 1;
                if ($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapse-<?php echo $i; ?>" aria-expanded="false" aria-controls="flush-collapse-<?php echo $i; ?>">
                                <?php echo htmlspecialchars($row['nome']); ?> - <?php echo htmlspecialchars($row['dia']); ?>
                            </button>
                        </h2>
                        <div id="flush-collapse-<?php echo $i++; ?>" class="accordion-collapse collapse" data-bs-parent="#accordionFlushExample">
                            <div class="accordion-body">
                                <div class="row">
                                    <div class="col-6">
                                        <div class="card">
                                            <h5 class="card-header">Manhã</h5>
                                            <div class="card-body">
                                                <p><b>Data de Início: </b> <?php echo htmlspecialchars($row['manha_inicio']); ?></p>
                                                <p><b>Data de Fim: </b> <?php echo htmlspecialchars($row['manha_fim']); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="card">
                                            <h5 class="card-header">Tarde</h5>
                                            <div class="card-body">
                                                <p><b>Data de Início: </b> <?php echo htmlspecialchars($row['tarde_inicio']); ?></p>
                                                <p><b>Data de Fim: </b> <?php echo htmlspecialchars($row['tarde_fim']); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <div class="btn-group">
                                        <a href="editar_horario.php?id=<?php echo $row['id_f']; ?>" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil-square me-1"></i>Editar
                                        </a>
                                        <button class="btn btn-sm btn-danger btn-eliminar" data-id="<?php echo $row['id_f']; ?>" data-dia="<?php echo $row['dia']; ?>">
                                            <i class="bi bi-trash me-1"></i>Eliminar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php
                    }
                } else {
                ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseOne" aria-expanded="false" aria-controls="flush-collapseOne">
                                Não existem registos
                            </button>
                        </h2>
                        <div id="flush-collapseOne" class="accordion-collapse collapse" data-bs-parent="#accordionFlushExample">
                            <div class="accordion-body">
                                Não existem registos para os filtros selecionados.
                            </div>
                        </div>
                    </div>
                <?php
                }
                ?>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../includes/layout.php';
?>

<!-- Modal de Confirmação de Exclusão -->
<div class="modal fade" id="modalConfirmDelete" tabindex="-1" aria-labelledby="modalConfirmDeleteLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0" style="border-radius: 15px; box-shadow: 0 0 20px rgba(0,0,0,0.2);">
            <div class="modal-header border-0 bg-danger text-white" style="border-radius: 15px 15px 0 0;">
                <h5 class="modal-title" id="modalConfirmDeleteLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Atenção: Eliminação de Horário
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="bi bi-exclamation-circle text-danger" style="font-size: 4rem;"></i>
                <h4 class="mt-3 mb-3">Tem certeza que deseja eliminar este horário?</h4>
                <p class="text-muted">Esta ação não pode ser desfeita e todos os dados associados serão permanentemente removidos.</p>
            </div>
            <div class="modal-footer border-0 justify-content-center pb-4">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal" style="border-radius: 8px;">
                    <i class="bi bi-x-circle me-2"></i>Cancelar
                </button>
                <a href="#" id="btnConfirmDelete" class="btn btn-danger px-4" style="border-radius: 8px;">
                    <i class="bi bi-trash me-2"></i>Eliminar
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal de confirmação de exclusão
    const modalConfirmDeleteElement = document.getElementById('modalConfirmDelete');
    
    if (!modalConfirmDeleteElement) {
        console.error('Modal element not found!');
        return;
    }

    const btnConfirmDelete = document.getElementById('btnConfirmDelete');

    // Add event listeners for delete buttons
    document.querySelectorAll('.btn-eliminar').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            const dia = this.getAttribute('data-dia');
            if (!id || !dia) {
                console.error('ID ou dia inválido');
                return;
            }
            btnConfirmDelete.href = `horarios.php?action=delete&id=${id}&dia=${dia}`;
            
            // Initialize modal and show it
            const modal = new bootstrap.Modal(modalConfirmDeleteElement, {
                backdrop: 'static',
                keyboard: false
            });
            modal.show();
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