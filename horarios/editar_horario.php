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

// Verifica se o ID foi fornecido
if (!isset($_GET['id'])) {
    $_SESSION['mensagem'] = "ID do horário não fornecido.";
    header("Location: horarios.php");
    exit();
}

$id = $conn->real_escape_string($_GET['id']);

// Processar o formulário de edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hora_inicio_manha = $conn->real_escape_string($_POST['hora_inicio_manha']);
    $hora_fim_manha = $conn->real_escape_string($_POST['hora_fim_manha']);
    $hora_inicio_tarde = $conn->real_escape_string($_POST['hora_inicio_tarde']);
    $hora_fim_tarde = $conn->real_escape_string($_POST['hora_fim_tarde']);

    // Primeiro, encontrar os IDs dos registros de manhã e tarde
    $query = "WITH horarios_numerados AS (
        SELECT 
            agenda_funcionario.id AS id_a,
            funcionario.id AS id_f,
            funcionario.nome, 
            funcionario_id,
            DATE(data_inicio) AS dia,
            data_inicio,
            data_fim,
            ROW_NUMBER() OVER (
                PARTITION BY funcionario_id, DATE(data_inicio)
                ORDER BY TIME(data_inicio)
            ) AS turno_num
        FROM agenda_funcionario
            INNER JOIN funcionario ON funcionario.id = agenda_funcionario.funcionario_id
        WHERE funcionario.id = '$id'
    )
    SELECT id_a, turno_num, DATE(data_inicio) as data FROM horarios_numerados WHERE turno_num IN (1, 2)";
    
    $result = $conn->query($query);
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $data = $row['data'];
            $id_a = $row['id_a'];
            
            if ($row['turno_num'] == 1) { // Turno da manhã
                $data_inicio = $data . ' ' . $hora_inicio_manha;
                $data_fim = $data . ' ' . $hora_fim_manha;
            } else { // Turno da tarde
                $data_inicio = $data . ' ' . $hora_inicio_tarde;
                $data_fim = $data . ' ' . $hora_fim_tarde;
            }
            
            $update_query = "UPDATE agenda_funcionario SET 
                           data_inicio = '$data_inicio',
                           data_fim = '$data_fim'
                           WHERE id = '$id_a'";
            
            if (!$conn->query($update_query)) {
                $_SESSION['mensagem'] = "Erro ao atualizar o horário: " . $conn->error;
                header("Location: horarios.php");
                exit();
            }
        }
        
        $_SESSION['success'] = "Horário atualizado com sucesso!";
        header("Location: horarios.php");
        exit();
    } else {
        $_SESSION['mensagem'] = "Erro ao encontrar os registros para atualização.";
        header("Location: horarios.php");
        exit();
    }
}

// Buscar dados do horário usando a mesma lógica da stored procedure
$query = "WITH horarios_numerados AS (
    SELECT 
        agenda_funcionario.id AS id_a,
        funcionario.id AS id_f,
        funcionario.nome, 
        funcionario_id,
        DATE(data_inicio) AS dia,
        data_inicio,
        data_fim,
        ROW_NUMBER() OVER (
            PARTITION BY funcionario_id, DATE(data_inicio)
            ORDER BY TIME(data_inicio)
        ) AS turno_num
    FROM agenda_funcionario
        INNER JOIN funcionario ON funcionario.id = agenda_funcionario.funcionario_id
    WHERE funcionario.id = '$id'
)
SELECT
    manha.id_a AS manha_id,
    tarde.id_a AS tarde_id,
    manha.id_f,
    manha.nome,
    manha.dia,
    manha.data_inicio AS manha_inicio,
    manha.data_fim AS manha_fim,
    tarde.data_inicio AS tarde_inicio,
    tarde.data_fim AS tarde_fim
FROM
    horarios_numerados AS manha
JOIN
    horarios_numerados AS tarde
    ON manha.funcionario_id = tarde.funcionario_id
    AND manha.dia = tarde.dia
WHERE
    manha.turno_num = 1
    AND tarde.turno_num = 2";

$result = $conn->query($query);

if (!$result || $result->num_rows === 0) {
    $_SESSION['mensagem'] = "Horário não encontrado.";
    header("Location: horarios.php");
    exit();
}

$horario = $result->fetch_assoc();

// Extrair apenas as horas dos horários
$hora_inicio_manha = date('H:i', strtotime($horario['manha_inicio']));
$hora_fim_manha = date('H:i', strtotime($horario['manha_fim']));
$hora_inicio_tarde = date('H:i', strtotime($horario['tarde_inicio']));
$hora_fim_tarde = date('H:i', strtotime($horario['tarde_fim']));

ob_start();
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Editar Horário</h1>
        <a href="horarios.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-2"></i>Voltar
        </a>
    </div>

    <?php if (isset($_SESSION['mensagem'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['mensagem'];
            unset($_SESSION['mensagem']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h5>Editar Horário de <?php echo htmlspecialchars($horario['nome']); ?> - <?php echo htmlspecialchars($horario['dia']); ?></h5>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Turno da Manhã</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="hora_inicio_manha" class="form-label">Horário de Início</label>
                                    <input type="time" class="form-control" id="hora_inicio_manha" name="hora_inicio_manha" value="<?php echo htmlspecialchars($hora_inicio_manha); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="hora_fim_manha" class="form-label">Horário de Fim</label>
                                    <input type="time" class="form-control" id="hora_fim_manha" name="hora_fim_manha" value="<?php echo htmlspecialchars($hora_fim_manha); ?>" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Turno da Tarde</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="hora_inicio_tarde" class="form-label">Horário de Início</label>
                                    <input type="time" class="form-control" id="hora_inicio_tarde" name="hora_inicio_tarde" value="<?php echo htmlspecialchars($hora_inicio_tarde); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="hora_fim_tarde" class="form-label">Horário de Fim</label>
                                    <input type="time" class="form-control" id="hora_fim_tarde" name="hora_fim_tarde" value="<?php echo htmlspecialchars($hora_fim_tarde); ?>" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../includes/layout.php';
?>