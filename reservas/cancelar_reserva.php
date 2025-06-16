<?php
// Prevent any output before headers
ob_start();

session_start();
include_once '../includes/db_conexao.php';

// Enable error reporting for debugging but don't display errors
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set proper JSON header
header('Content-Type: application/json; charset=utf-8');

// Function to send JSON response
function sendJsonResponse($success, $message) {
    ob_clean(); // Clear any previous output
    echo json_encode([
        'success' => $success,
        'message' => $message
    ]);
    exit;
}

try {
    // Log the incoming request
    error_log('Cancelar Reserva Request: ' . print_r($_POST, true));

    // Verifica se o usuário está logado
    if (!isset($_SESSION['utilizador_id'])) {
        error_log('Usuário não autenticado');
        sendJsonResponse(false, 'Usuário não autenticado');
    }

    // Verifica se os dados necessários foram enviados
    if (!isset($_POST['reserva_id']) || !isset($_POST['justificativa'])) {
        error_log('Dados incompletos: ' . print_r($_POST, true));
        sendJsonResponse(false, 'Dados incompletos');
    }

    $reserva_id = intval($_POST['reserva_id']);
    $justificativa = trim($_POST['justificativa']);

    // Verifica se a justificativa não está vazia
    if (empty($justificativa)) {
        error_log('Justificativa vazia para reserva ID: ' . $reserva_id);
        sendJsonResponse(false, 'A justificativa é obrigatória');
    }

    // Verifica a conexão com o banco de dados
    if (!$conn) {
        $error = mysqli_connect_error();
        error_log('Erro de conexão com o banco de dados: ' . $error);
        sendJsonResponse(false, 'Erro de conexão com o banco de dados: ' . $error);
    }

    // Verifica se a reserva existe e está confirmada
    $query = "SELECT status, observacao FROM reserva WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        $error = mysqli_error($conn);
        error_log('Erro ao preparar consulta: ' . $error);
        sendJsonResponse(false, 'Erro ao preparar consulta: ' . $error);
    }
    
    mysqli_stmt_bind_param($stmt, "i", $reserva_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt);
        error_log('Erro ao executar consulta: ' . $error);
        sendJsonResponse(false, 'Erro ao executar consulta: ' . $error);
    }
    
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        if ($row['status'] !== 'confirmada') {
            error_log('Tentativa de cancelar reserva não confirmada. ID: ' . $reserva_id . ', Status: ' . $row['status']);
            sendJsonResponse(false, 'Apenas reservas confirmadas podem ser canceladas. Status atual: ' . $row['status']);
        }
    } else {
        error_log('Reserva não encontrada. ID: ' . $reserva_id);
        sendJsonResponse(false, 'Reserva não encontrada');
    }

    // Prepara a nova observação
    $nova_observacao = "\nCancelado em " . date('Y-m-d H:i:s') . " - Justificativa: " . $justificativa;
    $observacao_atualizada = $row['observacao'] ? $row['observacao'] . $nova_observacao : $nova_observacao;

    // Atualiza o status da reserva e a observação
    $query = "UPDATE reserva SET status = 'cancelada', observacao = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        $error = mysqli_error($conn);
        error_log('Erro ao preparar atualização: ' . $error . "\nQuery: " . $query);
        sendJsonResponse(false, 'Erro ao preparar atualização: ' . $error);
    }
    
    mysqli_stmt_bind_param($stmt, "si", $observacao_atualizada, $reserva_id);

    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt);
        error_log('Erro ao executar atualização: ' . $error . "\nDados: " . print_r([
            'observacao' => $observacao_atualizada,
            'reserva_id' => $reserva_id
        ], true));
        sendJsonResponse(false, 'Erro ao executar atualização: ' . $error);
    }

    error_log('Reserva cancelada com sucesso. ID: ' . $reserva_id);
    sendJsonResponse(true, 'Reserva cancelada com sucesso');

} catch (Exception $e) {
    error_log('Erro ao cancelar reserva: ' . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
    sendJsonResponse(false, 'Erro ao cancelar reserva: ' . $e->getMessage());
} finally {
    if (isset($stmt)) {
        mysqli_stmt_close($stmt);
    }
    if (isset($conn)) {
        mysqli_close($conn);
    }
}
?> 