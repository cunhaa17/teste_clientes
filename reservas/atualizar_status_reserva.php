<?php
session_start();
include_once '../includes/db_conexao.php';

// Habilitar exibição de erros para debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Verificar se o usuário está logado
if (!isset($_SESSION['utilizador_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sessão expirada. Por favor, faça login novamente.']);
    exit;
}

// Verificar se os dados necessários foram enviados
if (!isset($_POST['reserva_id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos. Por favor, tente novamente.']);
    exit;
}

// Registrar dados recebidos para debug
error_log('POST data: ' . print_r($_POST, true));

$reserva_id = $_POST['reserva_id'];
$novo_status = strtolower($_POST['status']);
$justificativa = isset($_POST['justificativa']) ? $_POST['justificativa'] : null;

// Validar o status
$status_validos = ['pendente', 'confirmada', 'cancelada', 'concluída'];
if (!in_array($novo_status, $status_validos)) {
    echo json_encode(['success' => false, 'message' => 'Status inválido: ' . $novo_status]);
    exit;
}

// Se for cancelamento, verificar se tem justificativa
if ($novo_status === 'cancelada' && empty($justificativa)) {
    echo json_encode(['success' => false, 'message' => 'É necessário fornecer uma justificativa para o cancelamento.']);
    exit;
}

try {
    // Iniciar transação
    mysqli_begin_transaction($conn);

    // Preparar a query de atualização com ou sem justificativa
    if ($novo_status === 'cancelada' && $justificativa) {
        $stmt = mysqli_prepare($conn, "UPDATE reserva SET status = ?, observacao = ? WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Erro ao preparar a atualização do status e justificativa: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt, "ssi", $novo_status, $justificativa, $reserva_id);
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE reserva SET status = ? WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Erro ao preparar a atualização do status: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt, "si", $novo_status, $reserva_id);
    }
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Erro ao atualizar o status: " . mysqli_stmt_error($stmt));
    }
    
    // Confirmar transação
    mysqli_commit($conn);
    
    echo json_encode(['success' => true, 'message' => 'Status atualizado com sucesso.']);
    
} catch (Exception $e) {
    // Reverter transação em caso de erro
    mysqli_rollback($conn);
    error_log("Erro na atualização do status: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar o status: ' . $e->getMessage()]);
}

mysqli_close($conn);
?> 