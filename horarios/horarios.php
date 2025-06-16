<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$title = "Horários - Gestão";

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
$success_message = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$error_message = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['success']);
unset($_SESSION['error']);

if (isset($_GET['clear'])) {
    header("Location: horarios.php");
    exit();
}

// Processar exclusão de horário
if (isset($_POST['delete_horario'])) {
    $horario_id = $_POST['horario_id'];
    
    try {
        // Verificar se existem reservas associadas
        $check_sql = "SELECT COUNT(*) as count FROM Reserva WHERE horario_id = $horario_id";
        $check_result = $conn->query($check_sql);
        
        if ($check_result === false) {
            throw new Exception("Erro ao verificar reservas: " . $conn->error);
        }
        
        $row = $check_result->fetch_assoc();
        if ($row['count'] > 0) {
            throw new Exception("Não é possível excluir este horário pois existem reservas associadas a ele.");
        }
        
        // Excluir o horário
        $delete_sql = "DELETE FROM Horario WHERE id = $horario_id";
        $delete_result = $conn->query($delete_sql);
        
        if ($delete_result === false) {
            throw new Exception("Erro ao excluir horário: " . $conn->error);
        }
        
        if ($conn->affected_rows > 0) {
            $_SESSION['success'] = "Horário excluído com sucesso!";
        } else {
            $_SESSION['error'] = "Erro ao excluir horário.";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Erro ao excluir horário: " . $e->getMessage();
    }
    
    header('Location: horarios.php');
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

<div class="container py-4">
    <!-- Loading Overlay -->
    <div class="loading-overlay">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Carregando...</span>
        </div>
    </div>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="adicionar_horario.php" class="btn btn-success">Adicionar Novo Horário</a>
    </div>
    
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
                                        <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $row['id_f']; ?>">
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
                    noRows: "Nenhum horário encontrado",
                    info: "Mostrando {start} até {end} de {rows} horários",
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

        // Handle delete buttons
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const horarioId = this.dataset.id;
                
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
                        form.action = 'horarios.php';
                        
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'horario_id';
                        input.value = horarioId;
                        
                        const deleteInput = document.createElement('input');
                        deleteInput.type = 'hidden';
                        deleteInput.name = 'delete_horario';
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