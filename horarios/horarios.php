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
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM agenda_funcionario WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Horário removido com sucesso!";
    } else {
        $_SESSION['mensagem'] = "Erro ao remover o horário: " . $conn->error;
    }
    
    $stmt->close();
    header("Location: agenda_funcionarios.php");
    exit();
}

// Filtro de funcionário
$funcionario_filtro = isset($_GET['funcionario_id']) ? intval($_GET['funcionario_id']) : 0;
$data_filtro = isset($_GET['data']) ? $_GET['data'] : '';

// Buscar todos os funcionários para o dropdown de filtro
$sql_funcionarios = "SELECT id, nome FROM funcionario ORDER BY nome";
$result_funcionarios = $conn->query($sql_funcionarios);
$funcionarios = $result_funcionarios->fetch_all(MYSQLI_ASSOC);

// Consulta para buscar agenda com filtros
$sql = "SELECT af.id, af.funcionario_id, f.nome as funcionario_nome, 
               af.data_inicio, af.data_fim
        FROM agenda_funcionario af
        JOIN funcionario f ON af.funcionario_id = f.id
        WHERE 1=1";

$params = [];
$param_types = "";

if ($funcionario_filtro > 0) {
    $sql .= " AND af.funcionario_id = ?";
    $params[] = $funcionario_filtro;
    $param_types .= "i";
}

if (!empty($data_filtro)) {
    $sql .= " AND DATE(af.data_inicio) = ?";
    $params[] = $data_filtro;
    $param_types .= "s";
}

$sql .= " ORDER BY af.data_inicio DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$agendas = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

ob_start();
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Gestão de Agenda de Funcionários</h1>
        <a href="adicionar_horario.php" class="btn btn-success">Adicionar Novo Horário</a>
    </div>
    
    <?php if ($mensagem): ?>
        <div class="toast-container position-fixed bottom-0 end-0 p-3">
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
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var toastEl = document.getElementById('errorToast');
                var toast = new bootstrap.Toast(toastEl, {
                    animation: true,
                    autohide: true,
                    delay: 3000
                });
                toast.show();
            });
        </script>
    <?php endif; ?>
    
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
                        <option value="">Todos os funcionários</option>
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
        <div class="card-body">
            <?php if (empty($agendas)): ?>
                <p class="text-center">Nenhum horário encontrado.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Funcionário</th>
                                <th>Data de Início</th>
                                <th>Data de Fim</th>
                                <th>Duração</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($agendas as $agenda): ?>
                                <tr>
                                    <td><?php echo $agenda['id']; ?></td>
                                    <td><?php echo htmlspecialchars($agenda['funcionario_nome']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($agenda['data_inicio'])); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($agenda['data_fim'])); ?></td>
                                    <td>
                                        <?php 
                                        $inicio = new DateTime($agenda['data_inicio']);
                                        $fim = new DateTime($agenda['data_fim']);
                                        $diff = $inicio->diff($fim);
                                        
                                        if ($diff->days > 0) {
                                            echo $diff->days . " dias, " . $diff->h . " horas, " . $diff->i . " minutos";
                                        } else {
                                            echo $diff->h . " horas, " . $diff->i . " minutos";
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="editar_horario.php?id=<?php echo $agenda['id']; ?>" class="btn btn-sm btn-warning">Editar</a>
                                            <button class="btn btn-sm btn-danger btn-eliminar" data-id="<?php echo $agenda['id']; ?>">Eliminar</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de Confirmação de Exclusão -->
<div class="modal fade" id="modalConfirmDelete" tabindex="-1" aria-labelledby="modalConfirmDeleteLabel" aria-hidden="true">
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
    const modal = new bootstrap.Modal(document.getElementById('modalConfirmDelete'));
    const btnConfirmDelete = document.getElementById('btnConfirmDelete');
    
    document.querySelectorAll('.btn-eliminar').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            btnConfirmDelete.setAttribute('data-id', id);
            modal.show();
        });
    });

    // Função para eliminar horário via AJAX
    btnConfirmDelete.addEventListener('click', function(e) {
        e.preventDefault();
        const id = this.getAttribute('data-id');
        
        fetch('eliminar_horario.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=' + id
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Recarrega a página após eliminação bem-sucedida
                window.location.reload();
            } else {
                alert('Erro ao eliminar horário: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao processar a requisição');
        })
        .finally(() => {
            modal.hide();
        });
    });
});
</script>

<?php
$content = ob_get_clean();
include '../includes/layout.php';
?>